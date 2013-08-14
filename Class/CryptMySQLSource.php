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

require_once 'CryptDataSource.php';

/**
 * A MySQL based data source object for the CryptUsers.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
class CryptMySQLSource implements CryptDataSource {
	private $mysqli;
	private $errors;
	private $usersTable;
	
	
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
		
		if (!empty($databaseConfig['usersTable'])) $this->usersTable = $databaseConfig['usersTable'];
		else $this->usersTable = 'users';
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
	public function getUserByName($username) {
		$sql = "SELECT * FROM " . $this->usersTable . " WHERE username='" . $this->mysqli->real_escape_string($username) . "'";
		$rs = $this->mysqli->query($sql);
		if ($rs && $rs->num_rows) {
			return $rs->fetch_assoc();
		}
		
		// failed to find user
		return FALSE;
	}
	
	
	/**
	 * Get a list of usernames
	 * @return array An array of strings containing the usernames from the data source.
	 */
	public function getUsernames() {
		$sql = "SELECT * FROM " . $this->usersTable;
		$rs = $this->mysqli->query($sql);
		if ($rs && $rs->num_rows) {
			$usernames = array();
			while ($row = $rs->fetch_assoc()) {
				$usernames[] = $row['username'];
			}
			return $usernames;
		}
		
		// failed to find user
		return FALSE;
	}
	
	
	/**
	 * Save the provided user details in the data source.
	 * @param array $user An array of user elements to be saved.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function saveUser($user) {
		if ($this->getUserByName($user['username']) !== FALSE) {
			// user exists, update
			$sql = "UPDATE " . $this->usersTable . " SET " .
					"passwordHash='" . $this->mysqli->real_escape_string($user['passwordHash']) . "', " .
					"sslKey='" . $this->mysqli->real_escape_string($user['sslKey']) . "', " .
					"flags='" . $this->mysqli->real_escape_string($user['flags']) . "' " .
					"WHERE username='" . $this->mysqli->real_escape_string($user['username']) . "'";
		}
		else {
			// user does not exist, insert
			$sql = "INSERT INTO " . $this->usersTable . "(username, passwordHash, sslKey, flags) VALUES (" .
				"'" . $this->mysqli->real_escape_string($user['username']) . "', " .
				"'" . $this->mysqli->real_escape_string($user['passwordHash']) . "', " .
				"'" . $this->mysqli->real_escape_string($user['sslKey']) . "', " .
				"'" . $this->mysqli->real_escape_string($user['flags']) . "'" .
				")";
		}
		
		return $this->mysqli->query($sql);
	}
	
	
	/**
	 * Delete the specified user from the data source.
	 * @param string $username The username of the user to delete.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function deleteUser($username) {
		$sql = "DELETE FROM " . $this->usersTable . " WHERE username='" . $this->mysqli->real_escape_string($username) . "'";
		return $this->mysqli->query($sql);
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
	 * @return string SQL statement to create user table.
	 */
	public function getCreateUserTableSQL() {
		return "CREATE TABLE `" . $this->usersTable . "` (" . 
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
	public function createUsersTable($createSQL = NULL) {
		if ($this->mysqli->ping()) {
			if (empty($createSQL)) $createSQL = $this->getCreateUserTableSQL();
			return $this->mysqli->query($createSQL);
		}
		
		return FALSE;
	}
}

?>
