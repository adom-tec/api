<?php

namespace App\Http\Controllers;

use App\ReasonSuspensionService;
use Illuminate\Http\Request;

class ReasonSuspensionServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ReasonSuspensionService::all();
    }

    public function show($id)
    {
        return ReasonSuspensionService::findOrFail($id);
    }
}
