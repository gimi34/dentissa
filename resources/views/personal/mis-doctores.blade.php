@extends('layouts.app')
@section('title', 'Mis doctores')

@section('content')

<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
    <span style="color:var(--text)">Doctores a los que auxilio</span>
</div>

@if($doctores->count() > 0)
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
        @foreach($doctores as $doc)
        <div class="card" style="padding:18px 20px;{{ !$doc->activo ? 'opacity:.6' : '' }}">
            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px">
                <div style="width:46px;height:46px;border-radius:50%;background:var(--p100);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:600;color:var(--p500);flex-shrink:0">
                    {{ strtoupper(substr($doc->nombre,0,1)) }}{{ strtoupper(substr($doc->apellido,0,1)) }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:500;font-size:14px;color:var(--text)">{{ $doc->nombre_completo }}</div>
                    @if($doc->especialidad)
                        <div style="font-size:12px;color:var(--text-muted);margin-top:1px">{{ $doc->especialidad }}</div>
                    @endif
                    @if(!$doc->activo)
                        <span class="badge badge-red" style="margin-top:4px">Inactivo</span>
                    @endif
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:5px;font-size:12.5px;color:var(--text-muted);margin-bottom:14px">
                @if($doc->email)
                    <div style="display:flex;align-items:center;gap:6px;overflow:hidden">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2" style="flex-shrink:0">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $doc->email }}</span>
                    </div>
                @endif
                @if($doc->telefono)
                    <div style="display:flex;align-items:center;gap:6px">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2" style="flex-shrink:0">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                        </svg>
                        {{ $doc->telefono }}
                    </div>
                @endif
            </div>

            <div style="padding-top:12px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:12px;color:var(--text-muted)">Citas hoy</span>
                <span style="font-size:16px;font-weight:600;color:var(--p500)">{{ $doc->citas_hoy }}</span>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <p>Aún no tienes doctores asignados</p>
            <p style="font-size:12.5px;color:var(--text-light);margin-top:4px">Contacta a un administrador para que te asigne a un dentista.</p>
        </div>
    </div>
@endif

@endsection
