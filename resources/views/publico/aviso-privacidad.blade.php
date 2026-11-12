@extends('layouts.publico')

{{-- Spec 045 (CA2, CA3): el aviso de privacidad, con el texto aprobado del candidato c4 (design.md; lo fija TerminosPrivacidadTest::test_el_texto_es_el_aprobado). Los datos de la clínica salen de la configuración (D4). --}}
@section('titulo', 'Aviso de privacidad · Dentissa')
@section('descripcion', 'Qué datos personales guarda la clínica Dentissa, para qué, con quién los comparte y cómo ejercer tus derechos de acceso, rectificación, cancelación y oposición.')
@section('canonica', rtrim(config('app.url'), '/').'/aviso-de-privacidad')

@section('contenido')
<div class="lb-seccion">
    <div class="lb-wrap">
        <div class="lb-legal">
            <div class="lb-cabeza lb-legal__cabeza">
                <span class="pub-etiqueta">Información legal</span>
                <h1>Aviso de privacidad</h1>
                <p>Qué datos tuyos guarda la clínica, para qué y cómo puedes verlos, corregirlos o borrarlos.</p>
                <p class="lb-fecha">Última actualización: 12 de noviembre de 2026</p>
            </div>
            <nav class="lb-indice lb-indice--legal" aria-labelledby="titulo-indice">
                <p class="pub-etiqueta" id="titulo-indice">En esta página</p>
                <a href="#responsable"><span>Quién es responsable</span> <small aria-hidden="true">01</small></a>
                <a href="#datos"><span>Qué datos recabamos</span> <small aria-hidden="true">02</small></a>
                <a href="#usos"><span>Para qué los usamos</span> <small aria-hidden="true">03</small></a>
                <a href="#compartir"><span>Con quién los compartimos</span> <small aria-hidden="true">04</small></a>
                <a href="#conservacion"><span>Cuánto los conservamos</span> <small aria-hidden="true">05</small></a>
                <a href="#derechos"><span>Tus derechos</span> <small aria-hidden="true">06</small></a>
                <a href="#cookies"><span>Cookies</span> <small aria-hidden="true">07</small></a>
                <a href="#cambios"><span>Cambios a este aviso</span> <small aria-hidden="true">08</small></a>
            </nav>
            <aside class="pub-book__card lb-resumen" aria-labelledby="titulo-resumen">
                <svg class="pub-book__arco" viewBox="0 0 600 600" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M40 600 C60 200 220 40 300 40 C380 40 540 200 560 600"/><path d="M130 600 C150 280 250 150 300 150 C350 150 450 280 470 600"/></svg>
                <h2 id="titulo-resumen">En pocas palabras</h2>
                <ul role="list">
                    <li><p>No vendemos tus datos ni los usamos para publicidad.</p></li>
                    <li><p>Tus datos de salud son sensibles: los usa el personal de la clínica para atenderte.</p></li>
                    <li><p>Pide verlos, corregirlos o borrarlos en la clínica o al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>.</p></li>
                </ul>
            </aside>
            <div class="lb-lista lb-texto">
                <section id="responsable" aria-labelledby="titulo-responsable">
                    <h2 id="titulo-responsable"><span class="lb-num" aria-hidden="true">01</span> Quién es responsable</h2>
                    <p>Melissa López N. · Odontología integral («la clínica»), con domicilio en {{ $contacto['direccion'] }} y teléfono <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>, es responsable de tus datos personales y los trata conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</p>
                </section>
                <section id="datos" aria-labelledby="titulo-datos">
                    <h2 id="titulo-datos"><span class="lb-num" aria-hidden="true">02</span> Qué datos recabamos</h2>
                    <ul>
                        <li><strong>Al crear tu cuenta:</strong> nombre, apellidos, correo electrónico y contraseña (guardada de forma que nadie, ni la clínica, puede leerla), y, si quieres darlos, teléfono, fecha de nacimiento, género, dirección y ciudad.</li>
                        <li><strong>Si te registras o entras con Google:</strong> tu nombre, tu correo, tu identificador de Google y la dirección de tu foto de perfil.</li>
                        <li><strong>En tu ficha de paciente:</strong> el nombre y el teléfono de un contacto de emergencia, que anota el personal de la clínica.</li>
                        <li><strong>Datos de salud</strong>, que son <strong>datos sensibles</strong>: alergias, tipo de sangre, enfermedades crónicas, medicamentos y notas médicas; el diagnóstico, el tratamiento, las recetas y las observaciones de tu expediente, y las notas de tus citas. Los anota el personal de la clínica durante tu atención.</li>
                        <li><strong>Fotos de tratamientos:</strong> solo se publican en la galería con tu consentimiento y sin tu nombre.</li>
                        <li><strong>Datos técnicos:</strong> la dirección IP, el navegador que usas, la página pedida y la hora, en los registros del servidor y de seguridad del sitio.</li>
                    </ul>
                </section>
                <section id="usos" aria-labelledby="titulo-usos">
                    <h2 id="titulo-usos"><span class="lb-num" aria-hidden="true">03</span> Para qué los usamos</h2>
                    <ul>
                        <li>Darte atención dental: agendar, confirmar, cambiar y cancelar tus citas.</li>
                        <li>Llevar tu expediente clínico.</li>
                        <li>Escribirte sobre tu cuenta, por ejemplo, el enlace para recuperar tu contraseña o el aviso de que cambió.</li>
                        <li>Proteger el servicio y detectar accesos indebidos.</li>
                    </ul>
                    <p>No usamos tus datos para publicidad ni te enviamos promociones.</p>
                </section>
                <section id="compartir" aria-labelledby="titulo-compartir">
                    <h2 id="titulo-compartir"><span class="lb-num" aria-hidden="true">04</span> Con quién los compartimos</h2>
                    <p>No vendemos ni cedemos tus datos. Solo los reciben:</p>
                    <ul>
                        <li>Los proveedores que hacen funcionar el sitio por cuenta de la clínica: el servidor donde se aloja, Cloudflare, que lleva la conexión segura hasta él, y Google, si entras con tu cuenta de Google, y para enviar los correos de tu cuenta.</li>
                        <li>Las autoridades sanitarias, judiciales o administrativas, cuando la ley lo exige.</li>
                    </ul>
                </section>
                <section id="conservacion" aria-labelledby="titulo-conservacion">
                    <h2 id="titulo-conservacion"><span class="lb-num" aria-hidden="true">05</span> Cuánto los conservamos</h2>
                    <ul>
                        <li>Tu cuenta y tus datos de contacto, mientras tengas la cuenta.</li>
                        <li>Tu expediente clínico, al menos cinco años desde tu última atención, como pide la norma del expediente clínico (NOM-004-SSA3-2012).</li>
                        <li>El registro de seguridad (los accesos denegados y algunos cambios de la cuenta, como el restablecimiento de la contraseña), cinco años.</li>
                        <li>Las copias de seguridad, cifradas: se guardan las 14 más recientes y las anteriores se borran.</li>
                    </ul>
                </section>
                <section id="derechos" aria-labelledby="titulo-derechos">
                    <h2 id="titulo-derechos"><span class="lb-num" aria-hidden="true">06</span> Tus derechos</h2>
                    <p>Puedes pedir <strong>acceso</strong> a tus datos, su <strong>rectificación</strong> si son incorrectos, su <strong>cancelación</strong> u <strong>oponerte</strong> a un uso (derechos ARCO), y también revocar tu consentimiento o limitar el uso de tus datos.</p>
                    <p>Pídelo en persona en la clínica, {{ $horario }}, o llamando al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>. Te pediremos identificarte, o acreditar que representas a quien es titular de los datos. Te respondemos en un máximo de 20 días hábiles y, si procede, lo aplicamos en los 15 días hábiles siguientes.</p>
                    <p>Tus datos de contacto puedes corregirlos tú desde «Mi perfil». Los del expediente clínico no pueden borrarse antes del plazo de conservación de la norma.</p>
                    <p>Si no estás de acuerdo con la respuesta, puedes acudir a la autoridad de protección de datos personales.</p>
                </section>
                <section id="cookies" aria-labelledby="titulo-cookies">
                    <h2 id="titulo-cookies"><span class="lb-num" aria-hidden="true">07</span> Cookies</h2>
                    <p>El sitio usa cookies propias solo para funcionar: mantener abierta tu sesión y proteger los formularios contra envíos falsos. No usa cookies de analítica ni de publicidad.</p>
                    <p>El mapa de la página de inicio lo sirve Google: al mostrarse, Google recibe datos de tu navegador según su propia política de privacidad.</p>
                </section>
                <section id="cambios" aria-labelledby="titulo-cambios">
                    <h2 id="titulo-cambios"><span class="lb-num" aria-hidden="true">08</span> Cambios a este aviso</h2>
                    <p>Si cambiamos este aviso, publicamos la versión nueva en esta página con su fecha de actualización arriba.</p>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
