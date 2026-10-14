<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12384a">
    <title>@yield('title', 'Rionet Solar') | Rionet Solar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    <div class="app-shell flex">
        <aside class="app-sidebar fixed inset-y-0 left-0 z-40 hidden w-64 flex-col px-4 py-5 lg:sticky lg:flex" data-sidebar data-open="false">
            <a href="{{ route('dashboard') }}" class="mb-9 flex justify-center rounded-md bg-white px-3 py-2" aria-label="Rionet Solar, inicio">
                <img src="{{ asset('rionet-solar.svg') }}" alt="Rionet Solar" class="h-auto w-full max-w-[190px]">
            </a>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-[#8da9a6]">Catálogos</p>
            <nav class="flex flex-col gap-1 text-sm font-medium">
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif class="relative flex items-center gap-3 rounded-md px-3 py-2.5">
                    <span class="w-5 text-center text-[#f3bd35]">01</span> Resumen
                </a>
                <a href="{{ route('admin.appliances.index') }}" @if(request()->routeIs('admin.appliances.*')) aria-current="page" @endif class="relative flex items-center gap-3 rounded-md px-3 py-2.5">
                    <span class="w-5 text-center text-[#8ec7b1]">02</span> Electrodomésticos
                </a>
                <a href="{{ route('admin.electrical-equipment.index') }}" @if(request()->routeIs('admin.electrical-equipment.*')) aria-current="page" @endif class="relative flex items-center gap-3 rounded-md px-3 py-2.5">
                    <span class="w-5 text-center text-[#8ec7b1]">03</span> Equipos eléctricos
                </a>
                <a href="{{ route('admin.installation-materials.index') }}" @if(request()->routeIs('admin.installation-materials.*')) aria-current="page" @endif class="relative flex items-center gap-3 rounded-md px-3 py-2.5">
                    <span class="w-5 text-center text-[#8ec7b1]">04</span> Materiales
                </a>
            </nav>
            <div class="mt-auto border-t border-white/10 px-3 pt-4 text-xs text-[#9bb2ae]">
                <span class="mb-2 block h-1 w-9 rounded-full bg-[#f3bd35]"></span>
                Diseño y catálogo solar
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="flex h-[68px] items-center justify-between border-b border-[#dce6e1] bg-white/90 px-4 backdrop-blur sm:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" class="button-secondary inline-flex h-10 w-10 items-center justify-center rounded-md lg:hidden" data-mobile-menu aria-label="Abrir menú" aria-expanded="false">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                    </button>
                    <span class="text-sm font-semibold text-[#45616a]">Gestión de datos maestros</span>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.08em] text-[#55717a]">
                    <span class="h-2 w-2 rounded-full bg-[#69a24b]"></span>
                    Rionet Solar
                </div>
            </header>

            <main class="page-enter mx-auto w-full max-w-[1440px] px-4 py-7 sm:px-8 sm:py-10">
                @if (session('success'))
                    <div role="status" class="mb-6 flex items-start justify-between gap-4 border-l-4 border-[#5f963f] bg-[#edf5e9] px-4 py-3 text-sm text-[#31592e]">
                        <span>{{ session('success') }}</span>
                        <span aria-hidden="true">✓</span>
                    </div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mb-6 border-l-4 border-[#c4473d] bg-[#fff0ed] px-4 py-3 text-sm text-[#8e3029]">
                        <p class="font-semibold">Revisa los campos indicados para continuar.</p>
                    </div>
                @endif
                @yield('content')
            </main>
            <footer class="border-t border-[#dce6e1] px-4 py-5 text-center text-xs text-[#718187] sm:px-8">
                <span class="font-semibold text-[#155a91]">Rionet Solar</span> <span class="mx-1 text-[#f0b528]">•</span> Catálogo fotovoltaico
            </footer>
        </div>
    </div>
</body>
</html>