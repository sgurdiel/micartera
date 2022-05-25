<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

//Prepare test database for testing
if (
    isset($_SERVER['argv']) 
    && (
        in_array('--testsuite=integration', array_values($_SERVER['argv']))
        || in_array('--testsuite=application', array_values($_SERVER['argv']))
        || in_array('--testsuite=all', array_values($_SERVER['argv']))
        )
) {
    require dirname(__DIR__).'/tests/TestDbSetup.php';
}