<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    protected $table = 'cfg.Supplies';
    protected $primaryKey = 'SupplyId';
    protected $fillable = ['Presentation','Code','Name'];

    public $timestamps = false;

    
}
