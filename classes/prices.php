<?php
class TmePrices {
	private static $instance;

	private $connection= null;

	public function __construct() {
		$this->connection = ConnectionPool::getInstance()->getConnection();
	}

	public static function getInstance($className = null) {
		if (self::$instance === null) {
			self::$instance = new TmePrices();
		}

		return self::$instance;
	}


	/**
	 * Обновляет/добавляет позиции по стоимости для конретной позиции товара
	 */
	private function priceItemExists($productId, $amount, $priceValue, $special){
		$query  = "SELECT id FROM tme_product_prices WHERE product_id={$productId} AND amount={$amount} AND price_value='{$priceValue}' AND special={$special} LIMIT 1";
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
	private function actualizePriceItem($productId, $amount, $priceValue, $special, $modTime){
		// Обновим дату актуальности цены для текущей позиции, если она уже есть
		$priceId = $this->priceItemExists($productId, $amount, $priceValue, $special);

		if($priceId !== false){
			try{
				$query = "UPDATE tme_product_prices SET update_time={$modTime} WHERE id={$priceId}";
				$this->connection->queryResult($query);
				return $priceId;
			}
			catch(Exception $e){
				dump($e->getMessage(), __METHOD__. '.log');
			}
		}

		// Добавим новую позицию для цены
		try{
			$query = "INSERT INTO tme_product_prices (product_id, amount, price_value, special, update_time) VALUES({$productId}, {$amount}, '{$priceValue}', {$special}, {$modTime})";
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
			$query = "DELETE FROM tme_product_prices WHERE product_id={$productId} AND update_time<>{$modTime}";
			$this->connection->queryResult($query);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
		}
	}

	/**
	 * Добавляет продукту список цен
	 */
	public function update($productId, $priceList){
		// Может вернуть null, также если $priceList = [], т.е. по API оказалось,
		// что цены нет у продукта (такое бывает при нулевых остатках на складе.
		if(!count($priceList)){
			return null;
		}

		// Время актуальной модификации цены
		$modTime = time();

		// Актуализируем записи
		$completed = 0;
		foreach($priceList as $price){
			$amount = $price['Amount'];
			$priceValue = $price['PriceValue'];
			$special = $price['Special'];


			// Установим список новых цен с актуальной датой
			$priceId = $this->actualizePriceItem($productId, $amount, $priceValue, intval(!!$special), $modTime);

			if($priceId !== false){
				$completed++;
			}
		}

		// Удалим старые
		$this->removeOld($productId, $modTime);

		return $completed ? $productId : false;
	}

	/**
	 * Получить список цен товара с разбивкой по количеству
	 */
	public function getPriceList($productId){
		$query  = "SELECT amount, price_value, special FROM tme_product_prices WHERE product_id={$productId} ORDER BY amount ASC";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$res = [];
		while($row = $result->fetch()){
			$res[] = $row;
		}

		// КОСТЫЛЬ: Округляет все цены до 3-го знака после запятой и удаляет дублирущийся вариант по количеству
		// >>>>>>>
		$customRes = [];
		for($i = 0; $i < count($res); $i++){
			$priceMod = ceil($res[$i]['price_value'] * 1000) / 1000;

			if($i === 0){
				$customRes[] = [
					'amount' => $res[$i]['amount'],
					'price_value' => $priceMod,
					'special' => $res[$i]['special'],
				];
				continue;
			}

			$lastItemRes = end($customRes);
			if($lastItemRes['price_value'] > $priceMod){
				$customRes[] = [
					'amount' => $res[$i]['amount'],
					'price_value' => $priceMod,
					'special' => $res[$i]['special'],
				];
			}
		}
		$res = $customRes;
		// <<<<<<<

		return $res;
	}

	/**
	 * Возвращает последнюю актуальную дату обновления
	 * @param $productId
	 * @return int
	 */
	public function getUpdateTime($productId){
		$query  = "SELECT update_time FROM tme_product_prices WHERE product_id={$productId} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		if($row = $result->fetch()){
			return $row['update_time'];
		}

		return 0;
	}
}
