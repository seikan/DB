<?php

$config = [
	// Database server hostname
	'host' => '127.0.0.1',

	// Username
	'user' => 'seikan',

	// Password
	'password' => 'G3TXk4MfpH7F',

	// Schema name
	'database' => 'member_system',
];

// Include core DB library
require_once 'class.DB.php';

// Initialize DB object
$db = new DB('mysql:host='.$config['host'].';dbname='.$config['database'].';charset=utf8', $config['user'], $config['password']);

// Save error log
$db->saveErrorLog('error.log');

// Create `user` table
$query = '
CREATE TABLE `user` (
	`user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(50) NOT NULL,
	`email_address` VARCHAR(100) NOT NULL,
	`password` VARCHAR(50) NOT NULL,
	`date_created` DATETIME NOT NULL,
	PRIMARY KEY (`user_id`)
) COLLATE="utf8_bin" ENGINE=MyISAM';

$db->execute($query);

if ($errors = $db->getErrors()) {
	echo '<pre>';
	print_r($errors);
	echo '</pre>';
	die();
}

// Insert records into `user` table
$db->insert('user', [
	'name' => 'Audrey Elison',
	'email_address' => 'audrey56@live.com',
	'password' => '3gh4rQm6FqHL',
	'date_created' => gmdate('Y-m-d H:i:s'),
]);

echo 'User #'.$db->getLastId().' inserted.<br />';

$db->insert('user', [
	'name' => 'William Rider',
	'email_address' => 'will.rider@gmail.com',
	'password' => 'g4rTK3mye6pK',
	'date_created' => gmdate('Y-m-d H:i:s'),
]);

echo 'User #'.$db->getLastId().' inserted.<br />';

// Update `user` table, modify password for user ID #1
$db->update('user', [
	'password' => 'iKn38Kw6dibT',
], '`user_id` = :id', [
	':id' => 1,
]);

// Get details for user with email address "audrey56@live.com"
$user = $db->select('user', '`email_address` = :email', [
	':email' => 'audrey56@live.com',
]);

if ($db->rowCount() > 0) {
	echo 'Name : '.$user[0]['name'].'<br />';
	echo 'Email: '.$user[0]['email_address'].'<br />';
} else {
	echo 'User not found!';
}

// Get email from all users
$rows = $db->select('user', '1 = 1', [], 'email_address');

echo 'Query: '.$db->getQuery().'<br />';

foreach ($rows as $row) {
	echo $row['email_address'].'<br />';
}

// Delete user with ID #2
/*$db->delete('user', '`user_id` = :id', [
	':id'	=> 2,
]);*/
