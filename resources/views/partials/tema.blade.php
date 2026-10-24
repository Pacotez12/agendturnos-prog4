{{-- Aplica el tema antes del primer pintado para evitar el destello claro --}}
<script>
    (function () {
        try {
            var tema = localStorage.getItem('tema');
            var prefiereOscuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (tema === 'oscuro' || (!tema && prefiereOscuro)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                try {
                    if (!localStorage.getItem('tema')) {
                        document.documentElement.classList.toggle('dark', e.matches);
                    }
                } catch (err) {}
            });
        } catch (e) {}
    })();
</script>
