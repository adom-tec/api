<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'cfg.Patients';
    protected $primaryKey = 'PatientId';
    protected $fillable = ["Document", "DocumentTypeId", "BirthDate", "Age", "UnitTimeId", "FirstName",
        "SecondName", "Surname", "SecondSurname", "Email", "GenderId", "Occupation", "Address", "Telephone1",
        "Telephone2", "AttendantName", "AttendantRelationship", "AttendantPhone", "AttendantEmail",
        "Profile", "Neighborhood", "PatientTypeId", "NameCompleted"];

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = null;

    public function documentType()
    {
        return $this->belongsTo('App\DocumentType', 'DocumentTypeId');
    }

    public function unitTime()
    {
        return $this->belongsTo('App\UnitTime', 'UnitTimeId');
    }

    public function gender()
    {
        return $this->belongsTo('App\Gender', 'GenderId');
    }

    public function patientType()
    {
        return $this->belongsTo('App\PatientType', 'PatientTypeId');
    }
}
