<?php

/* 
classe nodo per lista (per HashMap)
*/

	namespace HashMap;

	//eccezioni
	use HashMap\PropertyNotExistsException;

	class Node {

		private $next; //chiave
		private $value; //valore

		public function __construct($value = null, Node $next = null) {
			$this->next = $next;
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

		public function __set($propertyName, $value) {

			$methodName =  'set_' . $propertyName;

			if (method_exists($this, $methodName)) {
				return call_user_func(array($this, $methodName), $value);
			} else {
				throw new PropertyNotExistsException($propertyName . " not exists!");
			}

		}
		
		//funzioni per ottenere i dati
		private function get_next() {
			return $this->next;
		}

		private function get_value() {
			return $this->value;
		}

		//funzioni per settare i dati

		private function set_next($next) {
			$this->next = $next;
		}

		private function set_value($value) {
			$this->value = $value;
		}

	}