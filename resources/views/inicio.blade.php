<x-layouts.invitado
    titulo="Inicio"
    descripcion="Clínica odontológica en San Pablo, Asunción. Odontopediatría y rehabilitación oral. Reservá tu turno online en menos de un minuto."
>
    @php
        $reservarUrl = match (true) {
            auth()->check() && auth()->user()->rol === \App\Enums\RolUsuario::Paciente => route('paciente.reservar'),
            auth()->check() => route('panel.turnos.crear'),
            default => route('registro'),
        };
    @endphp

    <div class="space-y-12 sm:space-y-16">
        <!-- Hero -->
        <section class="grid grid-cols-1 md:grid-cols-[1.1fr_0.9fr] gap-8 md:gap-12 items-center py-4 sm:py-8">
            <div>
                <h1 class="font-display font-bold text-3xl sm:text-4xl lg:text-5xl text-ink-900 leading-tight">
                    Tu sonrisa, en buenas manos
                </h1>
                <p class="mt-4 text-base sm:text-lg text-ink-500 leading-relaxed">
                    Odontopediatría y rehabilitación oral en San Pablo, Asunción. Reservá tu turno online en menos de un minuto.
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <x-button :pill="true" href="{{ $reservarUrl }}">
                        Reservar turno
                    </x-button>
                    @if (Route::has('servicios.catalogo'))
                        <x-button variant="secondary" :pill="true" href="{{ route('servicios.catalogo') }}">
                            Ver servicios
                        </x-button>
                    @endif
                </div>
                @guest
                    <p class="mt-3 text-sm text-ink-500">
                        ¿Ya tenés cuenta? <a href="{{ route('login') }}" class="underline font-medium text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded">Iniciá sesión</a>
                    </p>
                @endguest
            </div>

            <div class="bg-brand-300 dark:bg-card dark:border dark:border-line rounded-3xl h-52 sm:h-60 flex items-center justify-center p-8 overflow-hidden shadow-xs">
                <img src="{{ asset('images/glow-logo-blanco.svg') }}" alt="Glow Dental Protect" class="max-h-24 sm:max-h-28 w-auto">
            </div>
        </section>

        <!-- Cómo funciona -->
        <section aria-labelledby="como-funciona-heading">
            <h2 id="como-funciona-heading" class="font-display font-bold text-xl sm:text-2xl text-ink-900 mb-6">
                Cómo funciona
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                <x-card :paciente="true" class="p-5 flex flex-col justify-start">
                    <div class="mb-3">
                        <x-phosphor-tooth class="size-6 text-brand-text" aria-hidden="true" />
                    </div>
                    <h3 class="font-bold text-ink-900 text-base">
                        1. Elegí el servicio
                    </h3>
                    <p class="mt-1 text-sm text-ink-500">
                        Y la profesional que te atiende.
                    </p>
                </x-card>

                <x-card :paciente="true" class="p-5 flex flex-col justify-start">
                    <div class="mb-3">
                        <x-phosphor-calendar-check class="size-6 text-brand-text" aria-hidden="true" />
                    </div>
                    <h3 class="font-bold text-ink-900 text-base">
                        2. Elegí día y horario
                    </h3>
                    <p class="mt-1 text-sm text-ink-500">
                        Solo ves horarios libres.
                    </p>
                </x-card>

                <x-card :paciente="true" class="p-5 flex flex-col justify-start">
                    <div class="mb-3">
                        <x-phosphor-envelope-simple class="size-6 text-brand-text" aria-hidden="true" />
                    </div>
                    <h3 class="font-bold text-ink-900 text-base">
                        3. Te confirmamos
                    </h3>
                    <p class="mt-1 text-sm text-ink-500">
                        Por correo, con recordatorio.
                    </p>
                </x-card>
            </div>
        </section>

        <!-- Nuestras profesionales -->
        <section aria-labelledby="nuestras-profesionales-heading">
            <h2 id="nuestras-profesionales-heading" class="font-display font-bold text-xl sm:text-2xl text-ink-900 mb-6">
                Nuestras profesionales
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                @forelse ($profesionales as $profesional)
                    @php
                        $iniciales = mb_strtoupper(mb_substr($profesional->nombre, 0, 1) . mb_substr($profesional->apellido, 0, 1));
                        $especialidades = $profesional->servicios
                            ->pluck('especialidad.nombre')
                            ->filter()
                            ->unique()
                            ->values();
                        $detalles = [];
                        if ($especialidades->isNotEmpty()) {
                            $detalles[] = $especialidades->implode(' · ');
                        }
                        if ($profesional->matricula) {
                            $detalles[] = 'Reg. Prof. ' . $profesional->matricula;
                        }
                    @endphp
                    <x-card :paciente="true" class="p-6">
                        <div class="size-10 rounded-full bg-brand-300 text-brand-900 font-bold flex items-center justify-center text-sm mb-3">
                            {{ $iniciales }}
                        </div>
                        <h3 class="font-bold text-ink-900 text-base sm:text-lg">
                            {{ $profesional->nombre }} {{ $profesional->apellido }}
                        </h3>
                        @if (! empty($detalles))
                            <p class="mt-1 text-sm text-ink-500">
                                {{ implode(' · ', $detalles) }}
                            </p>
                        @endif
                    </x-card>
                @empty
                    <div class="col-span-full">
                        <x-empty-state
                            titulo="No hay profesionales disponibles"
                            texto="Pronto podrás ver al equipo médico."
                        />
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Pie de contacto -->
        <section class="pt-6 border-t border-line flex flex-col sm:flex-row items-center justify-between gap-4 text-xs sm:text-sm text-ink-500">
            <div class="flex items-center gap-2">
                <x-phosphor-map-pin class="size-4 shrink-0 text-brand-text" aria-hidden="true" />
                <span>Félix López 407 c/ Fermín López, San Pablo, Asunción</span>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <a href="tel:021522797" class="inline-flex items-center gap-1.5 hover:text-brand-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded">
                    <x-phosphor-phone class="size-4 shrink-0 text-brand-text" aria-hidden="true" />
                    <span>(021) 522-797</span>
                </a>
                <span aria-hidden="true">·</span>
                <a href="mailto:glowdentalprotect@gmail.com" class="inline-flex items-center gap-1.5 hover:text-brand-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded">
                    <x-phosphor-envelope-simple class="size-4 shrink-0 text-brand-text" aria-hidden="true" />
                    <span>glowdentalprotect@gmail.com</span>
                </a>
            </div>
        </section>
    </div>
</x-layouts.invitado>
