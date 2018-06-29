<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetailAnswer extends Model
{
    protected $table = 'sas.DetailAnswers';
    protected $primaryKey = 'DetailAnswerId';

    const CREATED_AT = 'RecordDate';
    const UPDATED_AT = null;
}
