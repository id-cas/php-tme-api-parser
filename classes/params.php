<?php
class TmeParams {
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


	public function getProductParamsId($productId){
		$query = "SELECT params_id FROM tme_products WHERE id={$productId}";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$row = $result->fetch();
		if(isset($row['params_id'])){
			return $row['params_id'];
		}

		return null;
	}



	/** ПАРАМЕТРЫ */
	private function getParams(){
		$query  = "SELECT id, name FROM tme_parameters";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$data = [];
		while($row = $result->fetch()){
			$data[] = $row;
		}

		return $data;
	}

	private function getParamById($id){
		$query  = "SELECT name FROM tme_parameters WHERE id={$id} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);
		$row = $result->fetch();

		return $result->length() ? $row : false;
	}

	private function addParam($id, $name){
		try{
			// Проверим наличие записи
			$item = $this->getParamById($id);

			if($item === false){
				$query = "INSERT INTO tme_parameters (id, name) VALUES({$id}, '{$name}')";
				$this->connection->queryResult($query);
			}
			else {
				if($name !== $item['name']){
					$query = "UPDATE tme_parameters SET name={$name} WHERE id={$id}";
					$this->connection->queryResult($query);
				}
			}
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
		}
	}



	/** ЗНАЧЕНИЯ ПАРАМЕТРОВ */
	private function getParamValues(){
		$query  = "SELECT id, value FROM tme_parameter_values";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$data = [];
		while($row = $result->fetch()){
			$data[] = $row;
		}

		return $data;
	}

	private function getParamValueById($id){
		$query  = "SELECT value FROM tme_parameter_values WHERE id={$id} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);
		$row = $result->fetch();

		return $result->length() ? $row : false;
	}

	private function addParamValue($id, $value){
		$value = addslashes($value);

		try{
			// Проверим наличие записи
			$item = $this->getParamValueById($id);
			if($item === false){
				$query = "INSERT INTO tme_parameter_values (id, value) VALUES({$id}, '{$value}')";
				$this->connection->queryResult($query);
			}
			else {
				if($value !== $item['value']){
					$query = "UPDATE tme_parameter_values SET value='{$value}' WHERE id={$id}";
					$this->connection->queryResult($query);
				}
			}
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
		}
	}



	/** РАЗДЕЛИТЕЛЬ ПАРАМЕТРОВ */
	private function getParamValueSeparators(){
		$query  = "SELECT id FROM tme_parameter_value_separator";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		$data = [];
		while($row = $result->fetch()){
			$data[] = $row;
		}

		return $data;
	}

	private function getParamValueSeparatorById($id){
		$query  = "SELECT id FROM tme_parameter_value_separator WHERE id={$id} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);
		$row = $result->fetch();

		return $result->length() ? $row : false;
	}

	private function addParamValueSeparator($id){
		try{
			// Проверим наличие записи
			$item = $this->getParamValueSeparatorById($id);
			if($item === false){
				$query = "INSERT INTO tme_parameter_value_separator (id) VALUES({$id})";
				$this->connection->queryResult($query);
			}
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
		}
	}



	/** СПИСОК ПАРАМЕТРОВ */
	private function getParametersItemById($productId, $paramId){
		$query  = "SELECT id, param_id, value_id, value_sep_id FROM tme_product_parameters WHERE id={$productId} AND param_id={$paramId} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);
		$row = $result->fetch();

		return $result->length() ? $row : false;
	}

	/** Удаляет все параметры продукта */
	private function remove($productId){
		$query  = "DELETE FROM tme_product_parameters WHERE product_id={$productId}";
		$result = $this->connection->queryResult($query);
		return $result;
	}

	/** Добавляет новые параметры пыродукта */
	private function addParametersItem($productId, $paramsId, $valueId, $valueSepId){
		if(empty($valueSepId)){
			$valueSepId = 'NULL';
		}

		try{
			$query = "INSERT INTO tme_product_parameters (product_id, param_id, value_id, value_sep_id) VALUES({$productId}, {$paramsId}, {$valueId}, {$valueSepId})";
			$this->connection->queryResult($query);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
			return false;
		}

		return true;
	}


	/** Актуализирует дату обновления/установки параметров продукта */
	private function setParamsActualDate($productId){
		try{
			$query = "SELECT actual_date FROM tme_product_parameters_date WHERE product_id={$productId} LIMIT 1";
			$result = $this->connection->queryResult($query);

			$actualDate = time();
			if($result->length()){
				$query = "UPDATE tme_product_parameters_date SET actual_date={$actualDate} WHERE product_id={$productId}";
			}
			else {
				$query = "INSERT INTO tme_product_parameters_date (product_id, actual_date) VALUES({$productId}, {$actualDate})";
			}
			$this->connection->queryResult($query);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
			return false;
		}
	}

	/** Возвращает последнюю дату актуализации параметров продукта */
	public function getParamsActualDate($productId){
		try{
			$query = "SELECT actual_date FROM tme_product_parameters_date WHERE product_id={$productId} LIMIT 1";
			$result = $this->connection->queryResult($query);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$row = $result->fetch();
			if(isset($row['actual_date'])){
				return $row['actual_date'];
			}

			return null;
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
			return false;
		}
	}


	/** Рассчитывает хэш входящих параметров */
	private function calcParamsHash($params){
		$data = [];
		foreach($params as $param){
			$data[] = $param['ParameterId']. '-'. $param['ParameterValue'];
		}

		if(!count($data)){
			return false;
		}

		sort($data);
		$str = implode('|', $data);

		return md5($str);
	}

	/** Рассчитывает хэш входящих параметров */
	private function productParamsHash($productId){
		try{
			$query = "SELECT CONCAT(param_id, '-', value_id) AS pair FROM tme_product_parameters WHERE product_id={$productId}";
			$result = $this->connection->queryResult($query);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$data = [];
			while($row = $result->fetch()){
				$data[] = $row['pair'];
			}

			if(!count($data)){
				return false;
			}

			sort($data);
			$str = implode('|', $data);

			return md5($str);
		}
		catch(Exception $e){
			dump($e->getMessage(), __METHOD__. '.log');
			return false;
		}
	}


	/** Добавляет продукт с характеристиками: параметры, цены, склады и остатки */
	public function add($productId, $params){
		// Пример входных данных в $params
		// [
		// 	{
		// 		"ParameterId": 2,
		// 		"ParameterName": "Manufacturer",

		// 		"ValueSeparatorId": null,

		// 		"ParameterValueId": 33,
		// 		"ParameterValue": "DC COMPONENTS"
		// 	},
		//  ...

		$inputHash = $this->calcParamsHash($params);
		$dbHash = $this->productParamsHash($productId);

//		// Ничего не делаем, если параметры остелись теми же
//		if($inputHash === $dbHash){
//			return $productId;
//		}

		// Удалим старый набор параметров, если у продукта новый набор параметров
		$this->remove($productId);

		$completed = 0;
		foreach($params as $param){
			$this->addParam($param['ParameterId'], $param['ParameterName']);
			$this->addParamValue($param['ParameterValueId'], $param['ParameterValue']);

			if(isset($param['ValueSeparatorId']) && !empty($param['ValueSeparatorId']) && !is_null($param['ValueSeparatorId'])){
				$this->addParamValueSeparator($param['ValueSeparatorId']);
			}

			$res = $this->addParametersItem($productId, $param['ParameterId'], $param['ParameterValueId'], $param['ValueSeparatorId']);

			if($res !== false){
				$completed++;
			}
		}

		// Если какие-то параметры были обработаны, сделаем отметку о дате, когда произошла актуализация.
		// Эта дата пригодится для защиты от слишком частого обновления параметров.
		if($completed){
			$this->setParamsActualDate($productId);
		}


		return $completed ? $productId : false;
	}

	/**
	 * Возвращает последнюю актуальную дату обновления
	 * @param $productId
	 * @return int
	 */
	public function getUpdateTime($productId){
		$query  = "SELECT actual_date FROM tme_product_parameters_date WHERE product_id={$productId} LIMIT 1";
		$result = $this->connection->queryResult($query);
		$result->setFetchType(IQueryResult::FETCH_ASSOC);

		if($row = $result->fetch()){
			return $row['actual_date'];
		}

		return 0;
	}
}
