<?php

namespace App\Http\Controllers;

use App\Professional;
use App\ServiceDetail;
use App\ServiceSupply;
use Illuminate\Http\Request;
use App\PatientService;
use App\CollectionAccount;
use Carbon\Carbon;
use App\VisitCollectionAccount;

class CopaymentController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/CoPayment/Get')->only('index');
        $this->middleware('verify.action:/CoPayment/Create')->only('store');
        $this->middleware('verify.action:/CoPayment/Edit')->only('update');
    }

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
        return $this->getServices($professionalId, $initDate, $finalDate, $copaymentStatus, $request->input('StateId'));
    }

    public function update(Request $request, $professional)
    {
        $professional = Professional::findOrFail($professional)->ProfessionalId;
        $request->validate([
            'services.*.AssignServiceId' => 'required:exists:sqlsrv.sas.AssignService,AssignServiceId',
            'services.*.professional_rate_id' => 'required:exists:sqlsrv.cfg.ProfesionalRates,id',
            'InitDate' => 'required',
            'FinalDate' => 'required',
        ]);
        $professionalTakenAmount = $request->input('professional_taken_amount', 0);
        $servicesId = array_column($request->input('services'), 'AssignServiceId');
        $services = PatientService::whereIn('AssignServiceId', $servicesId);

        if ($request->input('StateId') == 1 || $request->input('StateId') == 2) {
            $services->where('StateId', $request->input('StateId'));
        } else {
            $services->where('StateId', '<>', 3);
        }

        $services = $services->get();
        $initDate = $request->input('InitDate');
        $finalDate = $request->input('FinalDate');
        $dataServices = $request->input('services');
        foreach ($services as $service) {
            $ratesId = [];
            foreach ($dataServices as $dataService) {
                if ($dataService['AssignServiceId'] == $service->AssignServiceId) {
                    $ratesId[] = $dataService['professional_rate_id'];
                }
            }
            $service->load(['details' => function ($query) use ($professional, $initDate, $finalDate, $ratesId) {
                $query->where('sas.AssignServiceDetails.ProfessionalId', $professional)
                    ->whereBetween('DateVisit', [$initDate, $finalDate])
                    ->where('StateId', 2)
                    ->where('delivered', 0)
                    ->whereIn('professional_rate_id', $ratesId);
            }]);
        }

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

        \DB::beginTransaction();
        $serviceDetailsId = [];
        $visitsId = [];
        foreach ($services as $service) {
            $details = $service->details->toArray();
            $copaymentReceived = array_sum(array_column($details, 'ReceivedAmount'));
            $othersAmountReceived = array_sum(array_column($details, 'OtherAmount'));
            $service->TotalCopaymentReceived += $copaymentReceived;
            $service->OtherValuesReceived += $othersAmountReceived;
            $service->DeliveredCopayments = $service->TotalCopaymentReceived;
            $detailsId = array_column($details, 'AssignServiceDetailId');
            $serviceDetailsId = array_merge($serviceDetailsId, $detailsId);
            ServiceDetail::whereIn('AssignServiceDetailId', $detailsId)
                ->update(['delivered' => 1]);
            $queryDetails = ServiceDetail::where('AssignServiceId', $service->AssignServiceId);
            $detailsDelivered = $queryDetails->where('delivered', 1)->count();
            $detailsTotal = $queryDetails->count();
            if ($detailsDelivered == $detailsTotal) {
                $service->CopaymentStatus = 1;
            }
            $service->save();
            $visitsId = array_merge($visitsId, $detailsId);
        }

        $collectionAccount = new CollectionAccount();
        $collectionAccount->observation = $request->input('observation');
        $collectionAccount->ProfessionalTakenAmount = $professionalTakenAmount;
        $collectionAccount->save();

        $visitsCollectionAccount = [];
        foreach ($visitsId as $id) {
            $visitsCollectionAccount[] = [
                'CollectionAccountId' => $collectionAccount->id,
                'AssignServiceDetailId' => $id
            ];
        }

        VisitCollectionAccount::insert($visitsCollectionAccount);

        try {
            $pdf = $this->pfd($professional, $initDate, $finalDate, $collectionAccount, $request->input('StateId'), $serviceDetailsId, $professionalTakenAmount);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->getTrace()
            ], 500);
        }
        
        \DB::commit();
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream();
    }

    public function getServices($professionalId, $initDate, $finalDate, $copaymentStatus, $stateId, $services = [], $visitsId = [])
    {
        if (!$copaymentStatus) {
            $assignServices = PatientService::select('sas.AssignService.*', 'sas.AssignServiceDetails.professional_rate_id', 'sas.AssignServiceDetails.ReceivedAmount', 'sas.AssignServiceDetails.Pin', 'sas.AssignServiceDetails.OtherAmount', 'sas.AssignServiceDetails.ProfessionalId as ProfessionalVisit');
        } else {
            $assignServices = PatientService::select('sas.AssignService.*', 'sas.AssignServiceDetails.professional_rate_id', 'sas.AssignServiceDetails.ReceivedAmount', 'sas.AssignServiceDetails.Pin', 'sas.AssignServiceDetails.OtherAmount', 'sas.CollectionAccounts.id', 'sas.CollectionAccounts.RecordDate', 'sas.AssignServiceDetails.ProfessionalId as ProfessionalVisit');
        }
        if (!count($visitsId)) {
            $assignServices = $assignServices->join('sas.AssignServiceDetails', function ($join) use ($professionalId, $initDate, $finalDate, $copaymentStatus) {
                $join->on('sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
                    ->where('sas.AssignServiceDetails.ProfessionalId', $professionalId)
                    ->whereBetween('DateVisit', [$initDate, $finalDate])
                    ->where('sas.AssignServiceDetails.StateId', 2)
                    ->where('delivered', $copaymentStatus);
                });
        } else {
            $assignServices = $assignServices->join('sas.AssignServiceDetails', function ($join) use ($visitsId) {
                $join->on('sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
                    ->where('sas.AssignServiceDetails.StateId', 2)
                    ->where('delivered', 1)
                    ->whereIn('sas.AssignServiceDetails.AssignServiceDetailId', $visitsId);
                });
        }

        $assignServices = $assignServices->with(['patient.documentType', 'entity', 'service', 'coPaymentFrecuency', 'professional']);

        if ($stateId == 1 || $stateId == 2) {
            $assignServices->where('sas.AssignService.StateId', $stateId);
        } else {
            $assignServices->where('sas.AssignService.StateId', '<>', 3);
        }

        if (count($services)) {
            $assignServices->whereIn('sas.AssignServiceDetails.AssignServiceDetailId', $services);
        }
        if ($copaymentStatus) {
            $assignServices->join('sas.VisitCollectionAcount', function ($join) {
                $join->on('sas.VisitCollectionAcount.AssignServiceDetailId', '=', 'sas.AssignServiceDetails.AssignServiceDetailId');
            });

            $assignServices->join('sas.CollectionAccounts', function ($join) {
               $join->on('sas.CollectionAccounts.id', '=', 'sas.VisitCollectionAcount.CollectionAccountId');
            });

            $assignServices->orderBy('sas.CollectionAccounts.RecordDate');
        }

        $assignServices->orderBy('sas.AssignService.AssignServiceId');
        $assignServices = $assignServices->get();
        $count = count($assignServices);
        $identifiers = [];
	    $indexes = [];

        
        for ($i = 0; $i < $count; $i++) {
            if ($copaymentStatus) {
                $filter = [$assignServices[$i]->AssignServiceId, $assignServices[$i]->id, $assignServices[$i]->professional_rate_id];
            } else {
                $filter = [$assignServices[$i]->AssignServiceId, $assignServices[$i]->professional_rate_id];
            }

            $search = array_search($filter, $identifiers);
            
            if ($search === false) {
                $assignServices[$i]->QuantityRealized = 1;
                $assignServices[$i]->TotalCopaymentReceived = $assignServices[$i]->ReceivedAmount;
                $assignServices[$i]->TotalPin = $assignServices[$i]->Pin ? $assignServices[$i]->Pin . ' - ' : '';
                $assignServices[$i]->OtherValuesReceived = $assignServices[$i]->OtherAmount;
                $identifiers[] = $filter;
		        $indexes[] = $i;
            } else {
		        $position = $indexes[$search];
                $assignServices[$position]->QuantityRealized += 1;
                $assignServices[$position]->TotalCopaymentReceived += $assignServices[$i]->ReceivedAmount;
                $assignServices[$position]->TotalPin .= $assignServices[$i]->Pin ? $assignServices[$i]->Pin . ' - ' : '';
                $assignServices[$position]->OtherValuesReceived += $assignServices[$i]->OtherAmount;
                unset($assignServices[$i]);
            }
        }

        $data = [];
        $i = 0;

        foreach ($assignServices as $service) {
            $kitMNB = ServiceSupply::where('AssignServiceId', $service->AssignServiceId)
                ->where('SupplyId', 3)->sum('Quantity');
            $pin = $service->TotalPin ? substr($service->TotalPin, 0, -3) : '';
            if ($service->professional_rate_id == 1) {
                $paymentProfessional = $service->service->Value;
            } elseif ($service->professional_rate_id == 2) {
                $paymentProfessional = $service->service->special_value;
            } elseif ($service->professional_rate_id == 3) {
                $paymentProfessional = $service->service->particular_value;
            } elseif ($service->professional_rate_id == 4) {
                $paymentProfessional = $service->service->holiday_value;
            }

            $subTotal = $service->QuantityRealized * $paymentProfessional;
            $data[] = [
                'AssignServiceId' => $service->AssignServiceId,
                'PatientDocumentType' => $service->patient->documentType->Abbreviation,
                'PatientDocument' => $service->patient->Document,
                'PatientName' => $service->patient->NameCompleted,
                'EntityName' => $service->entity->BusinessName,
                'AuthorizationNumber' => $service->AuthorizationNumber,
                'ServiceName' => $service->service->Name,
                'PaymentProfessional' => (float) $paymentProfessional,
                'Quantity' => (int) $service->QuantityRealized,
                'CoPaymentAmount' => (float) $service->CoPaymentAmount,
                'CoPaymentFrecuency' => $service->CoPaymentFrecuency->Name,
                'TotalCopaymentReceived' => (float) $service->TotalCopaymentReceived,
                'Pin' => $pin,
                'KITMNB' => $kitMNB ? 'SI' : 'NO',
                'QuantityKITMNB' => (int) $kitMNB,
                'OtherValuesReceived' => (float) $service->OtherValuesReceived,
                'TotalCopaymentDelivered' => (float) $service->TotalCopaymentReceived + $service->OtherValuesReceived,
                'SubTotal' => (float) $subTotal,
                'professional_rate_id' => $service->professional_rate_id
            ];
            
            if (count($visitsId)) {
		$professional = Professional::findOrFail($service->ProfessionalVisit);
        	$name = $professional->user->FirstName . ' ';
        	if ($professional->user->SecondName) {
                    $name .= $professional->user->SecondName . ' ';
                }
        	$name .= $professional->user->Surname . ' ';
                if ($professional->user->SecondSurname) {
                    $name .= $professional->user->SecondSurname;
                }
                $data[$i]['ProfessionalDocument'] = $service->professional->Document;
                $data[$i]['ProfessionalName'] = $name;
                $data[$i]['ProfessionalId'] = $service->professional->ProfessionalId;
                if ($copaymentStatus) {
                    $data[$i]['DeliveredDate'] = $service->RecordDate;
                }
            }

            $i++;
        }
        return $data;
    }

    private function pfd($ProfessionalId, $InitDate, $FinalDate, $collectionAccount, $StateId, $servicesId, $professionalTakenAmount)
    {
        $services = $this->getServices($ProfessionalId, $InitDate, $FinalDate, 1, $StateId, $servicesId);

        $professional = Professional::findOrFail($ProfessionalId);
        $initDate = Carbon::createFromFormat('Y-m-d', $InitDate)->format('d/m/Y');
        $finalDate = Carbon::createFromFormat('Y-m-d', $FinalDate)->format('d/m/Y');
        $period = $initDate . ' hasta ' . $finalDate;
        $name = $professional->user->FirstName . ' ';
        if ($professional->user->SecondName) {
            $name .= $professional->user->SecondName . ' ';
        }
        $name .= $professional->user->Surname . ' ';
        if ($professional->user->SecondSurname) {
            $name .= $professional->user->SecondSurname;
        }
        $totalCopaymentReceived = array_sum(array_column($services, 'TotalCopaymentReceived'));
        $subTotal = array_sum(array_column($services, 'SubTotal'));
        $totalOtherValues = array_sum(array_column($services, 'OtherValuesReceived'));
        $data = [
            'collectionAccount' => $collectionAccount,
            'services' => $services,
            'professional' => $professional,
            'period' => $period,
            'now' => Carbon::now()->format('d/m/Y'),
            'name' => $name,
            'totalCopaymentReceived' => $totalCopaymentReceived,
            'subTotal' => $subTotal,
            'professionalTakenAmount' => $professionalTakenAmount,
            'totalOtherValues' => $totalOtherValues,
            'totalCopaymentDelivered' => ($totalCopaymentReceived + $totalOtherValues) - $professionalTakenAmount,
            'total' => $subTotal - $professionalTakenAmount
        ];

        $pdf = \PDF::loadView('pdf.copayment', $data);
        return $pdf;
    }
}
