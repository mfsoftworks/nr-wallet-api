<?php

namespace App;

class Device extends CachableModel
{
    /**
     * Assignable like values
     *
     * @var array
     */
    protected $fillable = [
        'device',
        'browser',
        'ip',
        'platform',
        'user_id'
    ];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
