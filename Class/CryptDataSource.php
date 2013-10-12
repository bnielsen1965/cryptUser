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
 * The data source interface used to define functions required by any data source.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
interface CryptDataSource {
	/**
	 * Get the source type. This function should return a string representing
	 * the type of data source, i.e. 'JSON' or 'MySQL'.
	 * 
	 * @return string The data source type.
	 */
	public function getSourceType();
	
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
	 * Get a list of usernames 
	 * @return array An array of strings containing the usernames from the data source.
	 */
	public function getUsernames();
	
	/**
	 * Save the provided user details in the data source.
	 * @param array $user An array of user elements to be saved.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function saveUser($user);
	
	/**
	 * Delete the specified user from the data source.
	 * @param string $username The username of the user to delete.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function deleteUser($username);

}

