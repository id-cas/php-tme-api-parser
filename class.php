<?php

/**
 * Class Tme
 * Source: https://github.com/tme-dev/TME-API
 */

//class Tme extends def_module {
class Tme {
	public function __construct(){
		$this->includeCommonClasses();

		$this->product = TmeProduct::getInstance();
		$this->params = TmeParams::getInstance();
		$this->prices = TmePrices::getInstance();
		$this->stocks = TmeStocks::getInstance();
		$this->products = TmeProducts::getInstance();
	}

	private function includeCommonClasses() {
		require_once 'classes/api.php';
		require_once 'classes/product.php';
		require_once 'classes/params.php';
		require_once 'classes/prices.php';
		require_once 'classes/stocks.php';
		require_once 'classes/products.php';
		// TODO:
		// require_once 'classes/delivery.php';
		// require_once 'classes/files.php';


		return $this;
	}

	public function getApi($ops){
		return new TmeApi($ops);
	}
}