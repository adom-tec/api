<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Supply;

class SupplyController extends Controller
{
    public function index()
    {
        return Supply::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'Presentation' => 'required',
            'Code' => 'required',
            'Name' => 'required'
        ]);

        $supply = new Supply($request->all());
        $supply->save();
        return response()->json($supply, 201);
    }

    public function update(Request $request, $id)
    {
        $supply = Supply::findOrFail($id);  

        $supply->fill($request->all());
        $supply->save();
        return response()->json($supply, 200);
    }
}
