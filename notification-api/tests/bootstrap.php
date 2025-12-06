<?php

// PHPUnit bootstrap: charge l'autoloader, prépare l'environnement de test et nettoie le cache Symfony

$autoload = __DIR__.'/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

// Environnement test par défaut
if (!getenv('APP_ENV')) {
    putenv('APP_ENV=test');
}
if (!getenv('APP_DEBUG')) {
    putenv('APP_DEBUG=0');
}
// Assure APP_VERSION pour éviter toute erreur de paramètre manquant
if (!getenv('APP_VERSION')) {
    putenv('APP_VERSION=0.1.0');
}

// Propager dans superglobaux utilisés par Symfony Runtime
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = getenv('APP_ENV');
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = getenv('APP_DEBUG');
$_SERVER['APP_VERSION'] = $_ENV['APP_VERSION'] = getenv('APP_VERSION');

// Nettoyage du cache test pour éviter l'utilisation d'un conteneur compilé avec un ancien fallback
$cacheDir = __DIR__.'/../var/cache/test';
if (is_dir($cacheDir)) {
    $it = new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    @rmdir($cacheDir);
}
