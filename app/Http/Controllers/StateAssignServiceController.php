<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\State;

class StateAssignServiceController extends Controller
{
    public function index()
    {
        return State::all();
    }
}
