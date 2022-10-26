#!/bin/bash

source .env

echo "type docker tag:"
read DOCKER_TAG

aws ecr get-login-password --region $AWS_REGION | docker login --username $AWS_USERNAME --password-stdin $AWS_REPOSITORY
docker build --target deploy -t php:$DOCKER_TAG -f ./infra/docker/php/Dockerfile .
docker build --target deploy -t nginx:$DOCKER_TAG -f ./infra/docker/nginx/Dockerfile .
docker tag php:$DOCKER_TAG $AWS_REPOSITORY/php:$DOCKER_TAG
docker tag nginx:$DOCKER_TAG $AWS_REPOSITORY/nginx:$DOCKER_TAG
docker push $AWS_REPOSITORY/php:$DOCKER_TAG
docker push $AWS_REPOSITORY/nginx:$DOCKER_TAG
