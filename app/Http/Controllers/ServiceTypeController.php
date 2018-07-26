<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ServiceType;

class ServiceTypEController extends Controller
{
    public function index()
    {
        return ServiceType::all();
    }
}
