FROM php:8.1-cli

# Install dependencies: make, git, unzip, zip, curl
RUN apt-get update \
    && apt-get install -y \
        make \
        git \
        unzip \
        zip \
        curl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
