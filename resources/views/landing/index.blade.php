<!doctype html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dentissa · Tu sonrisa, nuestra prioridad</title>
  
  <!-- Tailwind CSS por CDN con configuración de la paleta Dentissa -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            dentissa: {
              pink: '#D4537E',
              pinkHover: '#B83C65',
              light: '#FDF5F8',
              darkLighter: '#FBEAF0'
            }
          }
        }
      }
    }
  </script>
  <style>
    /* El degradado original de la plantilla, adaptado a Dentissa */
    .bg-dentissa-gradient {
      background-image: 
                      url("{{ asset('images/cover-bg.jpg') }}");
        background-size: cover;
  /* Cambiamos 'center' por 'right center' para empujar la foto a la derecha */
        background-position: right center;
        background-repeat: no-repeat;
    }
    
    /* Clases para la animación de desvanecimiento (Fade-In) */
    .fade-in-section {
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        will-change: opacity, visibility;
    }
    .fade-in-section.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Ocultar barra de desplazamiento para el carrusel */
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
  </style>
</head>
<body class="antialiased bg-gray-50 font-sans text-gray-900">
  <main class="w-full">
    
    <!-- ── Header & Navbar ──────────────────────────────────────── -->
    <header class="fixed top-0 left-0 w-full z-50 px-4 sm:px-8 lg:px-16 xl:px-40 2xl:px-64 bg-[#B83C65]/95 backdrop-blur-md shadow-lg transition-all">
      <!-- Barra superior oculta en móviles (Original de la plantilla) -->
      
        
      </div>

      <!-- Menú principal -->
      <div class="flex flex-wrap items-center justify-between py-4">
        <!-- Logo -->
        <div class="w-1/2 md:w-auto flex items-center gap-3">
          <img src="{{ asset('images/logo-icon.png') }}" alt="Logo" class="h-16 w-auto object-contain ">
          
        </div>

        <label for="menu-toggle" class="cursor-pointer md:hidden block">
          <svg class="fill-current text-white w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"></path></svg>
        </label>
        <input class="hidden peer" type="checkbox" id="menu-toggle">
        
        <!-- Enlaces -->
        <div class="hidden peer-checked:block md:block w-full md:w-auto transition-all" id="menu">
          <nav class="w-full bg-white md:bg-transparent rounded shadow-lg px-6 py-4 mt-4 text-center md:p-0 md:mt-0 md:shadow-none">
            <ul class="md:flex items-center justify-center space-x-0 md:space-x-8">
              <li class="my-2 md:my-0"><a class="text-white hover:text-dentissa-pink font-medium transition-colors" href="#servicios">Servicios</a></li>
              <li class="my-2 md:my-0"><a class="text-white hover:text-dentissa-pink font-medium transition-colors" href="#galeria">Galería</a></li>
              <li class="my-2 md:my-0"><a class="text-white hover:text-dentissa-pink font-medium transition-colors" href="#ubicacion">Ubicación</a></li>
              <li class="my-2 md:my-0"><a class="text-white hover:text-dentissa-pink font-medium transition-colors" href="#contacto">Contacto</a></li>
              
              <!-- Botón Dinámico de Laravel -->
              <li class="md:ml-8 mt-4 md:mt-0">
                @guest
                    <a class="inline-block font-bold px-6 py-2.5 text-white bg-dentissa-pink hover:bg-dentissa-pinkHover rounded-xl transition-all shadow-md" href="{{ route('login') }}">Iniciar sesión</a>
                @else
                    <a class="inline-block font-bold px-6 py-2.5 text-dentissa-dark bg-white hover:bg-gray-200 rounded-full transition-all shadow-md" href="{{ route('cliente.dashboard') }}">Ver mis citas</a>
                @endguest
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </header>

    <!-- ── Hero Section ────────────────────────────────────────── -->
    <div class="bg-gray-100">
      <section class="bg-dentissa-gradient relative px-4 sm:px-8 lg:px-16 xl:px-40 2xl:px-64 overflow-hidden pt-40 pb-24 flex items-center min-h-screen">
        <!-- Imagen de fondo de la plantilla -->
        <div class="h-full absolute top-0 left-0 w-full z-0">
          <img src="{{ asset('images/cover-bg.jpg') }}" alt="Fondo" class="w-full h-full object-cover opacity-10">
        </div>

         <div class="absolute inset-0 bg-gradient-to-r from-black/25 via-black/25 to-transparent"></div>
        
        <div class="w-full lg:w-3/4 xl:w-2/3 relative z-10 fade-in-section">
          <h1 class="text-white text-5xl md:text-6xl xl:text-7xl font-bold leading-tight mb-6">
            Tu sonrisa,<br>nuestra <em class="text-dentissa-pink">prioridad.</em>
          </h1>
          <p class="text-gray-200 text-xl md:text-2xl leading-snug mb-10 max-w-2xl">
            Gestiona tus citas, consulta tu historial y mantén tu salud dental al día con nuestra plataforma moderna.
          </p>
          
          @guest
          <a href="{{ route('register') }}" class="px-8 py-4 bg-dentissa-pink hover:bg-dentissa-pinkHover text-white rounded-full inline-block font-bold text-lg transition-transform hover:scale-105 shadow-[0_0_15px_rgba(212,83,126,0.4)]">
            Agendar mi primera cita
          </a>
          @endguest
        </div>
      </section>
    </div>

    <!-- ── Servicios (Tarjetas SVG de la Plantilla) ──────────── -->
    <section id="servicios" class="relative px-4 py-20 sm:px-8 lg:px-16 xl:px-40 2xl:px-64 bg-gray-50">
      <div class="text-center fade-in-section mb-16">
        <h2 class="text-4xl leading-tight font-bold text-dentissa-dark">Nuestros Servicios</h2>
        <p class="text-lg mt-4 text-gray-500 max-w-2xl mx-auto">Tratamientos especializados con la más alta tecnología para garantizar tu comodidad.</p>
      </div>

      <div class="flex flex-wrap justify-center -mx-4 fade-in-section" style="transition-delay: 0.2s;">
        <!-- Tarjeta 1 -->
        <div class="w-full md:w-1/2 lg:w-1/4 px-4 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 h-full hover:shadow-xl transition-shadow text-center">
            <svg class="w-20 h-20 mx-auto mb-6" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
  <g fill="none" fill-rule="evenodd">
    <path fill="#5EA7FF" d="M19.3214286,9 C16.7178005,9 15,11.9900983 15,15.6785714 C15,19.3670446 16.7178005,31 19.3214286,31 C20.8402925,31 21.0128203,21.3395704 23.0535714,21.3308234 C25.4871797,21.3395704 25.2668503,31 26.7857143,31 C29.3893424,31 31.1071429,19.3670446 31.1071429,15.6785714 C31.1071429,11.9900983 29.3893424,9 26.7857143,9 C25.5416667,9 24.297619,10.1785714 23.0535714,10.1785714 C21.8095238,10.1785714 20.5654762,9 19.3214286,9 Z"/>
    <polyline stroke="#80D25B" stroke-linecap="round" stroke-width="2" points="7.215 54.664 15.692 48.466 24.213 55.113 28.351 48.226 38.672 50.486 47.135 46.095 55.347 36.034"/>
    <polyline stroke="#979797" stroke-linecap="round" stroke-width="2" points="7.015 5 7.015 59.894 59.787 59.894"/>
    <path stroke="#979797" stroke-linecap="square" stroke-width="2" d="M36 15L58.0227155 15M36 19L45 19M49 19L55 19"/>
  </g>
</svg>
            <h4 class="text-xl font-bold text-dentissa-dark">Estética Dental</h4>
            <p class="mt-3 text-gray-500 text-sm">Blanqueamientos y diseño de sonrisa para que luzcas tu mejor versión.</p>
          </div>
        </div>
        <!-- Tarjeta 2 -->
        <div class="w-full md:w-1/2 lg:w-1/4 px-4 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 h-full hover:shadow-xl transition-shadow text-center">
            <svg class="w-20 h-20 mx-auto mb-6" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
  <g fill="none" fill-rule="evenodd">
    <polygon fill="#FF78C7" points="17.561 10.341 13.629 11.493 14.78 7.561 13.629 3.629 17.561 4.78 21.493 3.629 20.341 7.561 21.493 11.493" transform="rotate(-45 17.56 7.56)"/>
    <polygon fill="#FF78C7" points="17.78 55.67 15.107 56.453 15.89 53.78 15.107 51.107 17.78 51.89 20.453 51.107 19.67 53.78 20.453 56.453" transform="rotate(-45 17.78 53.78)"/>
    <path fill="#B4DFFB" d="M26.0714286,14 C21.8109462,14 19,18.8928881 19,24.9285714 C19,30.9642548 21.8109462,50 26.0714286,50 C28.5568423,50 28.8391605,34.1920244 32.1785714,34.177711 C36.1608395,34.1920244 35.8003005,50 38.2857143,50 C42.5461966,50 45.3571429,30.9642548 45.3571429,24.9285714 C45.3571429,18.8928881 42.5461966,14 38.2857143,14 C36.25,14 34.2142857,15.9285714 32.1785714,15.9285714 C30.1428571,15.9285714 28.1071429,14 26.0714286,14 Z"/>
    <polygon fill="#FF78C7" points="36.78 23.67 34.107 24.453 34.89 21.78 34.107 19.107 36.78 19.89 39.453 19.107 38.67 21.78 39.453 24.453" transform="rotate(-45 36.78 21.78)"/>
    <path stroke="#FF78C7" stroke-linecap="round" stroke-width="2" d="M17.4701388,29.01117 C9.93126448,29.8980783 4.92849706,31.4581568 4.92849706,33.2346166 C4.92849706,35.9960404 17.0168088,38.2346166 31.9284971,38.2346166 C46.8401853,38.2346166 58.9284971,35.9960404 58.9284971,33.2346166 C58.9284971,31.8411183 55.8501866,30.5807641 50.8841565,29.6740337" transform="rotate(-30 31.928 33.623)"/>
    <path stroke="#D650C7" stroke-linecap="round" stroke-width="2" d="M13.3558551,29.1929448 C8.13881926,30.1045985 4.8826859,31.3969451 4.8826859,32.830127 C4.8826859,35.5915508 16.9709977,37.830127 31.8826859,37.830127 L31.8826859,37.830127 C46.7943741,37.830127 58.8826859,35.5915508 58.8826859,32.830127 C58.8826859,31.0833094 54.0454791,29.5457122 46.7162979,28.6516378" transform="rotate(30 31.883 33.24)"/>
  </g>
</svg>
            
            <h4 class="text-xl font-bold text-dentissa-dark">Cirugía Oral</h4>
            <p class="mt-3 text-gray-500 text-sm">Extracciones y procedimientos complejos con especialistas certificados.</p>
          </div>
        </div>
        <!-- Tarjeta 3 -->
        <div class="w-full md:w-1/2 lg:w-1/4 px-4 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 h-full hover:shadow-xl transition-shadow text-center">
            <svg class="w-20 h-20 mx-auto mb-6" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
  <g fill="none" fill-rule="evenodd">
    <path stroke="#22BA8E" stroke-linecap="round" stroke-width="6" d="M6,11 C6,15.9705627 17.8644541,20 32.5,20 L32.5,20 C47.1355459,20 59,15.9705627 59,11" transform="matrix(1 0 0 -1 0 31)"/>
    <path fill="#B4DFFB" d="M23,4 C16.372583,4 12,11.6111593 12,21 C12,30.3888407 16.372583,60 23,60 C26.8661992,60 27.3053608,35.4098157 32.5,35.3875505 C38.6946392,35.4098157 38.1338008,60 42,60 C48.627417,60 53,30.3888407 53,21 C53,11.6111593 48.627417,4 42,4 C38.8333333,4 35.6666667,7 32.5,7 C29.3333333,7 26.1666667,4 23,4 Z"/>
    <path stroke="#80D25B" stroke-linecap="round" stroke-width="6" d="M6,20 C6,24.9705627 17.8644541,29 32.5,29 L32.5,29 C47.1355459,29 59,24.9705627 59,20"/>
    <path stroke="#FFF" stroke-linecap="round" stroke-width="2" d="M25,8 C20.581722,8 17,11.581722 17,16"/>
  </g>
</svg>
            <h4 class="text-xl font-bold text-dentissa-dark">Prevención</h4>
            <p class="mt-3 text-gray-500 text-sm">Limpiezas profundas y revisiones periódicas con técnicas sin dolor.</p>
          </div>
        </div>
        <!-- Tarjeta 4 -->
        <div class="w-full md:w-1/2 lg:w-1/4 px-4 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 h-full hover:shadow-xl transition-shadow text-center">
            <svg class="w-20 h-20 mx-auto mb-6" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
  <g fill="none" fill-rule="evenodd">
    <rect width="27" height="4" x="27" y="9" fill="#979797"/>
    <rect width="17" height="8" x="47" y="7" fill="#D8D8D8"/>
    <polygon fill="#ECECEC" points="12 4 27 6 27 16 12 18"/>
    <path stroke="#D8D8D8" stroke-linecap="square" stroke-width="2" d="M13 8L17 8M13 11L17 11M13 14L17 14"/>
    <path fill="#FF5656" d="M3,39.7321538 C3,39.7321538 8.60735188,37.8258652 16.3350495,40.165557 C21.0340347,41.5882547 26.5170174,45.7321538 32,45.7321538 C37.4831458,45.7321538 42.9662915,41.5881866 47.66537,40.16543 C55.392851,37.8257533 61,39.7321538 61,39.7321538 L61,56.7321538 L3,56.7321538 L3,39.7321538 Z"/>
    <g transform="translate(19 26)">
      <path fill="#B4DFFB" d="M7.07142857,0 C2.81094622,0 0,4.89288809 0,10.9285714 C0,16.9642548 2.81094622,36 7.07142857,36 C9.55684234,36 9.83916051,20.1920244 13.1785714,20.177711 C17.1608395,20.1920244 16.8003005,36 19.2857143,36 C23.5461966,36 26.3571429,16.9642548 26.3571429,10.9285714 C26.3571429,4.89288809 23.5461966,0 19.2857143,0 C17.25,0 15.2142857,1.92857143 13.1785714,1.92857143 C11.1428571,1.92857143 9.10714286,0 7.07142857,0 Z"/>
      <path stroke="#FFF" stroke-linecap="round" stroke-width="2" d="M8.17857143,3 C5.41714768,3 3.17857143,5.23857625 3.17857143,8"/>
    </g>
  </g>
</svg>
            <h4 class="text-xl font-bold text-dentissa-dark">Periodoncia</h4>
            <p class="mt-3 text-gray-500 text-sm">Cuidado experto de tus encías para mantener una base dental fuerte.</p>
          </div>
        </div>
      </div>
    </section>

<!-- ── Galería (Carrusel de Marcadores) ──────────────────── -->
    <section id="galeria" class="relative px-4 py-20 sm:px-8 lg:px-16 xl:px-40 2xl:px-64 bg-white">
      <div class="text-center fade-in-section mb-12">
        <h2 class="text-4xl leading-tight font-bold text-dentissa-dark">Nuestra Clínica</h2>
        <p class="text-lg mt-4 text-gray-500 max-w-2xl mx-auto">Conoce nuestros espacios de tratamiento diseñados para tu máximo confort.</p>
      </div>

      <!-- Contenedor Principal del Carrusel -->
      <div class="relative w-full fade-in-section" style="transition-delay: 0.2s;">
        
        <!-- Pista deslizable (Track) -->
        <div id="carrusel-galeria" class="flex overflow-x-auto snap-x snap-mandatory gap-6 pb-4 hide-scrollbar">
          
          <!-- Placeholder 1 -->
          <div class="snap-center shrink-0 w-[85%] md:w-1/2 lg:w-1/3 bg-gray-200 rounded-2xl h-72 flex flex-col items-center justify-center border-2 border-dashed border-gray-300">
            <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="text-gray-400 font-medium text-sm">Foto</span>
          </div>

          <!-- Placeholder 2 -->
          <div class="snap-center shrink-0 w-[85%] md:w-1/2 lg:w-1/3 bg-gray-200 rounded-2xl h-72 flex flex-col items-center justify-center border-2 border-dashed border-gray-300">
            <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="text-gray-400 font-medium text-sm">Foto</span>
          </div>

          <!-- Placeholder 3 -->
          <div class="snap-center shrink-0 w-[85%] md:w-1/2 lg:w-1/3 bg-gray-200 rounded-2xl h-72 flex flex-col items-center justify-center border-2 border-dashed border-gray-300">
            <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="text-gray-400 font-medium text-sm">Foto</span>
          </div>

          <!-- Placeholder 4 -->
          <div class="snap-center shrink-0 w-[85%] md:w-1/2 lg:w-1/3 bg-gray-200 rounded-2xl h-72 flex flex-col items-center justify-center border-2 border-dashed border-gray-300">
            <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="text-gray-400 font-medium text-sm">Foto</span>
          </div>
          
        </div>

        <!-- Controles / Botones -->
        <div class="flex justify-center gap-6 mt-8">
          <button onclick="document.getElementById('carrusel-galeria').scrollBy({left: -350, behavior: 'smooth'})" class="p-3 rounded-full bg-white text-dentissa-pink border-2 border-dentissa-pink hover:bg-dentissa-pink hover:text-white transition-all shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </button>
          <button onclick="document.getElementById('carrusel-galeria').scrollBy({left: 350, behavior: 'smooth'})" class="p-3 rounded-full bg-white text-dentissa-pink border-2 border-dentissa-pink hover:bg-dentissa-pink hover:text-white transition-all shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </button>
        </div>

      </div>
    </section>

    <!-- ── Ubicación ──────────────── -->
    <section id="ubicacion" class="relative bg-white px-4 sm:px-8 lg:px-16 xl:px-40 2xl:px-64 py-20 border-t border-gray-100">
      <div class="flex flex-col lg:flex-row lg:-mx-8 items-center fade-in-section">
        <div class="w-full lg:w-1/2 lg:px-8">
          <h2 class="text-4xl leading-tight font-bold text-dentissa-dark">Encuéntranos fácilmente</h2>
          <p class="text-lg mt-4 font-semibold text-dentissa-pink">Instalaciones de primer nivel</p>
          <p class="mt-4 text-gray-600 leading-relaxed mb-8">
            Nuestra clínica está diseñada pensando en tu comodidad y seguridad. Contamos con equipos de última generación, estacionamiento privado y acceso adaptado para silla de ruedas.
          </p>
          <div class="flex items-center gap-3 text-gray-700 font-medium">
            <svg class="w-6 h-6 text-dentissa-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Av. Reforma 103c Guillermo Prieto #123, Lázaro Cardenas, Mexico, 60950
          </div>
        </div>
        
        <div class="w-full lg:w-1/2 lg:px-8 mt-12 lg:mt-0">
          <!-- Este es el recuadro gris de la plantilla, perfecto para poner un mapa de Google después -->
          <div class="bg-gray-200 w-full h-80 rounded-2xl flex flex-col items-center justify-center border-2 border-dashed border-gray-300">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d948.6311884914788!2d-102.23333152011041!3d18.000834537363673!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x84315d22a6e6faed%3A0x3f644a0d254b6f8f!2sTeresita%201%2C%20Tercero%20de%20Flor%20de%20Abril%2C%2060950%20La%20Orilla%2C%20Mich.!5e0!3m2!1ses!2smx!4v1791297185233!5m2!1ses!2smx" 
              width="100%" 
              height="100%" 
              style="border:0;" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>

            </iframe>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Footer / Contacto ─────────────────────────────────── -->
    <footer id="contacto" class="bg-[#D4537E] text-white py-2 px-4 sm:px-8 lg:px-16 xl:px-40 2xl:px-64">
      <!-- Un solo contenedor unificado con la animación -->
      <div class="fade-in-section flex flex-col">
        
        <!-- Columnas superiores -->
        <div class="flex flex-col md:flex-row justify-between gap-10 mb-8">
          
          <div class="w-full lg:w-2/6">
            <div class="w-1/2 md:w-auto flex items-center gap-3">
          <img src="{{ asset('images/logo-icon.png') }}" alt="Logo" class="h-16 w-auto object-contain ">
          
        </div>
            <p class="text-white/90 text-sm leading-relaxed">
              Innovación y cuidado excepcional para toda la familia. Agenda hoy y descubre la diferencia.
            </p>
          </div>
          
          <div class="w-full lg:w-2/6">
            <h5 class="uppercase tracking-wider font-bold text-white mb-4">Horarios</h5>
            <ul class="text-white/90 space-y-2 text-sm">
              <li>Lunes a Viernes: 9:00 AM - 8:00 PM</li>
              <li>Sábados: 9:00 AM - 2:00 PM</li>
            </ul>
          </div>
          
          <div class="w-full lg:w-2/6">
            <h5 class="uppercase tracking-wider font-bold text-white mb-4">Contacto Directo</h5>
            <ul class="space-y-4 text-sm">
              <li>
                <a href="tel:+527531625220" class="flex items-center text-white/90 hover:text-white transition-colors gap-3">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                  +52 753 162 5220
                </a>
              </li>
              
            </ul>
          </div>
          
        </div>
        
        <!-- Línea divisoria y derechos integrados en el contenedor -->
        <div class=" text-center text-sm text-white/80">
          <p>&copy; {{ date('Y') }} Dentissa. Todos los derechos reservados.</p>
        </div>
        
      </div>
    </footer>

  </main>

  <!-- Script para animaciones On-Scroll -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.15, rootMargin: "0px 0px -50px 0px" });

        document.querySelectorAll('.fade-in-section').forEach((el) => {
            observer.observe(el);
        });
    });
  </script>
</body>
</html>