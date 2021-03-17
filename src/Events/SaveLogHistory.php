<?php

namespace VDVT\History\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;

class SaveLogHistory extends Event
{
    use SerializesModels;

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
