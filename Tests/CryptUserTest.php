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

require_once 'CryptUser.php';

/**
 * Description of CryptUserTest
 *
 * @author burnin
 */
class CryptUserTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Test bestSalt function
	 */
	public function testBestSalt() {
		$salt = BSN\CryptUser\CryptUser::bestSalt();
		$this->assertGreaterThan(0, strlen($salt), 'Best salt string length.');
	}
	
	
	/**
	 * Test hashPassword function
	 */
	public function testHashPassword() {
		$salt = BSN\CryptUser\CryptUser::bestSalt();
		$password = 'testPassword';
		
		$hash = BSN\CryptUser\CryptUser::hashPassword($password, $salt);
		$this->assertEquals($hash, BSN\CryptUser\CryptUser::hashPassword($password, $hash), 'Hash passwords equal.');
	}
	
	
	/**
	 * Test ACL flags
	 */
	public function testACLFlags() {
		// create a dummy user with no data source
		$user = new BSN\CryptUser\CryptUser('dummy', 'dummyPassword');
		
		// set admin and active flags for the dummy
		$user->setACLFlags(BSN\CryptUser\CryptUser::ACL_ADMIN_FLAG | BSN\CryptUser\CryptUser::ACL_ACTIVE_FLAG);
		$this->assertTrue($user->isActive() && $user->isAdmin(), 'ACL flags set.');
	}
	
	
	/**
	 * Test encryption
	 */
	public function testEncryption() {
		// create a dummy user with no data source
		$user = new BSN\CryptUser\CryptUser('dummy', 'dummyPassword');
		
		// set encryption key
		$user->setPrimaryKey();
		
		$testString = 'The quick brown fox.';
		$eString = $user->encryptPackage($testString);
		$dString = $user->decryptPackage($eString);
		$this->assertEquals($testString, $dString, 'Encryption and decryption equal.');
	}

}

?>
