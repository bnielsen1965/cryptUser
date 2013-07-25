<?php
require_once 'CryptDataSource.php';

/**
 * A JSON file based data source object.
 *
 * @author Bryan Nielsen, bnielsen1965@gmail.com
 * @copyright Copyright 2013, Bryan Nielsen
 */
class CryptJSONSource implements CryptDataSource {
	private $sourceType;
	private $filename;
	private $errors;
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
		
		$this->errors = array();
		$this->sourceType = 'JSON';
		$this->filename = $filename;
	}
	/**
	 * Get array of users that match a given name.
	 * @param string $username The username to search for in the data source.
	 * @return array|boolean An array of arrays containing user elements to create a user
	 * or FALSE if not found.
	 */
	public function getUserByName($username) {
		// read the JSON file
		$usersJSON = $this->lockedRead($this->filename);
		$users = json_decode($usersJSON, TRUE);
		
		if ($users) {
			foreach ($users as $user) {
				if ($user['username'] == $username) return $user;
			}
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
		// read the JSON file
		$usersJSON = $this->lockedRead($this->filename);
		$users = json_decode($usersJSON, TRUE);
		
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
	* Open and lock a file for both read and write operations.
	*
	* @param filename    Filename of file to open and lock.
	* @param mode    The file open mode.
	* @param createIfNotExist    Optional boolean to specify if the file should be created if it does not exist.
	* @return boolean    Open status.
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
				$this->error = "Failed to get the lock on file!";
				$this->lockedFilePointer = NULL;
				$this->lockedFilename = '';
				return FALSE;
			}
		}
	}
	
	
	/**
	* Read file with lock.
	*
	* @param filename    Filename of file to read.
	* @param createIfNotExist    Optional boolean to specify if the file should be created if it does not exist.
	* @return string or boolean    Returns file contents in a string or FALSE on failure.
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
			$this->error = "Failed to get the lock on file!";
			return FALSE;
		}
	}
	
	
	/**
	* Write file with lock
	*
	* @param message    Message string
	* @return boolean    FALSE if write fails
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
			$this->error = "Failed to get the lock on file!";
			return FALSE;
		}
	}
	
	
	/**
	* Get the current object error messages.
	*
	* @return array The current error messages.
	*/
	public function getErrors() {
		return $this->errors;
	}
	
}

?>
