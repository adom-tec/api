<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceSupply extends Model
{
    protected $table = 'sas.AssignServiceSupply';
    protected $primaryKey = 'AssignServiceSupplyId';
    protected $fillable = ['SupplyId', 'Quantity', 'BilledToId', 'Observation'];

    public $timestamps = false;

    public function supply()
    {
        return $this->belongsTo('App\Supply', 'SupplyId');
    }

    public function billedTo()
    {
        return $this->belongsTo('App\BilledTo', 'BilledToId');
    }
}
