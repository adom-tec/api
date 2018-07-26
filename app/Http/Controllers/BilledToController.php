<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BilledTo;

class BilledToController extends Controller
{
    public function index()
    {
        return BilledTo::all();
    }
}
