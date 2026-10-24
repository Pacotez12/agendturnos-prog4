import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.toggleTema = function (event) {
    const aplicarCambio = () => {
        const esOscuro = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('tema', esOscuro ? 'oscuro' : 'claro');
        } catch (e) {}
    };

    const prefiereReduccion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!document.startViewTransition || prefiereReduccion) {
        aplicarCambio();
        return;
    }

    const target = event?.currentTarget || event?.target;
    const rect = target?.getBoundingClientRect?.();
    const x = event?.clientX || (rect ? rect.left + rect.width / 2 : window.innerWidth / 2);
    const y = event?.clientY || (rect ? rect.top + rect.height / 2 : window.innerHeight / 2);

    const endRadius = Math.hypot(
        Math.max(x, window.innerWidth - x),
        Math.max(y, window.innerHeight - y)
    );

    const transition = document.startViewTransition(() => {
        aplicarCambio();
    });

    transition.ready.then(() => {
        try {
            document.documentElement.animate(
                {
                    clipPath: [
                        `circle(0px at ${x}px ${y}px)`,
                        `circle(${endRadius}px at ${x}px ${y}px)`,
                    ],
                },
                {
                    duration: 450,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                    pseudoElement: '::view-transition-new(root)',
                }
            );
        } catch (e) {}
    });
};

Alpine.start();
