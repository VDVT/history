<?php

namespace VDVT\History\Supports;

trait HistoryValueAttributeTrait
{
    /**
     * @param  mixed $attr
     * @return mixed
     */
    protected function getOriginalMutator($attr)
    {
        $origin = $this->getOriginal($attr);

        return ($this->hasGetMutator($attr))
        ? $this->mutateAttribute($attr, $origin)
        : $origin;
    }

    /**
     * @param  mixed $attr
     * @param  mixed $newValue
     * @return mixed
     */
    protected function getNewValueMutator($attr, $newValue)
    {
        return ($this->hasGetMutator($attr))
        ? $this->mutateAttribute($attr, $newValue)
        : $newValue;
    }

    /**
     * @param  mixed $attr
     * @return mixed
     */
    protected function getHistoryDisplayAttribute($attr)
    {
        return array_get($this->displayAttributes, $attr) ?: ucwords(implode(' ', explode('_', $attr)));
    }

    /**
     * @return mixed
     */
    protected function getHistoryDisplayTable()
    {
        return config("vdvt.history.nameTables.{$this->getTable()}") ?: $this->getTable();
    }

    /**
     * @return mixed
     */
    protected function getTargetHistory(): array
    {
        $logTargetAttributes = property_exists($this, 'logTargetAttributes') ? $this->logTargetAttributes : [];

        return [
            'target_type' => $this->getMorphClass(),
            'target_id' => $this->getAttribute(array_get($logTargetAttributes, 'primary', 'id')),
        ];
    }
}
