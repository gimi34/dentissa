@extends('layouts.app')
@section('title', 'Detalle de cita')

@section('topbar-actions')
    @if($cita->puede_cancelar)
        <form action="{{ route('cliente.citas.cancelar', $cita->id) }}" method="POST" style="display:inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-ghost"
                    style="color:var(--red)"
                    onclick="return confirm('¿Deseas cancelar esta cita?')">
                Cancelar cita
            </button>
        </form>
    @elseif($cita->aviso_cancelar)
        <span style="font-size:13px;color:var(--text-muted)">{{ $cita->aviso_cancelar }}</span>
    @endif
@endsection

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
    <a href="{{ route('cliente.citas') }}" style="color:var(--text-muted);text-decoration:none">Mis citas</a>
    <span>›</span>
    <span style="color:var(--text)">Detalle</span>
</div>

<div style="max-width:640px">
    <div class="card">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px">
            <div>
                <div style="font-family:'Roboto',sans-serif;font-size:20px;font-weight:500;color:var(--text)">
                    {{ $cita->servicio ?? 'Consulta dental' }}
                </div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:3px">
                    {{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    · {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }} hrs
                </div>
            </div>
            @include('partials.badge-estado', ['estado' => $cita->estado])
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
            <div style="background:var(--p50);border-radius:var(--radius-sm);padding:12px 14px">
                <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:4px">Dentista</div>
                <div style="font-size:14px;font-weight:500">{{ $cita->dentista }}</div>
                @if($cita->especialidad)
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">{{ $cita->especialidad }}</div>
                @endif
            </div>

            <div style="background:var(--p50);border-radius:var(--radius-sm);padding:12px 14px">
                <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:4px">Duración</div>
                <div style="font-size:14px;font-weight:500">{{ $cita->duracion_min }} min</div>
                <div style="font-size:12px;color:var(--text-muted)">
                    Termina: {{ \Carbon\Carbon::parse($cita->fecha_hora)->addMinutes($cita->duracion_min)->format('H:i') }}
                </div>
            </div>
        </div>

        @if($cita->notas_paciente)
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:6px">Indicaciones</div>
                <p style="font-size:13.5px;color:var(--text);line-height:1.6">{{ $cita->notas_paciente }}</p>
            </div>
        @endif

        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);font-size:12px;color:var(--text-muted)">
            Agendada el {{ \Carbon\Carbon::parse($cita->creado_en)->format('d/m/Y H:i') }}
        </div>
    </div>
</div>

@endsection
