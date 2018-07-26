<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UnitTime;

class UnitTimeController extends Controller
{
    public function index()
    {
        return UnitTime::all();
    }
}
