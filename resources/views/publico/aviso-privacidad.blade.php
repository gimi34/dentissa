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
                @include('publico.legal.privacidad-texto', ['nivel' => 'h2', 'prefijo' => '', 'enVentana' => false])
            </div>
        </div>
    </div>
</div>
@endsection
