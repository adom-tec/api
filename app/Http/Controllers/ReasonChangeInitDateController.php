<?php

namespace App\Http\Controllers;

use App\ReasonChangeInitDate;
use Illuminate\Http\Request;

class ReasonChangeInitDateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ReasonChangeInitDate::all();
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return ReasonChangeInitDate::findOrFail($id);
    }

}
