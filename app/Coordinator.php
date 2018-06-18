<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
    protected $table = 'cfg.Coordinators';
    protected $primaryKey = 'CoordinatorId';
    protected $fillable = ["Document", "DocumentTypeId", "GenderId", "BirthDate", "Telephone1", "Telephone2"];

    public $timestamps = false;

    public function documentType()
    {
        return $this->belongsTo('App\DocumentType', 'DocumentTypeId');
    }

    public function gender()
    {
        return $this->belongsTo('App\Gender', 'GenderId');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'UserId');
    }
}
