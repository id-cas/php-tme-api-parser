<?php

class TmeProduct {
	private static $instance;

	private $connection= null;

	public function __construct() {
		$this->connection = ConnectionPool::getInstance()->getConnection();
	}

	public static function getInstance($className = null) {
		if (self::$instance === null) {
			self::$instance = new TmeProduct();
		}

		return self::$instance;
	}

	/** Проверяет наличеие продукта */
	private function productExists($symbol){
		$query = "SELECT id FROM tme_products WHERE symbol='{$symbol}'";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$row = $result->fetch();
		if(isset($row['id'])){
			return $row['id'];
		}

		return false;
	}

	/** Добавить продукт */
	private function insertProduct($symbol){
		try{
			$query = "INSERT INTO tme_products (symbol) VALUES('{$symbol}')";
			$this->connection->queryResult($query);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
			return false;
		}

		return $this->connection->insertId();
	}

	/** Возвращает ID продукта в БД, если такой есть и/или создает новый продукт */
	public function getProduct($symbol, $existsOnly = false){
		if($productId = $this->productExists($symbol)){
			return $productId;
		}

		if($existsOnly === true){
			return false;
		}

		if($productId = $this->insertProduct($symbol)){
			return $productId;
		}

		return false;
	}

	/** Возвращает символ продукта */
	public function productSymbol($productId){
		$query = "SELECT symbol FROM tme_products WHERE id={$productId}";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$row = $result->fetch();
		if(isset($row['symbol'])){
			return $row['symbol'];
		}

		return false;
	}

	/** Связывает продукт из схемы TME с товаровм из схемы UMI */
	public function linkWithUmi($productId, $objId){
		try{
			$query = "UPDATE tme_products SET obj_id={$objId} WHERE id={$productId}";
			$this->connection->queryResult($query);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
			return false;
		}

		return true;
	}

	/** Получить obj_id по идентификатору продукта TME */
	public function getTmeId($objId){
		$query = "SELECT id FROM tme_products WHERE obj_id='{$objId}'";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$row = $result->fetch();
		if(isset($row['id'])){
			return $row['id'];
		}

		return false;
	}

	/** Получить параметры продукта */
	public function getProductParams($objId){
		$query = <<<SQL
SELECT pp.param_id, ps.name, pv.value, pp.value_sep_id
FROM tme_products p, tme_product_parameters pp, tme_parameters ps, tme_parameter_values pv
WHERE 1=1
		AND p.id=pp.product_id
		AND pp.param_id=ps.id
		AND pp.value_id=pv.id
		AND p.obj_id={$objId}
SQL;

		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$res = [];
		while($row = $result->fetch()){
			$res[] = $row;
		}

		return $res;
	}
}