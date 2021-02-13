#!/bin/bash

set -eo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/realpath.sh
ROOT=$(realpath $SELF_PATH/../..)

version=$1
if [ "$version" == "" ]; then
  echo "Version parameter is mandatory" >&2
  exit 1
fi

set -v

echo -n "$version" | tee $ROOT/config/.version
git log --pretty="%h" -n1 HEAD | tee $ROOT/config/.sentry-release
git log --pretty="%H" -n1 HEAD | tee $ROOT/config/.sentry-commit

# BUILD
composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader --no-dev --working-dir=$ROOT
yarn install --ignore-engines --frozen-lockfile --cwd $ROOT
yarn run production --cwd $ROOT

# PACKAGE
package=monica-$version
mkdir -p $package/database
ln -s $ROOT/.env.example $package/
ln -s $ROOT/app.json $package/
ln -s $ROOT/artisan $package/
ln -s $ROOT/CHANGELOG.md $package/
ln -s $ROOT/CONTRIBUTING.md $package/
ln -s $ROOT/CONTRIBUTORS $package/
ln -s $ROOT/composer.json $package/
ln -s $ROOT/composer.lock $package/
ln -s $ROOT/LICENSE $package/
ln -s $ROOT/nginx_app.conf $package/
ln -s $ROOT/package.json $package/
ln -s $ROOT/Procfile $package/
ln -s $ROOT/readme.md $package/
ln -s $ROOT/server.php $package/
ln -s $ROOT/webpack.mix.js $package/
ln -s $ROOT/yarn.lock $package/
ln -s $ROOT/app $package/
ln -s $ROOT/bootstrap $package/
ln -s $ROOT/config $package/
ln -s $ROOT/docs $package/
ln -s $ROOT/public $package/
ln -s $ROOT/resources $package/
ln -s $ROOT/routes $package/
ln -s $ROOT/vendor $package/

ln -s $ROOT/database/factories $package/database/
ln -s $ROOT/database/migrations $package/database/
ln -s $ROOT/database/seeds $package/database/

mkdir -p $package/storage/app/public
mkdir -p $package/storage/logs
mkdir -p $package/storage/framework/cache
mkdir -p $package/storage/framework/views
mkdir -p $package/storage/framework/sessions

tar chfj $ROOT/$package.tar.bz2 --exclude .gitignore --exclude .gitkeep $package

echo "::set-output name=package::$package.tar.bz2"

# ASSETS
assets=monica-assets-$version
mkdir -p $assets/public
ln -s $ROOT/public/mix-manifest.json $assets/public/
ln -s $ROOT/public/js $assets/public/
ln -s $ROOT/public/css $assets/public/
ln -s $ROOT/public/fonts $assets/public/

tar chfj $ROOT/$assets.tar.bz2 --exclude .gitignore --exclude .gitkeep $assets

echo "::set-output name=assets::$assets.tar.bz2"
