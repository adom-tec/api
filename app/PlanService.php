<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlanService extends Model
{
    protected $table = 'cfg.PlansRates';
    protected $primaryKey = 'PlanRateId';
    protected $fillable = ['ServiceId', 'Rate', 'Validity'];

    public $timestamps = false;

    public function service()
    {
        return $this->belongsTo('App\Service', 'ServiceId');
    }
}
