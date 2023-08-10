FROM ubuntu:23.10

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install nfs-kernel-server php-cli --yes
RUN mkdir /app

COPY . /app

STOPSIGNAL SIGINT

WORKDIR /app

USER root

ENTRYPOINT ["php", "src/entrypoint.php"]