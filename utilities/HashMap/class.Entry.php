<?php

/* 
classe entry per classe hashmap
*/

	namespace HashMap;

	//eccezioni
	use HashMap\PropertyNotExistsException;

	class Entry {

		private $key; //chiave
		private $value; //valore

		public function __construct($key = null, $value = null) {
			$this->key = $key;
			$this->value = $value;
		}


		//magic method get e set
		
		public function __get($propertyName) {

			$methodName =  'get_' . $propertyName;			

			if (method_exists($this, $methodName)) {
				return call_user_func(array($this, $methodName));
			} else {
				throw new PropertyNotExistsException($propertyName . " not exists!");
			}

		}		
		
		//funzioni per ottenere i dati
		private function get_key() {
			return $this->key;
		}

		private function get_value() {
			return $this->value;
		}
	}