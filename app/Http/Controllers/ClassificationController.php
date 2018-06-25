<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classification;

class ClassificationController extends Controller
{
    public function index()
    {
        return Classification::all();
    }
}
