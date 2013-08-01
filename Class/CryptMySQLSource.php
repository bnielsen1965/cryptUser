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
 * Description of CryptMySQLSource
 *
 * @author burnin
 */
class CryptMySQLSource implements CryptDataSource {
	private $mysqli;
	private $errors;
	
	
	/**
	 * Constructor to set up the data source
	 * 
	 */
	public function __construct($databaseConfig) {
		$this->errors = array();
		
		// connect if parameters provided
		if (!empty($databaseConfig['host']) && !empty($databaseConfig['username']) && !empty($databaseConfig['password']) && !empty($databaseConfig['database'])) {
			$this->mysqli = new mysqli($databaseConfig['host'], $databaseConfig['username'], $databaseConfig['password'], $databaseConfig['database']);
			
			if ($this->mysqli->connect_errno) {
				$this->errors[] = "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
			}
		}
	}
	
	
	/**
	 * Get source type
	 * 
	 * @return string The data source type.
	 */
	public function getSourceType() {
		return 'MySQL';
	}
	
	
	/**
	 * Get array of users that match a given name.
	 * @param string $username The username to search for in the data source.
	 * @return array|boolean An array of arrays containing user elements to create a user
	 * or FALSE if not found.
	 */
	public function getUserByName($username, $usersTable = 'users') {
		$sql = "SELECT * FROM " . $usersTable . " WHERE username='" . mysqli_real_escape_string($username) . "'";
		$rs = $this->mysqli->query($sql);
		if ($rs && $rs->num_rows) {
			return $rs->fetch_assoc();
		}
		
		// failed to find user
		return FALSE;
	}
	
	
	/**
	 * Save the provided user details in the data source.
	 * @param array $user An array of user elements to be saved.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function saveUser($user, $usersTable = 'users') {
		$sql = "INSERT INTO " . $usersTable . "";
		// determine if user exists
		if (($ui = $this->searchUsersForUser($users, $user['username'])) !== FALSE) {
			// found user index, update user
			$users[$ui] = $user;
		}
		else {
			// user not found, add to users
			$users[] = $user;
		}
		
		return $this->lockedWrite($this->filename, json_encode($users));
	}
	
	
	/**
	 * Search provided users array for the specified user.
	 * @param array $users An array of user rows to search.
	 * @return integer|boolean The array index for the user name or FALSE if not found.
	 */
	private function searchUsersForUser($users, $username) {
		if ($users) {
			foreach ($users as $ui => $user) {
				if ($user['username'] == $username) return $ui;
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Get SQL to create users table.
	 * 
	 * @param string $usersTable An optional table name for users table.
	 * @return string SQL statement to create user table.
	 */
	public function getCreateUserTableSQL($usersTable = 'users') {
		return "CREATE TABLE `" . $usersTable . "` (" . 
				"`username` VARCHAR (255), " . 
				"`passwordHash` VARCHAR (255) DEFAULT '', " . 
				"`sslKey` TEXT DEFAULT '', " . 
				"`flags` INTEGER DEFAULT 0, " . 
				"PRIMARY KEY (`username`) " . 
				") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	}
	
	
	/**
	 * Create users table.
	 * 
	 * @param string $createSQL Optional SQL statement to create the users table.
	 */
	public function createUsersTable($usersTable = 'users', $createSQL = NULL) {
		if ($this->mysqli->ping()) {
			if (empty($createSQL)) $createSQL = $this->getCreateUserTableSQL($usersTable);
			return $this->mysqli->query($createSQL);
		}
		
		return FALSE;
	}
}

?>
