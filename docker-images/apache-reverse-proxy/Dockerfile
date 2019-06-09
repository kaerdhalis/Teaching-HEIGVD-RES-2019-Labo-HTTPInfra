#Image Docker du serveur Apache
FROM php:7.2-apache

#Installation de vim
RUN apt-get update && \
apt-get install -y vim

#Copie le code source dans le container
COPY apache2-foreground /usr/local/bin/
COPY template /var/apache2/template
COPY conf/ /etc/apache2

#enable les sites
RUN a2enmod proxy proxy_http proxy_balancer lbmethod_byrequests headers
RUN a2ensite 000-* 001-* 
