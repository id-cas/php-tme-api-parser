<?php

/**
 * Class TmeProducts
 *
 * Класс больше завязанный на UMI и импорт товаров
 */
class TmeProducts {
	private static $instance;

	private $noImageHash = 'no_img';
	private $imagesPath = '/images';
	private $imageChmod = '0755';

	private $connection= null;

	public function __construct() {
		$this->connection = ConnectionPool::getInstance()->getConnection();
	}

	public static function getInstance($className = null) {
		if (self::$instance === null) {
			self::$instance = new TmeProducts();
		}

		return self::$instance;
	}

	/**
	 * Работает с разделами каталога UMI
	 * @param $tmeId
	 * @return bool
	 */
	public function umiCategoryIdByTmeId($tmeId){
		$pages = new selector('pages');
		$pages->types('object-type')->name('catalog', 'category');
		$pages->where('tme_id')->equals($tmeId);
		$pages->limit(0, 1);

		$total = $pages->length();
		if(!$total) return false;

		$result = $pages->result();
		return $result[0]->getId();
	}

	/**
	 * Меняет родителя, если новый отличается от текущего
	 */
	public function umiChangeItemParent($objId, $newParentId){
		// Найдем иерархию объекта
		$element = umiHierarchy::getInstance()->getObjectInstances($objId);
		if(!($element instanceof umiHierarchyElement)){
			return false;
		}

		// Проверим, что это разные родителя
		$dbParentId = $element->getParentId();
		if($newParentId == $dbParentId){
			return false;
		}

		// Проверим, что $newParent существует
		$parentElement = umiHierarchy::getInstance()->getElement($newParentId);
		if(!($parentElement instanceof umiHierarchyElement)){
			return false;
		}

		// Сменим родителя
		$element->moveFirst($element->getId(), $newParentId);
	}

	public function umiIdByTmeSymbol($symbol, $guid){
		$objects = new selector('objects');
		$objects->types('object-type')->guid($guid);
		$objects->where('name')->equals($symbol);
		$objects->limit(0, 1);

		$total = $objects->length();
		if(!$total) return false;

		$result = $objects->result();
		return $result[0]->getId();
	}

	public function getFieldsValuesHash($data, $fields){
		$hashData = '';
		foreach($fields as $field){
			$tmeFieldName = $field['tme_name'];

			// Аккумулируем данные о значениях полей, чтобы создать контрольную сумму, по которой будем
			// верифицировать необходимость обновления данных объекта при следующих импортах
			$hashData .= $data[$tmeFieldName]. '|';
		}

		return md5($hashData);
	}


	/**
	 * Обновляет данные ТОВАРА
	 */
	public function umiUpdateItem($objId, $data, $fields){
		$object = umiObjectsCollection::getInstance()->getObject($objId);
		if(!($object instanceof umiObject)){
			return false;
		}

		// Сначала проверим хэш параметров, если для текущих он не отличается от тех, что уже есть в БД,
		// то обновлять ничего не надо
		$umiHash = $object->getValue('props_md5');
		$actualHash = $this->getFieldsValuesHash($data, $fields);
		if($umiHash === $actualHash){
			return $objId;
		}

		// Если на прошлой итерации не удалось скачать картинку
		if(preg_replace('/'. $actualHash. '/', '', $umiHash) === $this->noImageHash){
			foreach($fields as $field){
				$tmeFieldName = $field['tme_name'];
				$fieldType = $field['type'];

				if($fieldType === 'image'){
					$image = $this->downloadImage($data[$tmeFieldName]);

					if($image !== false){
						$object->setValue('photo', $image['abs_path']);
					}
					else {
						throw new Exception('Error: unable download photo for UMI object_id <'. $objId. '>');
					}
				}
			}

			// Если удалось успешно обновить картинку
			if($image !== false){
				$object->setValue('props_md5', $actualHash);
			}
			// Подтвердим изменения
			$object->commit();

			// // Костыль, чтобы преобразовать картинку-строку в объект-файл (особенность UMI)
			// $element = umiHierarchy::getInstance()->getObjectInstances($objId);
			// $photo = $element->getValue('photo');
			// $photo = new umiImageFile($photo);

			return $objId;
		}

		// Обновляем только те поля, что отличаются
		$umiModified = false;
		foreach($fields as $field){
			$umiFieldName = $field['umi_name'];
			$tmeFieldName = $field['tme_name'];
			$fieldType = $field['type'];

			$umiFieldValue = $object->getValue($umiFieldName);

			if(in_array($fieldType, ['string', 'number', 'float', 'html'])){
				if($umiFieldValue !== $data[$tmeFieldName]){
					$object->setValue($umiFieldName, $data[$tmeFieldName]);
					$umiModified = true;
				}
			}
			elseif($fieldType === 'image'){
				// Если изменилось имя картинки
				if(basename($data[$tmeFieldName]) !== basename($umiFieldValue)){
					$image = $this->downloadImage($data[$tmeFieldName]);
					if($image !== false){
						$object->setValue('photo', $image['abs_path']);
						$umiModified = true;
					}
					else {
						throw new Exception('Error: unable download photo for UMI object_id <'. $objId. '>');
					}
				}
			}
		}

		// Если были произведены хоть какие-то изменения
		if($umiModified == true){
			$object->setValue('props_md5', $actualHash);
		}

		$object->commit();
		return $objId;
	}


	/**
	 * Добавляет ТОВАР в каталог
	 */
	public function umiInsertItem($ops){
		$name 		= $ops['name'];
		$parentId 	= $ops['parent_id'];
		$guid 		= $ops['guid'];
		$isActive 	= $ops['is_active'];
		$data 		= $ops['tme_data'];
		$fields 	= $ops['fields'];


		// Создаем новый разедл каталога
		$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
		$hierarchyType = $hierarchyTypes->getTypeByName('catalog', 'object');
		$hierarchyTypeId = $hierarchyType->getId();

		// Прикрепляем его к нужному родителю
		$hierarchy = umiHierarchy::getInstance();

		// Создаем новую страницу раздела каталога
		$altName = $hierarchy->convertAltName($name);
		$newElementId = $hierarchy->addElement($parentId, $hierarchyTypeId, $name, $altName);
		if($newElementId === false) {
			throw new Exception('Error: unable to create page for symbol <'. $name. '> with parentId <'. $parentId. '>');
			return false;
		}

		//Установим права на страницу в состояние "по умолчанию"
		$permissions = permissionsCollection::getInstance();
		$permissions->setDefaultPermissions($newElementId);

		//Получим экземпляр страницы
		$newElement = $hierarchy->getElement($newElementId);
		if(!($newElement instanceof umiHierarchyElement)) {
			throw new Exception('Error: wrong page created for symbol <'. $name. '> with parentId <'. $parentId. '>');
			return false;
		}

		// Получим экземляр объекта страницы
		$newObject = $newElement->getObject();
		if(!($newObject instanceof umiObject)) {
			throw new Exception('Error: wrong page object for symbol <'. $name. '> with parentId <'. $parentId. '>');
			return false;
		}

		// Установим правильный тип данных
		$typeId = umiObjectTypesCollection::getInstance()->getTypeByGUID($guid)->getId();
		$newObject->setTypeId($typeId);
		$newObject->commit();

		/** Установим значения полей */
		$h1 = $newObject->getValue('h1');
		if(empty($h1)){
			$newObject->setValue('h1', $name);
		}

		// Поля которые будут использоваться для импорта в каталог UMI + номер сессии
		$image = false;
		foreach($fields as $field){
			$umiFieldName = $field['umi_name'];
			$tmeFieldName = $field['tme_name'];
			$fieldType = $field['type'];

			if(in_array($fieldType, ['string', 'number', 'float', 'html'])){
				$newObject->setValue($umiFieldName, $data[$tmeFieldName]);
			}
			elseif($fieldType === 'image'){
				$image = $this->downloadImage($data[$tmeFieldName]);

				if($image !== false){
					$newObject->setValue('photo', $image['abs_path']);
				}
				else {
					throw new Exception('Error: unable download photo for symbol <'. $name. '>');
				}
			}
		}

		// Итоговый хэш параметров объекта
		$hash = $this->getFieldsValuesHash($data, $fields);

		// "Испортим" md5, чтобы при следующем проходе попробовать заново закачать изображение
		if($image === false){
			$hash .= $this->noImageHash;
		}

		// Установим хэш
		$newObject->setValue('props_md5', $hash);

		// Подтвердим установленные значения
		$newObject->commit();

		// Укажем, что страница является активной
		if($isActive === true){
			$newElement->setIsActive(true);
			$newElement->commit();
		}

		return $newObject->getId();
	}

	public function setDownloadImageDir($imagesPath, $chmod){
		$this->imagesPath = $imagesPath;
		$this->imageChmod = $chmod;
	}

	public function downloadImage($cdnImagePath){
		$cdnImagePath = preg_replace('/^\/\//', 'https://', $cdnImagePath);

		if(!is_dir($this->imagesPath)){
			mkdir($this->imagesPath, $this->imageChmod, true);
		}

		$imageName = basename(preg_replace('/\?(.*)/', '', $cdnImagePath));
		$imagePath = $this->imagesPath. '/'. $imageName;

		if(!file_exists($imagePath)){
			$cdnImageData = file_get_contents($cdnImagePath);
			if($cdnImageData === false)	{
				return false;
			}
			file_put_contents($imagePath, $cdnImageData);
		}


		return [
			'abs_path' => $imagePath
		];
	}
}