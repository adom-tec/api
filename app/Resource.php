<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $table = 'sec.Resources';
    protected $primaryKey = 'ResourceId';

    public function module()
    {
        return $this->belongsTo('App\Module', 'ModuleId');
    }
 }
