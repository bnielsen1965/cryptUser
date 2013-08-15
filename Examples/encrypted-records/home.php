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

// create the data source to hold the record entries
$recordDatasource = new CryptJSONSource(USER_DATA_FILE_PATH . $username . '.json');


// save record
if (!empty($_POST['submit']) && $_POST['submit'] == 'save_record') {
	// read current records
	$currentRecords = $recordDatasource->readJSONFile();
	
	// add new encrypted record to the list
	$currentRecords[] = array('title' => $_POST['title'], 'record' => $theUser->encryptPackage($_POST['record']));
	
	// save updated list
	$recordDatasource->writeJSONFile($currentRecords);
}


// delete record request
if (isset($_GET['delete'])) {
	// read current records
	$currentRecords = $recordDatasource->readJSONFile();
	
	// unset the specified record
	unset($currentRecords[$_GET['delete']]);
	
	// save updated list
	$recordDatasource->writeJSONFile($currentRecords);
}


// change password request
if (!empty($_POST['submit']) && $_POST['submit'] == 'change_password') {
	// change user password and re-encrypt records using callback
	if ($theUser->changePassword($_POST['password'], "changePasswordCallback")) {
		// password change successful, save user
		$theUser->saveUser();
		
		// save the changed user in the session
		$_SESSION['user'] = serialize($theUser);
	}
	else {
		$errorMessage = 'Password change failed!';
	}
}


// Callback function for password change to re-encrypt records
function changePasswordCallback($oldCryptUser, $newCryptUser) {
	// use the record data source from the page script
	global $recordDatasource;
	
	// read current records
	$currentRecords = $recordDatasource->readJSONFile();
	
	// create new set of records
	$newRecords = array();
	foreach ($currentRecords as $record) {
		// decrypt the old record
		$recordString = $oldCryptUser->decryptPackage($record['record']);
		
		// re-encrypt with the new user key
		$newRecords[] = array('title' => $record['title'], 'record' => $newCryptUser->encryptPackage($recordString));
	}
	
	// save the new set of records
	$recordDatasource->writeJSONFile($newRecords);
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
		</style>
    </head>
    <body>
		<div>
			Hello <?php echo $username; ?>
			| <a href="index.php">[Log Out]</a>
			<?php if ($theUser->isAdmin()) { ?>
			| <a href="admin.php">[Admin]</a>
			<?php } ?>
		</div>
		
		<?php if (!empty($errorMessage)) { ?>
		<br>
		<div style="color:red;"><?php echo $errorMessage; ?></div>
		<?php } ?>
		
		<?php if (isset($_GET['decrypt'])) { ?>
		<div>
			<h3>Decrypted Record</h3>
			<?php
			// read current records
			$currentRecords = $recordDatasource->readJSONFile();
			
			if ($currentRecords[$_GET['decrypt']]) {
				$record = $currentRecords[$_GET['decrypt']];
				
				echo '<table>';
				echo '<tr><th>Title:</th><td>' . $record['title'] . '</td></tr>';
				echo '<tr><th>Decrypted</th><th>Encrypted</th></tr>';
				echo '<tr><td>' . $theUser->decryptPackage($record['record']) . '</td>' .
					'<td style="word-break: break-all;"><b>Envelope:</b> ' . $record['record']['envelope'] . '<br>' .
					'<b>Package:</b> ' . $record['record']['package'] . '</td></tr>';
				echo '</table>';
			}
			else {
				echo 'Record not found!';
			}
			?>
		</div>
		
		<br>
		
		<?php } ?>
		<div>
			<h3>Create New Record</h3>
			<form method="post" action="home.php">
				Record Title:<br><input type="text" name="title" /><br>
				Record Text:<br>
				<textarea name="record"></textarea><br>
				<button type="submit" name="submit" value="save_record">Save Record</button>
			</form>
		</div>
		
		<?php
		$currentRecords = $recordDatasource->readJSONFile();
		if (count($currentRecords)) {
		?>
		<br>
		
		<div>
			<h3>Record List</h3>
			<table>
				<tr><th>Title</th><th></th></tr>
				<?php
				foreach($currentRecords as $recordIndex => $record) {
					echo '<tr>' .
						'<td>' . $record['title'] . '</td>' .
						'<td>' .
							'<a href="?delete=' . $recordIndex . '">Delete</a> | ' .
							'<a href="?decrypt=' . $recordIndex . '">Decrypt</a>' .
						'</td>' .
						'</tr>';
				}
				?>
			</table>
		</div>
		<?php } ?>
		
		<br>
		
		<h3>Change Password</h3>
		<div>
			<form method="post" action="home.php">
				New Password:<br>
				<input type="text" name="password" /><br>
				<button type="submit" name="submit" value="change_password">Change Password</button>
			</form>
		</div>

	</body>
</html>