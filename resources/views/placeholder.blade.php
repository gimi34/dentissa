@extends('layouts.app')
@section('title', $titulo ?? 'Módulo')

@section('content')
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:50vh;text-align:center">
    <div style="width:70px;height:70px;background:var(--p100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:20px">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="1.5">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
    </div>
    <h2 style="font-family:'Roboto',sans-serif;font-size:22px;font-weight:500;color:var(--text);margin-bottom:8px">
        {{ $titulo ?? 'Módulo en desarrollo' }}
    </h2>
    <p style="font-size:14px;color:var(--text-muted);max-width:340px;line-height:1.6">
        Este módulo está en construcción. Pronto estará disponible.
    </p>
    <a href="javascript:history.back()" class="btn btn-outline" style="margin-top:20px">← Volver</a>
</div>
@endsection