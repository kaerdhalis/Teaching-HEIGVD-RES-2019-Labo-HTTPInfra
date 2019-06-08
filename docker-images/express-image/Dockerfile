FROM node:8.11

RUN printf "deb http://archive.debian.org/debian/ jessie main\ndeb-src http://archive.debian.org/debian/ jessie main\ndeb http://security.debian.org jessie/updates main\ndeb-src http://security.debian.org jessie/updates main" > /etc/apt/sources.list


RUN apt-get update && \
apt-get install -y vim

COPY src /opt/app

CMD ["node","/opt/app/index.js"]
