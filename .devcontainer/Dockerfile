# Update the VARIANT arg in docker-compose.yml to pick a PHP version: 8, 8.0, 8.1
ARG VARIANT=8
FROM mcr.microsoft.com/vscode/devcontainers/php:dev-${VARIANT}

# Install packages
RUN set -ex; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        sqlite3 \
    ; \
    rm -rf /var/lib/apt/lists/*

# Update args in docker-compose.yaml to set the UID/GID of the "vscode" user.
ARG USER_UID=1000
ARG USER_GID=$USER_UID
RUN if [ "$USER_GID" != "1000" ] || [ "$USER_UID" != "1000" ]; then \
        groupmod --gid $USER_GID vscode \
        && usermod --uid $USER_UID --gid $USER_GID vscode \
        && chmod -R $USER_UID:$USER_GID /home/vscode \
        && chmod -R $USER_UID:root /usr/local/share/nvm; \
    fi

# [Optional] Install a version of Node.js using nvm for front end dev
ARG INSTALL_NODE="true"
ARG NODE_VERSION="lts/*"
RUN if [ "${INSTALL_NODE}" = "true" ]; then bash -c "source /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION}"; fi

# [Optional] Uncomment this section to install additional OS packages.
RUN set -ex; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        zlib1g-dev \
        libzip-dev \
        libsqlite3-dev \
    ; \
    \
    debMultiarch="$(dpkg-architecture --query DEB_BUILD_MULTIARCH)"; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j$(nproc) \
        intl \
        fileinfo \
        zip \
        pdo_sqlite \
    ; \
    rm -rf /var/lib/apt/lists/*

# [Optional] Uncomment this line to install global node packages.
RUN npm install -g --unsafe-perm yarn
