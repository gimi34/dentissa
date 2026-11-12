{{-- Spec 045 (D11): las secciones de el aviso de privacidad, una sola fuente para la página y para su ventana del
     registro. Parámetros: $nivel ('h2' en la página, 'h3' en la ventana), $prefijo de los ids ('' en la
     página) y $enVentana (los enlaces al otro texto abren su ventana). Usa $contacto y $horario. --}}
<section id="{{ $prefijo }}responsable" aria-labelledby="{{ $prefijo }}titulo-responsable">
    <{{ $nivel }} id="{{ $prefijo }}titulo-responsable"><span class="lb-num" aria-hidden="true">01</span> Quién es responsable</{{ $nivel }}>
    <p>Melissa López N. · Odontología integral («la clínica»), con domicilio en {{ $contacto['direccion'] }} y teléfono <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>, es responsable de tus datos personales y los trata conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</p>
</section>
<section id="{{ $prefijo }}datos" aria-labelledby="{{ $prefijo }}titulo-datos">
    <{{ $nivel }} id="{{ $prefijo }}titulo-datos"><span class="lb-num" aria-hidden="true">02</span> Qué datos recabamos</{{ $nivel }}>
    <ul>
        <li><strong>Al crear tu cuenta:</strong> nombre, apellidos, correo electrónico y contraseña (guardada de forma que nadie, ni la clínica, puede leerla), y, si quieres darlos, teléfono, fecha de nacimiento, género, dirección y ciudad.</li>
        <li><strong>Si te registras o entras con Google:</strong> tu nombre, tu correo, tu identificador de Google y la dirección de tu foto de perfil.</li>
        <li><strong>En tu ficha de paciente:</strong> el nombre y el teléfono de un contacto de emergencia, que anota el personal de la clínica.</li>
        <li><strong>Datos de salud</strong>, que son <strong>datos sensibles</strong>: alergias, tipo de sangre, enfermedades crónicas, medicamentos y notas médicas; el diagnóstico, el tratamiento, las recetas y las observaciones de tu expediente, y las notas de tus citas. Los anota el personal de la clínica durante tu atención.</li>
        <li><strong>Fotos de tratamientos:</strong> solo se publican en la galería con tu consentimiento y sin tu nombre.</li>
        <li><strong>Datos técnicos:</strong> la dirección IP, el navegador que usas, la página pedida y la hora, en los registros del servidor y de seguridad del sitio.</li>
    </ul>
</section>
<section id="{{ $prefijo }}usos" aria-labelledby="{{ $prefijo }}titulo-usos">
    <{{ $nivel }} id="{{ $prefijo }}titulo-usos"><span class="lb-num" aria-hidden="true">03</span> Para qué los usamos</{{ $nivel }}>
    <ul>
        <li>Darte atención dental: agendar, confirmar, cambiar y cancelar tus citas.</li>
        <li>Llevar tu expediente clínico.</li>
        <li>Escribirte sobre tu cuenta, por ejemplo, el enlace para recuperar tu contraseña o el aviso de que cambió.</li>
        <li>Proteger el servicio y detectar accesos indebidos.</li>
    </ul>
    <p>No usamos tus datos para publicidad ni te enviamos promociones.</p>
</section>
<section id="{{ $prefijo }}compartir" aria-labelledby="{{ $prefijo }}titulo-compartir">
    <{{ $nivel }} id="{{ $prefijo }}titulo-compartir"><span class="lb-num" aria-hidden="true">04</span> Con quién los compartimos</{{ $nivel }}>
    <p>No vendemos ni cedemos tus datos. Solo los reciben:</p>
    <ul>
        <li>Los proveedores que hacen funcionar el sitio por cuenta de la clínica: el servidor donde se aloja, Cloudflare, que lleva la conexión segura hasta él, y Google, si entras con tu cuenta de Google, y para enviar los correos de tu cuenta.</li>
        <li>Las autoridades sanitarias, judiciales o administrativas, cuando la ley lo exige.</li>
    </ul>
</section>
<section id="{{ $prefijo }}conservacion" aria-labelledby="{{ $prefijo }}titulo-conservacion">
    <{{ $nivel }} id="{{ $prefijo }}titulo-conservacion"><span class="lb-num" aria-hidden="true">05</span> Cuánto los conservamos</{{ $nivel }}>
    <ul>
        <li>Tu cuenta y tus datos de contacto, mientras tengas la cuenta.</li>
        <li>Tu expediente clínico, al menos cinco años desde tu última atención, como pide la norma del expediente clínico (NOM-004-SSA3-2012).</li>
        <li>El registro de seguridad (los accesos denegados y algunos cambios de la cuenta, como el restablecimiento de la contraseña), cinco años.</li>
        <li>Las copias de seguridad, cifradas: se guardan las 14 más recientes y las anteriores se borran.</li>
    </ul>
</section>
<section id="{{ $prefijo }}derechos" aria-labelledby="{{ $prefijo }}titulo-derechos">
    <{{ $nivel }} id="{{ $prefijo }}titulo-derechos"><span class="lb-num" aria-hidden="true">06</span> Tus derechos</{{ $nivel }}>
    <p>Puedes pedir <strong>acceso</strong> a tus datos, su <strong>rectificación</strong> si son incorrectos, su <strong>cancelación</strong> u <strong>oponerte</strong> a un uso (derechos ARCO), y también revocar tu consentimiento o limitar el uso de tus datos.</p>
    <p>Pídelo en persona en la clínica, {{ $horario }}, o llamando al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>. Te pediremos identificarte, o acreditar que representas a quien es titular de los datos. Te respondemos en un máximo de 20 días hábiles y, si procede, lo aplicamos en los 15 días hábiles siguientes.</p>
    <p>Tus datos de contacto puedes corregirlos tú desde «Mi perfil». Los del expediente clínico no pueden borrarse antes del plazo de conservación de la norma.</p>
    <p>Si no estás de acuerdo con la respuesta, puedes acudir a la autoridad de protección de datos personales.</p>
</section>
<section id="{{ $prefijo }}cookies" aria-labelledby="{{ $prefijo }}titulo-cookies">
    <{{ $nivel }} id="{{ $prefijo }}titulo-cookies"><span class="lb-num" aria-hidden="true">07</span> Cookies</{{ $nivel }}>
    <p>El sitio usa cookies propias solo para funcionar: mantener abierta tu sesión y proteger los formularios contra envíos falsos. No usa cookies de analítica ni de publicidad.</p>
    <p>El mapa de la página de inicio lo sirve Google: al mostrarse, Google recibe datos de tu navegador según su propia política de privacidad.</p>
</section>
<section id="{{ $prefijo }}cambios" aria-labelledby="{{ $prefijo }}titulo-cambios">
    <{{ $nivel }} id="{{ $prefijo }}titulo-cambios"><span class="lb-num" aria-hidden="true">08</span> Cambios a este aviso</{{ $nivel }}>
    <p>Si cambiamos este aviso, publicamos la versión nueva en esta página con su fecha de actualización arriba.</p>
</section>
