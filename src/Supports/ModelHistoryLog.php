<?php

namespace VDVT\History\Supports;

use Illuminate\Database\Eloquent\Model;

abstract class ModelHistoryLog extends Model
{
    use HistoryDetectionTrait;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}
