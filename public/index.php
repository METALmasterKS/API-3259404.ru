<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
//ini_set('max_execution_time', 0);
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';
require './vendor/Classes/PHPExcel.php';
require './vendor/Classes/TextLangCorrect/ReflectionTypeHint.php';
require './vendor/Classes/TextLangCorrect/Text/LangCorrect.php';
require './vendor/Classes/TextLangCorrect/UTF8.php';
require './vendor/Classes/a.charset.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
