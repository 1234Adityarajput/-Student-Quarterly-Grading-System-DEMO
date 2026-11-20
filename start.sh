#!/bin/bash
set -e

# Default to port 80 if PORT environment variable is not set by Render
PORT="${PORT:-80}"
echo "==> Configuring Apache for Render dynamic port: $PORT"

# Update Apache ports.conf to listen on dynamic $PORT
sed -i "s/Listen [0-9]*/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Render Reverse Proxy Fix:
# Prevents Apache from appending internal port (:10000) to trailing-slash or directory redirects
cat << 'EOF' > /etc/apache2/conf-available/reverse-proxy.conf
ServerName localhost
UseCanonicalName Off
UseCanonicalPhysicalPort Off
EOF
a2enconf reverse-proxy

# Enable AllowOverride All in apache2.conf so .htaccess rules are executed
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

echo "==> Starting Apache in foreground..."
exec apache2-foreground
