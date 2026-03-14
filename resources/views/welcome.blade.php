<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>K&P Fleet Management | Control Inteligente de Flotas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 antialiased">

    <nav class="sticky top-0 z-50 glass border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="bg-blue-600 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-800 uppercase">K&P <span class="text-blue-600">Fleet</span></span>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#" class="hover:text-blue-600 transition">Funcionalidades</a>
                <a href="#" class="hover:text-blue-600 transition">Beneficios</a>
                @auth
                    <a href="/menu" class="bg-slate-900 text-white px-5 py-2.5 rounded-full hover:bg-blue-700 transition shadow-lg shadow-blue-200">Panel de Control</a>
                @else
                    <a href="{{ route('login') }}" class="text-slate-900 hover:text-blue-600 transition">Iniciar Sesión</a>
                    <a href="#" class="bg-blue-600 text-white px-5 py-2.5 rounded-full hover:bg-blue-700 transition shadow-lg shadow-blue-200">Solicitar Demo</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="relative overflow-hidden pt-16 pb-28">
        <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="inline-block px-4 py-1.5 mb-6 text-xs font-semibold tracking-widest text-blue-700 uppercase bg-blue-100 rounded-full">
                    SaaS de Gestión Logística v2.0
                </span>
                <h1 class="text-5xl lg:text-6xl font-extrabold text-slate-900 leading-[1.1] mb-6">
                    Toma el control total de tu <span class="text-blue-600 underline decoration-blue-200">flota operativa.</span>
                </h1>
                <p class="text-lg text-slate-600 mb-10 leading-relaxed max-w-lg">
                    Optimiza el rendimiento de combustible, gestiona fondeos en tiempo real y audita cada kilómetro recorrido desde una sola plataforma profesional.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <button class="px-8 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all transform hover:-translate-y-1 shadow-xl shadow-blue-200 text-center">
                        Comenzar ahora
                    </button>
                    <button class="px-8 py-4 bg-white text-slate-700 font-bold rounded-xl border border-slate-200 hover:bg-slate-50 transition-all text-center">
                        Ver video demo
                    </button>
                </div>
            </div>

            <div class="relative">
                <div class="relative z-10 rounded-2xl overflow-hidden shadow-2xl border-4 border-white">
                    <img src="{{ asset('images/flota-kp.png') }}"  alt="Flota K&P Group" class="w-full h-full object-cover">
                </div>
                <div class="absolute -bottom-6 -right-6 w-64 h-64 bg-blue-100 rounded-full -z-10 blur-3xl opacity-60"></div>
                <div class="absolute -top-6 -left-6 w-64 h-64 bg-indigo-100 rounded-full -z-10 blur-3xl opacity-60"></div>
            </div>
        </div>
    </section>

    <div class="bg-white py-10 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6">
            <p class="text-center text-sm font-semibold text-slate-400 uppercase tracking-[0.2em] mb-8">Confían en nosotros</p>
            <div class="flex flex-wrap justify-center gap-12 opacity-50 grayscale">
                
                <span class="text-2xl font-bold italic text-slate-800">K&P GROUP</span>
                
            </div>
        </div>
    </div>

    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-3xl font-bold mb-4 text-slate-900">Diseñado para la eficiencia operativa</h2>
                <p class="text-slate-600">Eliminamos el papeleo y las hojas de cálculo confusas para darte datos precisos que reducen costos de inmediato.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-blue-300 transition-all group">
                    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 text-white shadow-lg shadow-blue-200 group-hover:scale-110 transition-transform">
                        ⛽
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-slate-900 text-balance">Control Maestro de Combustible</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Validación biométrica o por tarjeta activa. Olvídate de los registros falsos y obtén rendimientos reales por unidad y chofer.
                    </p>
                </div>

                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-blue-300 transition-all group">
                    <div class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center mb-6 text-white shadow-lg shadow-emerald-200 group-hover:scale-110 transition-transform">
                        📊
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-slate-900">Fondeo Operativo Inteligente</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Visualiza saldos en tiempo real y automatiza la reposición semanal. El sistema te avisa cuando los activos necesitan liquidez operativa.
                    </p>
                </div>

                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-blue-300 transition-all group">
                    <div class="w-12 h-12 bg-indigo-500 rounded-2xl flex items-center justify-center mb-6 text-white shadow-lg shadow-indigo-200 group-hover:scale-110 transition-transform">
                        🔐
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-slate-900 text-balance">Auditoría y Roles Multi-nivel</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Desde el chofer hasta el CEO. Cada usuario tiene una vista optimizada: auditoría para finanzas y facilidad de uso para el operador.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-slate-900 text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
            <div class="relative">
                 <img src="{{ asset('images/flota-kp.png') }}" alt="Operación en sitio" class="rounded-3xl shadow-2xl grayscale hover:grayscale-0 transition duration-700">
                 <div class="absolute -bottom-4 -left-4 bg-blue-600 p-6 rounded-2xl hidden md:block">
                    <p class="text-3xl font-bold">+25%</p>
                    <p class="text-xs uppercase tracking-wider opacity-80">Ahorro en consumo anual</p>
                 </div>
            </div>
            <div>
                <h2 class="text-4xl font-bold mb-6">De una necesidad interna a una solución global.</h2>
                <p class="text-slate-400 mb-8 text-lg">
                    Entendemos los problemas de la flota porque nosotros mismos los vivimos. Hemos pulido la herramienta en el campo de batalla logístico para que tú solo tengas que encenderla y empezar a ahorrar.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 italic">
                        <span class="text-blue-500">✓</span> Soporte técnico especializado 24/7.
                    </li>
                    <li class="flex items-center gap-3 italic">
                        <span class="text-blue-500">✓</span> Implementación en menos de 48 horas.
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="mb-8">
                <span class="text-2xl font-bold tracking-tight text-slate-800 uppercase">K&P <span class="text-blue-600">Fleet</span></span>
            </div>
            <p class="text-slate-500 mb-8 max-w-md mx-auto">KPLogistics dedicada a la transformación digital del sector transporte.</p>
            <div class="border-t border-slate-100 pt-8 text-sm text-slate-400">
                © {{ date('Y') }} K&P Group · Todos los derechos reservados.
            </div>
        </div>
    </footer>

</body>
</html>