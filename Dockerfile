FROM php:8.2-apache

# Install PostgreSQL C-library dependency and PHP extensions (PDO + pgsql)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Document container port
EXPOSE 80

# Environment defaults
ENV DB_HOST=travel-db \
    DB_PORT=5432 \
    POSTGRES_DB=travel_db \
    POSTGRES_USER=danish

# Copy application source code into Apache document root
COPY ./src /var/www/html

# Set proper ownership and permissions for Apache web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
