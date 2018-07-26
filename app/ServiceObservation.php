<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceObservation extends Model
{
    protected $table = 'sas.AssignServiceObservation';
    protected $primaryKey = 'AssignServiceObservationId';
    protected $fillable = ['Description'];

    const CREATED_AT = 'RecordDate';
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo('App\User', 'UserId');
    }
}
