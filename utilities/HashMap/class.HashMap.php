<?php

/*
	classe HashMap (stesse api di java)
*/
	
	//classi da usare
	use \HashMap\Node;
	use \HashMap\Entry;

	//eccezioni
	use \HashMap\IllegalArgumentException;
	use \HashMap\PropertyNotExistsException;

	//inserimento file necessari
	require_once("class.Entry.php");
	require_once("class.Node.php");
	require_once("class.IllegalArgumentException.php");
	require_once("class.PropertyNotExistsException.php");
	
	//implementazione classe hashmap php (utile per convertire velocemente sorgenti da c++ o java
	class HashMap {

		const DEFAULT_DIMM = 101; //lunghezza predefinita

		private $dimension; //dimensione 
		private $data; //array contenente i bucket
		private $size; //numero elementi


		private function hash($obj) {
			$hashCode = hash("sha256", $obj); //prendo il codice di hash

			$hashCode %= $this->dimension; //modulo dimensione mappa

			if ($hashCode < 0) { //positivizzo hashcode
				$hashCode *= -1;
			}

			return $hashCode; //ritorno la chiave ridotta
		}

		private function search($entry) { //ricerca di un nodo

			$node = $this->data[$this->hash($entry->key)]; //prelievo il giusto bucket

			while ($node->next != null and $node->next->value->key != $entry->key) { //cerco il nodo giusto
				$node = $node->next; //scorro la lista (bucket)
			}

			return $node; //ritorno il nodo
		}



		public function __construct($dimension = self::DEFAULT_DIMM) {
			if (!is_int($dimension) or $dimension < 1) {
				throw new IllegalArgumentException("Dimension must be an integer positive value!");
			}

			$this->dimension = $dimension;
			$this->size = 0;

			for ($i = 0; $i < $this->dimension; $i++) {
				$this->data[$i] = new Node();
			}
		}


		/*---INTERFACCIA PUBBLICA---*/

		public function length() { //restituiscer la lunghezza dell'array
			
			return $this->size;
		}

		public function put($key, $value) { //inserisce un elemento
			if ($key == null) {
				throw new IllegalArgumentException("Key cannot be NULL!");
			}

			$entry = new Entry($key, $value); //creo un entry

			$node = $this->search($entry); //ricerco se esiste un nodo

			if ($node->next == null) { //non è presente la chiave
 				$node->next = new Node($entry, null); //inserisco il nodo

 				$this->size++; //incremento sizes

 				return null;

 			} else { //presente
 				$value = $node->next->value->value; //prelevo il valore vecchio

 				$node->next = new Node($entry, $node->next->next); //sostituisco il valore

 				return $value; //ritorno il vechhio valore
 			}
		}


		public function get($key) { //metodo che restituisce un elemento in base alla chiave
			if ($key == null) {
				throw new IllegalArgumentException("Key cannot be NULL!");
			}

			$entry = new Entry($key, null); //creo entry

			$node = $this->search($entry); //ricerco il nodo
		
			if ($node->next == null) { //nodo non trovato
				return null;
			
			} else {
				return $node->next->value->value;
			}
		}
		

		public function remove($key) { //funzione switch per implementare overload funzioni
			if ($key == null) {
				throw new IllegalArgumentException("Key cannot be NULL!");
			}

			$entry = new Entry($key, null);

			$node = $this->search($entry);

			if ($node->next == null) { //nodo non trovato
				return null;
			
			} else {
				$value = $node->next->value->value; //prelevo il valore

				$node->next = $node->next->next; //elimino il nodo

				$this->size--;

				return $value;
			}
		}	
		
		
		public function contains($key) { //verifica la presenza della chiave
			if ($key == null) {
				throw new IllegalArgumentException("Key cannot be NULL!");
			}

			$entry = new Entry($key, null);

			$node = $this->search($entry);

			if ($node->next == null) { //nodo non trovato
				return false;
			
			} else {
				return true;
			}
			
		}

		public function keys() { //restituisce tutte le chiavi

			$keys = array();

			$j = 0;

			for ($i = 0; $i < $this->dimension; $i++) {

				$node = $this->data[$i]->next;

  				while ($node != null) {

  					$keys[$j++] = $node->value->key; //salvo le chiavi

  					$node = $node->next;
  				}

			}

			return $keys;
		}

		
	}