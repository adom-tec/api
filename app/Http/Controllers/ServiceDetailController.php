<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ServiceDetail;

class ServiceDetailController extends Controller
{
    public function index($service)
    {
        return ServiceDetail::where('AssignServiceId', $service)
            ->orderBy('Consecutive', 'asc')
            ->with(['professional', 'state'])
            ->get();
    }
}
