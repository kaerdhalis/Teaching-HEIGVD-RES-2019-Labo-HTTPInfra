#Image Docker pour node.js
FROM node:8.11

#resout le probleme d'installation de vim ou le docker ne trouve pas les paquets
RUN printf "deb http://archive.debian.org/debian/ jessie main\ndeb-src http://archive.debian.org/debian/ jessie main\ndeb http://security.debian.org jessie/updates main\ndeb-src http://security.debian.org jessie/updates main" > /etc/apt/sources.list

#installation de vim
RUN apt-get update && \
apt-get install -y vim

#copie du code source dans le docker
COPY src /opt/app

#execute la commande au demarrage du container
CMD ["node","/opt/app/index.js"]
