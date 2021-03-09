<?php

namespace VDVT\History\Supports;

trait HistoryValidationTrait
{
    /**
     * [validation description]
     * @param  [type] $origin  [description]
     * @param  [type] $current [description]
     * @return [type]          [description]
     */
    protected function historyValidation($origin, $current)
    {
        if (is_bool($current)) {
            return filter_var($origin, FILTER_VALIDATE_BOOLEAN) !== $current;
        }
        return $origin !== $current;
    }
}
