<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'cfg.Services';
    protected $primaryKey = 'ServiceId';
    protected $fillable = ['Value','Code','Name','ClassificationId','ServiceTypeId','HoursToInvest', 'special_value',  'particular_value', 'holiday_value'];
    

    public $timestamps = false; 

    public function serviceType()
    {
        return $this->belongsTo('App\ServiceType', 'ServiceTypeId');
    }

    public function classification()
    {
        return $this->belongsTo('App\Classification', 'ClassificationId');
    }
}
