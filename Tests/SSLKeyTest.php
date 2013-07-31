<?php
/*
* Copyright (C) 2013 Bryan Nielsen - All Rights Reserved
*
* Author: Bryan Nielsen (bnielsen1965@gmail.com)
*
*
* This file is part of cryptUser.
* cryptUser is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* 
* cryptUser is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with cryptUser.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'Class/SSLKey.php';

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
