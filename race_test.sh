#!/bin/bash
# ============================================
# Тест гонки (Race Condition Test)
# ============================================
# Скрипт проверяет, что при одновременном выполнении
# "Взять в работу" двумя мастерами — только один успеет,
# а второй получит HTTP 409 Conflict.
#
# Запуск: chmod +x race_test.sh && ./race_test.sh
# ============================================

set -e

BASE_URL="http://localhost"
COOKIE_DIR=$(mktemp -d)

echo "=========================================="
echo " Тест гонки — Ремонтная служба"
echo "=========================================="

# 1. Создаём тестовую заявку
echo ""
echo "[1/5] Создание тестовой заявки..."
CREATE_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/requests" \
  -d "client_name=Тест Гонки&phone=+71234567890&address=ул. Тестовая 1&problem_text=Проверка параллельных запросов")
CREATE_STATUS=$(echo "$CREATE_RESPONSE" | tail -1)
echo "       Статус: $CREATE_STATUS"

# 2. Вход как диспетчер
echo ""
echo "[2/5] Вход как диспетчер..."
curl -s -c "$COOKIE_DIR/dispatcher.txt" -L -X POST "$BASE_URL/login" \
  -d "email=dispatcher@example.com&password=password" > /dev/null 2>&1
echo "       OK"

# 3. Получаем ID последней заявки и назначаем мастера
echo ""
echo "[3/5] Назначение мастера на последнюю заявку..."

# Находим ID последней заявки через MySQL
REQUEST_ID=$(docker-compose exec -T mysql mysql -u app_user -papp_password app_database \
  -N -e "SELECT id FROM repair_requests WHERE status='new' ORDER BY id DESC LIMIT 1" 2>/dev/null | tr -d '[:space:]')

if [ -z "$REQUEST_ID" ]; then
    echo "       ОШИБКА: Не найдена заявка со статусом 'new'"
    rm -rf "$COOKIE_DIR"
    exit 1
fi

echo "       ID заявки: $REQUEST_ID"

# Назначаем мастера (master_id=2 — Мастер Петров)
ASSIGN_RESPONSE=$(curl -s -w "\n%{http_code}" -b "$COOKIE_DIR/dispatcher.txt" -L -X POST "$BASE_URL/dispatcher/assign" \
  -d "request_id=$REQUEST_ID&master_id=2")
ASSIGN_STATUS=$(echo "$ASSIGN_RESPONSE" | tail -1)
echo "       Назначение: HTTP $ASSIGN_STATUS"

# 4. Вход как два мастера
echo ""
echo "[4/5] Вход как мастер 1 и мастер 2..."
curl -s -c "$COOKIE_DIR/master1.txt" -L -X POST "$BASE_URL/login" \
  -d "email=master1@example.com&password=password" > /dev/null 2>&1
curl -s -c "$COOKIE_DIR/master2.txt" -L -X POST "$BASE_URL/login" \
  -d "email=master2@example.com&password=password" > /dev/null 2>&1
echo "       OK"

# 5. Параллельные запросы "Взять в работу"
echo ""
echo "[5/5] Отправка параллельных запросов 'Взять в работу'..."
echo "       (оба мастера одновременно пытаются взять заявку #$REQUEST_ID)"
echo ""

# Запускаем два curl параллельно
curl -s -o "$COOKIE_DIR/resp1.txt" -w "%{http_code}" \
  -b "$COOKIE_DIR/master1.txt" -X POST "$BASE_URL/master/take" \
  -d "request_id=$REQUEST_ID" > "$COOKIE_DIR/code1.txt" 2>&1 &
PID1=$!

curl -s -o "$COOKIE_DIR/resp2.txt" -w "%{http_code}" \
  -b "$COOKIE_DIR/master2.txt" -X POST "$BASE_URL/master/take" \
  -d "request_id=$REQUEST_ID" > "$COOKIE_DIR/code2.txt" 2>&1 &
PID2=$!

# Ждём завершения обоих
wait $PID1 $PID2

CODE1=$(cat "$COOKIE_DIR/code1.txt")
CODE2=$(cat "$COOKIE_DIR/code2.txt")

echo "=========================================="
echo " РЕЗУЛЬТАТЫ"
echo "=========================================="
echo " Мастер 1 (Петров):  HTTP $CODE1"
echo " Мастер 2 (Сидоров): HTTP $CODE2"
echo "=========================================="

# Проверяем результат
if [[ ("$CODE1" == "302" && "$CODE2" == "409") || ("$CODE1" == "409" && "$CODE2" == "302") ]]; then
    echo ""
    echo " ТЕСТ ПРОЙДЕН: Один мастер успел (302), второй получил конфликт (409)"
elif [[ ("$CODE1" == "302" && "$CODE2" == "302") ]]; then
    echo ""
    echo " ВНИМАНИЕ: Оба получили 302."
    echo " Это может означать, что запросы не были действительно параллельными."
    echo " Проверьте статус заявки в БД:"
    docker-compose exec -T mysql mysql -u app_user -papp_password app_database \
      -e "SELECT id, status, assigned_to FROM repair_requests WHERE id=$REQUEST_ID" 2>/dev/null
    echo " Проверьте аудит-лог:"
    docker-compose exec -T mysql mysql -u app_user -papp_password app_database \
      -e "SELECT * FROM audit_log WHERE request_id=$REQUEST_ID" 2>/dev/null
else
    echo ""
    echo " Неожиданный результат: $CODE1 и $CODE2"
fi

# Очистка
rm -rf "$COOKIE_DIR"
echo ""
echo "Тест завершён."
