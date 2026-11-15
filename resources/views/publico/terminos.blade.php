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
                @include('publico.legal.terminos-texto', ['nivel' => 'h2', 'prefijo' => '', 'enVentana' => false])
            </div>
        </div>
    </div>
</div>
@endsection
