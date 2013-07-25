<?php
require_once 'SSLKey.php';

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SSLKeyTest
 *
 * @author burnin
 */
class SSLKeyTest extends PHPUnit_Framework_TestCase {
	/**
	 * Test makePhrase function
	 */
	public function testMakePhrase() {
		$phraseLength = 32;
		$phrase = SSLKey::makePhrase($phraseLength);
		$this->assertEquals($phraseLength, strlen($phrase), 'Make phrase string length.');
	}
	
	
	/**
	 * Verify string encryption
	 */
	public function testEncrypt() {
		$phrase = SSLKey::makePhrase(4);

		$key = new SSLKey($phrase);

		$string = "The quick brown fox.";

		$eString = $key->encryptPackage($string);

		$this->assertNotNull($eString, 'Encrypted string not null.');
	}
	
	
	/**
	 * Verify encryption returns required array elements
	 */
	public function testEncryptReturnsArray() {
		$phrase = SSLKey::makePhrase(4);

		$key = new SSLKey($phrase);

		$string = "The quick brown fox.";

		$eString = $key->encryptPackage($string);

		$this->assertTrue(isset($eString['package']) && isset($eString['envelope']), 'Encrypted string returns array with package and envelope.');
	}
	
	
	/**
	 * Verify decryption works.
	 */
	public function testDecrypt() {
		$phrase = SSLKey::makePhrase(4);

		$key = new SSLKey($phrase);

		$string = "The quick brown fox.";

		$eString = $key->encryptPackage($string);

		$dString = $key->decryptPackage($eString['package'], $eString['envelope']);
		
		$this->assertNotNull($dString, 'Decrypted string not null.');
	}
	
	
	/**
	 * Verify can parse passphrase from PEM key
	 */
	public function testParsePhrase() {
		$phrase = SSLKey::makePhrase(4);

		$key = new SSLKey($phrase);

		$string = SSLKey::parsePhrase($key->getFullKey());

		$this->assertEquals($phrase, $string, 'Parse phrase from full PEM key.');
	}
}

?>
