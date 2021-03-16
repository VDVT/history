<?php

namespace VDVT\History\Traits;

trait ValidationTrait
{
    /**
     * Check value to allow write log
     *
     * @param  mixed $origin
     * @param  mixed $current
     * @return bool
     */
    protected function historyValidation($origin, $current): bool
    {
        if (is_bool($current)) {
            return filter_var($origin, FILTER_VALIDATE_BOOLEAN) !== $current;
        }
        return $origin !== $current;
    }
}
