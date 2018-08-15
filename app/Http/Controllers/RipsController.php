<?php

namespace App\Http\Controllers;

use App\ServiceDetail;
use Illuminate\Http\Request;
use App\PatientService;
use App\GeneratedRip;
use App\AdomInfo;
use App\PlanService;
use Carbon\Carbon;

class RipsController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/Rips/Get')->only('getServices');
        $this->middleware('verify.action:/Rips/Create')->only('generateRips');
    }

    public function getServices(Request $request)
    {
        $serviceType = $request->input('ServiceType');
        $services = PatientService::select('AssignServiceId','PatientId', 'sas.AssignService.ServiceId', 'EntityId', 'PlanEntityId', 'AuthorizationNumber', 'InitialDate', 'FinalDate', 'InvoiceNumber')
            ->where('InitialDate', '>=', $request->input('InitDate'))
            ->where('FinalDate', '<=', $request->input('FinalDate'))
            ->where('EntityId', $request->input('Entity'))
            ->join('cfg.Services', function ($join) use ($serviceType) {
                $join->on('cfg.Services.ServiceId', '=', 'sas.AssignService.ServiceId')
                    ->where('cfg.Services.ServiceTypeId', $serviceType);
            })
            ->where('StateId', 2)
            //->where('CopaymentStatus', 1)
            ->with(['patient:PatientId,NameCompleted,Document', 'service:ServiceId,Name', 'entity:EntityId,Name', 'planService:PlanEntityId,Name']);

        if ($request->input('PlanEntity')) {
            $services->where('PlanEntityId', $request->input('PlanEntity'));
        }

        return $services->get();
    } 

    public function generateRips(Request $request)
    {

        $count = PatientService::whereIn('AssignServiceId', $request->input('services'))
            ->whereNotNull('InvoiceNumber')->count();
        if ($count) {
            return response()->json([
                'message' => 'Error, Algunos servicios ya tienen factura asignada'
            ], 400);
        }
        $generatedRip = new GeneratedRip();
        $generatedRip->InvoiceNumber = $request->input('InvoiceNumber');
        $generatedRip->save();
        $services = PatientService::whereIn('AssignServiceId', $request->input('services'))
            ->with(['patient', 'service', 'entity', 'supplies'])
            ->get();
        $adomInfo = AdomInfo::all()[0];

        $i = 0;

        foreach ($services as $service)
        {
            $rate = PlanService::select('Rate')
                ->where('ServiceId', $service->ServiceId)
                ->where('PlanEntityId', $service->PlanEntityId)
                ->first()->Rate;
            $services[$i]->Rate = $rate;
            $i++;
        }
        extract($request->except('services'));
        $usData = $this->getUsData($services, $adomInfo);
        $acData = $this->getAcData($services, $adomInfo, $InvoiceNumber);
        $finalDate = Carbon::createFromFormat('Y-m-d', $request->input('InvoiceDate'))->format('d/m/Y');
        $servicesAp = $services->filter(function ($service) {
            return $service->service->ClassificationId == 2;
        });

        $apData = $this->getApData($servicesAp, $adomInfo, $InvoiceNumber, $finalDate);
        $atData = $this->getAtData($services, $adomInfo, $InvoiceNumber);
        $afData = $this->getAfData($services, $adomInfo, $InvoiceNumber, $InvoiceDate, $CopaymentAmount, $NetWorth);
        $ctData = $this->getCtData($adomInfo, $generatedRip->GeneratedRipsId, count($usData), count($acData), count($apData), count($atData), count($afData));
        \Storage::disk('rips')->makeDirectory($generatedRip->GeneratedRipsId);
        $directory = storage_path('app/public/rips') . '/' . $generatedRip->GeneratedRipsId . '/';
        $this->createCSVFile($directory . 'US' . $generatedRip->GeneratedRipsId . '.txt', $usData);
        $this->createCSVFile($directory . 'AC' . $generatedRip->GeneratedRipsId . '.txt', $acData);
        $this->createCSVFile($directory . 'AP' . $generatedRip->GeneratedRipsId . '.txt', $apData);
        $this->createCSVFile($directory . 'AT' . $generatedRip->GeneratedRipsId . '.txt', $atData);
        $this->createCSVFile($directory . 'AF' . $generatedRip->GeneratedRipsId . '.txt', $afData);
        $this->createCSVFile($directory . 'CT' . $generatedRip->GeneratedRipsId . '.txt', $ctData);
        $zip = new \ZipArchive();
        $fileName = $directory . 'rips.zip';
        
        if ($zip->open($fileName, \ZipArchive::CREATE) === true) {
            $zip->addFile($directory . 'US' . $generatedRip->GeneratedRipsId . '.txt', 'US' . $generatedRip->GeneratedRipsId . '.txt');
            $zip->addFile($directory . 'AC' . $generatedRip->GeneratedRipsId . '.txt', 'AC' . $generatedRip->GeneratedRipsId . '.txt');
            $zip->addFile($directory . 'AP' . $generatedRip->GeneratedRipsId . '.txt', 'AP' . $generatedRip->GeneratedRipsId . '.txt');
            $zip->addFile($directory . 'AT' . $generatedRip->GeneratedRipsId . '.txt', 'AT' . $generatedRip->GeneratedRipsId . '.txt');
            $zip->addFile($directory . 'AF' . $generatedRip->GeneratedRipsId . '.txt', 'AF' . $generatedRip->GeneratedRipsId . '.txt');
            $zip->addFile($directory . 'CT' . $generatedRip->GeneratedRipsId . '.txt', 'CT' . $generatedRip->GeneratedRipsId . '.txt');
            $zip->close();
        }
        
        PatientService::whereIn('AssignServiceId', $request->input('services'))
            ->update(['InvoiceNumber' => $request->input('InvoiceNumber')]);
        $content = file_get_contents($fileName);
        \Storage::disk('rips')->deleteDirectory($generatedRip->GeneratedRipsId);

        return \Response::make($content, 200);

    }

    private function getUsData($services, $adomInfo) {
        $data = [];
        $identifiers = [];

        $cant = count($services);

        for ($i = 0; $i < $cant; $i++) {
            if (array_search([$services[$i]->patient->PatientId, $services[$i]->service->ServiceId], $identifiers) === false) {
                $identifiers[] = [$services[$i]->patient->PatientId, $services[$i]->service->ServiceId];
            } else {
                unset($services[$i]);
            }
        }

        foreach ($services as $service) {
            $gender = $service->patient->GenderId == 1 ? 'M' : $service->patient->GenderId == 2 ? 'F' : '';
            $data[] = [
                
                $service->patient->documentType->Abbreviation,
                $service->patient->Document,
                $service->entity->Code,
                1,
                $service->patient->Surname,
                $service->patient->SecondSurname,
                $service->patient->FirstName,
                $service->patient->SecondName,
                $service->patient->Age,
                $service->patient->UnitTimeId,
                $gender,
                $adomInfo->DepartmentCode,
                $adomInfo->CityCode,
                $adomInfo->ResidenceArea,

            ];
        }

        return $data;
    }

    private function getAcData($services, $adomInfo, $invoiceNumber) {
        $data = [];

        foreach ($services as $service) {
            $details = ServiceDetail::where('AssignServiceId', $service->AssignServiceId)
                ->where('StateId', 2)
                ->get();
            foreach ($details as $detail) {
                $date = Carbon::createFromFormat('Y-m-d', $detail->DateVisit)->format('d/m/Y');

                $net = $service->Rate - $detail->ReceivedAmount;
                $data[] = [
                    $invoiceNumber,
                    $adomInfo->ProviderCode,
                    $service->patient->documentType->Abbreviation,
                    $service->patient->Document,
                    $date,
                    $service->AuthorizationNumber,
                    $service->service->Code,
                    $service->Consultation,
                    $service->External,
                    $service->Cie10,
                    '',
                    '',
                    '',
                    2,
                    $service->Rate,
                    $detail->ReceivedAmount,
                    $net
                ];
            }
        }

        return $data;
    }

    private function getApData($services, $adomInfo, $invoiceNumber, $finalDate)
    {
        $data = [];
        foreach ($services as $service) {
            $data[] = [
                $invoiceNumber,
                $adomInfo->ProviderCode,
                $service->patient->documentType->Abbreviation,
                $service->patient->Document,
                $finalDate,
                $service->AuthorizationNumber,
                $service->service->Code,
                1,
                2,
                4,
                $service->Cie10,
                '',
                '',
                '',
                $service->Rate
            ];
        }
        return $data;
    }

    private function getAtData($services, $adomInfo, $invoiceNumber)
    {
        $services->load('serviceSupplies.supply');
        $services = $services->filter(function ($service) {
            return count($service->serviceSupplies);
        }); 
        $data = [];
        foreach ($services as $service) {
            foreach ($service->serviceSupplies as $serviceSupply) {
                $data[] = [
                    $invoiceNumber,
                    $adomInfo->ProviderCode,
                    $service->patient->documentType->Abbreviation,
                    $service->patient->Document,
                    $service->AuthorizationNumber,
                    1,
                    $serviceSupply->supply->Code,
                    $serviceSupply->supply->Name,
                    $serviceSupply->Quantity,
                    0,
                    0
                ];
            }
            
        }

        return $data;
    }

    private function getAfData($services, $adomInfo, $invoiceNumber, $invoiceDate, $copayment, $netValue)
    {
        $data = [];
        $identifiers = [];
        $cant = count($services);
        $invoiceDate = Carbon::createFromFormat('Y-m-d', $invoiceDate)->format('d/m/Y');
        for ($i = 0; $i < $cant; $i++) {
            if (array_search($services[$i]->entity->EntityId, $identifiers) === false) {
                $identifiers[] = $services[$i]->entity->EntityId;
            } else {
                unset($services[$i]);
            }
        }

        $initDate = '';
        $finalDate = '';

        foreach ($services as $service) {
            $initDate = ServiceDetail::where('AssignServiceId', $service->AssignServiceId)
                ->where('StateId', 2)
                ->min('DateVisit');

            $finalDate = ServiceDetail::where('AssignServiceId', $service->AssignServiceId)
                ->where('StateId', 2)
                ->max('DateVisit');

            $initDate = Carbon::createFromFormat('Y-m-d', $initDate)->format('d/m/Y');
            $finalDate = Carbon::createFromFormat('Y-m-d', $finalDate)->format('d/m/Y');

            $data[] = [
                $adomInfo->ProviderCode,
                $adomInfo->BusinessName,
                $adomInfo->IdentificationType,
                $adomInfo->IdentificationNumber,
                $invoiceNumber,
                $invoiceDate,
                $initDate,
                $finalDate,
                $service->entity->Code,
                $service->entity->Name,
                '',
                '',
                '',
                $copayment,
                '0',
                $service->OtherValuesReceived,
                $netValue
            ]; 
        }

        return $data;
    }

    private function getCtData($adomInfo, $identifier, $usCount, $acCount, $apCount, $atCount, $afCount)
    {
        $date = Carbon::now()->format('d/m/Y');
        return [
            [
                $adomInfo->ProviderCode,
                $date,
                'AF' . $identifier,
                $afCount
            ],
            [
                $adomInfo->ProviderCode,
                $date,
                'US' . $identifier,
                $usCount
            ],
            [
                $adomInfo->ProviderCode,
                $date,
                'AP' . $identifier,
                $apCount
            ],
            [
                $adomInfo->ProviderCode,
                $date,
                'AC' . $identifier,
                $acCount
            ],
            [
                $adomInfo->ProviderCode,
                $date,
                'AT' . $identifier,
                $atCount
            ],
        ];
    }

    private function createCSVFile($fileName, $data)
    {
        $fp = fopen($fileName, 'a');

        $i = 0;
        foreach ($data as $datum) {
            $content = implode(',', $this->cleanDatum($datum));
            $content = $i > 0 ? "\r\n" . $content : $content;
            fwrite($fp, $content);
            $i++;
        }

        fclose($fp);
    }

    private function cleanDatum($row)
    {
        for ($i = 0; $i < count($row); $i++) {
            $row[$i] = $this->clean($row[$i]);
        }
        return $row;
    }

    private function clean($string)
    {
        $string = trim($string);

        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );

        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );

        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );

        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );

        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );

        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $string
        );

        $string = str_replace(
            array("\\", "¨", "º", "-", "~",
                "#", "@", "|", "!", "\"",
                "·", "$", "%", "&",
                "(", ")", "?", "'", "¡",
                "¿", "[", "^", "<code>", "]",
                "+", "}", "{", "¨", "´",
                ">", "< ", ";", ",", ":",
                "."),
            '',
            $string
        );
        return $string;
    }
}
