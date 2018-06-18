<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PatientType;

class PatientTypeController extends Controller
{
    public function index()
    {
        return PatientType::all();
    }
}
