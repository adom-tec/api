<?php

namespace App\Http\Controllers;

use App\DetailCancelReason;
use App\PatientService;
use App\Professional;
use App\ReasonSuspensionServiceDetail;
use Illuminate\Http\Request;
use App\ServiceDetail;
use Carbon\Carbon;

class ServiceDetailController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/AssignService/Edit')->only('index');
    }
    public function index($service, $me = false, Request $request)
    {
        $query = ServiceDetail::where('AssignServiceId', $service);
        if ($me) {
            $professional = Professional::where('UserId', $request->user()->UserId)->first();
            if ($professional) {
                $query->where('ProfessionalId', $professional->ProfessionalId);
            } else {
                return response()->json([
                    'message' => 'Su usario no está configurado como profesional'
                ], 403);
            }
        }
        return $query->orderBy('Consecutive', 'asc')
            ->with(['professional.user', 'state', 'detailCancelReason.cancelReason', 'detailSuspensionReason.suspensionReason'])
            ->get();
    }

    public function update(Request $request, $service, $id = null) 
    {
        $details = $request->input('details');
        $user = $request->user()->UserId;
        if ($details) {
            foreach ($details as $detail) {
                $serviceDetail = ServiceDetail::findOrFail($detail['AssignServiceDetailId']);
                $this->updateServiceDetail($serviceDetail, $detail, $user);
            }
        } else {
            $serviceDetail = ServiceDetail::findOrFail($id);
            $this->updateServiceDetail($serviceDetail, $request->all(), $user);
        }
        
       
        return response()->json([
            'message' => 'Visitas modificadas con exito'
        ], 200);
    }

    private function updateServiceDetail(ServiceDetail $serviceDetail, $detail, $user) {
        extract($detail);
        $assignServiceId = $serviceDetail->AssignServiceId;
        $assignServiceDetailId = $serviceDetail->AssignServiceDetailId;
        $state = $detail['StateId'] ? $StateId : $serviceDetail->StateId;
        $professionalId = $detail['ProfessionalId'] ? $ProfessionalId : $serviceDetail->ProfessionalId;
        $date = $detail['DateVisit'] ? $DateVisit : $serviceDetail->DateVisit;
        if (!is_null($date)) {
            $date = Carbon::createFromFormat('Y-m-d', substr($date, 0, 10))->format('Y-d-m');
        }
        
        $paymentType = $detail['PaymentType'] ? $PaymentType : $serviceDetail->PaymentType;
        $receivedAmount = $detail['ReceivedAmount'] >= 0 ? $ReceivedAmount : $serviceDetail->ReceivedAmount;
        $otherAmount = $detail['OtherAmount'] ? $OtherAmount : $serviceDetail->OtherAmount;
        $observation = $detail['Observation'] ? $Observation : $serviceDetail->Observation;
        $pin = $detail['Pin'] ? $Pin : $serviceDetail->Pin;
        $verified = $detail['Verified'] ? $Verified : $serviceDetail->Verified;
        if ($detail['Verified'] && !$serviceDetail->Verified) {
            $verifiedBy = $user;
        } else {
            $verifiedBy = $serviceDetail->VerifiedBy ? $serviceDetail->VerifiedBy : 'null';
        }
        
        $paymentType = $paymentType ? $paymentType : 0;
        $receivedAmount = $receivedAmount ? $receivedAmount : 0;
        $otherAmount = $otherAmount ? $otherAmount : 0;

        if (!is_null($date)) {
            $sql = "exec sas.UpdateAssignServiceDetails $assignServiceId, $assignServiceDetailId, $state, $professionalId, '$date', $paymentType, $receivedAmount, $otherAmount, '$observation', '$pin', $verified, $verifiedBy";
        } else {
            $sql = "exec sas.UpdateAssignServiceDetails $assignServiceId, $assignServiceDetailId, $state, $professionalId, null, $paymentType, $receivedAmount, $otherAmount, '$observation', '$pin', $verified, $verifiedBy";
        }
        
        $serviceId = \DB::select(\DB::raw($sql))[0]->AssignServiceId;

        $serviceDetail->professional_rate_id = $detail['professional_rate_id'] ? $detail['professional_rate_id'] : $serviceDetail->professional_rate_id;
        $serviceDetail->AuthorizationNumber = $detail['AuthorizationNumber'] ? $detail['AuthorizationNumber'] : $serviceDetail->AuthorizationNumber;
//        if (isset($InitDateAuthorizationNumber)) {
//            $serviceDetail->InitDateAuthorizationNumber = $InitDateAuthorizationNumber;
//        }
//        if (isset($FinalDateAuthorizationNumber)) {
//            $serviceDetail->FinalDateAuthorizationNumber = $FinalDateAuthorizationNumber;
//        }

        if ($state != 3) {
            DetailCancelReason::where('AssignServiceDetailId', $serviceDetail->AssignServiceDetailId)
                ->delete();

            if ($state == 4) {
                PatientService::where('AssignServiceId', $serviceDetail->AssignServiceId)
                    ->update(['StateId' => 4]);
            }

        }
        if ($state != 4) {
            ReasonSuspensionServiceDetail::where('AssignServiceDetailId', $serviceDetail->AssignServiceDetailId)
                ->delete();

        }
        $serviceDetail->save();
        PatientService::updateAuthorizationNumber($serviceDetail->AssignServiceId);
    }
}
