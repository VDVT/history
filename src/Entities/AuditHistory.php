<?php

namespace VDVT\History\Entities;

use Illuminate\Database\Eloquent\Model;

class AuditHistory extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vdvt_audit_histories';

    protected $fillable = [
        'target_type',
        'target_id',
        'author_id',
        'author_type',
        'type',
        'result',
        'detail',
    ];

    /**
     * The date fields for the model.clear
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Grab the revision history for the model that is calling
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function author()
    {
        return $this->morphTo();
    }

    /**
     * Grab the revision history for the model that is calling
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function target()
    {
        return $this->morphTo();
    }

    /**
     * getCreatedAtAttribute
     *
     * @param  $value
     * @return mixed
     */
    public function getCreatedAtAttribute($value)
    {
        return $value ? date(config('vdvt.history.history.format.datetime'), strtotime($value)) : '';
    }
}
