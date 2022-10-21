FROM nginx:1.20 AS base

WORKDIR /var/www/html

ENV TZ=Asia/Tokyo

FROM base AS development

COPY ./infra/docker/nginx/develop.default.conf /etc/nginx/conf.d/default.conf

FROM base AS deploy

COPY ./infra/docker/nginx/deploy.default.conf /etc/nginx/conf.d/default.conf
COPY ./src /var/www/html

