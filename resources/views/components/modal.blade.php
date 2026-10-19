@props([
    'name',
    'titulo',
])

<div
    x-data="{
        abierto: false,
        disparador: null,
        abrir(origen) {
            this.disparador = origen || document.activeElement;
            this.abierto = true;
            this.$nextTick(() => {
                const focusable = this.$refs.contenido.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex=\'-1\'])');
                if (focusable) {
                    focusable.focus();
                } else {
                    this.$refs.contenido.focus();
                }
            });
        },
        cerrar() {
            this.abierto = false;
            if (this.disparador && typeof this.disparador.focus === 'function') {
                this.$nextTick(() => this.disparador.focus());
            }
        }
    }"
    x-on:abrir-modal.window="if ($event.detail === '{{ $name }}' || (typeof $event.detail === 'object' && $event.detail.name === '{{ $name }}')) abrir($event.target)"
    x-on:cerrar-modal.window="if ($event.detail === '{{ $name }}' || ! $event.detail) cerrar()"
    @keydown.escape.window="if (abierto) cerrar()"
    x-show="abierto"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-titulo-{{ $name }}"
    style="display: none;"
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div
        x-show="abierto"
        x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-ink-900/40 backdrop-blur-xs transition-opacity"
        @click="cerrar()"
        aria-hidden="true"
    ></div>

    <!-- Dialog Panel Container -->
    <div class="fixed inset-0 z-10 flex min-h-full items-end sm:items-center justify-center p-4 text-center">
        <div
            x-show="abierto"
            x-ref="contenido"
            tabindex="-1"
            x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.outside="cerrar()"
            class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all border border-line"
        >
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <h3 id="modal-titulo-{{ $name }}" class="text-lg font-bold font-display text-ink-900">
                    {{ $titulo }}
                </h3>
                <button
                    type="button"
                    @click="cerrar()"
                    class="rounded-lg p-1.5 text-ink-500 hover:text-ink-900 hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer min-h-11 min-w-11 inline-flex items-center justify-center"
                    aria-label="Cerrar modal"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 text-sm text-ink-500">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
