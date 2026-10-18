<figure class="rounded-md border border-[#dce6e1] bg-[#f3f7f3] p-3 sm:p-5" aria-label="Diagrama visual del sistema fotovoltaico">
    <div class="mx-auto max-w-6xl space-y-4">
        <section class="border border-[#c8dabb] bg-[#f8fcf5] p-3 sm:p-4">
            <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                <div><p class="text-[10px] font-bold uppercase tracking-wide text-[#5f963f]">Campo fotovoltaico</p><p class="text-lg font-bold text-[#155a91]">{{ $design->panel_count }} paneles · {{ $design->solarPanel->name }}</p></div>
                <span class="text-xs font-semibold text-[#526a70]">{{ count($stringLayout) }} strings · {{ $design->panels_per_string }} paneles en serie por string</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($stringLayout as $stringNumber => $rows)
                    <div class="min-w-0 border border-[#dce8d4] bg-white p-2.5 sm:p-3">
                        <div class="mb-2 flex items-center justify-between gap-2"><p class="text-xs font-bold text-[#173b49]">String {{ $stringNumber }}</p><span class="text-[10px] text-[#526a70]">{{ array_sum($rows) }} paneles en serie</span></div>
                        <div class="space-y-2.5">
                            @foreach($rows as $rowIndex => $rowCount)
                                <div class="flex flex-wrap items-center gap-1.5 border-l-2 border-[#dce8d4] pl-2">
                                    <span class="w-11 shrink-0 text-[9px] font-semibold text-[#718187]">Fila {{ $rowIndex + 1 }}</span>
                                    <div class="flex flex-wrap items-center gap-0.5">
                                        @for($panelIndex = 0; $panelIndex < $rowCount; $panelIndex++)
                                            <img src="{{ asset('images/diagrama/panel.png') }}" alt="Panel {{ $panelIndex + 1 }} en la fila {{ $rowIndex + 1 }} del string {{ $stringNumber }}" class="h-10 w-10 object-contain sm:h-12 sm:w-12">
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid items-center gap-3 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1.2fr)_auto_minmax(0,1fr)]">
            <div class="space-y-2 rounded-md border border-[#d9e1e5] bg-white p-3 text-center">
                <div><img src="{{ asset('images/diagrama/energia-publica.png') }}" alt="Red eléctrica pública" class="mx-auto h-20 w-24 object-contain"><p class="text-[10px] font-bold text-[#526a70]">RED ELÉCTRICA · AC INPUT</p></div>
                <span class="block text-lg font-bold text-[#d96161]" aria-hidden="true">↓</span>
                <div class="rounded-md border border-[#e5bbbb] bg-[#fff8f7] p-2">
                    <img src="{{ asset('images/diagrama/caja-protecciones.png') }}" alt="Breaker AC de entrada" class="mx-auto h-14 w-16 object-contain">
                    <p class="text-xs font-bold text-[#173b49]">Breaker AC input</p>
                    <p class="text-[10px] text-[#526a70]">{{ $acInputProtection['item']?->name ?? 'Sin coincidencia' }}</p>
                    <p class="text-xs font-bold text-[#155a91]">{{ $acInputProtection['quantity'] }} · uno por inversor</p>
                </div>
            </div>
            <span class="text-center text-xl font-bold text-[#d96161] max-lg:rotate-90" aria-hidden="true">→</span>
            <div class="space-y-2 text-center">
                <div class="rounded-md border border-[#9fc4c5] bg-white p-2">
                    <img src="{{ asset('images/diagrama/caja-protecciones.png') }}" alt="Protecciones DC" class="mx-auto h-14 w-16 object-contain">
                    <p class="text-xs font-bold text-[#173b49]">Protecciones DC · {{ $dcProtection['quantity'] }} por string</p>
                    <p class="text-[10px] text-[#526a70]">{{ $dcProtection['item']?->name ?? 'Sin breaker en catálogo' }}</p>
                </div>
                <span class="block text-lg font-bold text-[#5f963f]" aria-hidden="true">↓ DC</span>
                <div class="rounded-md border border-[#dfbb50] bg-[#fffaf0] p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#927021]">Conversión DC / AC</p>
                    <div class="mt-2 flex flex-wrap justify-center gap-1.5">
                        @for($inverterIndex = 0; $inverterIndex < $design->inverter_count; $inverterIndex++)
                            <img src="{{ asset('images/diagrama/inversor.png') }}" alt="Inversor {{ $inverterIndex + 1 }} de {{ $design->inverter_count }}" class="h-16 w-20 object-contain sm:h-20 sm:w-24">
                        @endfor
                    </div>
                    <p class="mt-1 text-sm font-bold text-[#155a91]">{{ $design->inverter_count }} {{ $design->inverter_count === 1 ? 'inversor' : 'inversores' }}</p>
                    <p class="text-[10px] text-[#526a70]">{{ $design->inverter->name }}</p>
                </div>
                <span class="block text-lg font-bold text-[#d5a52d]" aria-hidden="true">↕</span>
                <div class="rounded-md border border-[#e5d49b] bg-[#fffaf0] p-2" aria-label="Banco de baterías">
                    <p class="mb-1 text-xs font-bold text-[#927021]">{{ $design->battery_count }} {{ $design->battery_count === 1 ? 'batería' : 'baterías' }} · {{ $design->battery->name }}</p>
                    <div class="flex flex-wrap justify-center gap-1">
                        @for($batteryIndex = 0; $batteryIndex < $design->battery_count; $batteryIndex++)
                            <img src="{{ asset('images/diagrama/bateria.png') }}" alt="Batería {{ $batteryIndex + 1 }} de {{ $design->battery_count }}" class="h-14 w-14 object-contain">
                        @endfor
                    </div>
                </div>
            </div>
            <span class="text-center text-xl font-bold text-[#d96161] max-lg:rotate-90" aria-hidden="true">→</span>
            <div class="space-y-2 rounded-md border border-[#d9c9ed] bg-white p-3 text-center">
                <div class="rounded-md border border-[#e5bbbb] bg-[#fff8f7] p-2">
                    <img src="{{ asset('images/diagrama/caja-protecciones.png') }}" alt="Breaker AC de salida" class="mx-auto h-14 w-16 object-contain">
                    <p class="text-xs font-bold text-[#173b49]">Breaker AC output</p>
                    <p class="text-[10px] text-[#526a70]">{{ $acOutputProtection['item']?->name ?? 'Sin coincidencia' }}</p>
                    <p class="text-xs font-bold text-[#155a91]">{{ $acOutputProtection['quantity'] }} · uno por inversor</p>
                </div>
                <span class="block text-lg font-bold text-[#d96161]" aria-hidden="true">↓</span>
                <div><img src="{{ asset('images/diagrama/cargas.png') }}" alt="Cargas de la instalación" class="mx-auto h-20 w-28 object-contain"><p class="text-xs font-bold text-[#173b49]">Cargas AC</p></div>
            </div>
        </div>
    </div>
    <figcaption class="mt-4 border-t border-[#dce6e1] pt-3 text-xs leading-5 text-[#718187]">Esquema eléctrico con imágenes del catálogo visual. Cada panel, inversor y batería se representa con su imagen; los strings reflejan las filas guardadas del proyecto.</figcaption>
</figure>
