#!/usr/bin/env bash
set -euo pipefail
export LC_ALL=C

# Configuración y parámetros (personalizables por variables de entorno)
DURATION="${BENCH_DURATION:-20s}"
WARMUP="${BENCH_WARMUP:-5s}"
REPETITIONS="${BENCH_REPETITIONS:-3}"
CONCURRENCIES="${BENCH_CONCURRENCIES:-10 50 100}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
OUTPUT_DIR="benchmarks/resultados/${TIMESTAMP}"
RESUMEN_CSV="${OUTPUT_DIR}/resumen.csv"
DOCS_RESUMEN="docs/benchmark-resumen.csv"
DOCS_IMG_DIR="docs/img"
HAPROXY_STATS_PNG="${DOCS_IMG_DIR}/haproxy-stats.png"

mkdir -p "${OUTPUT_DIR}" "${DOCS_IMG_DIR}"

echo "================================================================================"
echo " AgendTurnos — Benchmark de Arquitecturas (SCRUM-33 / SCRUM-35)"
echo " Fecha: $(date)"
echo " Duración por prueba: ${DURATION} | Calentamiento: ${WARMUP} | Repeticiones: ${REPETITIONS}"
echo " Concurrencias: ${CONCURRENCIES}"
echo " Directorio de resultados: ${OUTPUT_DIR}"
echo "================================================================================"

# 1. Trap para restaurar el entorno al finalizar o ante interrupción
cleanup() {
    echo ""
    echo "==> Restaurando entorno (limpiando caché de optimización)..."
    # A y B comparten el código (bind mount): una sola limpieza alcanza. Como www-data para no dejar archivos de root.
    docker compose exec -T -u www-data app php artisan optimize:clear >/dev/null 2>&1 || true
    echo "==> Entorno restaurado."
}
trap cleanup EXIT INT TERM

# 2. Pre-flight check: verificar que los 3 servicios respondan 200 en /health
echo ""
echo "==> Verificando disponibilidad de los tres entornos..."
TARGETS_CHECK=(
    "http://localhost:8080/health Arquitectura A (Nginx + PHP-FPM)"
    "http://localhost:8081/health Arquitectura B (Apache + mod_php)"
    "http://localhost:8000/health Balanceador HAProxy"
)

for ITEM in "${TARGETS_CHECK[@]}"; do
    URL="${ITEM%% *}"
    LABEL="${ITEM#* }"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL" || echo "000")
    if [ "$HTTP_CODE" != "200" ]; then
        echo "ERROR: El endpoint $LABEL en $URL devolvió código HTTP $HTTP_CODE (esperado 200)." >&2
        echo "Asegúrese de levantar el stack completo con 'docker compose up -d' antes de medir." >&2
        exit 1
    fi
    echo "  [OK] $LABEL responde 200 OK en $URL"
done

# 3. Poner en modo producción temporal SIN tocar .env
echo ""
echo "==> Optimizando Laravel temporalmente (rutas, vistas, config) con APP_DEBUG=false..."
docker compose exec -T -u www-data -e APP_DEBUG=false -e RATE_LIMIT_API=0 app php artisan optimize

# 4. Inicializar archivo CSV de resumen
echo "Arquitectura,Endpoint,Concurrencia,RPS,p50_ms,p95_ms,p99_ms,Errores_pct" > "${RESUMEN_CSV}"

# Definición de arquitecturas y endpoints
# Formato: "Nombre|BaseURL"
ARQUITECTURAS=(
    "A (Nginx+FPM)|http://localhost:8080"
    "B (Apache)|http://localhost:8081"
    "HAProxy|http://localhost:8000"
)

ENDPOINTS=(
    "/health"
    "/"
    "/api/v1/servicios"
)

SCREENSHOT_TAKEN=0

echo ""
echo "================================================================================"
echo " Iniciando mediciones..."
echo "================================================================================"

for ARQ_ENTRY in "${ARQUITECTURAS[@]}"; do
    ARQ_NAME="${ARQ_ENTRY%%|*}"
    BASE_URL="${ARQ_ENTRY##*|}"

    echo ""
    echo ">>> Arquitectura: ${ARQ_NAME} (${BASE_URL})"

    for EP in "${ENDPOINTS[@]}"; do
        FULL_URL="${BASE_URL}${EP}"
        SLUG=$(echo "${EP}" | tr '/' '_' | sed 's/^_//')
        [ -z "${SLUG}" ] && SLUG="portada"

        echo "  --> Endpoint: ${EP}"

        # Calentamiento de 5s descartado antes de cada endpoint
        echo -n "      [Calentamiento 5s] "
        docker run --rm --network host williamyeh/hey -c 10 -z "${WARMUP}" "${FULL_URL}" >/dev/null 2>&1
        echo "listo."

        for C in ${CONCURRENCIES}; do
            RAW_FILES=()

            echo -n "      Concurrencia ${C} (${REPETITIONS} reps de ${DURATION}): "

            for REP in $(seq 1 "${REPETITIONS}"); do
                RAW_FILE="${OUTPUT_DIR}/${ARQ_NAME// /_}_${SLUG}_c${C}_rep${REP}.txt"
                RAW_FILES+=("${RAW_FILE}")

                # Si es HAProxy y concurrencia 50, disparamos captura de pantalla del dashboard en segundo plano
                if [ "${ARQ_NAME}" = "HAProxy" ] && [ "${C}" = "50" ] && [ "${REP}" = "1" ] && [ "${SCREENSHOT_TAKEN}" = "0" ]; then
                    (
                        sleep 4
                        if command -v google-chrome >/dev/null 2>&1; then
                            google-chrome --headless=new --screenshot="${HAPROXY_STATS_PNG}" --window-size=1280,900 http://localhost:8404 >/dev/null 2>&1 || true
                        fi
                    ) &
                    SCREENSHOT_TAKEN=1
                fi

                docker run --rm --network host williamyeh/hey -c "${C}" -z "${DURATION}" "${FULL_URL}" > "${RAW_FILE}" 2>&1
                echo -n "#${REP} "
            done
            echo "-> calculando mediana..."

            # Calcular la mediana de las 3 repeticiones
            CSV_LINE=$(python3 scripts/parse_median.py "${ARQ_NAME}" "${EP}" "${C}" "${RAW_FILES[@]}")
            echo "${CSV_LINE}" >> "${RESUMEN_CSV}"

            # Mostrar resultado por consola
            IFS=',' read -r _arq _ep _c _rps _p50 _p95 _p99 _err <<< "${CSV_LINE}"
            printf "         [Mediana] RPS: %8s | p50: %8s ms | p95: %8s ms | p99: %8s ms | Errores: %s%%\n" \
                "${_rps}" "${_p50}" "${_p95}" "${_p99}" "${_err}"
        done
    done
done

# Si la captura de HAProxy no se tomó por alguna razón, tomarla ahora
if [ ! -f "${HAPROXY_STATS_PNG}" ]; then
    if command -v google-chrome >/dev/null 2>&1; then
        google-chrome --headless=new --screenshot="${HAPROXY_STATS_PNG}" --window-size=1280,900 http://localhost:8404 >/dev/null 2>&1 || true
    fi
fi

# Copiar el resumen a docs/
cp "${RESUMEN_CSV}" "${DOCS_RESUMEN}"

echo ""
echo "================================================================================"
echo " Resumen consolidado del benchmark:"
echo "================================================================================"
column -t -s',' "${RESUMEN_CSV}"
echo "================================================================================"
echo "Resultados crudos guardados en: ${OUTPUT_DIR}/"
echo "Resumen CSV copiado a: ${DOCS_RESUMEN}"
[ -f "${HAPROXY_STATS_PNG}" ] && echo "Captura de HAProxy guardada en: ${HAPROXY_STATS_PNG}"
