{{-- Citas futuras que quedan fuera del horario del dentista (spec 027, CA7). Solo tokens y clases del sistema. --}}
<section id="citas-fuera" aria-labelledby="citas-fuera-titulo">
    <div class="section-head">
        <h2 class="section-title" id="citas-fuera-titulo">Citas fuera del horario ({{ $citasFuera->count() }})</h2>
    </div>
    <div class="card card-0">
        <ul class="lista-citas">
            @foreach($citasFuera as $cita)
                @php $inicio = \Carbon\Carbon::parse($cita->fecha_hora); @endphp
                <li>
                    <span>{{ $cita->paciente }}</span>
                    <span>{{ $inicio->format('d/m/Y') }}</span>
                    <span>{{ $inicio->format('H:i') }}</span>
                    @include('partials.badge-estado', ['estado' => $cita->estado])
                    <a href="{{ route('admin.citas.show', $cita->id) }}" class="btn btn-outline btn-sm">Ver cita</a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
