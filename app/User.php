<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'settings',
        'fcm_token',
        'display_name',
        'stripe_customer_id',
        'stripe_connect_id',
        'default_currency',
        'country'
    ];
    protected $attributes = [
        'deactivated' => 0,
        'settings' => '{"encryptLogin": true, "paymentAuth": true, "balanceMin": 0, "withdrawLockout": false, "withdrawLockoutDatetime": null}'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'name',
        'deactivated',
        'banned_until',
        'settings',
        'email',
        'password',
        'remember_token',
        'email_verified_at',
        'fcm_token',
        'stripe_connect_id',
        'stripe_customer_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'banned_until' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Find the user instance for the given username.
     *
     * @param  string  $username
     * @return \App\User
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }

    // Return transactions for this user
    public function sent() {
        return $this->hasMany('App\Transaction', 'from_user_id');
    }
    public function received() {
        return $this->hasMany('App\Transaction', 'for_user_id');
    }
}
