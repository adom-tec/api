<html>
    <head>
        <style>
            body {
                padding-top: 120px;
            }
            header {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                padding: 15px 0;
            }
            header p {
                text-align: center;
                margin: 0;
                padding: 0;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .sequence {
                margin-bottom: 40px;
                text-align: right;
                padding-right: 10px;
            }
            #payment-info {
                font-size: 12px;
                text-align: left;   
            }
            #payment-info td, #payment-info th {
                padding: 4px 0;
                padding-right: 15px;
            }
            #payment-detail {
                margin-top: 30px;
                font-size: 8px;
                border-collapse: collapse;
            }
            #payment-detail th, #payment-detail td {
                max-width: 5%;
                border: solid 1px black;
                padding: 2px;
                text-align: center;
            }
            #firmas {
                position: absolute;
                bottom: 0;
                vertical-align: top; 
            }
            #firmas .firma {
                display: inline-block;
                width: 40%;
                border-top: solid 1px black;
                text-align: center;
            }
        </style>
    </head>

    <body>
        <header>
            <p>SERVICIOS ADOM SAS</p>
            <p>NIT: 830.001.237-4</p>
            <p>BOGOTÁ - COLOMBIA</p>
            <p>610 3520</p>
        </header>
        <p class="sequence"><strong>Numero Cuenta de Cobro:</strong> {{ $collectionAccount->id }}</p>
        <table id="payment-info">
            <tr>
                <th>Fecha de Cuenta de Cobro:</th>
                <td>{{ $now }}</td>
                <th>Periodo de Cuenta de Cobro:</th>
                <td colspan="3">{{ $period }}</td>
                <th>Correo Electronico:</th>
                <td>{{ $professional->user->Email }}</td>
            </tr>
            <tr>
                <th>Nombre del Profesional:</th>
                <td>{{ $name }}</td>
                <th>Numero de Documento:</th>
                <td>{{ $professional->Document }}</td>
                <th>Telefono:</th>
                <td>{{ $professional->Telephone1 }}</td>
            </tr>
            <tr>
                <th>Direccion:</th>
                <td colspan="6">{{ $professional->Address }}</td>
            </tr>   
        </table>

        <table id="payment-detail">
            <thead>
                <tr>
                    <th>N. DOCUMENTO DEL PACIENTE</th>
                    <th>PACIENTE</th>
                    <th>ENTIDAD</th>
                    <th>AUTORIZACION</th>
                    <th>TIPO DE TERAPIA</th>
                    <th>VALOR A PAGAR AL PROFESIONAL POR TERAPIA</th>
                    <th>CANTIDAD DE TERAPIAS REALIZADAS</th>
                    <th>COPAGO</th>
                    <th>FRECUENCIA DE COPAGO</th>
                    <th>VALOR TOTAL RECAUDADO COPAGOS</th>
                    <th>VALE/PIN</th>
                    <th>KIT MNB</th>
                    <th>CUANTOS KIT UTILIZO</th>
                    <th>VALORA ENTREGAR</th>
                    <th>SUBTOTAL</th>
                    <th>VALOR A PAGAR</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($services as $service)
                <tr>
                    <td>{{ $service['PatientName'] }}</td>
                    <td>{{ $service['PatientDocument'] }}</td>
                    <td>{{ $service['EntityName'] }}</td>
                    <td>{{ $service['AuthorizationNumber'] }}</td>
                    <td>{{ $service['ServiceName'] }}</td>
                    <td>{{ $service['PaymentProfessional'] }}</td>
                    <td>{{ $service['Quantity'] }}</td>
                    <td>{{ $service['CoPaymentAmount'] }}</td>
                    <td>{{ $service['CoPaymentFrecuency'] }}</td>
                    <td>{{ number_format($service['TotalCopaymentReceived'], 2, ',', '.') }}</td>
                    <td>{{ $service['Pin'] }}</td>
                    <td>{{ $service['KITMNB'] ? 'SI' : 'No' }}</td>
                    <td>{{ $service['KITMNB'] }}</td>
                    <td>{{ number_format($service['TotalCopaymentReceived'], 2, ',', '.') }}</td>
                    <td>{{ number_format($service['SubTotal'], 2, ',', '.') }}</td>
                    <td>{{ number_format($service['Total'], 2, ',', '.') }}</td>   
                </tr>   
            @endforeach
            </tbody>
        </table>

        <p style="margin: 20px 0;">Observaciones: {{ $collectionAccount->observation }}</p>

        <div id="firmas">
            <div class="firma"><p>Firma Recibe</p></div>
            <div class="firma" style="right: 0; position: absolute;"><p>Firma Entrega</p></div>
        </div>
    </body>
</html>