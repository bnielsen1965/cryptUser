<?php

include_once 'Class/SSLKey.php';

$phrase = SSLKey::makePhrase(4);

$key = new SSLKey($phrase);

$string = "The quick brown fox.";

$eString = $key->encryptPackage($string);

if ($eString === NULL) {
	echo "Encryption failed!<br>\n";
	print_r($key->getErrors());
	echo "<br>\n";
}

$dString = $key->decryptPackage($eString['package'], $eString['envelope']);

if ($dString === NULL) {
	echo "Decryption failed!<br>\n";
	print_r($key->getErrors());
	echo "<br>\n";
}
else {
	echo 'decrypted:' . $dString . ":<br>\n";
}

$key2 = new SSLKey($phrase, $key->getPrivateKey(), $key->getCertificate());
$eString = $key2->encryptPackage($string);

if ($eString === NULL) {
	echo "Encryption failed!<br>\n";
	print_r($key2->getErrors());
	echo "<br>\n";
}

$dString = $key2->decryptPackage($eString['package'], $eString['envelope']);

if ($dString === NULL) {
	echo "Decryption failed!<br>\n";
	print_r($key2->getErrors());
	echo "<br>\n";
}
else {
	echo 'decrypted:' . $dString . ":<br>\n";
}




echo "Private Key<br>\n" . $key->getPrivateKey() . "<br><br>\n";

echo "Certificate<br>\n" . $key->getCertificate() . "<br><br>\n";

$cert = $key->getFullKey();

echo 'phrase:' . SSLKey::parsePhrase($cert) . ":<br>\n";

echo 'cert:' . SSLKey::parseCert($cert) . ":<br>\n";

echo 'pKey:' . SSLKey::parsePrivateKey($cert) . ":<br>\n";

?>
