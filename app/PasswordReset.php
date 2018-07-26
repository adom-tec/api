<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $table = 'sec.PasswordReset';
    protected $primaryKey = null;
    public $incrementing = false; 

    public $timestamps = false;
}
