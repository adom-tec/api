<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceDetail extends Model
{
    protected $table = 'sas.AssignServiceDetails';
    protected $primaryKey = 'AssignServiceDetailId';

    public function professional()
    {
        return $this->belongsTo('App\Professional', 'ProfessionalId');
    }

    public function state()
    {
        return $this->belongsTo('App\State', 'StateId');
    }

}
