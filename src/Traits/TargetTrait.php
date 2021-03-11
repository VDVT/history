<?php

namespace VDVT\History\Traits;

trait TargetTrait
{
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
