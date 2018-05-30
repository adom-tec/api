<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'sec.Users';
    protected $primaryKey = 'UserId';

    use HasApiTokens, Notifiable;

    public $timestamps = false;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'Password'
    ];
    public function findForPassport($username) {
        return $this->whereEmail($username)->first();
    }

    public function getAuthPassword()
    {
        return $this->Password;
    }
}
