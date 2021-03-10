<?php

namespace VDVT\History\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use VDVT\History\Constants\References;
use VDVT\History\Events\SaveLogHistory;

trait DetectionTrait
{
    use IgnoreAttributeTrait;
    use ValidationTrait;
    use FormatDataTypeTrait;
    use ValueAttributeTrait;

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
     * primaryIndex
     *
     * @var string
     */
    protected $primaryIndex = 'id';

    /**
     * Ensure that the bootDetectionTrait is called only
     * if the current installation is a laravel 4 installation
     * Laravel 5 will call bootDetectionTrait() automatically
     */
    protected static function bootDetectionTrait()
    {
        if (config('history.enable')) {
            foreach (static::getEventListeners() as $event => $fn) {
                static::$event(function ($model) use ($fn) {
                    $this->{$fn}();
                });
            }
        }
    }

    /**
     * Handle the User "created" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function createdObserver()
    {
        if (empty($this->isWriteLogCreated)) {
            return false;
        }

        list(
            'tableName' => $tableName,
            'primaryValue' => $primaryValue,
            'fieldName' => $fieldName,
            'historyData' => $historyData
        ) = $this->getDataCreateOrDeleteHistory($this->createAttributes, 'getContentCreateObserver');

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => References::HISTORY_EVENT_CREATED,
                    'detail' => __('history::history.actions.created', [
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

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function deletedObserver()
    {
        if (empty($this->isWriteLogDeleted)) {
            return false;
        }

        list(
            'tableName' => $tableName,
            'primaryValue' => $primaryValue,
            'fieldName' => $fieldName,
            'historyData' => $historyData
        ) = $this->getDataCreateOrDeleteHistory('getContentDeleteObserver');

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => References::HISTORY_EVENT_DELETED,
                    'detail' => __('history::history.actions.deleted', [
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

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function updatedObserver()
    {
        if (empty($this->isWriteLogUpdated)) {
            return false;
        }

        $fieldsChanged = $this->ignoreAttributes(
            $this->isDirty() ? $this->getDirty() : []
        );

        if (!$fieldsChanged) {
            return false;
        }

        foreach ($fieldsChanged as $attribute => $newValue) {
            # code...
            if ($this->getOriginal($attribute) == null && empty($newValue)) {
                continue;
            }

            $origin = $this->getOriginalMutator($attribute);
            $current = $this->getNewValueMutator($attribute, $newValue);

            # historyValidation model change
            if (
                $this->historyValidation(
                    ...$this->formatAttributeWithType($attribute, $origin, $current)
                )
            ) {
                $this->createOrUpdateLogHistory($attribute, $origin, $current);
            }
        }
    }

    /**
     * @param  array $cfAttribute
     * @param  string $fnName | function override
     * @return array
     */
    protected function getDataCreateOrDeleteHistory(string $fnName): array
    {
        $tableName = $this->getHistoryDisplayTable();
        $primaryValue = $this->getAttribute($this->primaryIndex);
        $fieldName = $this->getHistoryDisplayAttribute($this->primaryIndex);

        $dataHistory = array();
        if (method_exists($this, $fnName)) {
            $dataHistory = $this->{$fnName}() ?: [];
        }

        return compact('tableName', 'primaryValue', 'fieldName', 'dataHistory');
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
            $override = $this->getContentUpdateObserver($attribute, $origin, $current) ?: [];
        }

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => References::HISTORY_EVENT_UPDATED,
                    'result' => Config::get('history.history_result_log.fields_changed'),
                    'detail' => __('history::history.actions.updated', [
                        'table' => $tableName,
                        'column' => $fieldName,
                        'origin' => $origin,
                        'current' => $current,
                        'target' => $targetName,
                    ]),
                    'old_value' => $origin,
                    'new_value' => $current,
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
            References::HISTORY_EVENT_CREATED => 'createdObserver',
            References::HISTORY_EVENT_UPDATED => 'updatedObserver',
            References::HISTORY_EVENT_DELETED => 'deletedObserver',
        ];
    }
}
