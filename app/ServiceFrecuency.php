<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceFrecuency extends Model
{
    protected $table = 'cfg.ServiceFrecuency';
    protected $primaryKey = 'ServiceFrecuencyId';

    public $timestamps = false;

    protected $hidden = [
        'Active'
    ];
}
