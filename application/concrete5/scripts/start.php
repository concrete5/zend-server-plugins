<?php
$DIR_BASE_CORE = getenv('ZS_APPLICATION_BASE_DIR') . "/concrete";

define('DIR_BASE', dirname($DIR_BASE_CORE));
chdir(DIR_BASE);

require $DIR_BASE_CORE . '/bootstrap/configure.php';
require $DIR_BASE_CORE . '/bootstrap/autoload.php';

/** @var \Concrete\Core\Application\Application $cms */
return require $DIR_BASE_CORE . '/bootstrap/start.php';
