<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceDetail extends Model
{
    protected $table = 'sas.AssignServiceDetails';
    protected $primaryKey = 'AssignServiceDetailId';

    const UPDATED_AT = 'UpdateDate';
    protected $casts = [
       'Verified' => 'string'
   ];
    public function professional()
    {
        return $this->belongsTo('App\Professional', 'ProfessionalId');
    }

    public function state()
    {
        return $this->belongsTo('App\State', 'StateId');
    }

    public function detailCancelReason()
    {
        return $this->hasOne('App\DetailCancelReason', 'AssignServiceDetailId');
    }

    public function detailSuspensionReason()
    {
        return $this->hasOne('App\ReasonSuspensionServiceDetail', 'AssignServiceDetailId');
    }

    public function service()
    {
        return $this->belongsTo('App\PatientService', 'AssignServiceId');
    }

}
