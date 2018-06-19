<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PatientService extends Model
{
    protected $table = 'sas.AssignService';
    protected $primaryKey = 'AssignServiceId';
    protected $appends = ['countScheduledVisits', 'countMadeVisits'];

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'PatientId');
    }

    public function service()
    {
        return $this->belongsTo('App\Service', 'ServiceId');
    }

    public function serviceFrecuency()
    {
        return $this->belongsTo('App\ServiceFrecuency', 'ServiceFrecuencyId');
    }

    public function professional()
    {
        return $this->belongsTo('App\Professional', 'ProfessionalId');
    }

    public function coPaymentFrecuency()
    {
        return $this->belongsTo('App\CoPaymentFrecuency', 'CoPaymentFrecuencyId');
    }

    public function state()
    {
        return $this->belongsTo('App\State', 'StateId');
    }

    public function entity()
    {
        return $this->belongsTo('App\Entity', 'EntityId');
    }

    public function planService()
    {
        return $this->belongsTo('App\PlanService', 'PlanEntityId');
    }

    public function getCountScheduledVisitsAttribute()
    {
        return ServiceVisit::where('AssignServiceId', $this->AssignServiceId)->count();
    }

    public function getCountMadeVisitsAttribute()
    {
        return ServiceVisit::where('AssignServiceId', $this->AssignServiceId)
            ->where('StateId', '>', '1')
            ->count();
    }
}
