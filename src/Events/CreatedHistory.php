<?php

namespace VDVT\History\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class CreatedHistory extends Event
{
    use SerializesModels;

    /**
     * @var model
     */
    public $model;

    /**
     * Path history
     *
     * @var String
     */
    public $path;

    /**
     * Event history
     *
     * @var String
     */
    public $eventName;

    /**
     * CreatedHistory constructor.
     *
     * @param Model $model
     * @param string $path
     * @param string $eventName
     */
    public function __construct(Model $model, string $path, string $eventName)
    {
        $this->model = $model;
        $this->path = $path;
        $this->eventName = $eventName;
    }
}
