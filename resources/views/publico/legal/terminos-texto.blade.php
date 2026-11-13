{{-- Spec 045 (D11): las secciones de los términos de uso, una sola fuente para la página y para su ventana del
     registro. Parámetros: $nivel ('h2' en la página, 'h3' en la ventana), $prefijo de los ids ('' en la
     página) y $enVentana (los enlaces al otro texto abren su ventana). Usa $contacto y $horario. --}}
<section id="{{ $prefijo }}quien" aria-labelledby="{{ $prefijo }}titulo-quien">
    <{{ $nivel }} id="{{ $prefijo }}titulo-quien"><span class="lb-num" aria-hidden="true">01</span> Quién ofrece este sitio</{{ $nivel }}>
    <p>Dentissa es el sitio en línea de Melissa López N. · Odontología integral («la clínica»), en {{ $contacto['direccion'] }}, teléfono <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>.</p>
    <p>Aquí puedes conocer los servicios de la clínica y, con una cuenta de paciente, pedir y gestionar tus citas. Al crear tu cuenta aceptas estos términos y el <a href="{{ route('privacidad') }}"@if ($enVentana) data-dialogo="dialogo-privacidad" aria-haspopup="dialog"@endif>aviso de privacidad</a>.</p>
</section>
<section id="{{ $prefijo }}cuenta" aria-labelledby="{{ $prefijo }}titulo-cuenta">
    <{{ $nivel }} id="{{ $prefijo }}titulo-cuenta"><span class="lb-num" aria-hidden="true">02</span> Tu cuenta</{{ $nivel }}>
    <ul>
        <li>Es personal: no la compartas ni uses la de otra persona.</li>
        <li>Da datos verdaderos y mantenlos al día desde «Mi perfil».</li>
        <li>Cuida tu contraseña. Si crees que alguien más entró en tu cuenta, cámbiala con «¿Olvidaste tu contraseña?» y avísanos.</li>
        <li>Si la cuenta es para una persona menor de edad, la crea y la usa su madre, su padre o su tutor.</li>
    </ul>
</section>
<section id="{{ $prefijo }}citas" aria-labelledby="{{ $prefijo }}titulo-citas">
    <{{ $nivel }} id="{{ $prefijo }}titulo-citas"><span class="lb-num" aria-hidden="true">03</span> Citas en línea</{{ $nivel }}>
    <p>Puedes pedir una cita en los horarios que la agenda muestra libres. Cada cita aparece en tu cuenta con su estado.</p>
    <ul>
        <li>Puedes cancelarla o cambiarla de día desde tu cuenta hasta 24 horas antes. Después, llama a la clínica.</li>
        <li>La clínica puede confirmar, cambiar o cancelar una cita cuando hace falta; lo verás en tu cuenta.</li>
    </ul>
    <p><strong>El sitio no sirve para urgencias.</strong> Si tienes una, llama al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a> o acude al servicio de urgencias más cercano.</p>
</section>
<section id="{{ $prefijo }}no-permitido" aria-labelledby="{{ $prefijo }}titulo-no-permitido">
    <{{ $nivel }} id="{{ $prefijo }}titulo-no-permitido"><span class="lb-num" aria-hidden="true">04</span> Lo que no está permitido</{{ $nivel }}>
    <ul>
        <li>Usar una cuenta que no es tuya o hacerte pasar por otra persona.</li>
        <li>Intentar ver datos de otras personas o entrar en partes del sitio que no te corresponden.</li>
        <li>Entorpecer el servicio, por ejemplo, con envíos automáticos o repetidos.</li>
    </ul>
    <p>Si pasa, la clínica puede desactivar la cuenta y, si corresponde, avisar a las autoridades.</p>
</section>
<section id="{{ $prefijo }}consulta" aria-labelledby="{{ $prefijo }}titulo-consulta">
    <{{ $nivel }} id="{{ $prefijo }}titulo-consulta"><span class="lb-num" aria-hidden="true">05</span> No sustituye la consulta</{{ $nivel }}>
    <p>La información del sitio sobre tratamientos y servicios es general. No es un diagnóstico ni una recomendación para tu caso: eso solo puede dártelo un dentista en consulta.</p>
</section>
<section id="{{ $prefijo }}cambios" aria-labelledby="{{ $prefijo }}titulo-cambios">
    <{{ $nivel }} id="{{ $prefijo }}titulo-cambios"><span class="lb-num" aria-hidden="true">06</span> Cambios a estos términos</{{ $nivel }}>
    <p>Si cambiamos estos términos, publicamos la versión nueva en esta página con su fecha de actualización arriba. Si sigues usando tu cuenta después, aceptas la versión nueva.</p>
</section>
<section id="{{ $prefijo }}contacto" aria-labelledby="{{ $prefijo }}titulo-contacto">
    <{{ $nivel }} id="{{ $prefijo }}titulo-contacto"><span class="lb-num" aria-hidden="true">07</span> Contacto</{{ $nivel }}>
    <p>Para cualquier duda sobre estos términos, llama al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a> o pasa por la clínica {{ $horario }}.</p>
</section>
