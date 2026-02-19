FROM php:8.3-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    zip \
    unzip \
    vim \
    autoconf \
    g++ \
    make \
    libonig-dev \
    libssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Создание необходимых директорий для логов
RUN mkdir -p /var/log/php-fpm && \
    chown -R www-data:www-data /var/log/php-fpm

# Установка расширений для MySQL
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli

# Установка дополнительных полезных расширений
RUN docker-php-ext-install \
    mbstring \
    bcmath \
    opcache

# Установка Redis расширения
RUN pecl install redis-5.3.7 && \
    docker-php-ext-enable redis && \
    docker-php-ext-install sockets

# Проверка что Redis установлен
RUN php -m | grep -i redis

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка рабочей директории
WORKDIR /var/www/html

# Expose порт
EXPOSE 9000

CMD ["php-fpm"]