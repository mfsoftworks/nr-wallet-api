<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency', 'ammount', 'description'
    ];

    /**
     * The related attributes that should be returned
     */
    protected $with = [
        'forUser', 'fromUser'
    ];

    /**
     * Get the budget list for this item.
     */
    public function forUser()
    {
        return $this->belongsTo('App\User', 'for_user_id');
    }

    /**
     * Get the budget list for this item.
     */
    public function fromUser()
    {
        return $this->belongsTo('App\User', 'from_user_id');
    }
}
