<?php

namespace VDVT\History\Traits;

trait PathTrait
{
    /**
     * @param  mixed $fieldsChanged
     * @return array
     */
    protected function getPathHistory(): string
    {
        return uniqid(
            $this->getAttribute(
                $this->primaryIndex
            ) ?: ''
        );
    }
}
