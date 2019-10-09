<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount',
        'currency',
        'description',
        'for_user_id',
        'from_user_id'
    ];

    /**
     * The relationships to be returned with model
     */
    protected $with = [
        'forUser', 'fromUser'
    ];

    /**
     * Get the User this request is for
     */
    public function forUser()
    {
        return $this->belongsTo('App\User', 'for_user_id');
    }

    /**
     * Get the User this request is from
     */
    public function fromUser()
    {
        return $this->belongsTo('App\User', 'from_user_id');
    }
}
