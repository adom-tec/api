<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PlanService;
use App\PlanEntity;

class PlanServiceController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/PlanRate/Get')->only('index');
        $this->middleware('verify.action:/PlanRate/Create')->only('store');
        $this->middleware('verify.action:/PlanRate/Edit')->only('update');
    }

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
        $request->validate([
            'Rate' => 'numeric'
        ]);
        $planService->fill($request->all());
        $planService->save();
        return response()->json($planService, 200);
    }

    public function destroy($plan, $id)
    {
        $planService = PlanService::findOrFail($id);
        try {
            $planService->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error, El servicio no puede ser eliminado, verifique que no tenga datos asociados a este registro'
            ], 400);
        }
        return response()->json([
            'message' => 'Servicio borrado con exito'
        ], 200);
    }
}
