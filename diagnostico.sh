#!/usr/bin/env bash
# Diagnóstico do projeto (Linux/macOS). Executar na raiz: ./diagnostico.sh
set -u

echo "=== 1. Diagnóstico completo ==="
php spark app:diagnostico

echo -e "\n=== 2. Rotas registadas ==="
php spark routes

echo -e "\n=== 3. Estado das migrations ==="
php spark migrate:status
