#/bin/bash

openssl req -newkey rsa:2048 -nodes -keyout key.pem -x509 -days 365 -out certificate.pem -subj "/C=BG/ST=Sofia/L=Sofia/O=SeQR.link/OU=SeQR/CN=*.seqr.link"
a2enmod ssl
cp ./ser.link.conf /etc/apache2/sites-available
a2ensite seqr.link
