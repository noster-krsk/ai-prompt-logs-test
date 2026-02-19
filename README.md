# 🐳 Docker Compose стек: Nginx + PHP 8.3 + MySQL + Redis

Полностью готовый Docker стек для разработки и продакшена с современными технологиями.

## 📋 Содержание

- [Требования](#требования)
- [Структура проекта](#структура-проекта)
- [Быстрый старт](#быстрый-старт)
- [Детальное описание](#детальное-описание)
- [Команды Docker](#команды-docker)
- [Подключение к сервисам](#подключение-к-сервисам)
- [Переменные окружения](#переменные-окружения)
- [Решение проблем](#решение-проблем)
- [Безопасность](#безопасность)

---

## 📦 Требования

Убедись что установлены:

- **Docker** (версия 20.10+)
  ```bash
  docker --version
  ```
- **Docker Compose** (версия 2.0+)
  ```bash
  docker-compose --version
  ```

### Установка Docker

- **Windows/macOS**: Скачай [Docker Desktop](https://www.docker.com/products/docker-desktop)
- **Linux**: 
  ```bash
  curl -fsSL https://get.docker.com -o get-docker.sh
  sudo sh get-docker.sh
  sudo usermod -aG docker $USER
  ```

---

## 📂 Структура проекта

```
project/
├── docker-compose.yml              # Основной конфиг Docker Compose
├── .env                            # Переменные окружения (БЕЗ git!)
├── .env.example                    # Пример .env файла
│
├── app/                            # Твоё PHP приложение
│   └── public/
│       └── index.php               # Точка входа
│
├── nginx/
│   ├── conf.d/
│   │   └── default.conf            # Nginx конфиг
│   └── ssl/                        # Папка для SSL сертификатов
│
├── php/
│   ├── Dockerfile                  # Сборка PHP образа
│   ├── php.ini                     # Конфиг PHP
│   └── www.conf                    # Конфиг PHP-FPM
│
├── mysql/
│   └── init.sql                    # SQL для инициализации БД
│
└── README.md                       # Этот файл
```

---

## 🚀 Быстрый старт

### 1️⃣ Клонируй репозиторий (или распакуй архив)

```bash
git clone <repository>
cd <project-folder>
```

Или если архив:
```bash
unzip docker_ngnix_mysq_php83_redis.zip
cd docker_ngnix_mysq_php83_redis
```

### 2️⃣ Создай `.env` файл

```bash
cp .env.example .env
```

Отредактируй `.env` если нужно изменить пароли:

```env
# MySQL
MYSQL_ROOT_PASSWORD=root_password
MYSQL_DATABASE=app_database
MYSQL_USER=app_user
MYSQL_PASSWORD=app_password

# PHP
PHP_MEMORY_LIMIT=256M
PHP_MAX_EXECUTION_TIME=300
```

### 3️⃣ Запусти контейнеры

```bash
docker-compose up -d
```

Флаг `-d` запускает в фоновом режиме.

### 4️⃣ Проверь статус

```bash
docker-compose ps
```

Должно вывести что-то вроде:
```
NAME           IMAGE            COMMAND                  SERVICE   STATUS
mysql-db       mysql:8.0        "docker-entrypoint.s…"   mysql     Up (healthy)
redis-cache    redis:7-alpine   "docker-entrypoint.s…"   redis     Up (healthy)
php-fpm        <project>-php    "docker-php-entrypoi…"   php       Up
nginx-server   nginx:latest     "/docker-entrypoint.…"   nginx     Up
```

### 5️⃣ Открой браузер

```
http://localhost
```

Должна загрузиться красивая страница со статусом всех сервисов ✅

---

## 📚 Детальное описание

### Сервисы в стеке

#### 🌐 Nginx (веб-сервер)
- **Порт**: 80 (HTTP), 443 (HTTPS)
- **Роль**: Прокси-сервер, обслуживает статические файлы
- **Конфиг**: `nginx/conf.d/default.conf`
- **Контейнер имя**: `nginx-server`

#### 🐘 PHP-FPM 8.3
- **Версия**: PHP 8.3 с FPM (FastCGI Process Manager)
- **Порт**: 9000 (внутри сети Docker)
- **Расширения установлены**:
  - PDO для MySQL
  - Redis (для кэша и очередей)
  - mbstring (для UTF-8)
  - bcmath (математические операции)
  - opcache (кэширование)
  - Composer (управление зависимостями)
- **Конфиг**: `php/php.ini` и `php/www.conf`
- **Контейнер имя**: `php-fpm`

#### 🗄️ MySQL 8.0
- **Порт**: 3306
- **База по умолчанию**: `app_database`
- **Пользователь**: `app_user` / `app_password`
- **Root пароль**: `root_password`
- **Инициализация**: `mysql/init.sql`
- **Хранилище**: Docker volume `mysql-data` (сохраняется при перезагрузке)
- **Контейнер имя**: `mysql-db`

#### ⚡ Redis 7-Alpine
- **Порт**: 6379
- **Использование**: Кэширование, очереди, сессии
- **Команда**: `redis-server --appendonly yes` (сохранение данных на диск)
- **Хранилище**: Docker volume `redis-data`
- **Контейнер имя**: `redis-cache`

---

## 🐳 Команды Docker

### Основные команды

```bash
# Запуск всех контейнеров
docker-compose up -d

# Запуск с пересборкой образов (после изменения Dockerfile)
docker-compose up -d --build

# Остановка всех контейнеров
docker-compose stop

# Запуск остановленных контейнеров
docker-compose start

# Перезагрузка контейнеров
docker-compose restart

# Полное удаление контейнеров
docker-compose down

# Удаление контейнеров И томов (ВНИМАНИЕ: потеря данных БД!)
docker-compose down -v
```

### Просмотр логов

```bash
# Логи всех контейнеров в реальном времени
docker-compose logs -f

# Логи конкретного сервиса
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mysql
docker-compose logs -f redis

# Последние 50 строк логов
docker-compose logs --tail=50 php

# Без следования (просто вывести и выйти)
docker-compose logs php
```

### Вход в контейнер

```bash
# Bash в PHP контейнере
docker-compose exec php bash

# Bash в Nginx контейнере
docker-compose exec nginx bash

# Bash в MySQL контейнере
docker-compose exec mysql bash

# Redis CLI
docker-compose exec redis redis-cli
```

### Выполнение команд в контейнерах

```bash
# PHP версия
docker-compose exec php php -v

# Список расширений PHP
docker-compose exec php php -m

# Запуск Composer
docker-compose exec php composer install
docker-compose exec php composer require symfony/console

# MySQL команды
docker-compose exec mysql mysql -u app_user -p app_password app_database
```

---

## 🔌 Подключение к сервисам

### Из PHP кода

#### MySQL подключение
```php
<?php
try {
    $pdo = new PDO(
        'mysql:host=mysql;dbname=app_database',
        'app_user',
        'app_password'
    );
    echo "MySQL подключена ✅";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
```

**Важно**: Используй `host=mysql` (имя сервиса), не `localhost`!

#### Redis подключение
```php
<?php
$redis = new Redis();
$redis->connect('redis', 6379);

// Пример использования
$redis->set('key', 'value', 3600); // Установи значение на 1 час
$value = $redis->get('key');       // Получи значение

echo $value; // выведет: "value"
?>
```

### С хоста (с твоего компьютера)

#### MySQL через CLI
```bash
mysql -h 127.0.0.1 -P 3306 -u app_user -p app_password app_database
```

Или интерактивно:
```bash
mysql -h localhost -u app_user -p  # Будет запрос пароля
```

#### Redis через redis-cli
```bash
redis-cli -h 127.0.0.1 -p 6379

# В redis-cli:
> SET key value
> GET key
> KEYS *
> FLUSHALL
```

#### PhpMyAdmin (опционально)

Добавь в `docker-compose.yml`:
```yaml
phpmyadmin:
  image: phpmyadmin:latest
  container_name: phpmyadmin
  environment:
    PMA_HOST: mysql
    PMA_USER: root
    PMA_PASSWORD: root_password
  ports:
    - "8080:80"
  depends_on:
    - mysql
  networks:
    - app-network
```

Затем открой: `http://localhost:8080`

---

## 🔐 Переменные окружения

### Как работают переменные

**В Docker Compose** переменные из `.env` подставляются в `docker-compose.yml`:

```yaml
environment:
  MYSQL_PASSWORD: ${MYSQL_PASSWORD}  # Подставится из .env
```

**В PHP коде** используй:

```php
$host = getenv('MYSQL_HOST');       // mysql
$user = getenv('MYSQL_USER');       // app_user
$pass = getenv('MYSQL_PASSWORD');   // app_password
```

**В Dockerfile** переменные окружения контейнера:

```dockerfile
ENV PHP_MEMORY_LIMIT=256M
ENV PHP_MAX_EXECUTION_TIME=300
```

### Сетевые имена сервисов

Контейнеры видят друг друга по именам сервисов из `docker-compose.yml`:

| Сервис | Хост | Порт |
|--------|------|------|
| MySQL | `mysql` | 3306 |
| PHP | `php` | 9000 |
| Redis | `redis` | 6379 |
| Nginx | `nginx` | 80 |

---

## 🛠️ Решение проблем

### Контейнер PHP постоянно перезагружается

**Ошибка**: `ERROR: failed to open access log`

**Решение**: Эта ошибка уже исправлена в Dockerfile, но если ты видишь её:

```bash
docker-compose logs php
```

Проверь `/var/log/php-fpm` существует в контейнере. Пересобери образ:

```bash
docker-compose down -v
docker-compose up -d --build
```

### Nginx не может найти PHP

**Ошибка**: `host not found in upstream "php"`

**Решение**: Убедись что:
1. Сервис PHP запущен: `docker-compose ps`
2. Nginx конфиг правильный: `fastcgi_pass php:9000;`
3. Оба в одной сети: `networks: - app-network`

Перезагрузи:
```bash
docker-compose restart nginx
```

### Порт 80 уже занят

**Ошибка**: `Error response from daemon: driver failed programming external connectivity`

**Решение**: Измени в `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "8080:80"  # Вместо 80:80
```

Затем открой: `http://localhost:8080`

Или найди процесс на порте 80:

```bash
# macOS/Linux
sudo lsof -i :80

# Windows (PowerShell)
netstat -ano | findstr :80
```

### MySQL не подключается

**Ошибка**: `Connection refused` или `Access denied`

**Решение**:
1. Проверь статус: `docker-compose ps mysql`
2. Посмотри логи: `docker-compose logs mysql`
3. Убедись что MySQL полностью инициализирован (ждёт ~15 сек)
4. Используй правильные учётные данные из `.env`

### Redis не работает

**Проверка**:
```bash
docker-compose exec redis redis-cli ping
# Должно вывести: PONG
```

Если не работает:
```bash
docker-compose logs redis
docker-compose restart redis
```

### "Permission denied" при редактировании файлов

**На Linux**: Файлы в контейнере созданы пользователем `www-data`

**Решение**:
```bash
# Дай права на папку
chmod -R 755 app/

# Или смени владельца
sudo chown -R $USER:$USER .
```

---

## 🔒 Безопасность

### ⚠️ Development vs Production

**Текущая конфигурация** подходит для **development** (разработки).

### Для Production используй:

1. **Измени пароли** в `.env`:
   ```env
   MYSQL_ROOT_PASSWORD=SuperSecurePassword123!@#
   MYSQL_PASSWORD=AnotherSecure123!@#
   ```

2. **Добавь Redis пароль**:
   ```yaml
   redis:
     command: redis-server --requirepass YourSecurePassword
   ```

3. **Используй HTTPS**:
   - Добавь SSL сертификаты в `nginx/ssl/`
   - Обнови Nginx конфиг:
     ```nginx
     server {
         listen 443 ssl;
         ssl_certificate /etc/nginx/ssl/cert.pem;
         ssl_certificate_key /etc/nginx/ssl/key.pem;
     }
     ```

4. **Не коммитьте `.env`**:
   ```bash
   echo ".env" >> .gitignore
   ```

5. **Используй secrets** в production:
   - Docker Swarm secrets
   - Kubernetes secrets
   - Или переменные окружения на сервере

6. **Регулярно обновляй образы**:
   ```bash
   docker pull php:8.3-fpm
   docker pull nginx:latest
   docker pull mysql:8.0
   docker pull redis:7-alpine
   ```

7. **Включи логирование**:
   ```yaml
   logging:
     driver: "json-file"
     options:
       max-size: "10m"
       max-file: "3"
   ```

---

## 📖 Полезные ссылки

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [PHP 8.3 Official](https://www.php.net/releases/8.3/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [MySQL 8.0 Documentation](https://dev.mysql.com/doc/mysql-installation-excerpt/8.0/en/)
- [Redis Documentation](https://redis.io/documentation)

---

## 💡 Примеры использования

### Создание новой БД таблицы

```bash
docker-compose exec mysql mysql -u app_user -p app_password app_database

# В MySQL prompt:
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

EXIT;
```

### Экспорт БД

```bash
docker-compose exec mysql mysqldump -u app_user -p app_password app_database > backup.sql
```

### Импорт БД

```bash
docker exec -i mysql-db mysql -u app_user -p app_password app_database < backup.sql
```

### Работа с Redis

```bash
docker-compose exec redis redis-cli

> SET user:1 '{"name":"John","email":"john@example.com"}'
> GET user:1
> KEYS user:*
> DEL user:1
```

### Установка PHP пакета через Composer

```bash
docker-compose exec php composer require monolog/monolog
```

---

## 🤝 Поддержка и проблемы

Если что-то не работает:

1. Проверь **логи**: `docker-compose logs -f`
2. Проверь **статус контейнеров**: `docker-compose ps`
3. Перезагрузи контейнеры: `docker-compose restart`
4. Пересоздай стек: `docker-compose down -v && docker-compose up -d --build`

---

## 📄 Лицензия

MIT License - используй свободно


