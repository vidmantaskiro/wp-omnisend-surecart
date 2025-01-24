#!/usr/bin/env bash

alias composer='docker run --rm -it -v "$(pwd):/app" composer/composer'

action=$1

case $action in
"check")
  composer update
  composer install
  ./vendor/bin/phpcs -s --ignore=node_modules,build/\* omnisend-for-surecart
  ;;
"fix")
  composer update
  composer install
  ./vendor/bin/phpcbf --ignore=node_modules,build/\* omnisend-for-surecart
  ;;
*)
  echo "pass one of these argument: check,fix"
  exit 1
  ;;
esac
