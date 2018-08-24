<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    protected $table = 'cfg.Professionals';
    protected $primaryKey = 'ProfessionalId';
    protected $fillable = ["Document", "BirthDate", "Neighborhood",  "Address", "Telephone1", "Telephone2",
        "AccountNumber", "CodeBank", "GenderId", "SpecialtyId", "DocumentTypeId","DateAdmission",
        "Availability", "FamilyName", "FamilyRelationship", "FamilyPhone", "Coverage", "AccountTypeId", 'ContractTypeId'];

    public $timestamps = false;
    
    public function gender()
    {
        return $this->belongsTo('App\Gender', 'GenderId');
    }

    public function documentType()
    {
        return $this->belongsTo('App\DocumentType', 'DocumentTypeId');
    }

    public function specialty()
    {
        return $this->belongsTo('App\Specialty', 'SpecialtyId');
    }

    public function accountType()
    {
        return $this->belongsTo('App\AccountType', 'AccountTypeId');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'UserId');
    }

    public function contractType()
    {
	return $this->belongsTo('App\ContractType', 'ContractTypeId');
    }
}
