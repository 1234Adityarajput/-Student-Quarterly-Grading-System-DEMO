FROM php:8.2-apache

# Install required system dependencies and PHP extensions (mysqli, pdo_mysql, gd, zip)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    dos2unix \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd zip \
    && docker-php-ext-enable mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy project source code
COPY . /var/www/html/

# Copy startup script, ensure unix line endings and executable bit
COPY start.sh /usr/local/bin/start.sh
RUN dos2unix /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# Set proper ownership and permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose default port
EXPOSE 80

# Execute custom startup script with dynamic PORT and reverse proxy configuration
CMD ["/usr/local/bin/start.sh"]
