<?php

namespace App\Http\Controllers;

use App\ReasonSuspensionService;
use App\ReasonSuspensionServiceDetail;
use App\ServiceDetail;
use Illuminate\Http\Request;

class ReasonSuspensionServiceDetailController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'reasons.*.AssignServiceDetailId' => 'required|exists:sqlsrv.sas.AssignServiceDetails,AssignServiceDetailId',
            'reasons.*.ReasonSuspensionId' => 'required|exists:sqlsrv.sas.ReasonsSuspensionService,id'
        ]);

        $reasons = $request->input('reasons');
        foreach ($reasons as $reason) {
            ReasonSuspensionServiceDetail::where('AssignServiceDetailId', $reason['AssignServiceDetailId'])
                ->delete();
            $reasonSuspensionServiceDetail = new ReasonSuspensionServiceDetail();
            $reasonSuspensionServiceDetail->AssignServiceDetailId = $reason['AssignServiceDetailId'];
            $reasonSuspensionServiceDetail->ReasonSuspensionId = $reason['ReasonSuspensionId'];
            $reasonSuspensionServiceDetail->save();
        }



        return response()->json([
            'message' => 'Razones de suspensión registradas con éxito'
        ], 201);
    }

}
