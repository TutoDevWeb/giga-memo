#!/bin/bash

PROJECT_NAME="giga-memo-review"

echo "Création de l'archive d'analyse Symfony..."

zip -r "${PROJECT_NAME}.zip" \
    templates \
    assets \
    src/Controller \
    src/Form \
    src/Entity \
    config/packages \
    composer.json \
    symfony.lock \
    package.json \
    webpack.config.js \
    2>/dev/null \
    -x "*.git*" \
    -x "*.env*" \
    -x "*/var/*" \
    -x "*/vendor/*" \
    -x "*/node_modules/*"

echo "Archive créée : ${PROJECT_NAME}.zip"