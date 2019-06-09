#Image Docker pour serveur Apache
FROM php:7.2-apache

#Installation de Vim
RUN apt-get update && \
apt-get install -y vim

#Copie du code source dans le container
COPY src/ /var/www/html/