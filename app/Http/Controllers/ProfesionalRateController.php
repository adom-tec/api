<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProfesionalRate;

class ProfesionalRateController extends Controller
{
    public function index()
    {
        return ProfesionalRate::all();
    }
}
