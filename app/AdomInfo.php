<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdomInfo extends Model
{
    protected $table = 'cfg.AdomInfo';
    protected $primaryKey = 'ProviderCode';
    public $timestamps = false;
}
