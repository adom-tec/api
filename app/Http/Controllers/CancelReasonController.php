<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CancelReason;

class CancelReasonController extends Controller
{
    public function index()
    {
        return CancelReason::all();
    }
}
