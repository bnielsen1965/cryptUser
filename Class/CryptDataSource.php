<?php

/**
 * The data source interface used to define functions required by any data source.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
interface CryptDataSource {
	/**
	 * Get array of user elements that match a given name.
	 * The aray of user elements may come from any type of data source, a database
	 * table, a flat file, etc., but the returned user array must include the following
	 * elements:
	 * username, passwordHash, sslKey, flags
	 * 
	 * @param string $username The username to search for in the data source.
	 * @return array|boolean An array of arrays containing user elements to create a user
	 * or FALSE if not found.
	 */
	public function getUserByName($username);
	
	/**
	 * Save the provided user details in the data source.
	 * @param array $user An array of user elements to be saved.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function saveUser($user);
	
	/**
	* Get the current object error messages.
	* @return array The current error messages.
	*/
	public function getErrors();
}

?>
