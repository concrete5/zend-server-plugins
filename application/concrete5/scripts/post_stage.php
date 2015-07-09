<?php
/* The script post_stage.php will be executed after the staging process ends. This will allow
 * users to perform some actions on the source tree or server before an attempt to
 * activate the app is made. For example, this will allow creating a new DB schema
 * and modifying some file or directory permissions on staged source files
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

try {

    $DIR_BASE_CORE = getenv('ZS_APPLICATION_BASE_DIR') . '/concrete';
    define('DIR_BASE', getenv('ZS_APPLICATION_BASE_DIR'));

    require $DIR_BASE_CORE . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'configure.php';
    require $DIR_BASE_CORE . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'autoload.php';
    $cms = require $DIR_BASE_CORE . '/bootstrap/start.php';

    /*
    Database::extend('install', function () use ($options) {
        return Database::getFactory()->createConnection(array(
            'host'     => getenv('ZS_DB_HOST'),
            'user'     => getenv('ZS_DB_USERNAME'),
            'password' => getenv('ZS_DB_PASSWORD'),
            'database' => getenv('ZS_DB_DATABASE')
        ));
    });


    Database::setDefaultConnection('install');
    Config::set('database.connections.install', array());*/

    $config = array();
    $config['database'] = array(
        'default-connection' => 'concrete',
        'connections' => array(
            'concrete' => array(
                'driver' => 'c5_pdo_mysql',
                'server'     => getenv('ZS_DB_HOST'),
                'username'     => getenv('ZS_DB_USERNAME'),
                'password' => getenv('ZS_DB_PASSWORD'),
                'database' => getenv('ZS_DB_DATABASE'),
                'charset' => 'utf8'
            )
        )
    );

    $installFile = fopen(getenv('ZS_APPLICATION_BASE_DIR') . '/application/config/site_install.php', 'w+');
    $renderer = new \Concrete\Core\Config\Renderer($config);

    fwrite($installFile, $renderer->render());
    fclose($installFile);

    chmod(getenv('ZS_APPLICATION_BASE_DIR') . '/application/config/site_install.php', 0644);

    $installUserConfig = fopen(getenv('ZS_APPLICATION_BASE_DIR') . '/application/config/site_install_user.php', 'w+');
    $text = '<?php
define(\'INSTALL_USER_EMAIL\', \'' . getenv('ZS_ADMIN_EMAIL') . '\');
define(\'INSTALL_USER_PASSWORD\', \'' . getenv('ZS_ADMIN_PASSWORD') . '\');
define(\'INSTALL_STARTING_POINT\', \'elemental_full\');
define(\'SITE\', \'concrete5 Site\');';

    fwrite($installUserConfig, $text);
    fclose($installUserConfig);
    chmod(getenv('ZS_APPLICATION_BASE_DIR') . '/application/config/site_install_user.php', 0644);

    $link = mysqli_connect(getenv('ZS_DB_HOST'), getenv('ZS_DB_USERNAME'), getenv('ZS_DB_PASSWORD'));
    $query = "DROP DATABASE IF EXISTS " . getenv('ZS_DB_DATABASE') . ";";
    $result = mysqli_query($link, $query);

    $query = "CREATE DATABASE " . getenv('ZS_DB_DATABASE') . ";";
    $result = mysqli_query($link, $query);

    $spl = \Concrete\Core\Package\StartingPointPackage::getClass('elemental_full');
    require getenv('ZS_APPLICATION_BASE_DIR') . '/application/config/site_install.php';
    require getenv('ZS_APPLICATION_BASE_DIR') . '/application/config/site_install_user.php';


    $routines = $spl->getInstallRoutines();
    foreach ($routines as $r) {
        call_user_func(array($spl, $r->getMethod()));
    }

    exec("chmod -R g+w ". escapeshellcmd(getenv('ZS_APPLICATION_BASE_DIR')));
    exec("chmod -R 777 ". escapeshellcmd(DIR_BASE . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config'));
    exec("chmod -R 777 ". escapeshellcmd(DIR_BASE . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'files'));
    exec("chmod -R 777 ". escapeshellcmd(DIR_BASE . DIRECTORY_SEPARATOR . 'packages'));

    echo 'Post Stage Successful';
    exit(0);

} catch(Exception $e) {
    echo($e->getMessage());
    exit(1);
}
