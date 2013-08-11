<?php
/**
 * This is an example PHP script to demonstrate the use of the CryptUser class
 * for user management and data encryption. The code in this script is organized 
 * into functional blocks to make it easier to see how a functional form is 
 * processed.
 */

// The user class and a datasource class are required.
require_once '../../Class/CryptUser.php';
require_once '../../Class/CryptJSONSource.php';

// The JSON data source will require a source file.
$filePath = dirname(__FILE__) . '/users.json';

// Create the data source object for this application using the profided file.
$dataSource = new CryptJSONSource($filePath);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>cryptUser JSON Example</title>
    </head>
    <body>
		<div>
			<h1>CryptUser example using JSON data source</h1>
			<p>
				This CryptUser example utilizes a JSON data source in the form of a text 
				file named <i>users.json</i> in the example directory.
			</p>
			
			<p>
				The example code is structured with each form providing a common function
				that would exist in an application with the associated PHP code following the
				form so you can see how to implement the function in your application.
			</p>
		</div>
		
		
		<br><br>
		
		
		<!--
		This form and block of code provide a simple demonstration of user creation.
		-->
		<div>Create new user</div>
		<div>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>
				<input type="checkbox" name="active" value="1" /> Active &nbsp;&nbsp;<input type="checkbox" name="admin" value="1" /> Administrator<br>
				<button type="submit" name="submit" value="create">Create</button>
			</form>
			
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

	// save as new user
	$result = $newUser->newUser((!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));
	
	// display a result message
	if ($result) {
		echo 'New user ' . $newUser->getUsername() . ' created.';
	}
	else {
		echo 'Failed to create user ' . $newUser->getUsername() . '!';
	}
}
?>
		
		</div>
				
		
		<br><br>
		
		
		<!--
		This form and block of code provide a simple demonstration of user password change.
		-->
		<div>Change user password</div>
		<div>
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
			
<?php
/**
 * When changing a user's password there are two possible paths of action that
 * can be followed. There is a simple password change where the new password is 
 * passed to the changePassword() method, or there is the callback method that will
 * execute a provided callback function to enable re-encryption of user data in
 * an application.
 * 
 * 
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
		// create an encrypted package using the user's key before the password change
		$oldPackage = $theUser->encryptPackage('The quick brown fox.');
		
		// call the password change function with a callback function name
		$result = $theUser->changePassword($_POST['password'], "encryptionCallback");
		
		// warn the user if the change password with callback fails
		if ($result === FALSE) {
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
	
	// decrypt the old package
	$decryptedPackage = $oldCryptUser->decryptPackage($oldPackage);
	echo 'Old Package Decrypted: ' . $decryptedPackage . "<br>\n";
	
	// re-encrypt with the new user instance
	$newPackage = $newCryptUser->encryptPackage($decryptedPackage);
	if ($newPackage) {
		echo 'Re-encryption successful.' . "<br>\n";
		$decryptedPackage = $newCryptUser->decryptPackage($newPackage);
		echo 'New Package Decrypted: ' . $decryptedPackage . "<br>\n";
	}
	else {
		echo 'Re-encryption failed!' . "<br>\n";
	}
}

?>
		
		</div>
		
		
		<br><br>
		
		
		<div>Delete user</div>
		<div>
			<form method="post">
				<select name="username">
					<option value="">Select Username</option>
					<?php
					// get a list of all usernames for use in the following forms
					$usernames = $dataSource->getUsernames();
					
					if ($usernames) foreach ($usernames as $name) {
						echo '<option value="' . $name . '">' . $name . "</option>\n";
					}
					?>
				</select>
				<button type="submit" name="submit" value="delete">Delete</button>
			</form>
<?php
if (!empty($_POST['submit']) && $_POST['submit'] == 'delete') {
	$dataSource->deleteUser($_POST['username']);
}
?>
		
		</div>
		
		
		<br><br>
		
		
		<div>Authenticate user</div>
		<div>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>

				<button type="submit" name="submit" value="authenticate">Authenticate</button>
			</form>
<?php
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
		
		
		<br><br>
		
		
		<div>List of users:</div>
		<?php
		// get a list of all usernames for use in the following forms
		$usernames = $dataSource->getUsernames();
					
		if ($usernames === FALSE) {
			echo '<div>No users found!</div>' . "\n";
		}
		else {
			// display the list of users
			echo '<table border="1">' . "\n";
			echo '<tr><th>Username</th><th>Active</th><th>Administrator</th></tr>' . "\n";
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
		?>

    </body>
</html>
