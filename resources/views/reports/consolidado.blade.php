<table>
    <thead>
    <tr>
        <th>NOMBRE PACIENTE</th>
        <th>TIPO DOCUMENTO</th>
        <th>NÚMERO DOCUMENTO</th>
        <th>ENTIDAD</th>
        <th>CONTRATO</th>
        <th>PLAN</th>
        <th>AUTORIZACIÓN</th>
        <th>SERVICIO</th>
        <th>N. TOTAL SESIONES</th>
        <th>N. SESIONES COMPL</th>
        <th>N. SESIONES PROGRAM</th>
        <th>FREC. SERVICIO</th>
        <th>ESTADO</th>
        <th>TARIFA</th>
        <th>COPAGO</th>
        <th>FREC. COPAGO</th>
        <th>ESTADO COPAGO</th>
        <th>FECHA INICIO SOLICITUD</th>
        <th>FECHA INICIO TENT.</th>
        <th>FECHA FIN TENT.</th>
        <th>FECHA INICIO REAL</th>
        <th>FECHA FIN REAL</th>
        <th>CIE10</th>
        <th>DES. CIE10</th>
        <th>TIPO PACIENTE</th>
        <th>EDAD</th>
        <th>FECHA NACIMIENTO</th>
        <th>GENERO</th>
        <th>TELÉFONO 1</th>
        <th>TELÉFONO 2</th>
        <th>DIRECCIÓN</th>
        <th>EMAIL</th>
        <th>ASIGNADO POR</th>
        <th>OBSERVACIONES</th>
        @for ($i = 1; $i <= $cant; $i++) 
            <th>DOC{{ $i }}</th>
            <th>NOMBRE{{ $i }}</th>
        @endfor
    </tr>
    </thead>
    <tbody>
    @foreach($data as $datum)
        <tr>
            <td>{{ $datum['PatientName'] }}</td>
            <td>{{ $datum['PatientDocumentType'] }}</td>
            <td>{{ $datum['PatientDocument'] }}</td>
            <td>{{ $datum['ContractNumber'] }}</td>
            <td>{{ $datum['PlanEntityName'] }}</td>
            <td>{{ $datum['AuthorizationNumber'] }}</td>
            <td>{{ $datum['ServiceName'] }}</td>
            <td>{{ $datum['TotalSessions'] }}</td>
            <td>{{ $datum['ProgrammedSessions'] }}</td>
            <td>{{ $datum['CompletedSessions'] }}</td>
            <td>{{ $datum['ServiceFrecuency'] }}</td>
            <td>{{ $datum['ServiceStatus'] }}</td>
            <td>{{ $datum['Rate'] }}</td>
            <td>{{ $datum['CoPaymentAmount'] }}</td>
            <td>{{ $datum['CopaymentFrecuency'] }}</td>
            <td>{{ $datum['CopaymentStatus'] }}</td>
            <td>{{ $datum['RequestDate'] }}</td>
            <td>{{ $datum['InitialDate'] }}</td>
            <td>{{ $datum['FinalDate'] }}</td>
            <td>{{ $datum['RealInitialDate'] }}</td>
            <td>{{ $datum['RealFinalDate'] }}</td>
            <td>{{ $datum['Cie10'] }}</td>
            <td>{{ $datum['DescriptionCie10'] }}</td>
            <td>{{ $datum['PatientType'] }}</td>
            <td>{{ $datum['Age'] }}</td>
            <td>{{ $datum['PatientBirthday'] }}</td>
            <td>{{ $datum['PatientGender'] }}</td>
            <td>{{ $datum['PatientPhone1'] }}</td>
            <td>{{ $datum['PatientPhone2'] }}</td>
            <td>{{ $datum['PatientAddress'] }}</td>
            <td>{{ $datum['PatientEmail'] }}</td>
            <td>{{ $datum['AssignedBy'] }}</td>
            <td>{{ $datum['Observations'] }}</td>
            @foreach($datum['professionals'] as $professional)
                <td>{{ $professional['Document'] }}</td>
                <td>{{ $professional['Name'] }}</td>
            @endforeach
            @for($i = 1; $i >= $cant - count($datum['professionals']); $i++)
                <td> </td>
                <td> </td>
            @endfor
        </tr>
    @endforeach
    </tbody>
</table>