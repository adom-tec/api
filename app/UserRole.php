<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $table = 'sec.UsersRoles';
    protected $primaryKey = 'UserRoleId';
}
