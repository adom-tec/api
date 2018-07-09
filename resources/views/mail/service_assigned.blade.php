<p>HOLA, {{ $professionalName }}</p>

<p>Se te ha asignado el siguiente servicio, por favor confirma telefónicamente si puedes atenderlo:</p>

<p><b>Identificación:</b>{{ $service->patient->Document }}</p>
<p><b>Nombre paciente:</b>{{ $service->patient->NameCompleted }}</p>
<p><b>Dirección:</b>{{ $service->patient->Address }}</p>
<p><b>Teléfono:</b>{{ $service->patient->Telephone1 }}</p>
<p><b>Nro. Autorización:</b>{{ $service->AuthorizationNumber }}</p>
<p><b>Servicio:</b>{{ $service->service->Name }}</p>
<p><b>Fecha inicio*:</b>{{ $InitialDate }}</p>
<p><b>Fecha finalización**:</b>{{ $FinalDate }}</p>
<p><b>Frecuencia Terapia:</b>{{ $service->coPaymentFrecuency->Name }}</p>
<p><b>Valor Copago:</b>{{ $service->coPaymentAmount }}</p>
<p><b>Frecuencia Copago:</b>{{ $service->coPaymentFrecuency->Name }}</p>
<p><b>Entidad:</b> {{ $service->entity->Name }}</p>
<br>
<br>
<a href="#">Ingreso a Blue</a>
<br>
<br>
<p>* La fecha de inicio es una fecha sugerida, ya que la fecha y hora de atención dependerá de lo que se convenga entre terapeuta y paciente.</p>
<p>** La fecha de finalización es de carácter referencial, ya que todo dependerá de lo que se convenga entre terapeuta y paciente, y de la evolución del mismo.</p>