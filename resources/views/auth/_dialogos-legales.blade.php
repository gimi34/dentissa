{{-- Spec 045 (CA4, D6, D11, D12): las ventanas de los términos de uso y del aviso de privacidad del registro,
     con el mismo texto que sus páginas (los parciales de publico/legal). Los enlaces de la casilla las abren
     sin salir del registro; sin JavaScript (o sin showModal), el enlace lleva a la página. --}}
@php
    $contacto = config('clinica.contacto');
    $horario = \App\Http\Controllers\PaginasPublicasController::horarioEnTexto();
@endphp
@foreach ([
    ['id' => 'dialogo-terminos', 'titulo' => 'Términos de uso', 'parcial' => 'publico.legal.terminos-texto', 'prefijo' => 'terminos-'],
    ['id' => 'dialogo-privacidad', 'titulo' => 'Aviso de privacidad', 'parcial' => 'publico.legal.privacidad-texto', 'prefijo' => 'privacidad-'],
] as $ventana)
<dialog class="dialogo-legal" id="{{ $ventana['id'] }}" aria-labelledby="{{ $ventana['id'] }}-titulo">
    <div class="dialogo-legal__cabeza">
        <div>
            <h2 id="{{ $ventana['id'] }}-titulo">{{ $ventana['titulo'] }}</h2>
            <p class="dialogo-legal__fecha">Última actualización: 12 de noviembre de 2026</p>
        </div>
        <button type="button" class="btn-back dialogo-legal__cerrar" data-cerrar>Cerrar</button>
    </div>
    <div class="dialogo-legal__texto">
        @include($ventana['parcial'], ['nivel' => 'h3', 'prefijo' => $ventana['prefijo'], 'enVentana' => true])
    </div>
</dialog>
@endforeach
<script>
// Spec 045 (CA4): los enlaces de la casilla abren su texto en una ventana sin salir del registro. Sin showModal,
// el enlace lleva a la página. La ventana se cierra con «Cerrar», con Escape (nativo) o pulsando fuera, y el foco
// vuelve al enlace que la abrió. El enlace al otro texto, dentro de una ventana, abre su ventana.
(function () {
    var ultimo = null;
    document.querySelectorAll('[data-dialogo]').forEach(function (enlace) {
        enlace.addEventListener('click', function (evento) {
            var destino = document.getElementById(enlace.getAttribute('data-dialogo'));
            if (!destino || typeof destino.showModal !== 'function') return;
            evento.preventDefault();
            var abierto = document.querySelector('dialog.dialogo-legal[open]');
            if (abierto) abierto.close(); else ultimo = enlace;
            destino.showModal();
        });
    });
    document.querySelectorAll('dialog.dialogo-legal').forEach(function (d) {
        d.querySelector('[data-cerrar]').addEventListener('click', function () { d.close(); });
        d.addEventListener('click', function (evento) { if (evento.target === d) d.close(); });
        d.addEventListener('close', function () {
            if (!document.querySelector('dialog.dialogo-legal[open]') && ultimo) ultimo.focus();
        });
    });
})();
</script>
