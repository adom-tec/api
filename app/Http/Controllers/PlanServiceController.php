<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PlanService;
use App\PlanEntity;

class PlanServiceController extends Controller
{
    public function index($plan)
    {
        return PlanService::where('PlanEntityId', $plan)
            ->with('service:ServiceId,Name')
            ->get();
    }

    public function store(Request $request, $plan)
    {
        $planEntity = PlanEntity::findOrFail($plan)->PlanEntityId;
        $request->validate([
            'ServiceId' => 'required|exists:sqlsrv.cfg.Services,ServiceId',
            'Rate' => 'required|numeric',
            'Validity' => 'required'
        ]);
        
        $planService = new PlanService($request->all());
        $planService->PlanEntityId = $planEntity;
        $planService->save();
        return response()->json($planService, 201);

    }

    public function update(Request $request, $plan, $id)
    {
        $planService = PlanService::findOrFail($id);

        $planService->fill($request->all());
        $planService->save();
        return response()->json($planService, 200);
    }
}
