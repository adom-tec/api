<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'cfg.Services';
    protected $primaryKey = 'ServiceId';

    public $timestamps = false; 
}
