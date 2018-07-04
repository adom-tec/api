<?php

namespace App\Http\Controllers;

use App\Professional;
use App\ServiceDetail;
use App\ServiceSupply;
use Illuminate\Http\Request;
use App\PatientService;

class CopaymentController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'ProfessionalId' => 'required|exists:sqlsrv.cfg.Professionals,ProfessionalId',
            'InitDate' => 'required',
            'FinalDate' => 'required',
            'CopaymentState' => 'required'
        ]);
        $professionalId = $request->input('ProfessionalId');
        $initDate = $request->input('InitDate');
        $finalDate = $request->input('FinalDate');
        $copaymentStatus = $request->input('CopaymentState');
        $assignServices = PatientService::select('sas.AssignService.*')
            ->join('sas.AssignServiceDetails', function ($join) use ($professionalId, $initDate, $finalDate, $copaymentStatus) {
                $join->on('sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
                    ->where('sas.AssignServiceDetails.ProfessionalId', $professionalId)
                    ->whereBetween('DateVisit', [$initDate, $finalDate])
                    ->where('sas.AssignServiceDetails.StateId', 2)
                    ->where('delivered', $copaymentStatus);
            })
            ->orderBy('sas.AssignService.AssignServiceId')
            ->with(['patient.documentType', 'entity', 'service', 'coPaymentFrecuency']);
        if (array_search($request->input('StateId'), [1,2])) {
            $assignServices->where('sas.AssignService.StateId', $request->input('StateId'));
        } else {
            $assignServices->where('sas.AssignService.StateId', '<>', 3);
        }

        $assignServices = $assignServices->get();
        //return $assignServices;
        $count = count($assignServices);
        $identifiers = [];
        for ($i = 0; $i < $count; $i++) {
            if (array_search($assignServices[$i]->AssignServiceId, $identifiers) === false) {
                $identifiers[] = $assignServices[$i]->AssignServiceId;
            } else {
                unset($assignServices[$i]);
            }
        }
        $assignServices->load(['details' => function ($query) use ($professionalId, $initDate, $finalDate, $copaymentStatus) {
            $query->where('sas.AssignServiceDetails.ProfessionalId', $professionalId)
                ->whereBetween('DateVisit', [$initDate, $finalDate])
                ->where('StateId', 2)
                ->where('delivered', $copaymentStatus);
        }]);
        $data = [];
        foreach ($assignServices as $service) {
            $pin = array_reduce(array_column($service->details->toArray(), 'Pin'), function ($carry, $item) {
                $carry .= $item ? $item . '-' : '';
                return $carry;
            }, '');
            $kitMNB = ServiceSupply::where('AssignServiceId', $service->AssignServiceId)
                ->where('SupplyId', 3)->sum('Quantity');
            $pin = count($pin) ? substr($pin, 0, -1) : null;
            $data[] = [
                'AssignServiceId' => $service->AssignServiceId,
                'PatientDocument' => $service->patient->Document,
                'PatientName' => $service->patient->NameCompleted,
                'EntityName' => $service->entity->BusinessName,
                'AuthorizationNumber' => $service->AuthorizationNumber,
                'ServiceName' => $service->service->Name,
                'PaymentProfessional' => (float) $service->service->Value,
                'Quantity' => count($service->details),
                'CoPaymentAmount' => (float) $service->CoPaymentAmount,
                'CoPaymentFrecuency' => $service->CoPaymentFrecuency->Name,
                'TotalCopaymentReceived' => array_sum(array_column($service->details->toArray(), 'ReceivedAmount')),
                'Pin' => $pin,
                'PatientDocumentType' => $service->patient->documentType->Abbreviation,
                'KITMNB' => (int) $kitMNB,
                'SubTotal' => count($service->details) * $service->service->Value,
                'Total' => (count($service->details) * $service->service->Value) - array_sum(array_column($service->details->toArray(), 'ReceivedAmount')),
                'OtherValuesReceived' => array_sum(array_column($service->details->toArray(), 'OtherAmount'))
            ];
        }

        return $data;
    }

    public function update(Request $request, $professional)
    {
        $professional = Professional::findOrFail($professional)->ProfessionalId;
        $request->validate([
            'services.*' => 'required:exists:sqlsrv.sas.AssignService,AssignServiceId',
            'InitDate' => 'required',
            'FinalDate' => 'required',
        ]);
        $services = PatientService::whereIn('AssignServiceId', $request->input('services'));

        if (array_search($request->input('StateId'), [1,2])) {
            $services->where('StateId', $request->input('StateId'));
        } else {
            $services->where('StateId', '<>', 3);
        }

        $services = $services->get();
        $initDate = $request->input('InitDate');
        $finalDate = $request->input('FinalDate');
        $services->load(['details' => function ($query) use ($professional, $initDate, $finalDate) {
            $query->where('sas.AssignServiceDetails.ProfessionalId', $professional)
                ->whereBetween('DateVisit', [$initDate, $finalDate])
                ->where('StateId', 2)
                ->where('delivered', 0);
        }]);

        $isOwner = true;

        foreach ($services as $service) {
            if (!count($service->details)) {
                $isOwner = false;
                break;
            }
        }

        if (!$isOwner) {
            return response()->json([
                'message' => 'El profesional no está asignado en algunos servicios'
            ], 400);
        }

        foreach ($services as $service) {
            $details = $service->details->toArray();
            $copaymentReceived = array_sum(array_column($details, 'ReceivedAmount'));
            $othersAmountReceived = array_sum(array_column($details, 'OtherAmount'));
            $service->TotalCopaymentReceived += $copaymentReceived;
            $service->OtherValuesReceived += $othersAmountReceived;
            $service->DeliveredCopayments = $service->TotalCopaymentReceived;
            $detailsId = array_column($details, 'AssignServiceDetailId');
            ServiceDetail::whereIn('AssignServiceDetailId', $detailsId)
                ->update(['delivered' => 1]);
            $queryDetails = ServiceDetail::where('AssignServiceId', $service->AssignServiceId);
            $detailsDelivered = $queryDetails->where('delivered', 1)->count();
            $detailsTotal = $queryDetails->count();
            if ($detailsDelivered == $detailsTotal) {
                $service->CopaymentStatus = 1;
            }
            $service->save();
        }

        return response()->json([
            'message' => 'Servicios Actualizados con exito'
        ]);
    }
}
