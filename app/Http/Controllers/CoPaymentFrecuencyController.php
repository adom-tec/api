<?php

namespace App\Http\Controllers;

use App\CoPaymentFrecuency;
use Illuminate\Http\Request;

class CoPaymentFrecuencyController extends Controller
{
    public function index()
    {
        return CoPaymentFrecuency::all();
    }
}
