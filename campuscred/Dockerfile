# ==============================================================================
# CampusCred - Container Deployment Dockerfile
# Base: PHP 8.2 with Apache Web Server
# ==============================================================================
FROM php:8.2-apache

# Install required system packages and PHP MySQL extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules for clean URLs and security headers
RUN a2enmod rewrite headers

# Configure PHP settings
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy project files into web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Configure directory permissions for uploads and persistent data
RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/uploads

# Expose default HTTP port
EXPOSE 80

CMD ["apache2-foreground"]
