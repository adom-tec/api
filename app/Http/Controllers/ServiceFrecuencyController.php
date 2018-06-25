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

    public function store(Request $request)
    {
        $request->validate([
            'Name' => 'required'
        ]);
        $serviceFrecuency = new ServiceFrecuency();
        $serviceFrecuency->Name = $request->input('Name');
        $serviceFrecuency->save();
        return response()->json($serviceFrecuency, 201);
    }

    public function update($id, Request $request)
    {
        $serviceFrecuency = ServiceFrecuency::findOrFail($id);
        $request->validate([
            'Name' => 'required'
        ]);
        $serviceFrecuency->Name = $request->input('Name');
        $serviceFrecuency->save();
        return response()->json($serviceFrecuency, 200);
    }
}
