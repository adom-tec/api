<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service;

class ServiceController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/Service/Get')->only('index');
        $this->middleware('verify.action:/Service/Edit')->only('update');
        $this->middleware('verify.action:/Service/Create')->only('store');
    }

    public function index()
    {
        return Service::with(['classification', 'serviceType'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'Value' => 'required',
            'Code' => 'required',
            'Name' => 'required',
            'ClassificationId' => 'required|exists:sqlsrv.cfg.Classification,Id',
            'ServiceTypeId' => 'required|exists:sqlsrv.cfg.ServiceType,Id',
            'HoursToInvest' => 'required'
        ]);
        $service = new Service($request->all());
        $service->save();
        $service->load(['classification', 'serviceType']);
        return response()->json($service, 201);
    }

    public function update($id, Request $request)
    {
        $service = Service::findOrFail($id);
        $request->validate([
            'ClassificationId' => 'exists:sqlsrv.cfg.Classification,Id',
            'ServiceTypeId' => 'exists:sqlsrv.cfg.ServiceType,Id',
        ]);
        $service->fill($request->all());
        $service->save();
        $service->load(['classification', 'serviceType']);
        return response()->json($service, 200);
    }
}
