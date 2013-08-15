<?php


// The user class and a datasource class are required.
require_once '../../Class/CryptUser.php';
require_once '../../Class/CryptJSONSource.php';

// define application paths
define('ROOT_PATH', dirname(__FILE__) . '/');
define('JSON_FILE_PATH', ROOT_PATH . 'json/');
define('USERS_FILE', JSON_FILE_PATH . 'users.json');
define('USER_DATA_FILE_PATH', JSON_FILE_PATH . 'data/');

?>
