<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetailCancelReason extends Model
{
    protected $table = 'sas.DetailCancelReason';
    protected $primaryKey = 'DetailCancelReasonId';

    public $timestamps = false;

    public function cancelReason()
    {
        return $this->belongsTo('App\CancelReason', 'CancelReasonId');
    }
}
