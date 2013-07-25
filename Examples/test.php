<?php

// JSON file path
$filePath = dirname(__FILE__) . '/users.json';

// using a JSON data source
include '../Class/CryptJSONSource.php';
$ds = new CryptJSONSource($filePath);

// user classes
include '../Class/CryptUser.php';
$u = new CryptUser('bryan', 'blah', $ds);

/*
$u->setPrimaryKey();
$u->setACLFlags(CryptUser::ACL_ADMIN_FLAG | CryptUser::ACL_ACTIVE);
$u->saveUser();
*/

$u->changePassword('blah2');
$u->saveUser();

$string = "The quick brown fox.";

$eString = $u->encryptPackage($string);

if ($eString) {
	echo 'Encoded Package:' . base64_encode($eString['package']) . "<br>\n";
	echo 'Encoded Envelope:' . base64_encode($eString['envelope']) . "<br>\n";
}



$dString = $u->decryptPackage($eString['package'], $eString['envelope']);

if ($dString) {
	echo 'Decrypted:' . $dString . ":<br>\n";
}



?>
