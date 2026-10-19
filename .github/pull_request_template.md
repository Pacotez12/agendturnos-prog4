## Qué hace

<!-- Una o dos líneas. Ticket: SCRUM-<n> -->

## Cómo probarlo

<!-- Pasos o URL para verlo funcionando -->

## Checklist

- [ ] Tests en verde (`docker compose exec -T -u 1000 app php artisan test`)
- [ ] Pint sin cambios (`docker compose exec -T -u 1000 app ./vendor/bin/pint`)
- [ ] Rama `feat/SCRUM-<n>-…` y título `feat(SCRUM-<n>): …`
- [ ] `docs/arquitectura.md` actualizado si cambia algo del stack, el modelo o una decisión

**Si toca pantallas** (ver `docs/guia-frontend.md`):

- [ ] Usa un layout y los componentes; sin colores sueltos ni estilos copiados
- [ ] Probado a 390 px y a 1280 px, sin scroll horizontal
- [ ] Usable con teclado (foco visible) y campos con etiqueta
- [ ] Capturas adjuntas
