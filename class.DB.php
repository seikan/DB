<?php
/**
 * DB: A very simple PDO wrapper.
 *
 * Copyright (c) 2017 Sei Kan
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  2017 Sei Kan <seikan.dev@gmail.com>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @see       https://github.com/seikan/DB
 */
class DB extends PDO
{
	/**
	 * Collection of errors.
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Maps for value bindings.
	 *
	 * @var array
	 */
	private $binds = [];

	/**
	 * SQL query.
	 *
	 * @var string
	 */
	private $query = '';

	/**
	 * Path to error log.
	 *
	 * @var string
	 */
	private $errorLog = '';

	/**
	 * Row affected by query.
	 *
	 * @var int
	 */
	private $rowCount = 0;

	/**
	 * Last insert ID.
	 *
	 * @var array
	 */
	private $lastId = null;

	/**
	 * Initialize PDO object.
	 *
	 * @param string $dsn
	 * @param string $user
	 * @param string $password
	 *
	 * @throws \Exception
	 */
	public function __construct($dsn, $user, $password)
	{
		try {
			parent::__construct($dsn, $user, $password, [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
			]);
		} catch (PDOException $e) {
			throw new Exception('Unable to connect database server.');
		} catch (Exception $e) {
			throw new Exception('Unable to connect database server.');
		}
	}

	/**
	 * Set the location to save error log.
	 *
	 * @param string $errorLog
	 *
	 * @throws \Exception
	 */
	public function saveErrorLog($errorLog)
	{
		if (!file_exists($errorLog)) {
			@touch($errorLog);
		}

		if (!is_writable($errorLog)) {
			throw new Exception('"' . $errorLog . '" is not writable.');
		}
		$this->errorLog = $errorLog;
	}

	/**
	 * Get the total row affected by query.
	 *
	 * @return int
	 */
	public function rowCount()
	{
		return $this->rowCount;
	}

	/**
	 * Fetch records from database.
	 *
	 * @param string $table
	 * @param string $where
	 * @param array  $binds
	 * @param string $fields
	 *
	 * @return array|false
	 */
	public function select($table, $where = '', $binds = '', $fields = '*')
	{
		return $this->execute('SELECT ' . $fields . ' FROM `' . $table . '`' . ((!empty($where)) ? ' WHERE ' . $where : '') . ';', $binds);
	}

	/**
	 * Insert record into database.
	 *
	 * @param string $table
	 * @param array  $data
	 *
	 * @return false|int
	 */
	public function insert($table, $data)
	{
		$fields = $this->getFields($table, $data);
		$binds = [];

		foreach ($fields as $field) {
			$binds[':' . $field] = $data[$field];
		}

		return $this->execute('INSERT INTO `' . $table . '`(`' . implode('`, `', $fields) . '`) VALUES(:' . implode(', :', $fields) . ');', $binds);
	}

	/**
	 * Delete record from database.
	 *
	 * @param string $table
	 * @param string $where
	 * @param array  $binds
	 *
	 * @return false|int
	 */
	public function delete($table, $where, $binds = '')
	{
		return $this->execute('DELETE FROM `' . $table . '` WHERE ' . $where . ';', $binds);
	}

	/**
	 * Modify existing record.
	 *
	 * @param string $table
	 * @param array  $data
	 * @param string $where
	 * @param array  $binds
	 *
	 * @return false|int
	 */
	public function update($table, $data, $where, $binds = '')
	{
		$fields = $this->getFields($table, $data);
		$binds = $this->getBinds($binds);
		$query = 'UPDATE `' . $table . '` SET ';

		foreach ($fields as $field) {
			$query .= '`' . $field . '` = :new_' . $field . ', ';
			$binds[':new_' . $field] = $data[$field];
		}
		$query = rtrim($query, ', ') . ' WHERE ' . $where . ';';

		return $this->execute($query, $binds);
	}

	/**
	 * Execute SQL query.
	 *
	 * @param string $query
	 * @param array  $binds
	 *
	 * @throws \Exception
	 *
	 * @return array|false|int
	 */
	public function execute($query, $binds = '')
	{
		$this->rowCount = 0;
		$this->lastId = null;
		$this->query = trim($query);
		$this->binds = $this->getBinds($binds);

		try {
			$st = $this->prepare($this->query);
			if ($st->execute($this->binds) !== false) {
				$this->rowCount = $st->rowCount();

				if (preg_match('/^(SELECT|DESCRIBE|PRAGMA|SHOW)/i', $this->query)) {
					return $st->fetchAll(PDO::FETCH_ASSOC);
				}

				if (preg_match('/^(UPDATE|DELETE|INSERT)/i', $this->query)) {
					$this->lastId = parent::lastInsertId();

					return $st->rowCount();
				}
			}
		} catch (PDOException $e) {
			$this->errors[] = $e->getMessage();

			if (is_writable($this->errorLog)) {
				@file_put_contents($this->errorLog, implode("\t", [
					gmdate('Y-m-d H:i:s'),
					((isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : ''),
					(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
					$e->getFile() . ':' . $e->getLine(),
					$e->getMessage(),
					$this->getQuery(),
				]) . "\n", FILE_APPEND);
			}

			return false;
		}
	}

	/**
	 * Get execited SQL query.
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return str_replace(array_keys($this->binds), array_map(function ($s) {
			return "'" . str_replace('\'', '\\\'', $s) . "'";
		}, array_values($this->binds)), $this->query);
	}

	/**
	 * Get MySQL errors.
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Get the last insert ID.
	 *
	 * @return int
	 */
	public function getLastId()
	{
		return $this->lastId;
	}

	/**
	 * Get fields of the table.
	 *
	 * @param string $table
	 * @param array  $data
	 *
	 * @return array
	 */
	private function getFields($table, $data)
	{
		if (($records = $this->execute('DESCRIBE `' . $table . '`')) === false) {
			return [];
		}

		$fields = [];

		foreach ($records as $record) {
			$fields[] = $record['Field'];
		}

		return array_values(array_intersect($fields, array_keys($data)));
	}

	/**
	 * Rebuild maps of bindings.
	 *
	 * @param array $binds
	 *
	 * @return array
	 */
	private function getBinds($binds)
	{
		if (!is_array($binds)) {
			return (!empty($binds)) ? [$binds] : [];
		}

		foreach ($binds as $key => $bind) {
			if (is_array($bind)) {
				$fields = '';
				$index = 1;

				foreach ($bind as $value) {
					if (empty($value)) {
						continue;
					}

					$binds[':bind_rbNGYyL' . $index] = $value;
					$fields .= ':bind_rbNGYyL' . $index . ', ';

					++$index;
				}

				$this->query = str_replace($key, rtrim($fields, ', '), $this->query);
				unset($binds[$key]);

				$binds = array_filter($binds);
			}
		}

		return $binds;
	}
}
