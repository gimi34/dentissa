@extends('layouts.publico')

{{-- Spec 045 (CA1): los términos de uso, con el texto aprobado del candidato c4 (design.md; lo fija TerminosPrivacidadTest::test_el_texto_es_el_aprobado). Los datos de la clínica salen de la configuración (D4). --}}
@section('titulo', 'Términos de uso · Dentissa')
@section('descripcion', 'Las reglas para usar el sitio de la clínica Dentissa y tu cuenta de paciente: citas en línea, lo que no está permitido y cómo contactarnos.')
@section('canonica', rtrim(config('app.url'), '/').'/terminos')

@section('contenido')
<div class="lb-seccion">
    <div class="lb-wrap">
        <div class="lb-legal">
            <div class="lb-cabeza lb-legal__cabeza">
                <span class="pub-etiqueta">Información legal</span>
                <h1>Términos de uso</h1>
                <p>Las reglas para usar este sitio y la cuenta de paciente.</p>
                <p class="lb-fecha">Última actualización: 12 de noviembre de 2026</p>
            </div>
            <nav class="lb-indice lb-indice--legal" aria-labelledby="titulo-indice">
                <p class="pub-etiqueta" id="titulo-indice">En esta página</p>
                <a href="#quien"><span>Quién ofrece este sitio</span> <small aria-hidden="true">01</small></a>
                <a href="#cuenta"><span>Tu cuenta</span> <small aria-hidden="true">02</small></a>
                <a href="#citas"><span>Citas en línea</span> <small aria-hidden="true">03</small></a>
                <a href="#no-permitido"><span>Lo que no está permitido</span> <small aria-hidden="true">04</small></a>
                <a href="#consulta"><span>No sustituye la consulta</span> <small aria-hidden="true">05</small></a>
                <a href="#cambios"><span>Cambios a estos términos</span> <small aria-hidden="true">06</small></a>
                <a href="#contacto"><span>Contacto</span> <small aria-hidden="true">07</small></a>
            </nav>
            <aside class="pub-book__card lb-resumen" aria-labelledby="titulo-resumen">
                <svg class="pub-book__arco" viewBox="0 0 600 600" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M40 600 C60 200 220 40 300 40 C380 40 540 200 560 600"/><path d="M130 600 C150 280 250 150 300 150 C350 150 450 280 470 600"/></svg>
                <h2 id="titulo-resumen">En pocas palabras</h2>
                <ul role="list">
                    <li><p>La cuenta es personal y con datos verdaderos.</p></li>
                    <li><p>Cancela o cambia tus citas hasta 24 horas antes.</p></li>
                    <li><p>Para urgencias, llama al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>: el sitio no sirve para eso.</p></li>
                </ul>
            </aside>
            <div class="lb-lista lb-texto">
                <section id="quien" aria-labelledby="titulo-quien">
                    <h2 id="titulo-quien"><span class="lb-num" aria-hidden="true">01</span> Quién ofrece este sitio</h2>
                    <p>Dentissa es el sitio en línea de Melissa López N. · Odontología integral («la clínica»), en {{ $contacto['direccion'] }}, teléfono <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>.</p>
                    <p>Aquí puedes conocer los servicios de la clínica y, con una cuenta de paciente, pedir y gestionar tus citas. Al crear tu cuenta aceptas estos términos y el <a href="{{ route('privacidad') }}">aviso de privacidad</a>.</p>
                </section>
                <section id="cuenta" aria-labelledby="titulo-cuenta">
                    <h2 id="titulo-cuenta"><span class="lb-num" aria-hidden="true">02</span> Tu cuenta</h2>
                    <ul>
                        <li>Es personal: no la compartas ni uses la de otra persona.</li>
                        <li>Da datos verdaderos y mantenlos al día desde «Mi perfil».</li>
                        <li>Cuida tu contraseña. Si crees que alguien más entró en tu cuenta, cámbiala con «¿Olvidaste tu contraseña?» y avísanos.</li>
                        <li>Si la cuenta es para una persona menor de edad, la crea y la usa su madre, su padre o su tutor.</li>
                    </ul>
                </section>
                <section id="citas" aria-labelledby="titulo-citas">
                    <h2 id="titulo-citas"><span class="lb-num" aria-hidden="true">03</span> Citas en línea</h2>
                    <p>Puedes pedir una cita en los horarios que la agenda muestra libres. Cada cita aparece en tu cuenta con su estado.</p>
                    <ul>
                        <li>Puedes cancelarla o cambiarla de día desde tu cuenta hasta 24 horas antes. Después, llama a la clínica.</li>
                        <li>La clínica puede confirmar, cambiar o cancelar una cita cuando hace falta; lo verás en tu cuenta.</li>
                    </ul>
                    <p><strong>El sitio no sirve para urgencias.</strong> Si tienes una, llama al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a> o acude al servicio de urgencias más cercano.</p>
                </section>
                <section id="no-permitido" aria-labelledby="titulo-no-permitido">
                    <h2 id="titulo-no-permitido"><span class="lb-num" aria-hidden="true">04</span> Lo que no está permitido</h2>
                    <ul>
                        <li>Usar una cuenta que no es tuya o hacerte pasar por otra persona.</li>
                        <li>Intentar ver datos de otras personas o entrar en partes del sitio que no te corresponden.</li>
                        <li>Entorpecer el servicio, por ejemplo, con envíos automáticos o repetidos.</li>
                    </ul>
                    <p>Si pasa, la clínica puede desactivar la cuenta y, si corresponde, avisar a las autoridades.</p>
                </section>
                <section id="consulta" aria-labelledby="titulo-consulta">
                    <h2 id="titulo-consulta"><span class="lb-num" aria-hidden="true">05</span> No sustituye la consulta</h2>
                    <p>La información del sitio sobre tratamientos y servicios es general. No es un diagnóstico ni una recomendación para tu caso: eso solo puede dártelo un dentista en consulta.</p>
                </section>
                <section id="cambios" aria-labelledby="titulo-cambios">
                    <h2 id="titulo-cambios"><span class="lb-num" aria-hidden="true">06</span> Cambios a estos términos</h2>
                    <p>Si cambiamos estos términos, publicamos la versión nueva en esta página con su fecha de actualización arriba. Si sigues usando tu cuenta después, aceptas la versión nueva.</p>
                </section>
                <section id="contacto" aria-labelledby="titulo-contacto">
                    <h2 id="titulo-contacto"><span class="lb-num" aria-hidden="true">07</span> Contacto</h2>
                    <p>Para cualquier duda sobre estos términos, llama al <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a> o pasa por la clínica {{ $horario }}.</p>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
