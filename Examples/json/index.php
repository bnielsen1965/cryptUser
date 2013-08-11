<?php
// using CryptUser class
include '../../Class/CryptUser.php';

// using a JSON data source
include '../../Class/CryptJSONSource.php';

// JSON file path for the data source
$filePath = dirname(__FILE__) . '/users.json';

// create the data source object
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
		
		
		<div>Create new user</div>
		<div>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>
				<input type="checkbox" name="active" value="1" /> Active &nbsp;&nbsp;<input type="checkbox" name="admin" value="1" /> Administrator<br>
				<button type="submit" name="submit" value="create">Create</button>
			</form>
			
<?php
// process create new user form if submitted
if (!empty($_POST['submit']) && $_POST['submit'] == 'create') {
	// create a user object for this new user
	$u = new CryptUser($_POST['username'], '', $dataSource);

	// change to the supplied password
	$u->changePassword($_POST['password']);

	// create a new primary key for this user
	$u->setPrimaryKey();

	// set user flags as requested
	if (!empty($_POST['active'])) $u->setACLFlags(CryptUser::ACL_ACTIVE_FLAG);
	if (!empty($_POST['admin'])) $u->setACLFlags (CryptUser::ACL_ADMIN_FLAG);

	// save the new user
	$u->saveUser();
}
?>
		
		</div>
				
		
		<br><br>
		
		
		<div>Change user password</div>
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
				</select><br>
				New Password: <input type="text" name="password" /><br>
				Old Password: <input type="text" name="old_password" /> (required when using callback for re-encryption process)<br>
				<button type="submit" name="submit" value="change_password">Change Password</button>
			</form>
			
<?php
if (!empty($_POST['submit']) && $_POST['submit'] == 'change_password') {
	// create a user object for this user, include the old password if provided
	$u = new CryptUser($_POST['username'], (!empty($_POST['old_password']) ? $_POST['old_password'] : ''), $dataSource);

	// if old password provided then change password with callback function
	if (!empty($_POST['old_password'])) {
		// call the password change function with a callback function name
		$u->changePassword($_POST['password'], "encryptionCallback");
	}
	else {
		// simple password change with no callback
		$u->changePassword($_POST['password']);
	}

	// save the new user
	$u->saveUser();
}


// encryption callback function used when password is changed and encrypted data must be re-encrypted with new key
function encryptionCallback($oldCryptUser, $newCryptUser) {
	echo ($oldCryptUser->isAuthenticated() ? 'Yes' : 'No') . "<br>\n";
	$old = $oldCryptUser->encryptPackage('The quick brown fox.');
	echo 'en: ' . print_r($old, TRUE) . "<br>\n";
	
	echo ($newCryptUser->isAuthenticated() ? 'Yes' : 'No') . "<br>\n";
	$oldd = $newCryptUser->decryptPackage($old['envelope'], $old['package']);
	echo 'dc: ' . $oldd . "<br>\n";
	
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
			<?php $outputString = $authenticatedUser->decryptPackage($encryptionPackage['package'], $encryptionPackage['envelope']); ?>
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
