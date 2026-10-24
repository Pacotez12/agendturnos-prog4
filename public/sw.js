self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin conexión · Glow Dental Protect</title>
    <style>
        body {
            margin: 0;
            padding: 2rem 1rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #FBF7F2;
            color: #171311;
            box-sizing: border-box;
            text-align: center;
        }
        .card {
            background: #ffffff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            max-width: 24rem;
            width: 100%;
        }
        h1 {
            font-size: 1.25rem;
            margin: 0 0 0.5rem;
            color: #995432;
        }
        p {
            margin: 0 0 1.5rem;
            color: #4A3E39;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        button {
            background-color: #995432;
            color: #ffffff;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
        }
        button:hover {
            background-color: #7A3E22;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Sin conexión</h1>
        <p>Sin conexión. Revisá tu internet y volvé a intentar.</p>
        <button type="button" onclick="window.location.reload()">Reintentar</button>
    </div>
</body>
</html>`;

self.addEventListener('fetch', (event) => {
    // Solo navegaciones: el resto (assets, formularios, datos) va directo a la red sin pasar por el SW
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => new Response(OFFLINE_HTML, {
            headers: {
                'Content-Type': 'text/html; charset=utf-8',
            },
        }))
    );
});
