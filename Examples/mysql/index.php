<?php
/**
 * This is an example PHP script to demonstrate the use of the CryptUser class
 * for user management and data encryption. The code in this script is organized 
 * into functional blocks to make it easier to see how a functional form is 
 * processed.
 */

// The user class and a datasource class are required.
require_once '../../Class/CryptUser.php';
require_once '../../Class/CryptMySQLSource.php';

// the MySQL data source will require the connection settings.
$databaseConfig = array(
	'host' => 'localhost',
	'username' => 'mycrypt',
	'password' => 'mycrypt',
	'database' => 'mycrypt',
	'usersTable' => 'testTable'
);

// Create the data source object for this application using the profided file.
$dataSource = new CryptMySQLSource($databaseConfig);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>CryptUser MySQL Example</title>
    </head>
    <body>
		<div>
			<h1>CryptUser example using MySQL data source</h1>
			<p>
				This CryptUser example utilizes a MySQL data source and will require
				that you create a database and edit the database connection settings in
				this example script.
			</p>
			
			<p>
				The example code is structured with each form providing a common function
				that would exist in an application with the associated PHP code included with
				the form so you can see how to implement the function in your application.
			</p>
			
			<p>
				NOTE: The example code does not provide user input validation! You must add
				input validation as needed for your application.
			</p>
		</div>
		
		
		<br>
		
		
		<!--
		create table block
		This form and block of code provide a simple demonstration of creating the MySQL users table.
		-->
		<h3>Create users table</h3>
		<div>
<?php
/**
 * A new application database will require that the users table be created. The MySQL
 * data source object includes a function the create this table.
 */
// process create users table form if submitted
if (!empty($_POST['submit']) && $_POST['submit'] == 'create_table') {
	// create the table
	if ($dataSource->createUsersTable()) {
		echo 'Users table created.';
	}
	else {
		echo 'Failed to create the table.';
	}
}
?>
			<form method="post">
				<button type="submit" name="submit" value="create_table">Create Users Table</button>
			</form>
		</div>
		<!-- end of create table block -->
				
		
		<br><br>
		
		
		<!--
		create user block
		This form and block of code provide a simple demonstration of user creation.
		-->
		<h3>Create new user</h3>
		<div>
<?php
/**
 * When the create user form is submitted a user instance is created using the
 * username, password and data source. At this point the user is incomplete and
 * only exists as an instance in memory.
 * 
 * After creating an instance of the user the newUser() method is used to 
 * initialize the user and save the user in the data source. Note that any
 * user flag settings can be passed to the newUser() method when creating this
 * user.
 */
// process create new user form if submitted
if (!empty($_POST['submit']) && $_POST['submit'] == 'create') {
	// create a user object for this new user
	$newUser = new CryptUser($_POST['username'], $_POST['password'], $dataSource);
	
	// set the user flags
	$newUser->setACLFlags((!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));

	// save as new user
	$result = $newUser->newUser();//(!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));
	
	// display a result message
	if ($result) {
		echo 'New user ' . $newUser->getUsername() . ' created.';
	}
	else {
		echo 'Failed to create user ' . $newUser->getUsername() . '!';
	}
}
?>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>
				<input type="checkbox" name="active" value="1" /> Active &nbsp;&nbsp;<input type="checkbox" name="admin" value="1" /> Administrator<br>
				<button type="submit" name="submit" value="create">Create</button>
			</form>
		</div>
		<!-- end of create user block -->
				
		
		<br><br>
		
		
		<!--
		delete user block
		This form and block of code provide a simple demonstration of listing users
		and the delete user process.
		-->
		<h3>Delete user</h3>
		<div>
<?php
/**
 * Deleting a user only requires removal from the data source.
 */
if (!empty($_POST['submit']) && $_POST['submit'] == 'delete') {
	$dataSource->deleteUser($_POST['username']);
}
?>
			<form method="post">
				<select name="username">
					<option value="">Select Username</option>
					<?php
					// get a list of all usernames for use in the following forms
					$usernames = $dataSource->getUsernames();
					
					// if we have usernames then list options
					if ($usernames) foreach ($usernames as $name) {
						echo '<option value="' . $name . '">' . $name . "</option>\n";
					}
					?>
				</select>
				<button type="submit" name="submit" value="delete">Delete</button>
			</form>
		</div>
		<!-- end delete user block -->
		
		
		<br><br>
		
		
		<!--
		change password block
		This form and block of code provide a simple demonstration of user password change.
		-->
		<h3>Change user password</h3>
		<div>
<?php
/**
 * When changing a user's password there are two possible paths of action that
 * can be followed. There is a simple password change where the new password is 
 * passed to the changePassword() method, or there is the callback method that will
 * execute a provided callback function to enable re-encryption of user data in
 * an application.
 * 
 * Changing the user's password also requires a new primary encryption key because 
 * the user's password is also the pass phrase for the key. Any user information 
 * that has been encrypted with their key will need to be re-encrypted with the
 * new key after the password is changed.
 * 
 * The callback argument provides the hook for the re-encryption capability by 
 * passing both the old user instance and the new user instance
 * to your applications callback function where you would decrypt user data with the
 * old key and then re-encrypt it with the new key.
 * 
 * If no re-encryption is required then the password function can be called with
 * the new password and no callback.
 */
if (!empty($_POST['submit']) && $_POST['submit'] == 'change_password') {
	// create a user object for this user, include the old password if provided
	$theUser = new CryptUser($_POST['username'], (!empty($_POST['old_password']) ? $_POST['old_password'] : ''), $dataSource);

	// if old password was not provided then do a simple password change
	if (empty($_POST['old_password'])) {
		// simple password change with no callback
		$result = $theUser->changePassword($_POST['password']);
	}
	else {
		// for testing purposes, create an encrypted package using the user's key before the password change
		$oldPackage = $theUser->encryptPackage('The quick brown fox.');
		
		// call the password change function with a callback function name
		$result = $theUser->changePassword($_POST['password'], "encryptionCallback");
		
		// warn the user if the change password with callback fails
		if ($result === FALSE) {
			// the old password must be valid to generate the old primary key
			echo 'Password change with callback failed! Verify the old password is valid.';
		}
	}

	// save the new user if changing the password is successful
	if ($result) $theUser->saveUser();
}


/**
 * This is the example callback function for the password change with callback.
 * 
 * Note that the callback must accept two arguments, an old CryptUser object and
 * a new CryptUser object. The old CryptUser can be used to access user data that
 * was encrypted with the old password and key. Once decrypted the data can then 
 * be resaved using the new CryptUser's key.
 */
function encryptionCallback($oldCryptUser, $newCryptUser) {
	// using the $oldPackage that was created before the password change
	global $oldPackage;
	
	// decrypt the old package using the old user instance
	$decryptedPackage = $oldCryptUser->decryptPackage($oldPackage);
	echo 'Old Package Decrypted: ' . $decryptedPackage . "<br>\n";
	
	// re-encrypt with the new user instance
	$newPackage = $newCryptUser->encryptPackage($decryptedPackage);
	
	// if the decryption and re-encryption passed
	if ($decryptedPackage && $newPackage) {
		echo 'Re-encryption successful.' . "<br>\n";
		
		// just to verify all is good we decrypt the new package with the new user
		$decryptedPackage = $newCryptUser->decryptPackage($newPackage);
		echo 'New Package Decrypted: ' . $decryptedPackage . "<br>\n";
	}
	else {
		echo 'Re-encryption failed!' . "<br>\n";
	}
}
?>
			<form method="post">
				<select name="username">
					<option value="">Select Username</option>
					<?php
					// get a list of all usernames for use in the following forms
					$usernames = $dataSource->getUsernames();
					
					// display username options
					if ($usernames) foreach ($usernames as $name) {
						echo '<option value="' . $name . '">' . $name . "</option>\n";
					}
					?>
				</select><br>
				New Password: <input type="text" name="password" /><br>
				Old Password: <input type="text" name="old_password" /> (required when using callback for re-encryption process)<br>
				<button type="submit" name="submit" value="change_password">Change Password</button>
			</form>
		</div>
		<!-- end of change password block -->
		
		
		<br><br>
		
		
		<!--
		change user flags block
		This form and block of code provide a simple demonstration of user flags change.
		-->
		<h3>Change user flags</h3>
		<div>
<?php
/**
 * When modifying a user's flag settings it as simple as creating a CryptUser instance
 * for the user, changing the flag settings, and finally saving the modified user.
 */
// process create new user form if submitted
if (!empty($_POST['submit']) && $_POST['submit'] == 'change_flags') {
	// create a user object for this new user without the user's password
	$newUser = new CryptUser($_POST['username'], '', $dataSource);

	// clear old flag settings
	$newUser->clearAllACLFlags();
	
	// set the new flags settings
	$newUser->setACLFlags((!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));
	
	// save the user
	$newUser->saveUser();

	echo 'User settings saved.';
}
?>
			<form method="post">
				<select name="username">
					<option value="">Select Username</option>
					<?php
					// get a list of all usernames for use in the following forms
					$usernames = $dataSource->getUsernames();
					
					// display username options
					if ($usernames) foreach ($usernames as $name) {
						echo '<option value="' . $name . '">' . $name . "</option>\n";
					}
					?>
				</select><br>
				<input type="checkbox" name="active" value="1" /> Active &nbsp;&nbsp;<input type="checkbox" name="admin" value="1" /> Administrator<br>
				<button type="submit" name="submit" value="change_flags">Change Flags</button>
			</form>
		</div>
		<!-- end of change flags block -->
		
		
		<br><br>
		
		
		<!--
		authenticate user block
		This form and block of code demonstrates user authentication checks and
		the encryption process.
		-->
		<h3>Authenticate user</h3>
		<div>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>

				<button type="submit" name="submit" value="authenticate">Authenticate</button>
			</form>
<?php
/**
 * Authenticating a user requires the creation of a user instance based on the 
 * username, password and data source follow by a check of the isAuthenticated() method.
 * 
 * When the user object is created the provided password will be checked against the
 * data source to determine if the user authentication passes and the user's encryption
 * key will be established at the same time.
 * 
 * As long as the authentication is successful the encryption and decryption functions
 * in the user instance can be utilized.
 */
if (!empty($_POST['submit']) && $_POST['submit'] == 'authenticate') {
	$authenticatedUser = new CryptUser($_POST['username'], $_POST['password'], $dataSource);
	
	if (!empty($authenticatedUser)) { ?>
			<br><br>
			
			Authenticated user tests...<br>
			Username: <?php echo $authenticatedUser->getUsername(); ?><br>
			Authenticated: <?php echo ($authenticatedUser->isAuthenticated() ? 'Yes' : 'No'); ?><br>
			<?php $inputString = 'The quick brown fox.'; ?>
			Input String: <?php echo $inputString; ?><br>
			<?php $encryptionPackage = $authenticatedUser->encryptPackage($inputString); ?>
			Encryption Envelope: <span style="word-break: break-all;"><?php echo base64_encode($encryptionPackage['envelope']); ?></span> <br>
			Encryption Package: <?php echo base64_encode($encryptionPackage['package']); ?> <br>
			<?php $outputString = $authenticatedUser->decryptPackage($encryptionPackage); ?>
			Decrypted String: <?php echo $outputString; ?><br>
			<?php } ?>
<?php
}
?>
		</div>
		<!-- end of authenticate user block -->
		
		
		<br><br>
		
		
		<!--
		list users block
		This section demonstrates some of the functions to list users and some
		user details.
		-->
		<h3>List of users</h3>
		<?php
		// get a list of all usernames for use in the following forms
		$usernames = $dataSource->getUsernames();
					
		if ($usernames) {
			// display the list of users
			echo '<table border="1">' . "\n";
			echo '<tr><th>Username</th><th>Active</th><th>Administrator</th></tr>' . "\n";
			
			// loop through all the names
			foreach ($usernames as $name) {
				echo "<tr>\n";
				echo '<td>' . $name . "</td>\n";
				
				// get this user's details
				$user = $dataSource->getUserByName($name);
				
				// create a user object so we can use the functions
				$u = new CryptUser($user['username'], '', $dataSource);
				
				// use the user object to determine flag settings
				echo '<td>' . ($u->isActive() ? 'Yes' : 'No') . "</td>\n";
				echo '<td>' . ($u->isAdmin() ? 'Yes' : 'No') . "</td>\n";
				
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
		else {
			// no users in the list
			echo '<div>No users found!</div>' . "\n";
		}

		?>

    </body>
</html>
