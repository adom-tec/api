<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'sas.StateAssignService';
    protected $primaryKey = 'Id';
	public $timestamps = false;
}
