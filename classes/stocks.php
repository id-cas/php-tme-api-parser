<?php
class TmeStocks {
	private static $instance;

	private $connection= null;

	public function __construct() {
		$this->connection = ConnectionPool::getInstance()->getConnection();
	}

	public static function getInstance($className = null) {
		if (self::$instance === null) {
			self::$instance = new TmeStocks();
		}

		return self::$instance;
	}

	/**
	 * Обновляет/добавляет позиции по стоимости для конретной позиции товара
	 */
	private function stockExists($productId, $amount, $unit){
		$query  = "SELECT id FROM tme_product_stocks WHERE product_id={$productId} AND amount={$amount} AND unit='{$unit}' LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		if($row = $result->fetch()){
			return $row['id'];
		}

		return false;
	}


	/**
	 * Обновляет/добавляет позиции по стоимости для конретной позиции товара
	 */
	private function actualizeStock($productId, $amount, $unit, $modTime){
		// Обновим дату актуальности цены для текущей позиции, если она уже есть
		if($stockId = $this->stockExists($productId, $amount, $unit)){
			try{
				$query = "UPDATE tme_product_stocks SET update_time={$modTime} WHERE id={$stockId}";
				$this->connection->queryResult($query);
				return true;
			}
			catch(Exception $e){
				dump($e->getMessage(), __METHOD__. '.log');
			}
		}

		// Добавим новую позицию для цены
		try{
			$query = "INSERT INTO tme_product_stocks (product_id, amount, unit, update_time) VALUES({$productId}, {$amount}, '{$unit}', {$modTime})";
			$this->connection->queryResult($query);
			return $this->connection->insertId();
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
		}

		return false;
	}

	/**
	 * Удаляет старые цены
	 */
	private function removeOld($productId, $modTime){
		try{
			$query = "DELETE FROM tme_product_stocks WHERE product_id={$productId} AND update_time<>{$modTime}";
			$this->connection->queryResult($query);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
		}
	}

	/**
	 * Добавляет продукту список цен
	 */
	public function update($productId, $stock){
		// Время актуальной модификации цены
		$modTime = time();

		// Актуализируем записи
		$amount = $stock['amount'];
		$unit = $stock['unit'];

		// Установим список новых цен с актуальной датой
		$stockId = $this->actualizeStock($productId, $amount, $unit, $modTime);

		if($stockId === false){
			return false;
		}

		// Удалим старые
		$this->removeOld($productId, $modTime);

		return $stockId;
	}

	/**
	 * Получить список остатков и ед. измерения
	 */
	public function getStock($productId){
		$query  = "SELECT amount, unit FROM tme_product_stocks WHERE product_id={$productId} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$res = [
			'amount' => 0,
			'unit' => null
		];

		if($row = $result->fetch()){
			$res['amount']= $row['amount'];
			$res['unit']= $row['unit'];
		}

		return $res;
	}

	/**
	 * Возвращает последнюю актуальную дату обновления
	 * @param $productId
	 * @return int
	 */
	public function getUpdateTime($productId){
		$query  = "SELECT update_time FROM tme_product_stocks WHERE product_id={$productId} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		if($row = $result->fetch()){
			return $row['update_time'];
		}

		return 0;
	}
}
