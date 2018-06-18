<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Gender;

class GenderController extends Controller
{
    public function index()
    {
        return Gender::all();
    }
}
