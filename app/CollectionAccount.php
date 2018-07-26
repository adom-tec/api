<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CollectionAccount extends Model
{
    protected $table = 'sas.CollectionAccounts';
    protected $primaryKey = 'id';
    
    const CREATED_AT = 'RecordDate';
    const UPDATED_AT = null;
}
