<p>Hola {{ $username }}, han sido creadas las credenciales para que puedas acceder al sistema ADOM  </p>
<br>
<p><strong>Usuario:</strong> {{ $email }} </p>
<p><strong>Contraseña:</strong> {{ $password }} </p>
<br>
<p>Si desea acceder vaya al siguiente link <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>