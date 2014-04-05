<?php
chdir(dirname(__DIR__));

if (php_sapi_name() === 'cli-server' && is_file( dirname(__FILE__) . $_SERVER['REQUEST_URI'])) {
    return false;
}

# setup autoloading
require 'vendor/autoload.php';

# starts the session, required for flash messages
session_start();

# load configs
$config = array_replace_recursive(require 'config-defaults.php', file_exists('config.php') ? require 'config.php' : array());

# wireup dependencies
$injector = new Auryn\Provider();
$injector->define('Scrudler\Scrudler', array(':config' => $config));
$injector->define('PDO', array(':dsn' => $config['db']['dsn'], ':username' => $config['db']['username'], ':password' => $config['db']['password']));

# initialize locale
# note: will not work on builtin server
putenv('LC_ALL=' . $config['locale']);
setlocale(LC_ALL, $config['locale']);
bindtextdomain('messages', 'locales');
textdomain('messages');

# handle request
\Http\Resource::handle($config['resources'], array($injector, 'make'));

