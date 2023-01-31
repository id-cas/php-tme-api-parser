<?php
class TmeFiles {
	private static $instance;

	private $connection= null;

	public function __construct() {
		$this->connection = ConnectionPool::getInstance()->getConnection();
	}

	public static function getInstance($className = null) {
		if (self::$instance === null) {
			self::$instance = new TmeParams();
		}

		return self::$instance;
	}
}
