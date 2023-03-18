docker run -it --rm \
-p 80:80 -p 443:443 \
-v "/etc/letsencrypt:/etc/letsencrypt" \
certbot/certbot certonly \
--standalone \
--email fjk89025@gmail.com \
--agree-tos \
-d third.yfujiki.com
