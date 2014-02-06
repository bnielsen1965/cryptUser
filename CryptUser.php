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

namespace BSN\CryptUser;
use Exception;

require_once 'SSLKey.php';

/**
 * CryptUser object.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
class CryptUser {
	/**
	 * Class constants
	 */
	const ACL_ADMIN_FLAG = 1;
	const ACL_ACTIVE_FLAG = 2;
	const ACL_ALL_FLAGS = 3; // OR all flags together
	
	
	/**
	 * Class properties
	 */
	private $username;
	private $password;
	private $passwordHash; // hashed password
	private $authenticated; // user authenticated flag
	private $primaryKey; // SSLKey instance
	private $sslKey; // PEM formatted private key and certificate
	private $flags; // user flag settings
	private $dataSource;
	
	
	/**
	 * Object constructor
	 * @param string username The username for this user.
	 * @param string $password The password for this user.
	 * @param string $dataSource An object to provide the user data.
	 */
	function __construct($username, $password, $dataSource = NULL) {
		$this->username = $username;
		$this->password = $password;
		$this->dataSource = $dataSource;
		$this->clearAllACLFlags();
		$this->sslKey = '';

		if (!empty($this->dataSource)) $this->loadUser();
	}
	
	
	/**
	 * Load this user defined by this object from the data source
	 * @return boolean TRUE on success, FALSE on failure
	 */
	private function loadUser() {
		// retrieve user's data from the data source
		$userData = $this->dataSource->getUserByName($this->username);
		
		// if user data returned then process
		if ($userData) {
			// use data values for this user
			$this->username = $userData['username'];
			$this->passwordHash = $userData['passwordHash'];
			$this->flags = $userData['flags'];
			$this->sslKey = $userData['sslKey'];

			// authenticate user by checking data source password hash with a hash of the provided password
			if ($this->passwordHash == $this->hashPassword($this->password, $this->passwordHash)) {
				// authentication passed
				$this->authenticated = TRUE;
			}
			else {
				// authentication failed
				$this->authenticated = FALSE;
			}
            
            // get key parts from user data
            $pkey = SSLKey::parsePrivateKey($this->sslKey);
            $cert = SSLKey::parseCertificate($this->sslKey);
            
            // set the primary SSLKey for this user if we have key parts
            if (!empty($pkey) || !empty($cert)) {
                $this->setPrimaryKey($pkey, $cert);
            }
			
			// load successful
			return TRUE;
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
			'sslKey' => $this->sslKey,
			'flags' => $this->flags
		));
	}
	
	
	/**
	 * Complete the steps to establish this as a new user in the data source.
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function newUser() {
		// if user does not exist then create
		if ($this->dataSource->getUserByName($this->username) === FALSE) {
			// use the change password function to set the password hash and create a primary key
			$this->changePassword($this->password);

			// save the new user
			$this->saveUser();

			return TRUE;
		}
		else {
			// user already exists
			return FALSE;
		}
	}
	
	
	/**
	 * Get the username string for this user.
	 * @return string The username value.
	 */
	public function getUsername() {
		return $this->username;
	}
	
	
	/**
	 * Set the primary SSLKey to use with this user.
	 * @param string $key Optional PEM encoded private key to use in generating the SSLKey.
	 * @param string $certificate Optional PEM encoded certificate to use in generating the SSLKey.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function setPrimaryKey($key = NULL, $certificate = NULL) {
		// create primary key
		$this->primaryKey = new SSLKey($this->password, $key, $certificate);
		
		// if the provided key or certificate were empty then use the new values from the new primary key
		if (empty($key) || empty($certificate)) $this->sslKey = $this->primaryKey->getPrivateKey() . $this->primaryKey->getCertificate();
		
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
	 * Set all possible ACL flags on this user.
	 */
	public function setAllACLFlags() {
		$this->flags = CryptUser::ACL_ALL_FLAGS;
	}
	
	
	/**
	 * Clear all possible ACL flags on this user.
	 */
	public function clearAllACLFlags() {
		$this->flags = 0;
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
		return $this->isACLFlagSet(CryptUser::ACL_ADMIN_FLAG);
	}
	
	
	/**
	 * Determine if this user account is active.
	 * @return boolean TRUE if active, FALSE if not.
	 */
	public function isActive() {
		// check flags against active mask
		return $this->isACLFlagSet(CryptUser::ACL_ACTIVE_FLAG);
	}
	
	
	/**
	 * Check if this user is authenticated.
	 * @return boolean TRUE if authenticated, FALSE if not.
	 */
	public function isAuthenticated() {
		return ($this->authenticated ? TRUE : FALSE);
	}
	
	
	/**
	 * Change the user's password and the SSL Key elements that rely on the password.
	 * @param string $password The new password to use.
	 * @param string $callback Optional callback function used by the application for
	 * post password change maintenance like re-encryption of data. The callback must
	 * accept two CryptUser objects, the first object is the CryptUser with the new
	 * password and encryption key, the second object is the Cryptuser with the old
	 * password and encryption key.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function changePassword($password, $callback = NULL) {
		// if a callback is provided then prepare to use callback
		if (!empty($callback)) {
			// if this user is not authenticated then decryption will not be possible so we fail
			if (!$this->isAuthenticated()) return FALSE;
			
			// create a clone of this user to be sent to callback
			$oldCryptUser = clone $this;
		}
		
		$this->password = $password;
		$this->passwordHash = $this->hashPassword($password);
		
		// set a new primary key
		$this->setPrimaryKey();
		
		// set the sslKey value using the new primary key
		$this->sslKey = $this->primaryKey->getPrivateKey() . $this->primaryKey->getCertificate();
		
		// if a callback is provided then call now with the old and this new user
		if (!empty($callback)) call_user_func($callback, $oldCryptUser, $this);
		
		// password change complete
		return TRUE;
	}
	
	
	
	
	
	/**
	 * Encrypt a package using the primary SSL key pair.
	 * @param mixed $package The package to encrypt.
	 * @return array An array containing the envelope and encrypted package or NULL if an error occurs.
	 */
	public function encryptPackage($package) {
		if ($this->primaryKey) {
			// encrypt the package
			$encryptedPackage = $this->primaryKey->encryptPackage($package);
			
			if ($encryptedPackage) {
				// use base 64 encoding to make strings safe for various storage mechanisms
				$encryptedPackage['envelope'] = base64_encode($encryptedPackage['envelope']);
				$encryptedPackage['package'] = base64_encode($encryptedPackage['package']);
				
				return $encryptedPackage;
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Decrypt the sealed package using the primary SSL key pair.
	 * @param mixed $package An associative array containing 'package' and 'envelope'.
	 * @return mixed The decrypted package or FALSE if there was an error.
	 */
	public function decryptPackage($package) {
		if ($this->primaryKey) {
			return $this->primaryKey->decryptPackage(base64_decode($package['package']), base64_decode($package['envelope']));
		}
		
		return FALSE;
	}


	
	/**
	 * Get the data source used for this user.
	 * @return object The data source object for this user.
	 */
	public function getDatasource() {
		return $this->dataSource;
	}
        
        
        /*
         * Get SSL Key error messages
         * @return array The array of strings containing any SSL Key error messages.
         */
        public function getKeyErrors() {
            return $this->primaryKey->getErrors();
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

