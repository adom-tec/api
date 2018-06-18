<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlanEntity extends Model
{
    protected $table = 'cfg.PlansEntity';
    protected $primaryKey = 'PlanEntityId';
    protected $fillable = ['Name', 'State'];

    public $timestamps = false;
}
