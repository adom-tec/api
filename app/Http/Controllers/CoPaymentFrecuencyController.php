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

    public function store(Request $request)
    {
        $request->validate([
            'Name' => 'required'
        ]);
        $copaymentFrecuency = new CoPaymentFrecuency();
        $copaymentFrecuency->Name = $request->input('Name');
        $copaymentFrecuency->save();
        return response()->json($copaymentFrecuency, 201);
    }

    public function update($id, Request $request)
    {
        $copaymentFrecuency = CoPaymentFrecuency::findOrFail($id);
        $request->validate([
            'Name' => 'required'
        ]);
        $copaymentFrecuency->Name = $request->input('Name');
        $copaymentFrecuency->save();
        return response()->json($copaymentFrecuency, 200);
    }
}
