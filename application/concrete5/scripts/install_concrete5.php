<?php

function install_concrete5(\Symfony\Component\Console\Output\Output $output) {
    $install_command = new \Concrete\Core\Console\Command\InstallCommand();

    $params = array(
        '--db-server' => getenv('ZS_DB_HOST'),
        '--db-username' => getenv('ZS_DB_USERNAME'),
        '--db-password' => getenv('ZS_DB_PASSWORD'),
        '--db-database' => getenv('ZS_DB_DATABASE'),
        '--site' => 'concrete5 Site',
        '--starting-point' => 'elemental_full',
        '--admin-email' => getenv('ZS_ADMIN_EMAIL'),
        '--admin-password' => getenv('ZS_ADMIN_PASSWORD'),
        '--attach' => true
    );

    // Enable force attach for non-run once nodes.
    if (getenv("ZS_RUN_ONCE_NODE") != 1) {
        $output->writeln('Not the run once node.');
        $params['--force-attach'] = true;
    } else {
        $link = mysqli_connect(getenv('ZS_DB_HOST'), getenv('ZS_DB_USERNAME'), getenv('ZS_DB_PASSWORD'));
        $query = "DROP DATABASE IF EXISTS " . getenv('ZS_DB_DATABASE') . ";";
        $result = mysqli_query($link, $query);

        $query = "CREATE DATABASE " . getenv('ZS_DB_DATABASE') . ";";
        $result = mysqli_query($link, $query);
    }

    $input = new Symfony\Component\Console\Input\ArrayInput($params, $install_command->getDefinition());

    $output->writeln('Running install');

    $install_command->run($input, $output);
}
