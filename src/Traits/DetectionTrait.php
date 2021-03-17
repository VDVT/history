<?php

namespace VDVT\History\Traits;

use VDVT\History\Constants\References;
use VDVT\History\Events\CreatedHistory;

trait DetectionTrait
{
    use IgnoreAttributeTrait;
    use ValidationTrait;
    use FormatDataTypeTrait;
    use ValueAttributeTrait;
    use PathTrait;
    use StoreTrait;

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
                    $model->{$fn}();
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

        $payload = $this->getDataCreateOrDeleteHistory('getContentCreateObserver');

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => References::HISTORY_EVENT_CREATED,
                    'detail' => __('history::history.actions.created', [
                        'table' => $payload['tableName'],
                        'column' => $payload['fieldName'],
                        'value' => $payload['primaryValue'],
                    ]),
                    'path' => $this->getPathHistory(),
                ],
                $payload['historyData']
            ),
            References::HISTORY_EVENT_CREATED
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

        $payload = $this->getDataCreateOrDeleteHistory('getContentDeleteObserver');

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => References::HISTORY_EVENT_DELETED,
                    'detail' => __('history::history.actions.deleted', [
                        'table' => $payload['tableName'],
                        'column' => $payload['fieldName'],
                        'value' => $payload['primaryValue'],
                    ]),
                    'path' => $this->getPathHistory(),
                ],
                $payload['historyData']
            ),
            References::HISTORY_EVENT_DELETED
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

        $path = $this->getPathHistory();

        foreach ($fieldsChanged as $attribute => $newValue) {
            # code...
            if ($this->getOriginal($attribute) == null && empty($newValue)) {
                continue;
            }

            $payload = [
                $attribute,
                $this->getOriginalMutator($attribute),
                $this->getNewValueMutator($attribute, $newValue),
            ];

            # historyValidation model change
            if (
                $this->historyValidation(
                    ...$this->formatAttributeWithType(...$payload)
                )
            ) {
                $this->createOrUpdateLogHistory(...array_merge($payload, $path));
            }
        }

        event(new CreatedHistory
            (
                $this,
                $path,
                References::HISTORY_EVENT_UPDATED
            )
        );
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $origin
     * @param  mixed $current
     * @return void
     */
    protected function createOrUpdateLogHistory($attribute, $origin, $current, $path = null)
    {
        list($origin, $current, $columnType) = $this->getHistoryDisplayValueAttribute($attribute, $origin, $current);

        # GET display target update
        if ($this->isDisplayHistoryUpdate ?? false) {
            $targetName = " \"" . $this->getAttribute($this->displayHistoryUpdate ?? 'id') . "\"";
        }

        if (method_exists($this, 'getContentUpdateObserver')) {
            $override = $this->getContentUpdateObserver($attribute, $origin, $current) ?: [];
        }

        $this->saveLogAttribute(
            array_merge(
                [
                    'type' => References::HISTORY_EVENT_UPDATED,
                    'detail' => __('history::history.actions.updated', [
                        'table' => $this->getHistoryDisplayTable(),
                        'column' => $this->getHistoryDisplayAttribute($attribute),
                        'origin' => $origin,
                        'current' => $current,
                        'target' => isset($targetName) ? $targetName : null,
                    ]),
                    'column' => $attribute,
                    'table' => $this->getTable(),
                    'old_value' => $origin,
                    'new_value' => $current,
                    'path' => $path,
                ],
                isset($override) ? $override : []
            ),
            References::HISTORY_EVENT_UPDATED
        );
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
