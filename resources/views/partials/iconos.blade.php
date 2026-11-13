{{-- Íconos del sitio, en el <head> de todas las plantillas (ficha 2026-11-12-chore-favicon; SE5.a de docs/seo.md).
     Salen de public/images/favicon.png; el del iPhone lleva fondo blanco porque iOS pinta de negro lo transparente. --}}
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="48x48">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ filemtime(public_path('favicon-32x32.png')) }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ filemtime(public_path('apple-touch-icon.png')) }}">
