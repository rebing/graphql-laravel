#!/usr/bin/env bash

# Inspired by https://github.com/nunomaduro/larastan/blob/669b489e10558bd45fafc2429068fd4a73843802/tests/laravel-test.sh
#
# Create a fresh Kumen installation, install our package in it and run some
# basic tests to ensure everything works.
#
# This script is meant to be run on Travis CI

echo "Install Lumen"
travis_retry composer create-project --quiet --prefer-dist "laravel/lumen" ../lumen
cd ../lumen

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../graphql-laravel" } ],|' -i composer.json
travis_retry composer require --dev "rebing/graphql-laravel:*"

echo "Install library"

echo "Register library"
sed -e 's|// \$app->register(App\\\Providers\\\EventServiceProvider::class);|&\n$app->register(Rebing\\\GraphQL\\\GraphQLLumenServiceProvider::class);|' -i bootstrap/app.php
echo "Publish vendor files"
php artisan graphql:publish
echo "Initialize configuration"
sed -e 's|\$app->register(Rebing\\\GraphQL\\\GraphQLLumenServiceProvider::class);|$app->configure("graphql");\n&\n|' -i bootstrap/app.php


echo "Make GraphQL queries"
php artisan make:graphql:query ExampleQuery
php artisan make:graphql:query ExampleMultiLevelQuery

echo "Add ExampleQuery to config"
sed -e "s|// 'example_query' => ExampleQuery::class,|\\\App\\\GraphQL\\\Queries\\\ExampleQuery::class,|" -i config/graphql.php

echo "Add ExampleMultiLevelQuery in a multi path level schema to the config"
sed -e "s|^        'default' => \[|'multi/level' => ['query' => [ \\\App\\\GraphQL\\\Queries\\\ExampleMultiLevelQuery::class]],\n&|" -i config/graphql.php

echo "Use local copy of GraphiQL view"
sed -e "s|'view' => 'graphql::graphiql'|'view' => 'vendor/graphql/graphiql'|" -i config/graphql.php

echo "Removing non-existent csrf_token() call"
sed -e "s|.*csrf_token.*||" -i resources/views/vendor/graphql/graphiql.php

echo "Start Webserver"
php -S 127.0.0.1:8002 -t public >/dev/null 2>&1 &
sleep 2

echo "Send GraphQL HTTP request to fetch ExampleQuery"
curl 'http://127.0.0.1:8002/graphql?query=%7Bexample%7D' -sSfLv | grep 'The example works'

if [[ $? = 0 ]]; then
  echo "GraphQL ExampleQuery works 👍"
else
  echo "GraphQL ExampleQuery DID NOT work 🚨"
  curl 'http://127.0.0.1:8002/graphql?query=%7Bexample%7D' -sSfLv
  cat storage/logs/*
  exit 1
fi


echo "Test accessing GraphiQL"
curl 'http://127.0.0.1:8002/graphiql' -sSfLv | grep '<div id="graphiql">Loading...</div>'

if [[ $? = 0 ]]; then
  echo "Can access GraphiQL 👍"
else
  echo "Cannot access GraphiQL 🚨"
  curl 'http://127.0.0.1:8002/graphiql' -sSfLv
  cat storage/logs/*
  exit 1
fi


echo "Send GraphQL HTTP request to fetch ExampleMultiLevelQuery"
curl 'http://127.0.0.1:8002/graphql/multi/level?query=%7BexampleMultiLevel%7D' -sSfLv | grep 'The exampleMultiLevel works'

if [[ $? = 0 ]]; then
  echo "GraphQL ExampleMultiLevelQuery works 👍"
else
  echo "GraphQL ExampleMultiLevelQuery DID NOT work 🚨"
  curl 'http://127.0.0.1:8002/graphql/multi/level?query=%7BexampleMultiLevel%7D' -sSfLv
  cat storage/logs/*
  exit 1
fi

