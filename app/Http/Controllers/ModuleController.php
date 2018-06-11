<?php

namespace App\Http\Controllers;

use App\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        return Module::all();
    }
}
