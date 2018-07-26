<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ServiceSupply;
use App\PatientService;

class ServiceSupplyController extends Controller
{
    public function index($service)
    {
        return ServiceSupply::where('AssignServiceId', $service)
            ->with(['billedTo', 'supply'])
            ->get();
    }

    public function store(Request $request, $service)
    {
        $service = PatientService::findOrFail($service)->AssignServiceId;
        $request->validate([
            'SupplyId' => 'required|exists:sqlsrv.cfg.Supplies,SupplyId',
            'Quantity' => 'required|numeric',
            'BilledToId' => 'required|exists:sqlsrv.cfg.BilledTo,Id'
        ]);
        $serviceSupply = new ServiceSupply($request->all());
        $serviceSupply->AssignServiceId = $service;
        $serviceSupply->save();
        return response()->json($serviceSupply, 201);
    }

    public function destroy($service, $id)
    {
        $serviceSupply = ServiceSupply::findOrFail($id);
        try {
            $serviceSupply->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error, El insumo no puede ser eliminado, verifique que no tenga datos asociados a este registro'
            ], 400);
        }
        return response()->json([
            'message' => 'Insumo borrado con exito'
        ], 200);
    }
}
