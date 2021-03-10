<?php

namespace VDVT\History\Supports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use VDVT\History\Constants\References;
use VDVT\History\Events\SaveLogHistory;

trait HistoryDetectionTrait
{
    use HistoryValidationTrait;
    use HistoryFormatDataTypeTrait;
    use HistoryValueAttributeTrait;

    /**
     * Allow store log create
     * @var boolean
     */
    protected $isWriteLogCreated = true;

    /**
     * Allow store log create
     * @var boolean
     */
    protected $isWriteLogDeleted = true;

    /**
     * Allow store log create
     * @var boolean
     */
    protected $isWriteLogUpdated = true;

    /**
     * [$attributeDelete description]
     * @var array
     */
    protected $deleteAttributes = [
        'primaryIndex' => 'id',
    ];

    /**
     * [$createAttributes description]
     * @var array
     */
    protected $createAttributes = [
        'primaryIndex' => 'id',
    ];

    /**
     * [$ignoreLogAttributes description]
     * @var array
     */
    protected $ignoreLogAttributes = [
        'updated_at',
        'updated_by',
    ];

    /**
     * [bootHistoryDetection description]
     * Register auto detection history
     * @author TrinhLe
     * @return void
     */
    protected static function bootHistoryDetectionTrait()
    {
        if (config('history.enable')) {
            foreach (static::getEventListeners() as $event) {
                static::$event(function ($model) use ($event) {
                    $model->createLogHistory($event);
                });
            }
        }
    }

    /**
     * @param  mixed $model
     * @author TrinhLe
     * @return void
     */
    protected function createLogHistory($eventObserver)
    {
        $actionMethod = "{$eventObserver}Observer";
        if (method_exists($this, $actionMethod)) {
            $this->$actionMethod();
        }
    }

    /**
     * @param  array $cfAttribute
     * @param  string $fnName | function override
     * @return array
     */
    protected function getDataCreateOrDeleteHistory(array $cfAttribute, string $fnName): array
    {
        $tableName = $this->getHistoryDisplayTable();
        $primaryValue = $this->getAttribute($cfAttribute['primaryIndex']);
        $fieldName = $this->getHistoryDisplayAttribute($cfAttribute['primaryIndex']);

        $dataHistory = array();
        if (method_exists($this, $fnName)) {
            $dataHistory = $this->{$fnName}();
        }

        return compact('tableName', 'primaryValue', 'fieldName', 'dataHistory');
    }

    /**
     * Handle the User "created" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function createdObserver()
    {
        if ($this->isWriteLogCreated) {
            list(
                'tableName' => $tableName,
                'primaryValue' => $primaryValue,
                'fieldName' => $fieldName,
                'historyData' => $historyData
            ) = $this->getDataCreateOrDeleteHistory($this->createAttributes, 'getContentCreateObserver');

            $this->saveLogAttribute(
                array_merge(
                    [
                        'type' => Config::get('history.history_type.log'),
                        'result' => Config::get('history.history_result_log.fields_changed'),
                        'details' => __('history.actions.created', [
                            'table' => $tableName,
                            'column' => $fieldName,
                            'value' => $primaryValue,
                        ]),
                    ],
                    $historyData
                ),
                References::HISTORY_EVENT_CREATED,
            );
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function deletedObserver()
    {
        if ($this->isWriteLogDeleted) {
            list(
                'tableName' => $tableName,
                'primaryValue' => $primaryValue,
                'fieldName' => $fieldName,
                'historyData' => $historyData
            ) = $this->getDataCreateOrDeleteHistory($this->deleteAttributes, 'getContentDeleteObserver');

            $this->saveLogAttribute(
                array_merge(
                    [
                        'type' => Config::get('history.history_type.log'),
                        'result' => Config::get('history.history_result_log.fields_changed'),
                        'details' => __('history.actions.deleted', [
                            'table' => $tableName,
                            'column' => $fieldName,
                            'value' => $primaryValue,
                        ]),
                    ],
                    $historyData
                ),
                References::HISTORY_EVENT_DELETED,
            );
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function updatedObserver()
    {
        if ($this->isWriteLogUpdated) {
            $fieldsChanged = $this->isDirty() ? $this->getDirty() : false;

            if ($fieldsChanged) {
                $fieldsChanged = $this->ignoreAttributes($fieldsChanged);

                foreach ($fieldsChanged as $attribute => $newValue) {
                    # code...
                    if ($this->getOriginal($attribute) == null && empty($newValue)) {
                        continue;
                    }

                    $origin = $this->getOriginalMutator($attribute);
                    $current = $this->getNewValueMutator($attribute, $newValue);
                    list($_origin, $_current) = $this->formatAttributeWithType($attribute, $origin, $current);

                    # historyValidation model change
                    if ($this->historyValidation($_origin, $_current)) {
                        $this->createOrUpdateLogHistory($attribute, $origin, $current);
                    }
                }
            }
        }
    }

    /**
     * @param  mixed $fieldsChanged
     * @return array
     */
    public function ignoreAttributes(array $fieldsChanged): array
    {
        if (!empty($this->historyOnlySpecialColumns) && is_array($this->historyOnlySpecialColumns)) {
            return array_intersect_key(
                $fieldsChanged, /* main array*/
                array_flip( /* to be extracted */
                    $this->historyOnlySpecialColumns
                )
            );
        }

        if (is_array($this->ignoreLogAttributes)) {
            return array_diff_key($fieldsChanged, array_flip($this->ignoreLogAttributes));
        }

        return $fieldsChanged;
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $origin
     * @param  mixed $current
     * @return void
     */
    protected function createOrUpdateLogHistory($attribute, $origin, $current)
    {
        $tableName = $this->getHistoryDisplayTable();
        $fieldName = $this->getHistoryDisplayAttribute($attribute);
        list($origin, $current, $columnType) = $this->getHistoryDisplayValueAttribute($attribute, $origin, $current);
        $origin = is_array($origin) ? json_encode($origin) : $origin;
        $current = is_array($current) ? json_encode($current) : $current;

        # GET display target update
        $targetName = null;
        if ($this->isDisplayHistoryUpdate ?? false) {
            $targetName = " \"" . $this->getAttribute($this->displayHistoryUpdate ?? 'id') . "\"";
        }

        $override = array();
        if (method_exists($this, 'getContentUpdateObserver')) {
            $override = $this->getContentUpdateObserver($attribute, $origin, $current) ?? [];
        }

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => Config::get('history.history_type.log'),
                    'result' => Config::get('history.history_result_log.fields_changed'),
                    'details' => __('history.actions.updated', [
                        'table' => $tableName,
                        'column' => $fieldName,
                        'origin' => $origin,
                        'current' => $current,
                        'target' => $targetName,
                    ]),
                ],
                $override
            ),
            References::HISTORY_EVENT_UPDATED
        );
    }

    /**
     * @return void
     */
    protected function saveLogAttribute(array $data = [], string $type)
    {
        event(new SaveLogHistory
            (
                array_merge(
                    [
                        'user_id' => Auth::id(),
                    ],
                    $this->getTargetHistory(),
                    $data
                ),
                $type
            )
        );
    }

    /**
     * override event Observer
     *
     * @author TrinhLe
     * @return array
     */
    protected static function getEventListeners(): array
    {
        return [
            References::HISTORY_EVENT_CREATED,
            References::HISTORY_EVENT_UPDATED,
            References::HISTORY_EVENT_DELETED,
        ];
    }
}
