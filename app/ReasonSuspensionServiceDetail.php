<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReasonSuspensionServiceDetail extends Model
{
    protected $table = 'sas.ReasonSuspensionAssignServiceDetail';

    public function suspensionReason()
    {
        return $this->belongsTo('App\ReasonSuspensionService', 'ReasonSuspensionId');
    }
}
