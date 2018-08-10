<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GeneratedRip extends Model
{
    protected $table = 'sas.GeneratedRips';
    protected $primaryKey = 'GeneratedRipsId';
    const CREATED_AT = 'RecordDate';
    const UPDATED_AT = null;
}
