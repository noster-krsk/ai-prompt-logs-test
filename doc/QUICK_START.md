# ⚡ БЫСТРЫЙ СТАРТ за 5 минут

## Для Windows (PowerShell)

```powershell
# 1. Перейди в папку проекта
cd E:\my-docker-project

# 2. Скопируй .env файл
copy .env.example .env

# 3. Запусти контейнеры
docker-compose up -d

# 4. Жди ~30 сек и открой браузер
Start-Process "http://localhost"

# 5. Проверь статус
docker-compose ps
```

---

## Для macOS/Linux

```bash
# 1. Перейди в папку проекта
cd ~/my-docker-project

# 2. Скопируй .env файл
cp .env.example .env

# 3. Запусти контейнеры
docker-compose up -d

# 4. Жди ~30 сек и открой браузер
open http://localhost

# 5. Проверь статус
docker-compose ps
```

---

## Проверка что работает

Все контейнеры должны быть `Up`:

```
NAME           IMAGE            COMMAND              STATUS
mysql-db       mysql:8.0        ...                  Up (healthy)
redis-cache    redis:7-alpine   ...                  Up (healthy)
php-fpm        test1-php        ...                  Up
nginx-server   nginx:latest     ...                  Up
```

---

## Если что-то не работает

```bash
# Посмотри ошибки
docker-compose logs

# Перезагрузи
docker-compose restart

# Или пересоздай всё
docker-compose down -v
docker-compose up -d --build
```

---

## Полезные команды

```bash
# Логи PHP
docker-compose logs -f php

# Вход в PHP контейнер
docker-compose exec php bash

# Вход в MySQL
docker-compose exec mysql mysql -u app_user -p app_password app_database

# Redis CLI
docker-compose exec redis redis-cli

# Остановить всё
docker-compose down
```

---

## Где находятся твои файлы?

- **PHP код**: `app/public/index.php`
- **Конфиги**: `php/`, `nginx/conf.d/`, `mysql/`
- **Пароли**: `.env` файл
- **БД данные**: Сохраняются в Docker volumes (не теряются при перезагрузке)

---

**Готово! 🚀 Открой http://localhost и начни разработку!**
