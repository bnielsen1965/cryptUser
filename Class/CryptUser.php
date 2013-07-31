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

require_once 'SSLKey.php';

/**
 * User object.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
class CryptUser {
	/**
	 * Class constants
	 */
	const ACL_ADMIN_FLAG = 1;
	const ACL_ACTIVE = 2;
	
	
	/**
	 * Class properties
	 */
	private $username;
	private $password;
	private $passwordHash;
	private $authenticated;
	private $primaryKey;
	private $flags;
	private $dataSource;
	private $keyRing;
	
	
	/**
	 * Object constructor
	 * @param string username The username for this user.
	 * @param string $password The password for this user.
	 * @param string $dataSource An object to provide the user data.
	 */
	function __construct($username, $password, $dataSource) {
		$this->username = $username;
		$this->password = $password;
//		$this->passwordHash = $this->hashPassword($password);
		$this->dataSource = $dataSource;
		$this->loadUser();
	}
	
	
	/**
	 * Load this user defined by this object from the data source
	 * @return boolean TRUE on success, FALSE on failure
	 */
	private function loadUser() {
		if ($this->dataSource) {
			$userData = $this->dataSource->getUserByName($this->username);
			
			if ($userData) {
				// use data values for this user
				$this->username = $userData['username'];
				$this->passwordHash = $userData['passwordHash'];
				
				// authenticate user by checking data source password hash with a hash of the provided password
				if ($this->passwordHash == $this->hashPassword($this->password)) $this->authenticated = TRUE;
				else $this->authenticated = FALSE;
				
				// if authenticated then build the user's primary key and set flags
				if ($this->authenticated) {
					$this->primaryKey = new SSLKey(
							$this->password, 
							SSLKey::parsePrivateKey($userData['sslKey']), 
							SSLKey::parseCertificate($userData['sslKey'])
					);
					$this->flags = $userData['flags'];
				}
				
				return TRUE;
			}
		}
		
		// failed to load the user
		return FALSE;
	}
	
	
	/**
	 * Save this user to the data source
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function saveUser() {
		return $this->dataSource->saveUser(array(
			'username' => $this->username,
			'passwordHash' => $this->passwordHash,
			'sslKey' => $this->primaryKey->getPrivateKey() . $this->primaryKey->getCertificate(),
			'flags' => $this->flags
		));
	}
	
	
	/**
	 * Set the primary SSLKey to use with this user.
	 * @param string $key Optional PEM encoded private key to use in generating the SSLKey.
	 * @param string $certificate Optional PEM encoded certificate to use in generating the SSLKey.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function setPrimaryKey($key = NULL, $certificate = NULL) {
		$this->primaryKey = new SSLKey($this->password, $key, $certificate);
		return TRUE;
	}
	
	
	/**
	 * Sets the Access Control Level flags specified in the flagMask variable.
	 * @param integer $flagMask Integer containing the binary flags to turn on.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function setACLFlags($flagMask) {
		$this->flags = $this->flags | $flagMask;
		
		return TRUE;
	}

	
	/**
	 * Clears the Access Control Level flags specified in flagMask.
	 * @param integer $flagMask Integer containing the binary flags to turn off.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function clearACLFlags($flagMask) {
		$this->flags = $this->flags & ~$flagMask;
		
		return TRUE;
	}

	
	/**
	 * Test if the specified flags are set as specified in the flagMask argument.
	 * @param integer $flagmask Integer containing the binary flags to test.
	 * @return boolean TRUE if the specified flags are set, FALSE if any of the flags are not set.
	 */
	public function isACLFlagSet($flagMask) {
		return ($flagMask & $this->flags ? TRUE : FALSE);
	}
	
	
	/**
	 * Determine if this user is an admin
	 * @return boolean TRUE if admin, FALSE if not.
	 */
	public function isAdmin() {
		// check flags against admin mask
		return $this->isACLFlagSet(ACL_ADMIN_FLAG);
	}
	
	
	/**
	 * Determine if this user account is active.
	 * @return boolean TRUE if active, FALSE if not.
	 */
	public function isActive() {
		// check flags against active mask
		return $this->isACLFlagSet(ACL_ACTIVE);
	}
	
	
	/**
	 * Change the user's password and the other user elements that rely on the password.
	 * @param string $password The new password to use.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function changePassword($password) {
		$this->password = $password;
		$this->passwordHash = $this->hashPassword($password);
		
		// copy the old primary key to be used in downstream re-encryption calls
		$oldPrimaryKey = $this->primaryKey;
		
		// set a new primary key
		$this->setPrimaryKey();
		
		// handle downstream re-encryption here
		
		return TRUE;
	}
	
	
	
	
	
	/**
	 * Encrypt a package using the primary SSL key pair.
	 * @param mixed $package The package to encrypt.
	 * @return array An array containing the envelope and encrypted package or NULL if an error occurs.
	 */
	public function encryptPackage($package) {
		return $this->primaryKey->encryptPackage($package);
	}
	
	
	/**
	 * Decrypt the sealed package using the primary SSL key pair.
	 * @param string $package The package to decrypt.
	 * @param mixed $envelope The envelope for the package.
	 * @return mixed The decrypted package or FALSE if there was an error.
	 */
	public function decryptPackage($package, $envelope) {
		return $this->primaryKey->decryptPackage($package, $envelope);
	}


	
	
	/**
	 * Hash the provided password string.
	 * @param string $password The plain text user password.
	 * @param string $salt Optional salt to use with crypt. The salt must be provided
	 * when performing a password hash check to make sure the hash values come out the
	 * same. If no salt is provided then the best salt for this server will be generated.
	 * @return string The hashed password.
	 */
	public static function hashPassword($password, $salt = NULL) {
		// if password empty then return empty
		if( empty($password) ) return '';

		if( is_null($salt) ) $salt = cryptUser::bestSalt();

		return crypt($password, $salt);
	}
	
	
	/**
	 * Determine the best salt to use for the crypt function on this server.
	 * @return string The salt to be used with crypt.
	 */
	public static function bestSalt() {
		if( defined('CRYPT_SHA512') && CRYPT_SHA512 == 1 ) return '$6$' . SSLKey::makePhrase(16) . '$';
		if( defined('CRYPT_SHA256') && CRYPT_SHA256 == 1 ) return '$5$' . SSLKey::makePhrase(16) . '$';
		if( defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1 ) return '$2a$07$' . base64_encode(SSLKey::makePhrase(22)) . '$';
		if( defined('CRYPT_MD5') && CRYPT_MD5 == 1 ) return '$1$' . SSLKey::makePhrase(12) . '$';
		if( defined('CRYPT_EXT_DES') && CRYPT_EXT_DES == 1 ) return '_' . SSLKey::makePhrase(8);
		if( defined('CRYPT_STD_DES') && CRYPT_STD_DES == 1 ) return SSLKey::makePhrase(2);
		return '';
	}
}

?>
