<?php
/**
 * This script is part of the Encrypted Records example application used to 
 * demonstrate the use of the CryptUser API.
 */

require_once 'config.php';

session_start();

// clear any pre-existing user from the session
unset($_SESSION['user']);

// Create the users data source object for this application using the profided file.
try {
	$usersDatasource = new CryptJSONSource(USERS_FILE);
}
catch (Exception $exception) {
	// data source failure
	$errorMessage = 'Failed to create data source!';
}


// make sure there is an admin user in the data source
if ($usersDatasource) {
	$adminUser = $usersDatasource->getUserByName('admin');
	if (empty($adminUser)) {
		// create admin user
		$adminUser = new CryptUser('admin', 'admin', $usersDatasource);
		$adminUser->setACLFlags(CryptUser::ACL_ALL_FLAGS);

		try {
			$adminUser->newUser();
		}
		catch (Exception $e) {
			$errorMessage = 'Failed to create default admin user!';
		}
	}
}


// authentication request
if (!empty($_POST['submit']) && $_POST['submit'] == 'authenticate') {
	// create user with provided credentials
	$authUser = new CryptUser($_POST['username'], $_POST['password'], $usersDatasource);
	
	// check if authentication passed and user account is active
	if ($authUser->isAuthenticated() && $authUser->isActive()) {
		// store the user in session
		$_SESSION['user'] = serialize($authUser);
		
		// direct user to home page
		header('Location: home.php');
		exit;
	}
	else {
		if (!$authUser->isAuthenticated()) $errorMessage = 'Authentication Failed!';
		else $errorMessage = 'Inactive account!';
	}
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>CryptUser Encrypted Records</title>
    </head>
    <body>
		<div>
			<h1>Encrypted Records: A CryptUser example application.</h1>
			<p>
				This is a simple application used to demonstrate the use of the CryptUser
				API to develop a multi-user application that stores and retrieves
				user owned records using encryption.
			</p>
			
			<p>
				The application utilizes the JSON data source for both the users data
				store and a custom data store specifically for each user's encrypted records.
				Since the JSON data source for the user's encrypted records cannot rely on
				SQL statements as would be expected for an application using a database it
				instead uses the CryptJSONSource functions for readJSONFile() and writeJSONFile()
				to read and write the user's records to the custom JSON data file.
			</p>
			
			<p>
				The example application will automatically create an administrative
				user with the username of 'admin' and the password also set to 'admin'.
			</p>
		</div>
		
		
		<br>
		
		
		<div>
			<?php
			if (!empty($errorMessage)) echo '<div style="color:red;">' . $errorMessage . '</div>';
			?>
			<form method="post" action="index.php">
				Username:<br>
				<input type="text" name="username" /><br>
				Password:<br>
				<input type="password" name="password" /><br>
				<button type="submit" name="submit" value="authenticate">Log In</button>
			</form>
		</div>

    </body>
</html>
