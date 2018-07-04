<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ServiceDetail;

class PatientService extends Model
{
    protected $table = 'sas.AssignService';
    protected $primaryKey = 'AssignServiceId';
    protected $fillable = ["AuthorizationNumber", "Validity", "ApplicantName","ServiceId",
    "Quantity","InitialDate","FinalDate","ServiceFrecuencyId","ProfessionalId","CoPaymentAmount",
    "CoPaymentFrecuencyId","Consultation","External","StateId","Observation","ContractNumber",
    "EntityId","PlanEntityId", "Cie10","DescriptionCie10","AssignedBy","CopaymentStatus","TotalCopaymentReceived",
    "OtherValuesReceived","DeliveredCopayments","Discounts","InvoiceNumber","DelieveredCopaymentDate", "ReceivedBy"];
    protected $appends = ['countMadeVisits'];

    const CREATED_AT = 'RecordDate';
    const UPDATED_AT = null;

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'PatientId');
    }

    public function supplies()
    {
        return $this->belongsToMany('App\Supply', 'sas.AssignServiceSupply', 'AssignServiceId', 'AssignServiceId');
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

    public function getCountMadeVisitsAttribute()
    {
        return ServiceVisit::where('AssignServiceId', $this->AssignServiceId)
            ->where('StateId', '>', '1')
            ->count();
    }


    public function details()
    {
        return $this->hasMany('App\ServiceDetail', 'AssignServiceId');
    }
}
