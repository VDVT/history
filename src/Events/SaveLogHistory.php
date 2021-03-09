<?php

namespace VDVT\History\Events;

class SaveLogHistory
{
    /**
     * @var Array
     */
    public $historyData;

    /**
     * @var String
     */
    public $historyType;

    public function __construct(array $historyData, string $historyType)
    {
        $this->historyData = $historyData;
        $this->historyType = $historyType;
    }
}
