<?php

// Ce fichier permet d'aligner explicitement le fuseau horaire PHP
// sur celui utilisé par MySQL (Africa/Lagos) en environnement dev.

if (!\function_exists('date_default_timezone_set')) {
    return;
}

// Ne pas forcer en production, uniquement en dev/test.
if ((\getenv('APP_ENV') ?: 'dev') !== 'prod') {
    \date_default_timezone_set('Africa/Lagos');
}

