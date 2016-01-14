<?php
/* The script post_activate.php allow users to "clean up" after a new version of the application
 * is activated, for example by removing a "Down for Maintenance" message
 * set in Pre-activate.
 * The following environment variables are accessable to the script:
 *
 * - ZS_RUN_ONCE_NODE - a Boolean flag stating whether the current node is
 *   flagged to handle "Run Once" actions. In a cluster, this flag will only be set when
 *   the script is executed on once cluster member, which will allow users to write
 *   code that is only executed once per cluster for all different hook scripts. One example
 *   for such code is setting up the database schema or modifying it. In a
 *   single-server setup, this flag will always be set.
 * - ZS_WEBSERVER_TYPE - will contain a code representing the web server type
 *   ("IIS" or "APACHE")
 * - ZS_WEBSERVER_VERSION - will contain the web server version
 * - ZS_WEBSERVER_UID - will contain the web server user id
 * - ZS_WEBSERVER_GID - will contain the web server user group id
 * - ZS_PHP_VERSION - will contain the PHP version Zend Server uses
 * - ZS_APPLICATION_BASE_DIR - will contain the directory to which the deployed
 *   application is staged.
 * - ZS_CURRENT_APP_VERSION - will contain the version number of the application
 *   being installed, as it is specified in the package descriptor file
 * - ZS_PREVIOUS_APP_VERSION - will contain the previous version of the application
 *   being updated, if any. If this is a new installation, this variable will be
 *   empty. This is useful to detect update scenarios and handle upgrades / downgrades
 *   in hook scripts
 */

// Start concrete5
$cms = require __DIR__ . "/start.php";
$output = new Symfony\Component\Console\Output\ConsoleOutput();

try {
    $output->writeln('Configuring concrete5');
    \Config::save('concrete.session.handler', 'database');

    $output->writeln('Caching Doctrine Proxy Classes');

    $em = ORM::entityManager();
    $config = $em->getConfiguration();
    if (is_object($cache = $config->getMetadataCacheImpl())) {
        $cache->flushAll();
    }

    $dbm = Core::make('database/structure', array(\Database::createEntityManager()));
    $dbm->destroyProxyClasses('ApplicationSrc');
    if ($dbm->hasEntities()) {
        $dbm->generateProxyClasses();
    }

    $output->writeln('Set permissions');

    exec("chmod -R g+w " . escapeshellcmd(getenv('ZS_APPLICATION_BASE_DIR')));
    exec("chmod -R 777 " . escapeshellcmd(DIR_BASE . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config'));
    exec("chmod -R 777 " . escapeshellcmd(DIR_BASE . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'files'));
    exec("chmod -R 777 " . escapeshellcmd(DIR_BASE . DIRECTORY_SEPARATOR . 'packages'));

    $output->writeln('completed');
} catch (\Exception $e) {
    $output->writeln($e->getMessage());
    $output->getErrorOutput()->writeln($e->getMessage());

    error_log($e->getMessage());

    die(1);
}

die(0);
