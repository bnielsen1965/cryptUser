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

/**
 * Provides encryption and decryption methods using openssl.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
class SSLKey {
	
	/**
	 * Class properties
	 */
	private $passphrase; // pass phrase used to protect private key
	private $privateKey; // passphrase protected private key in PEM format
	private $certificate; // SSL certificate in PEM format
	private $errors; // error messages

	
	/**
	 * Object constructor
	 * @param string $phrase Passphrase to use with protected key.
	 * @param string $key A PEM encoded private key, NULL when creating a new key pair.
	 * @param string $cert A PEM encoded certificate, NULL when creating a new key pair.
	 * @param array $SSLParams Associative array of parameters used when creating a new 
	 * key pair. The array is a combination of the Distinquished Name and configargs 
	 * values. The array may include the following...
	 *  'ssl_key_bits' => '4096', // bit size of the encryption key
	 *	'private_key_type' => OPENSSL_KEYTYPE_RSA, // the openssl key type
	 *  'countryName' => 'US', // distinquished name country
	 *  'stateOrProvinceName' => 'Nevada', // distinquished name province
	 *  'localityName' => 'Las Vegas', // distinquished name locality
	 *  'organizationName' => 'SSLKey', // distinquished name organization
	 *  'commonName' => 'localhost', // distinquished name domain
	 *  'emailAddress' => 'admin@localhost' // distinquished name email
	 * @param array $CACert Associative array containing a certificate authority parameters
	 * used to sign a new key pair. If NULL then the new key pair will be self signed. 
	 * The array must include the following elements...
	 *	'certificate' => CA certificate in PEM format,
	 *  'privateKey' => CA private key in PEM format,
	 *	'passPhrase' => CA passphrase.
	 */
	function __construct($phrase = "", $key = NULL, $cert = NULL, $SSLParams = NULL, $CACert = NULL) {
		// initialize error array
		$this->errors = array();
		
		// if a key and cert were not passed then this is a new user, generate ssl keys
		if ($key == NULL || $cert == NULL) {
			// create a distinguished name using SSL parameters
			$dn = $this->makeDN($SSLParams);
			
			// create the configargs array
			$configArgs = $this->makeConfigArgs($SSLParams);// array();
		
			// generate a new key pair
			$keyPair = openssl_pkey_new($configArgs);

			// export the private key in PEM format and encrypt with new user password
			if (openssl_pkey_export($keyPair, $key, $phrase)) {
				// generate a certificate signing request with the defined dn identity
				if (($csr = openssl_csr_new($dn, $keyPair))) {
					if (is_null($CACert)) {
						// self sign the csr to generate the master certificate
						$rawCert = openssl_csr_sign($csr, null, $keyPair, 0);
					} else {
						// certificate authority sisgned csr to generate a user certificate
						$rawCert = openssl_csr_sign($csr, $CACert['certificate'], array($CACert['privateKey'], $CACert['passPhrase']), 0);
					}

					if ($rawCert) {
						// export certificate in PEM format
						if (!openssl_x509_export($rawCert, $cert))
							$this->errors[] = "Error exporting certificate in PEM format. ";
					}
					else $this->error[] = "Error signing certificate. ";
				}
				else $this->errors[] = "Error generating certificate signing request. ";
			}
			else $this->errors[] = "Error exporting private key. ";
		}

		$this->privateKey = $key;
		$this->passphrase = $phrase;
		$this->certificate = $cert;
	}


	/**
	 * Decrypt the sealed package using this SSL key pair.
	 * @param string $package The package to decrypt.
	 * @param mixed $envelope The envelope for the package.
	 * @param string $phrase The passphrase to use as the pass phrase for decryption.
	 * @return mixed The decrypted package or FALSE if there was an error.
	 */
	public function decryptPackage($package, $envelope, $phrase = NULL) {
		$decrypted = NULL;
		
		// use the passphrase from this instance if not provided in arguments
		if ($phrase == NULL) $phrase = $this->passphrase;
		
		// try to get the private key from the certificate
		if (($key = openssl_get_privatekey($this->privateKey, $phrase)) === FALSE) {
			$this->errors[] = 'Failed to get private key!';
			return NULL;
		}
		
		// try to decrypt the package
		if ((openssl_open($package, $decrypted, $envelope, $key)) === FALSE) {
			$this->errors[] = 'Failed to decrypt package!';
			return NULL;
		}
		
		return $decrypted;
	}

	
	/**
	 * Encrypt a package using this SSL key pair.
	 * @param mixed $package The package to encrypt.
	 * @return array An array containing the envelope and encrypted package or NULL if an error occurs.
	 */
	public function encryptPackage($package) {
		$encrypted = NULL;
		$envelope = NULL;
		
		if (is_null($package)) {
			$this->errors[] = 'No package provided to encrypt!';
			return NULL;
		}
		
		// try to get the public key from the certificate
		if (($key = openssl_get_publickey($this->certificate)) === FALSE) {
			$this->errors[] = 'Failed to get public key from certificate!';
			return NULL;
		}
		
		// try to encrypt the package
		if ((openssl_seal($package, $encrypted, $envelope, array($key))) === FALSE) {
			$this->errors[] = 'Failed to encrypt package!';
			return NULL;
		}
		
		// return the encrypted package and the envelope in an array
		return array("package" => $encrypted, "envelope" => $envelope[0]);
	}

	
	/**
	 * Parses the passed string looking for a phrase using PEM style
	 * encoding. This is a custom PEM parameter for SSLKey.
	 * @param string $str The string to parse looking for the pass phrase component.
	 * @return string The discovered pass phrase or NULL if not found.
	 */
	public static function parsePhrase($str) {
		if (preg_match('/(-----BEGIN PHRASE-----.*-----END PHRASE-----)/msU', $str, $ma, PREG_OFFSET_CAPTURE)) {
			return trim($ma[1][0]);
		}

		return NULL;
	}

	
	/**
	 * Parses the passed string looking for a certificate. 
	 * @param string $str The string to parse looking for the certificate component.
	 * @return string The discovered certificate or NULL if not found.
	 */
	public static function parseCertificate($str) {
		if (preg_match('/(-----BEGIN CERTIFICATE-----.*-----END CERTIFICATE-----)/msU', $str, $ma, PREG_OFFSET_CAPTURE)) {
			return trim($ma[1][0]);
		}

		return NULL;
	}

	
	/**
	 * Parses the passed string looking for a private key. 
	 * @param string $str The string to parse looking for the private key component.
	 * @return string The discovered private key or NULL if not found.
	 */
	public static function parsePrivateKey($str) {
		if (preg_match('/(-----BEGIN ENCRYPTED PRIVATE KEY-----.*-----END ENCRYPTED PRIVATE KEY-----)/msU', $str, $ma, PREG_OFFSET_CAPTURE)) {
			return trim($ma[1][0]);
		}

		return NULL;
	}

	
	/**
	 * Creates a random passphrase.
	 * @param integer $len The length of the generated pass phrase.
	 * @param boolean $alphnumeric Determines if the generated pass phrase includes only alphanumeric characters.
	 * @return string The generated pass phrase.
	 */
	public static function makePhrase($len = 64, $alphanumeric = FALSE) {
		// determine the character set to use
		if ($alphanumeric === TRUE) {
			$charlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		}
		else
			$charlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#&*(),.{}[];:";
		$phrase = "";
		
		// loop to create random phrase
		do {
			$phrase .= substr($charlist, mt_rand(0, strlen($charlist) - 1), 1);
		} while (--$len > 0);

		return $phrase;
	}

	
	/**
	 * Retrieve the private key from this instance.
	 */
	public function getPrivateKey() {
		return $this->privateKey;
	}

	
	/**
	 * Retrieve the certificate from this instance.
	 */
	public function getCertificate() {
		return $this->certificate;
	}
	
	
	/**
	 * Retrieve the PEM encoded key (privateKey + certificate)
	 */
	public function getKey() {
		$key = $this->getPrivateKey();
		$key .- $this->getCertificate();
		return $key;
	}
	
	
	/**
	 * Retrieve a custom PEM encoded key with all needed components for encryption and decryption.
	 */
	public function getFullKey() {
		// assemble all key components into a single PEM string.
		$key = "-----BEGIN PHRASE-----\n" . $this->passphrase . "\n-----END PHRASE-----\n\n";
		$key .= $this->getKey();
		/*
		$key .= $this->getPrivateKey();
		$key .= $this->getCertificate();
		 */
		return $key;
	}


	/**
	 * Creates a distinquished name array for SSL certificate generation.
	 * @param array $sslParams The parameters to use when generating the distinquished name.
	 */
	public function makeDN($sslParams = NULL) {
		$newName = array();
		
		// fields to be constructed and their default values
		$parameterFields = array(
			'countryName' => 'XX',
			'stateOrProvinceName' => 'XXXX',
			'localityName' => 'XXXX',
			'organizationName' => 'SSLKey',
			'commonName' => 'localhost',
			'emailAddress' => 'admin@localhost'
		);
		
		// build Distinquished Name from parameters and defaults
		foreach ($parameterFields as $field => $parameter) {
			if (isset($sslParams[$field])) $newName[$field] = $sslParams[$field];
			else $newName[$field] = $parameter;
		}
		
		return $newName;
	}
	
	
	/**
	 * Creates a configArgs array to be used when creating certificates
	 * @param array $sslParams The parameters to use when generating the configArgs.
	 */
	public function makeConfigArgs($sslParams = NULL) {
		$newConfig = array();
		
		// fields to be constructed and their default values
		$parameterFields = array(
			'private_key_bits' => 1024,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		);
		
		// build configArgs from parameters and defaults
		foreach ($parameterFields as $field => $value) {
			if (isset($SSLParams[$field])) $newConfig[$field] = $SSLParams[$field];
			else $newConfig[$field] = $value;
		}
		
		return $newConfig;
	}
	
	
	/**
	 * Get the current errors.
	 */
	public function getErrors() {
		return $this->errors;
	}

}

