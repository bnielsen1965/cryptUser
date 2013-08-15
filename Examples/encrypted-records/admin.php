<?php
/**
 * This script is part of the Encrypted Records example application used to 
 * demonstrate the use of the CryptUser API.
 */

require_once 'config.php';

session_start();

// make sure session has a user
if (empty($_SESSION['user'])) {
	// no user in session
	header('Location: index.php');
	exit;
}

// retrieve user from session
$theUser = unserialize($_SESSION['user']);
$username = $theUser->getUsername();

// make sure the user is an admin
if (!$theUser->isAdmin()) {
	// not admin
	header('Location: index.php');
	exit;
}


// get the user data source from the user
$dataSource = $theUser->getDatasource();


// create new user request
if (!empty($_POST['submit']) && $_POST['submit'] == 'create_user') {
	// clean up the username
	$newUsername = $_POST['username'];
			
	if (!preg_match('/[^\da-z]/i', $newUsername)) { //!empty($newUsername)) {
		// create a user object for this new user
		$newUser = new CryptUser($newUsername, $_POST['password'], $dataSource);
		
		// set the user flags
		$newUser->setACLFlags((!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));
		
		// save as new user
		$result = $newUser->newUser();//(!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));
	
		// display a result message
		if (!$result) {
			$errorMessage = 'Failed to create new user!';
		}
	}
	else {
		$errorMessage = 'Bad username! Must be alphanumeric only!';
	}
}


// delete user request
if (!empty($_GET['delete'])) {
	// get clean username
	$deleteUsername = preg_replace('/[^\da-z]/i', '', $_GET['delete']);
	
	// try to delete the user
	if ($dataSource->deleteUser($deleteUsername)) {
		// remove any user data files
		unlink(USER_DATA_FILE_PATH . $deleteUsername . '.json');
	}
}


// set user flags request
if (!empty($_POST['submit']) && $_POST['submit'] == 'change_flags') {
	// create a user object for this new user without the user's password
	$newUser = new CryptUser($_POST['username'], '', $dataSource);

	// clear old flag settings
	$newUser->clearAllACLFlags();
	
	// set the new flags settings
	$newUser->setACLFlags((!empty($_POST['active']) ? CryptUser::ACL_ACTIVE_FLAG : 0) | (!empty($_POST['admin']) ? CryptUser::ACL_ADMIN_FLAG : 0));
	
	// save the user
	$newUser->saveUser();
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>CryptUser Encrypted Records</title>
		
		<style type="text/css">
			table, th, td {
				border: 1px solid black;
			}
			
			td {
				word-break: break-all;
			}
		</style>
	</head>
	<body>
		<div>
			Hello <?php echo $username; ?>
			| <a href="index.php">Log Out</a>
			| <a href="home.php">Home</a>
		</div>
		
		<?php if (!empty($errorMessage)) { ?>
		<br>
		<div style="color:red;"><?php echo $errorMessage; ?></div>
		<?php } ?>
		
		<h3>Create new user</h3>
		<div>
			<form method="post" action="admin.php">
				Username:<br>
				<input type="text" name="username" /><br>
				Password:<br>
				<input type="text" name="password" /><br>
				<input type="checkbox" name="active" value="1" /> Active &nbsp;&nbsp;<input type="checkbox" name="admin" value="1" /> Administrator<br>
				<button type="submit" name="submit" value="create_user">Create User</button>
			</form>
		</div>
		
		
		<br>
		
		
		<h3>Set User Flags</h3>
		<div>
			<form method="post" action="admin.php">
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

		
		<br>
		
		
		<h3>List of users</h3>
		<?php
		// get a list of all usernames for use in the following forms
		$usernames = $dataSource->getUsernames();
					
		if ($usernames) {
			// display the list of users
			echo '<table>' . "\n";
			echo '<tr><th>Username</th><th>Active</th><th>Administrator</th><th></th></tr>' . "\n";
			
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
				
				echo '<td><a href="admin.php?delete=' . $user['username'] . '">Delete</a></td>' . "\n";
				
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