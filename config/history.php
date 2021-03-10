<?php

use VDVT\History\Events\Handlers\SaveLogHistoryHandle;

return [
    /**
     *
     */
    'history_type' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom display table name
    |--------------------------------------------------------------------------
     */
    'history_result_log' => [],
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
    'event_handler' => SaveLogHistoryHandle::class,
];
