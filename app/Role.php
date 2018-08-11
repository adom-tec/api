<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'sec.Roles';
    protected $primaryKey = 'RoleId';
    protected $fillable = ['Name', 'State'];
    public $timestamps = false;
    protected $casts = [
        'State' => 'string'
    ];
}
