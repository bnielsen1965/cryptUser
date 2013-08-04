		<?php
		include '../../Class/CryptUser.php';
		
		// JSON file path
		$filePath = dirname(__FILE__) . '/users.json';

		// using a JSON data source
		include '../../Class/CryptJSONSource.php';
		
		// create the data source object
		$ds = new CryptJSONSource($filePath);
		
		if (!empty($_POST['submit'])) {
			switch ($_POST['submit']) {
				case 'create':
					if (!empty($_POST['username'])) {
						// create a user object for this new user
						$u = new CryptUser($_POST['username'], $_POST['password'], $ds);
						
						// create a new primary key for this user
						$u->setPrimaryKey();
						
						// set user flags as requested
						if (!empty($_POST['active'])) $u->setACLFlags(CryptUser::ACL_ACTIVE_FLAG);
						if (!empty($_POST['admin'])) $u->setACLFlags (CryptUser::ACL_ADMIN_FLAG);
						
						// save the new user
						$u->saveUser();
					}
					break;
					
					
				case 'delete':
					$ds->deleteUser($_POST['username']);
					break;
			}
		}
		?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>cryptUser JSON Example</title>
    </head>
    <body>
		<div>This crypUser example utilizes a JSON data source in the form of a text file named <i>users.json</i> in the example directory.</div>
		
		<div>List of users:</div>
		<?php
		// get a list of all usernames
		$usernames = $ds->getUsernames();
		
		if ($usernames === FALSE) {
			echo '<div>No users found!</div>' . "\n";
		}
		else {
			// display the list of users
			echo "<ul>\n";
			foreach ($usernames as $name) {
				echo "<li><ul>\n";
				echo '<li>Username: ' . $name . "</li>\n";
				
				// get this user's details
				$user = $ds->getUserByName($name);
				
				// create a user object so we can use the functions
				$u = new CryptUser($user['username'], '', $ds);
				
				// use the user object to determine flag settings
				echo '<li>' . ($u->isActive() ? 'Active' : 'Not Active') . "</li>\n";
				echo '<li>' . ($u->isAdmin() ? 'Administrator' : 'Not Administrator') . "</li>\n";
				
				echo "</ul></li>\n";
			}
			echo "</ul>\n";
		}
		?>
		
		<div>Create new user</div>
		<div>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>
				<input type="checkbox" name="active" value="1" /> Active &nbsp;&nbsp;<input type="checkbox" name="admin" value="1" /> Administrator<br>
				<button type="submit" name="submit" value="create">Create</button>
			</form>
		</div>
		
		<?php if ($usernames && count($usernames) > 0) { ?>
		<div>Delete user</div>
		<div>
			<form method="post">
				<select name="username">
					<?php
					foreach ($usernames as $name) {
						echo '<option value="' . $name . '">' . $name . "</option>\n";
					}
					?>
				</select>
				<button type="submit" name="submit" value="delete">Delete</button>
			</form>
		</div>
		<?php } ?>

    </body>
</html>
