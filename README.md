# DB

This is a very simple PDO wrapper I has been using for years in most of my projects. Hope you find it useful.



## Usage

### Configuration

This library is extended from existing PDO class. The method to initialize the PDO object is exactly the same.

> \$db = new DB( **string** $dsn, **string** \$username, **string** \$password );

```php
$config = [
	// Database server hostname
	'host'		=> '127.0.0.1',
	
	// Username
	'user'		=> 'seikan',
	
	// Password
	'password'	=> 'G3TXk4MfpH7F',
	
	// Schema name
	'database'	=> 'member_system',
];

// Include core DB library
require_once 'class.DB.php';

// Initialize DB object
$db = new DB('mysql:host='.$config['host'].';dbname='.$config['database'].';charset=utf8', $config['user'], $config['password']);
```



### Error Log

To prevent your web application from throwing ugly error message, you can save error message into a specific log file.

> \$db->saveErrorLog( **string** \$file_path );

```php
$db->saveErrorLog('/var/www/website/logs/error.log');
```



### Display Error

If you need to display MySQL.

> **array** \$db->getErrors( );

```php
$errors = $db->getErrors();

echo '<pre>';
print_r($errors);
echo '</pre>';
```



### Execute

Executes a MySQL query.

>  **int** \$db->execute( **string** \$query );

```php
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
```



### Insert

Inserts data into table.

> **int** \$db->insert( **string** \$table_name, **array** \$fields );

```php
// Insert records into `user` table
$db->insert('user', [
	'name'			=> 'Audrey Elison',
	'email_address'	=> 'audrey56@live.com',
	'password'		=> '3gh4rQm6FqHL',
	'date_created'	=> gmdate('Y-m-d H:i:s'),
]);

$db->insert('user', [
	'name'			=> 'William Rider',
	'email_address'	=> 'will.rider@gmail.com',
	'password'		=> 'g4rTK3mye6pK',
	'date_created'	=> gmdate('Y-m-d H:i:s'),
]);

// Get user ID
$userId = $db->getLastId();
```



### Update

Updates a record.

> **int** \$db->update( **string** \$table_name, **array** $fields, **string** \$conditions, **array** \$binds );

```php
// Update `user` table, modify password for user ID #1
$db->update('user', [
	'password'	=> 'iKn38Kw6dibT',
], '`user_id` = :id', [
	':id'	=> 1,
]);
```



### Select

Fetches records from table.

> **array** \$db->select( **string** \$table_name\[, **string** $where\]\[, **array** \$binds\]\[, **string** \$fields\] );

```php
// Get details for user with email address "audrey56@live.com"
$user = $db->select('user', '`email_address` = :email', [
	':email'	=> 'audrey56@live.com',
]);

if ($db->rowCount() > 0) {
	echo 'Name : ' . $user[0]['name'] . '<br />';
  	echo 'Email: ' . $user[0]['email_address'] . '<br />';
} else {
	echo 'User not found!';
}

// Get email from all users
$rows = $db->select('user', '1 = 1', [], 'email_address');

foreach ($rows as $row) {
	echo $row['email_address'] . '<br />';
}
```



### Delete

Deletes a record.

> **int** \$db->delete( **string** \$table_name, **string** \$where, **array** \$binds );

```php
// Delete user with ID #2
$db->delete('user', '`user_id` = :id', [
	':id'	=> 2,
]);
```



### Get Query

Gets the full SQL query just executed.

> **string** \$db->getQuery( );

```php
$user = $db->select('user', '`email_address` = :email', [
	':email'	=> 'audrey56@live.com',
]);

echo $db->getQuery();
```

**Output**

```sql
SELECT * FROM `user` WHERE `email_address` = 'audrey56@live.com';
```

