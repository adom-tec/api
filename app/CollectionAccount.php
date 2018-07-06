<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CollectionAccount extends Model
{
    protected $table = 'sas.CollectionAccounts';
    protected $primaryKey = 'id';
    
    public $timestamps = false;
}
