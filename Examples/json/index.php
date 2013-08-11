		<?php
		// using CryptUser class
		include '../../Class/CryptUser.php';
		
		// using a JSON data source
		include '../../Class/CryptJSONSource.php';
		
		// JSON file path for the data source
		$filePath = dirname(__FILE__) . '/users.json';

		// create the data source object
		$dataSource = new CryptJSONSource($filePath);
		
		// if form submitted then process the form
		if (!empty($_POST['submit'])) {
			// use the submit value to determine what action was requested
			switch ($_POST['submit']) {
				// create a user
				case 'create':
					if (!empty($_POST['username'])) {
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
					break;
					
				// delete a user
				case 'delete':
					$dataSource->deleteUser($_POST['username']);
					break;
				
				// authenticate a user
				case 'authenticate':
					$authenticatedUser = new CryptUser($_POST['username'], $_POST['password'], $dataSource);
					break;
			}
		}
		?>
<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>cryptUser JSON Example</title>
    </head>
    <body>
		<div>This cryptUser example utilizes a JSON data source in the form of a text file named <i>users.json</i> in the example directory.</div>
		
		<div>List of users:</div>
		<?php
		// get a list of all usernames
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
		
		<br><br>
		
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
		
		<br><br>
		
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
		
		<br><br>
		
		<div>Authenticate user</div>
		<div>
			<form method="post">
				Username: <input type="text" name="username" /><br>
				Password: <input type="text" name="password" /><br>

				<button type="submit" name="submit" value="authenticate">Authenticate</button>
			</form>
			
			<?php if (!empty($authenticatedUser)) { ?>
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
		</div>

    </body>
</html>
