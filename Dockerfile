# Image PHP avec FPM
FROM php:8.2-fpm-bullseye

# Installer dépendances système + extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Copier Composer depuis l’image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer

# Créer un utilisateur non-root pour Symfony
RUN useradd -m symfonyuser
USER symfonyuser
WORKDIR /home/symfonyuser/app

# Copier tout le projet
COPY --chown=symfonyuser:symfonyuser . .

# Installer les dépendances Symfony avec scripts activés
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Préparer les dossiers var et vendor
RUN mkdir -p var/cache var/log var/sessions && chmod -R 777 var vendor

# Exposer le port PHP intégré
EXPOSE 10000

# Lancer le serveur PHP intégré
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
