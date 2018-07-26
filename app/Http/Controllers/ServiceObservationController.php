<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ServiceObservation;
use App\PatientService;

class ServiceObservationController extends Controller
{
    public function index($service)
    {
        return ServiceObservation::where('AssignServiceId', $service)
            ->with('user')
            ->get();
    }

    public function store(Request $request, $service)
    {
        $service = PatientService::findOrFail($service)->AssignServiceId;
        $request->validate([
            'Description' => 'required'
        ]);

        $serviceObservation = new ServiceObservation($request->all());
        $serviceObservation->AssignServiceId = $service;
        $serviceObservation->UserId = $request->user()->UserId;
        $serviceObservation->save();
        $serviceObservation->load('user');

        return response()->json($serviceObservation, 201);
    }

    public function destroy($service, $id)
    {
        $serviceObservation = ServiceObservation::findOrFail($id);
        try {
            $serviceObservation->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error, La observación no puede ser eliminada, verifique que no tenga datos asociados a este registro'
            ], 400);
        }
        return response()->json([
            'message' => 'Observación borrada con exito'
        ], 200);
    }
}
