<?php
/*
	classe contenitore dei dati del protocollo Jack
*/

	//eccezioni
	use \IllegalArgumentException;

	class JData {

		private $data; //mappa contenente i dati

		public function __construct() {
			$this->data = new HashMap();
		}

		public function length() { //restituisce la lunghezza dei dati
			return $this->data->length();
		}

		public function add($key, $value) { //inserisce un elemento
			try {
				return $this->data->put($key, $value);

			} catch (HashMap\IllegalArgumentException $e) {
				throw new IllegalArgumentException($e->getMessage());
			}
		}

		public function get($key) { //metodo che restituisce un elemento in base alla chiave
			try {
				return $this->data->get($key);

			} catch (HashMap\IllegalArgumentException $e) {
				throw new IllegalArgumentException($e->getMessage());
			}
		}
		
		public function remove($key) { //funzione switch per implementare overload funzioni
			try {
				return $this->data->remove($key);

			} catch (HashMap\IllegalArgumentException $e) {
				throw new IllegalArgumentException($e->getMessage());
			}
		}	
		
		public function contains($key) { //verifica la presenza della chiave
			try {
				return $this->data->contains($key);

			} catch (HashMap\IllegalArgumentException $e) {
				throw new IllegalArgumentException($e->getMessage());
			}
		}

		public function keys() { //restituisce tutte le chiavi
			return $this->data->keys();
		}
		
	}