#!/usr/bin/env bash
#
# ci-local.sh — roda localmente os mesmos checks do CI (.github/workflows/)
# ANTES de commitar.
#
# Espelha os jobs do GitHub Actions: Pint, PHPCS, PHPMD, PHPStan e PHPUnit.
# Este package roda direto no host (PHP/Composer), sem Docker e sem frontend.
#
# Não para no primeiro erro — roda tudo e imprime um resumo no fim, para você
# ver o panorama completo (o projeto pode ter dívida pré-existente em baseline).
# Sai com código != 0 se algum check falhar, então serve como gate de pre-commit.
#
# Uso:
#   ./ci-local.sh                # roda todos os checks
#   ./ci-local.sh --fix          # Pint e PHPCBF aplicam correções de estilo
#   ./ci-local.sh phpstan        # roda só os checks nomeados (pint|phpcs|phpmd|phpstan|phpunit)
#
set -uo pipefail

cd "$(dirname "$0")"

# --- Flags ------------------------------------------------------------------
FIX=0
ONLY=()
for arg in "$@"; do
  case "$arg" in
    --fix)    FIX=1 ;;
    pint|phpcs|phpmd|phpstan|phpunit) ONLY+=("$arg") ;;
    -h|--help)
      sed -n '2,18p' "$0"; exit 0 ;;
    *)
      echo "Argumento desconhecido: $arg" >&2; exit 2 ;;
  esac
done

# Se nenhum check foi nomeado, roda todos.
should_run() {
  [ ${#ONLY[@]} -eq 0 ] && return 0
  for c in "${ONLY[@]}"; do [ "$c" = "$1" ] && return 0; done
  return 1
}

# --- Cores e contadores -----------------------------------------------------
if [ -t 1 ]; then
  RED=$'\033[31m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; BOLD=$'\033[1m'; NC=$'\033[0m'
else
  RED=''; GREEN=''; YELLOW=''; BOLD=''; NC=''
fi

declare -a RESULTS
FAILED=0

run_check() {
  local name="$1"; shift
  should_run "$name" || return 0

  echo
  echo "${BOLD}━━━ ${name} ━━━${NC}"
  if "$@"; then
    echo "${GREEN}✓ ${name} passou${NC}"
    RESULTS+=("${GREEN}✓${NC} ${name}")
  else
    echo "${RED}✗ ${name} falhou${NC}"
    RESULTS+=("${RED}✗${NC} ${name}")
    FAILED=1
  fi
}

# --- Verifica dependências ---------------------------------------------------
if [ ! -f vendor/bin/pint ]; then
  echo "${RED}Dependências não instaladas. Rode: composer install${NC}" >&2
  exit 1
fi

# --- Os checks (espelham .github/workflows/) --------------------------------

# 1) Pint — pint.yml
check_pint() {
  if [ "$FIX" -eq 1 ]; then
    composer lint
  else
    composer lint:test
  fi
}

# 2) PHPCS — phpcs.yml  (PSR-12, config em phpcs.xml)
check_phpcs() {
  if [ "$FIX" -eq 1 ]; then
    vendor/bin/phpcbf || true
  fi
  composer cs
}

# 3) PHPMD — phpms.yml
check_phpmd() {
  composer md
}

# 4) PHPStan — phpstan.yml
check_phpstan() {
  composer stan
}

# 5) PHPUnit — phpunit.yml  (Testbench + SQLite in-memory via phpunit.xml)
check_phpunit() {
  composer test
}

# --- Executa ----------------------------------------------------------------
echo "${BOLD}Rodando checks do CI localmente${NC}"
[ "$FIX" -eq 1 ] && echo "${YELLOW}Modo --fix: Pint/PHPCBF vão aplicar correções de estilo.${NC}"

run_check "pint"    check_pint
run_check "phpcs"   check_phpcs
run_check "phpmd"   check_phpmd
run_check "phpstan" check_phpstan
run_check "phpunit" check_phpunit

# --- Resumo -----------------------------------------------------------------
echo
echo "${BOLD}━━━ Resumo ━━━${NC}"
for r in "${RESULTS[@]}"; do echo "  $r"; done
echo

if [ "$FAILED" -eq 0 ]; then
  echo "${GREEN}${BOLD}Tudo verde — seguro para commitar.${NC}"
else
  echo "${RED}${BOLD}Há checks falhando — revise antes de commitar.${NC}"
fi
exit "$FAILED"
