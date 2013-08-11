<?php


// JSON file path
$filePath = dirname(__FILE__) . '/users.json';

// using a JSON data source
include '../Class/CryptJSONSource.php';
$ds = new CryptJSONSource($filePath);


/*
$databaseConfig = array(
	'host' => 'localhost',
	'username' => 'mycrypt',
	'password' => 'mycrypt',
	'database' => 'mycrypt',
	'usersTable' => 'testTable'
);

include '../Class/CryptMySQLSource.php';
$ds = new CryptMySQLSource($databaseConfig);
*/

// user classes
include '../Class/CryptUser.php';
$u = new CryptUser('bryan', 'blah', $ds);

//$ds->createUsersTable();

$u->setPrimaryKey();
$u->setACLFlags(CryptUser::ACL_ADMIN_FLAG | CryptUser::ACL_ACTIVE_FLAG);
$u->saveUser();


$u->changePassword('blah2');
$u->saveUser();

$u2 = new CryptUser('bryan', 'blah2');

$string = "The quick brown fox.";

$eString = $u2->encryptPackage($string);

if ($eString) {
	echo 'Encoded Package:' . base64_encode($eString['package']) . "<br>\n";
	echo 'Encoded Envelope:' . base64_encode($eString['envelope']) . "<br>\n";
}



$dString = $u2->decryptPackage($eString['package'], $eString['envelope']);

if ($dString) {
	echo 'Decrypted:' . $dString . ":<br>\n";
}



?>
