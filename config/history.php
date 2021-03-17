<?php

use VDVT\History\Events\Handlers\CreatedHistoryHandle;
use VDVT\History\Events\Handlers\SaveLogHistoryHandle;

return [
    /*
    |--------------------------------------------------------------------------
    | Custom display table name
    |--------------------------------------------------------------------------
     */
    'nameTables' => [],

    /*
    |--------------------------------------------------------------------------
    | Allow user write log
    |--------------------------------------------------------------------------
     */
    'enable' => true,
    /*
    |--------------------------------------------------------------------------
    | Config entity model
    |--------------------------------------------------------------------------
     */
    'format' => [
        'datetime' => 'm/d/Y H:i',
    ],
    /**
     * Event handle
     */
    'event_handler' => [
        'store' => SaveLogHistoryHandle::class,
        'created' => CreatedHistoryHandle::class,
    ],
];
