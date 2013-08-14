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
 * A JSON file based data source object for the CryptUsers.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
class CryptJSONSource implements CryptDataSource {
	private $filename;
	private $lockedFilePointer; // pointer to the currently open locked file
	private $lockedFilename; // filename for the currently open locked file
	
	
	/**
	 * Constructor to set up the data source
	 * 
	 */
	public function __construct($filename) {
		// reset locked file values
		$this->lockedFilePointer = NULL;
		$this->lockedFilename = '';
		
		$this->filename = $filename;
		
		// read file to test file locking
		$this->readJSONFile();
	}
	
	
	/**
	 * Get source type
	 * 
	 * @return string The data source type.
	 */
	public function getSourceType() {
		return 'JSON';
	}
	
	
	/**
	 * Get array of users that match a given name.
	 * @param string $username The username to search for in the data source.
	 * @return array|boolean An array of arrays containing user elements to create a user
	 * or FALSE if not found.
	 */
	public function getUserByName($username) {
		// read the JSON file
		$users = $this->readJSONFile($this->filename);
		
		if ($users) {
			foreach ($users as $user) {
				if ($user['username'] == $username) return $user;
			}
		}
		
		// failed to find user
		return FALSE;
	}
	
	
	/**
	 * Get a list of usernames
	 * @return array An array of strings containing the usernames from the data source.
	 */
	public function getUsernames() {
		$users = $this->readJSONFile($this->filename);
		
		if ($users) {
			$usernames = array();
			
			foreach ($users as $user) {
				$usernames[] = $user['username'];
			}
			
			return $usernames;
		}
		else return FALSE;
	}
	
	
	/**
	 * Save the provided user details in the data source.
	 * @param array $user An array of user elements to be saved.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function saveUser($user) {
		// read the JSON file
		$users = $this->readJSONFile($this->filename);
		
		// determine if user exists
		if (($ui = $this->searchUsersForUser($users, $user['username'])) !== FALSE) {
			// found user index, update user
			$users[$ui] = $user;
		}
		else {
			// user not found, add to users
			$users[] = $user;
		}
		
		return $this->writeJSONFile($users, $this->filename);
	}
	
	
	/**
	 * Delete the specified user from the data source.
	 * @param string $username The username of the user to delete.
	 * @return boolean Returns TRUE on success and FALSE on failure.
	 */
	public function deleteUser($username) {
		// read the JSON file
		$users = $this->readJSONFile($this->filename);
		
		// find the index to the specified user
		$userIndex = $this->searchUsersForUser($users, $username);
		
		if ($userIndex !== FALSE) {
			// remove the user from the list
			unset($users[$userIndex]);
			
			// save the list
			return $this->writeJSONFile($users, $this->filename);
		}
		else {
			return FALSE;
		}
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
	 * Read a JSON formatted file using the file locking mechanism and return as an 
	 * associative array
	 * 
	 * @param string $filename Optional filename of the JSON file to write. If not
	 * specified then the objects filename setting will be used.
	 * @return array | boolean An associative array or FALSE on failure.
	 */
	public function readJSONFile($filename = NULL) {
		// use this source filename if not provided
		if (empty($filename)) $filename = $this->filename;
		
		// read the JSON file
		$stringJSON = $this->lockedRead($filename);
		$arrayJSON = json_decode($stringJSON, TRUE);
		return $arrayJSON;
	}
	
	
	/**
	 * Write a JSON formatted file using the file locking mechanism. The provided 
	 * data array or object element will be used for the file contents.
	 * 
	 * @param array | object $data An associative array or object to convert to JSON.
	 * @param string $filename Optional filename of the JSON file to write. If not
	 * specified then the objects filename setting will be used.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function writeJSONFile($data, $filename = NULL) {
		// use this source filename if not provided
		if (empty($filename)) $filename = $this->filename;
		print_r($data);
		
		// save the JSON data to the file
		return $this->lockedWrite($this->filename, json_encode($data));
	}

	
	/**
	* Open and lock a file for both read and write operations.
	*
	* @param filename Filename of file to open and lock.
	* @param createIfNotExist Optional boolean to specify if the file should be created if it does not exist.
	* @return boolean Open status.
	* @throws Exception If cannot get lock on file for any reason.
	*/
	public function lockFile($filename, $createIfNotExist = TRUE) {
		// check to see if opening a new file
		if ($filename != $this->lockedFilename) {
			// create file if it does not exist
			if ($createIfNotExist && !file_exists($filename)) touch($filename);
		
			// if file pointer is already open then close
			if ($this->lockedFilePointer) {
				flock($this->lockedFilePointer, LOCK_UN);
				fclose($this->lockedFilePointer);
				$this->lockedFilePointer = NULL;
				$this->lockedFilename = '';
			}
			
			// open the file
			$this->lockedFilePointer = fopen($filename, 'r+');
			
			// try to get exclusive lock on the file
			if ($this->lockedFilePointer && flock($this->lockedFilePointer, LOCK_EX)) {
				$this->lockedFilename = $filename;
			}
			else {
				// lock failed
				$this->lockedFilePointer = NULL;
				$this->lockedFilename = '';
				
				throw new Exception("Failed to get the lock on file!");
			}
		}
	}
	
	
	/**
	* Read file with lock.
	*
	* @param filename Filename of file to read.
	* @param createIfNotExist Optional boolean to specify if the file should be created if it does not exist.
	* @return string or boolean Returns file contents in a string or FALSE on failure.
	* @throws Exception If for any reason the file is not locked.
	*/
	public function lockedRead($filename, $createIfNotExist = TRUE) {
		// make sure file is opened and locked
		$this->lockFile($filename, $createIfNotExist);
		
		// if we have the file locked then read
		if( $this->lockedFilePointer ) {
			// make sure we are at beginning of file
			fseek($this->lockedFilePointer, 0);
			
			// read lines from the file
			$buffer = '';
			while ($line = fgets($this->lockedFilePointer)) {
				$buffer .= $line;
			}
			
			// return buffer
			return $buffer;
		}
		else {
			throw new Exception("Failed to get the lock on file!");
		}
	}
	
	
	/**
	* Write buffer to file with lock
	*
	* @param string $filename The filename to write.
	* @param string $buffer The buffer to write to the file.
	* @param boolean $createIfNotExist Optional, specifies if the file should be 
	* created if it does not already exist.
	* @return boolean FALSE if write fails
	* @throws Exception If for any reason the file is not locked.
	*/
	public function lockedWrite($filename, $buffer, $createIfNotExist = TRUE) {
		// make sure file is opened and locked
		$this->lockFile($filename, $createIfNotExist);
		
		// if we have the file locked then write
		if( $this->lockedFilePointer ) {
			// make sure we are at beginning of file
			fseek($this->lockedFilePointer, 0);
			
			// erase file using truncate
			ftruncate($this->lockedFilePointer, 0);
			
			// write buffer
			fwrite($this->lockedFilePointer, $buffer);
			fflush($this->lockedFilePointer);
			
			// return success
			return TRUE;
		}
		else {
			throw new Exception("Failed to get the lock on file!");
		}
	}
	
}

?>
