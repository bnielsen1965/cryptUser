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
			
			if( $this->mysqli->connect_errno ) {
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
	
}

?>
