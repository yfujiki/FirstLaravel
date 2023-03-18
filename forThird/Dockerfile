# Base image
FROM php:8.1.17-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql

# Install Cloud SQL Proxy
RUN apt-get update && apt-get install -y wget gnupg certbot
RUN wget https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64 -O cloud_sql_proxy
RUN chmod +x cloud_sql_proxy
RUN mv cloud_sql_proxy /usr/local/bin/

# Copy application code into the container
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Update Apache configuration
COPY httpd.conf /etc/apache2/httpd.conf
COPY default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Expose port 80, 443
EXPOSE 80 443

# Start Apache
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
