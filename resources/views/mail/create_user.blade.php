<p>Bienvenido , {{ $username }}.</p>
<br>
<p>Tus credenciales de acceso al sistema son las siguientes:</p>
<p><strong>Usuario:</strong> {{ $email }} </p>
<p><strong>Contraseña:</strong> {{ $password }} </p>
<p><strong>Accesso:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a> </p>