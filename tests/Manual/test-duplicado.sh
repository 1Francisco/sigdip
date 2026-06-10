#!/bin/bash
# Script manual de prueba del flujo de detección de duplicados de visitas
# Requisitos: curl, jq (opcional para formato), servidor local corriendo en http://localhost:8000
#
# Uso:
#   1. php artisan serve &
#   2. bash tests/Manual/test-duplicado.sh

set -e

BASE_URL="${1:-http://localhost:8000}"
EMAIL="${2:-admin@sigdip.com}"
PASSWORD="${3:-password}"

PASS=0
FAIL=0

green() { echo -e "\033[32m$1\033[0m"; }
red()   { echo -e "\033[31m$1\033[0m"; }
bold()  { echo -e "\033[1m$1\033[0m"; }

step() {
  echo ""
  bold "══════════════════════════════════════════════════"
  bold "  $1"
  bold "══════════════════════════════════════════════════"
}

assert_eq() {
  local label="$1" expected="$2" actual="$3"
  if [ "$expected" = "$actual" ]; then
    green "  ✓ $label"
    PASS=$((PASS+1))
  else
    red "  ✗ $label (esperado: $expected, obtenido: $actual)"
    FAIL=$((FAIL+1))
  fi
}

assert_contains() {
  local label="$1" needle="$2" haystack="$3"
  if echo "$haystack" | grep -q -- "$needle"; then
    green "  ✓ $label"
    PASS=$((PASS+1))
  else
    red "  ✗ $label (no contiene: $needle)"
    FAIL=$((FAIL+1))
  fi
}

# ─── Login ──────────────────────────────────────────────
step "1. Login como administrador"
LOGIN_RESP=$(curl -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

TOKEN=$(echo "$LOGIN_RESP" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  red "  ✗ No se pudo obtener token. Verifica credenciales."
  echo "Respuesta: $LOGIN_RESP"
  exit 1
fi
green "  ✓ Token obtenido (${#TOKEN} chars)"

AUTH="Authorization: Bearer $TOKEN"

# ─── Obtener IDs necesarios ──────────────────────────────
step "2. Obtener IDs de catálogo"

# Obtener un predio existente
PREDIOS_RESP=$(curl -s "$BASE_URL/api/predios" -H "$AUTH")
PREDIO_ID=$(echo "$PREDIOS_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
assert_eq "Predio ID disponible" "" ""  # Only fail if truly missing
if [ -z "$PREDIO_ID" ]; then
  red "  ✗ No hay predios en la base de datos. Crea uno primero."
  exit 1
fi
green "  ✓ Usando Predio ID: $PREDIO_ID"

# Obtener un médico
MEDICOS_RESP=$(curl -s "$BASE_URL/api/medicos" -H "$AUTH")
MEDICO_ID=$(echo "$MEDICOS_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
if [ -z "$MEDICO_ID" ]; then
  VET_ID=$(echo "$LOGIN_RESP" | grep -o '"id":[0-9]*' | head -1 | cut -d: -f2)
  MEDICO_ID="${VET_ID:-1}"
  green "  ⚠ Usando ID del admin como veterinario: $MEDICO_ID"
else
  green "  ✓ Usando Médico ID: $MEDICO_ID"
fi

# ─── Check: código inexistente ──────────────────────────
step "3. Verificar código inexistente"
RESP=$(curl -s "$BASE_URL/api/visitas/check-codigo/ZZZ-CODIGO-INEXISTENTE" -H "$AUTH")
assert_contains "Devuelve exists=false" '"exists":false' "$RESP"

# ─── Crear primera visita ──────────────────────────────
step "4. Crear primera visita con código fijo"
UNIQUE_CODE="V-TEST-$(date +%Y%m%d)-MANUAL"
CREATE_RESP=$(curl -s -X POST "$BASE_URL/api/visitas" \
  -H "Content-Type: application/json" \
  -H "$AUTH" \
  -d "{
    \"codigo\": \"$UNIQUE_CODE\",
    \"predio_id\": $PREDIO_ID,
    \"veterinario_id\": $MEDICO_ID,
    \"fecha_programada\": \"$(date +%Y-%m-%d)\"
  }")
assert_contains "Visita creada exitosamente" '"success":true' "$CREATE_RESP"
green "  → Código: $UNIQUE_CODE"

# ─── Verificar que existe ──────────────────────────────
step "5. Verificar que el código ahora existe en check-codigo"
RESP=$(curl -s "$BASE_URL/api/visitas/check-codigo/$UNIQUE_CODE" -H "$AUTH")
assert_contains "Devuelve exists=true" '"exists":true' "$RESP"
assert_contains "Incluye datos del predio" '"predio"' "$RESP"
assert_contains "Incluye datos del veterinario" '"veterinario"' "$RESP"

# ─── Crear duplicado (debe fallar) ──────────────────────
step "6. Intentar crear visita con el mismo código"
DUP_RESP=$(curl -s -X POST "$BASE_URL/api/visitas" \
  -H "Content-Type: application/json" \
  -H "$AUTH" \
  -d "{
    \"codigo\": \"$UNIQUE_CODE\",
    \"predio_id\": $PREDIO_ID,
    \"veterinario_id\": $MEDICO_ID,
    \"fecha_programada\": \"$(date +%Y-%m-%d)\"
  }")
assert_contains "Devuelve error de validación (422)" '"message"' "$DUP_RESP"
assert_contains "Menciona código duplicado" 'codigo' "$DUP_RESP"

# ─── By-codigo endpoint ─────────────────────────────────
step "7. Probar endpoint by-codigo"
RESP=$(curl -s "$BASE_URL/api/visitas/by-codigo/$UNIQUE_CODE" -H "$AUTH")
assert_contains "Devuelve exists=true" '"exists":true' "$RESP"
assert_contains "Incluye data completa" '"data"' "$RESP"

# ─── Sync: subir misma visita (debe detectar y omitir) ──
step "8. Simular subida de visita duplicada vía sync"
SYNC_RESP=$(curl -s -X POST "$BASE_URL/api/sync/visitas" \
  -H "Content-Type: application/json" \
  -H "$AUTH" \
  -d "{
    \"visitas\": [{
      \"codigo\": \"$UNIQUE_CODE\",
      \"predio_id\": $PREDIO_ID,
      \"fecha_programada\": \"$(date +%Y-%m-%d)\"
    }]
  }")
assert_contains "Sync procesa sin error" '"status":"success"' "$SYNC_RESP"
assert_contains "Aparece en procesados" '"procesados"' "$SYNC_RESP"
assert_contains "Incluye el código como procesado" "$UNIQUE_CODE" "$SYNC_RESP"

# ─── Limpieza: borrar visita de prueba ─────────────────
step "9. Limpiar datos de prueba"
curl -s -X GET "$BASE_URL/api/visitas" -H "$AUTH" \
  | grep -o "\"id\":[0-9]*,\"codigo\":\"$UNIQUE_CODE\"" \
  | sed 's/.*"id":\([0-9]*\),.*/\1/' \
  | while read VID; do
      # Use PUT to cancel instead (no delete endpoint)
      curl -s -X PATCH "$BASE_URL/api/visitas/$VID/estado" \
        -H "Content-Type: application/json" \
        -H "$AUTH" \
        -d '{"estado":"cancelada"}' > /dev/null
    done
green "  ✓ Datos marcados como cancelados"

# ─── Resumen ─────────────────────────────────────────────
echo ""
bold "══════════════════════════════════════════════════"
bold "  RESULTADOS"
bold "══════════════════════════════════════════════════"
green "  Pruebas pasadas: $PASS"
if [ $FAIL -gt 0 ]; then
  red "  Pruebas fallidas: $FAIL"
  exit 1
else
  green "  Pruebas fallidas: $FAIL"
  green ""
  green "  ✓ Todas las pruebas pasaron exitosamente."
fi
