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
        'email',
        'account_type',
        'password',
        'settings',
        'fcm_token',
        'display_name',
        'stripe_customer_id',
        'stripe_connect_id'
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
        'deactivated',
        'banned_until',
        'settings',
        'email',
        'password',
        'remember_token',
        'email_verified_at',
        'fcm_token',
        'stripe_customer_id',
        'stripe_connect_id'
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
}
