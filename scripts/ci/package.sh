#!/bin/bash

set -eo pipefail

SELF_PATH=$(cd -P -- "$(dirname -- "$0")" && /bin/pwd -P)
source $SELF_PATH/../realpath.sh
ROOT=$(realpath $SELF_PATH/../..)

version=$1
if [ -z "$version" ]; then
  echo "Version parameter is mandatory" >&2
  exit 1
fi

commit=$2
if [ -z "$commit" ]; then
  commit=$(git --git-dir $ROOT/.git log --pretty="%H" -n1 HEAD)
  release=$(git --git-dir $ROOT/.git log --pretty="%h" -n1 HEAD)
fi

echo -n "version: "
echo -n "$version" | tee $ROOT/config/.version
echo -n -e "\ncommit: "
echo -n $commit | tee $ROOT/config/.commit
echo -n -e "\nrelease: "
echo -n ${release:-$version} | tee $ROOT/config/.release
echo ""


# BUILD
composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader --no-dev --working-dir=$ROOT
yarn --cwd $ROOT install --immutable
yarn --cwd $ROOT run build


# PACKAGE
package=monica-$version
mkdir -p $package/database
ln -s $ROOT/.dockerignore $package/
ln -s $ROOT/.editorconfig $package/
ln -s $ROOT/.env.example $package/
ln -s $ROOT/.prettierignore $package/
ln -s $ROOT/.prettierrc.json $package/
ln -s $ROOT/.tool-versions $package/
ln -s $ROOT/.yarnrc.yml $package/
ln -s $ROOT/artisan $package/
ln -s $ROOT/composer.json $package/
ln -s $ROOT/composer.lock $package/
ln -s $ROOT/eslint.config.js $package/
ln -s $ROOT/jsconfig.json $package/
ln -s $ROOT/LICENSE.md $package/
ln -s $ROOT/package.json $package/
ln -s $ROOT/postcss.config.js $package/
ln -s $ROOT/README.md $package/
ln -s $ROOT/SECURITY.md $package/
ln -s $ROOT/tailwind.config.js $package/
ln -s $ROOT/vite.config.js $package/
ln -s $ROOT/yarn.lock $package/
ln -s $ROOT/.devcontainer $package/
ln -s $ROOT/.yarn $package/
ln -s $ROOT/app $package/
ln -s $ROOT/bootstrap $package/
ln -s $ROOT/config $package/
ln -s $ROOT/lang $package/
ln -s $ROOT/public $package/
ln -s $ROOT/resources $package/
ln -s $ROOT/routes $package/
ln -s $ROOT/vendor $package/

ln -s $ROOT/database/factories $package/database/
ln -s $ROOT/database/migrations $package/database/
ln -s $ROOT/database/seeders $package/database/

mkdir -p $package/storage/app/public
mkdir -p $package/storage/logs
mkdir -p $package/storage/framework/cache
mkdir -p $package/storage/framework/views
mkdir -p $package/storage/framework/sessions

tar chfj $package.tar.bz2 --exclude .gitignore --exclude .gitkeep $package
sha512sum "$package.tar.bz2" > "$package.tar.bz2.sha512"

echo "package=$package.tar.bz2" >> $GITHUB_OUTPUT


# ASSETS
assets=monica-assets-$version
mkdir -p $assets/public
ln -s $ROOT/public/build $assets/public/

tar chfj $assets.tar.bz2 --exclude .gitignore --exclude .gitkeep $assets
sha512sum "$assets.tar.bz2" > "$assets.tar.bz2.sha512"

echo "assets=$assets.tar.bz2" >> $GITHUB_OUTPUT


# SIGN
if [ -n "${GPG_FINGERPRINT:-}" -a -n "${GPG_PASSPHRASE:-}" ]; then
    for f in {$package,$assets}.tar.bz2{,.sha512}; do
        echo "Signing '$f'…"
        echo "$GPG_PASSPHRASE" | gpg --batch --yes --passphrase-fd 0 --pinentry-mode=loopback --local-user $GPG_FINGERPRINT --sign --armor --detach-sig --output "$f.asc" "$f"
        echo -e "\nSigned with key fingerprint $GPG_FINGERPRINT" >> "$f.asc"
    done
fi
