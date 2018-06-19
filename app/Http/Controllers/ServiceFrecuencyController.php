<?php

namespace App\Http\Controllers;

use App\ServiceFrecuency;
use Illuminate\Http\Request;

class ServiceFrecuencyController extends Controller
{
    public function index()
    {
        return ServiceFrecuency::all();
    }
}
