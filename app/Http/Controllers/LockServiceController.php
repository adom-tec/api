<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AdomInfo;

class LockServiceController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/LockServices/Edit')->only('update');
    }

    public function index()
    {
        $date = AdomInfo::where('ProviderCode', '110011114201')->first()->ServicesLockDate;
        return response()->json([
            'ServicesLockDate' => $date
        ], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'ServicesLockDate' => 'required'
        ]);
        AdomInfo::where('ProviderCode', '110011114201')
            ->update(['ServicesLockDate' => $request->input('ServicesLockDate')]);
        return response()->json([
            'Modificación realizada con éxito'
        ], 200);
    }
}
