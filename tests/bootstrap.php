<?php

use Symfony\Component\Dotenv\Dotenv;
use xVer\MiCartera\Ui\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$kernel = new Kernel('test', false);
$kernel->boot();

//Prepare test database for testing
$testsuite = getopt('', ['testsuite:']);
if (is_array($testsuite) && isset($testsuite['testsuite'])) {
    $testsuites = explode(',', $testsuite['testsuite']);
    if (
        in_array('integration', $testsuites)
        || in_array('application', $testsuites)
        || in_array('all', $testsuites)
    ) {
        require dirname(__DIR__).'/tests/TestDbSetup.php';
    }
}
