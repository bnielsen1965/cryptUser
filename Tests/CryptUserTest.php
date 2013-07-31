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

require_once 'Class/CryptUser.php';

/**
 * Description of CryptUserTest
 *
 * @author burnin
 */
class CryptUserTest extends PHPUnit_Framework_TestCase {
	/**
	 * Test bestSalt function
	 */
	public function testBestSalt() {
		$salt = CryptUser::bestSalt();
		$this->assertGreaterThan(0, strlen($salt), 'Best salt string length.');
	}
	
	
	/**
	 * Test hashPassword function
	 */
	public function testHashPassword() {
		$salt = CryptUser::bestSalt();
		$password = 'testPassword';
		
		$hash = CryptUser::hashPassword($password, $salt);
		$this->assertEquals($hash, CryptUser::hashPassword($password, $hash), 'Hash passwords equal.');
	}

}

?>
