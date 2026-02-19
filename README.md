# Ремонтная служба — Управление заявками на ремонт

Веб-приложение для управления заявками в ремонтную службу.
Две роли: **Диспетчер** (назначает и отменяет заявки) и **Мастер** (берёт в работу и завершает).

## Технологический стек

- **PHP 8.3** (без фреймворка, MVC + Service Layer)
- **MySQL 8.0** (хранение данных, SELECT FOR UPDATE для гонки)
- **Redis 7** (хранение сессий)
- **Nginx** (веб-сервер)
- **Docker / Docker Compose** (контейнеризация)
- **BladeOne** (шаблонизатор), **FastRoute** (роутинг), **phpdotenv** (.env)

## Требования

- Docker >= 20.10, Docker Compose >= 2.0 (вариант с Docker)
- PHP >= 8.3, MySQL >= 8.0, Redis >= 7, Composer (вариант без Docker)

## Установка и запуск (Docker)

```bash
git clone <repo-url>
cd <project>
cp .env.example .env
docker-compose up -d --build
docker-compose exec php bash -c "cd /var/www/html && composer install"
```

> При первом запуске MySQL автоматически создаёт таблицы и демо-пользователей из `mysql/init.sql`.
> Если нужно пересоздать БД: `docker-compose down -v && docker-compose up -d`

## Установка без Docker (сервер)

```bash
git clone <repo-url>
cd <project>/app

# Создать и настроить .env приложения (MYSQL_HOST, MYSQL_USER и т.д.)
cp .env.example .env
nano .env

composer install

# Создание таблиц
php console migrate

# Заполнение демо-данными
php console db:seed

# Или всё сразу (пересоздание БД + сиды)
php console migrate:fresh --seed
```

> Файл `app/.env` — конфигурация фреймворка. Настройте `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`, `REDIS_HOST` под ваш сервер.

### Консольные команды

| Команда | Описание |
|---------|----------|
| `php console migrate` | Выполнить непримёненные миграции |
| `php console migrate:fresh` | Удалить все таблицы и запустить миграции заново |
| `php console migrate:fresh --seed` | Пересоздать БД и заполнить демо-данными |
| `php console db:seed` | Запустить все сиды |
| `php console migrate:status` | Показать статус миграций |

## Демо-пользователи

| Роль | Имя | Email | Пароль |
|------|-----|-------|--------|
| Диспетчер | Диспетчер Иванова | dispatcher@example.com | password |
| Мастер | Мастер Петров | master1@example.com | password |
| Мастер | Мастер Сидоров | master2@example.com | password |

## Страницы приложения

| URL | Описание |
|-----|----------|
| http://localhost/login | Вход в систему |
| http://localhost/requests/create | Создание заявки (без авторизации) |
| http://localhost/dispatcher | Панель диспетчера |
| http://localhost/master | Панель мастера |

## Архитектура

```
Controller (тонкий) → Service (бизнес-логика + транзакции) → Repository (SQL) → Database (PDO)
                              ↓
                     StateMachine (правила переходов)
                              ↓
                     AuditService (лог изменений)
```

- **State Machine** — единственный источник правил переходов статусов
- **Service Layer** — граница транзакций (`BEGIN` → `SELECT FOR UPDATE` → валидация → `UPDATE` → `COMMIT`)
- **Контроллеры** — только HTTP ввод/вывод, без бизнес-логики

### Статусы заявки

```
new → assigned → in_progress → done
 ↓        ↓
canceled  canceled
```

## Тест гонки (Race Condition)

Скрипт проверяет, что при одновременном «Взять в работу» двумя мастерами один успеет, а второй получит HTTP 409 Conflict.

```bash
chmod +x race_test.sh
./race_test.sh
```

**Ожидаемый результат:** один запрос — HTTP 302 (успех), второй — HTTP 409 (конфликт).

### Как работает защита

1. `BEGIN TRANSACTION`
2. `SELECT * FROM repair_requests WHERE id = ? FOR UPDATE` — блокировка строки
3. Проверка статуса через State Machine
4. `UPDATE repair_requests SET status = 'in_progress'`
5. `COMMIT`

Второй мастер ждёт на шаге 2 (блокировка строки), после разблокировки видит `status = 'in_progress'` → получает `ConcurrencyException`.

## Запуск тестов

```bash
docker-compose exec php bash -c "cd /var/www/html && vendor/bin/phpunit"
```

Тесты:
- **StateMachineTest** — проверка всех допустимых и недопустимых переходов статусов
- **RaceConditionTest** — интеграционный тест защиты от гонки (требует MySQL)

## Структура проекта

```
app/
├── console                    # CLI (миграции, сиды)
├── public/index.php           # Front controller
├── src/
│   ├── Core/                  # Инфраструктура (Database, Router, Container, Session, Auth, View)
│   ├── Console/               # MigrationRunner, SeederRunner
│   ├── Domain/                # Домен (Enum, Exception, StateMachine)
│   ├── Repository/            # Доступ к данным (SQL)
│   ├── Service/               # Бизнес-логика + транзакции
│   └── Controller/            # Тонкие контроллеры
├── database/
│   ├── migrations/            # Файлы миграций (up/down)
│   └── seeders/               # Сиды (демо-данные)
├── views/                     # Blade-шаблоны
├── config/routes.php          # Маршруты
├── tests/                     # PHPUnit-тесты
└── composer.json
```

## См. также

- [DECISIONS.md](DECISIONS.md) — архитектурные решения (7 ADR)
