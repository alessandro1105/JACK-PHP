<?php
/*
	classe di controllo del protocollo Jack
*/	

	/*---IMPORTAZIONI COMPONENTI---*/
	//eccezioni
	require_once("exceptions\class.IllegalArgumentException.php"); //illegal argument exception

	//importo classe HashMap
	require_once("utilities/HashMap/class.HashMap.php");

	//importo la classe JData
	require_once("class.JData.php");

	//importo interfaccia JTrasmissionMethod
	require_once("interface.JTrasmissionMethod.php");


	//eccezioni
	use IllegalArgumentException;

	//classe di controllo per il protocollo JACK
	abstract class Jack {

		const DEAFULT_TIME_BEFORE_RESEND = 1000; //tempo di default prima del reinvio
		const DEFAULT_TIME_POLLING = 100; //tempo di defualt di polling
		const DEFAULT_USE_ACK = false; //valore deafult sistema ack

		private $timeBeforeResend; //tempo reinvio messaggi
		private $useAck = false; //true invia il messaggio una volta sola (no ack)
		private $timePolling = 100; //tempo di polling per i "thread"

		private $jtm; //mezzo di trasmissione di tipo JTrasmissionMethod

		//buffer VECCHIO CODICE
		private $objSendMessageBuffer; //buffer per i mex da inviare
		private $objSendMessageTimer; //buffer dei timer per i mex da inviare
		private $objSendMessageBufferJData; //buffer per i messaggi da inviare nel formato JData
		private $objSendAckBuffer; //buffer che contiene i mex ack da inviare (non necessitano di timer)
		private $objIdMessageReceived; //buffer che contiene gli id dei messaggi ricevuti per evitare duplicazioni nei dati


		/*---METODI ASTRATTI DA IMPLEMENTARE---*/
		abstract protected function onReceive($message); //evento scatenato al ricevimento di un mex valido
		abstract protected function onReceiveAck($id); //evento scatenato al ricevimento di una conferma di un mex	
		abstract protected function getTimestamp(); //metodo usato per ottenere il timestamp del sistema


		/*---PARTE PRIVATA---*/
		//verifica che il messaggio sia conforme al protocollo JACK
		private function validate($messageString) {
			if (!is_string($messageString) or strlen($messageString) == 0) { //se non stringa o vuota
				return false;
			}

			$message = json_decode($message);

			if (!$message) { //se non è un messaggio JSON valido
				return false;
			}

			//check proprietà
			if (!array_key_exists("id", $message)) { //se non contiene id
				return false;
			}
			if (array_key_exists("values") xor array_key_exists("length")) { //se presente values deve esserlo anche length
				return false;
			}
			if (array_key_exists("ack") xor array_key_exists("length-ack")) { //se presente ack deve esserlo anche length-ack
				return false;
			}

			//MANCA VERIFICA ID E VALUES

			//DA PENSARE SE RESTITUIRE DIRETTAMENTE JDATA
		}


		/*---INTERFACCIA PUBBLICA---*/
		public function __construct(JTrasmissionMethod $jtm, $useAck = self::DEFAULT_USE_ACK, $timePolling = self::DEFAULT_TIME_POLLING, $timeBeforeResend = self::DEAFULT_TIME_BEFORE_RESEND) {
			if (!is_bool($useAck)) {
				throw new IllegalArgumentException("useAck must be a boolean value!");
			}
			if (!is_int($timePolling) or $timePolling < 1) {
				throw new IllegalArgumentException("timePolling must be a integer value major than 1!");
			}
			if (!is_int($timeBeforeResend) or $timeBeforeResend < 1) {
				throw new IllegalArgumentException("timeBeforeResend must be a integer value major than 1!");
			}

			$this->useAck = $useAck;
			$this->timePolling = $timePolling;
			$this->timeBeforeResend = $timeBeforeResend;
			$this->jtm = $jtm;

			//DA COMPLETARE (VECCHIO CODICE)
			//buffer
			$this->objSendMessageBuffer = new HashMap(); //buffer messaggi
			
			$this->objSendMessageTimer = new HashMap(); //timer per invio messaggi
			
			$this->objSendMessageBufferJData = new HashMap(); //buffer messaggi da inviare formato JData
			
			$this->objSendAckBuffer = new HashMap(); //buffer ack
			
			$this->objIdMessageReceived = new HashMap(); //buffer id messaggi già ricevuti

		}
		 

		//VECCHIO CODICE

		//funzione che avvia la classe di controllo
		public function start() {
			
			//$this->stopPolling = false; //varibile che indica se far partire i mertodi per il polling
			
			while ($this->objJTM->available()) {
				$this->getMessagePollingFunction();	
			}
			
		}
		
		//funzione che ferma la classe di controllo
		public function stop() { //stop jack (richiamo l'invio dei dati)
			
			//$this->stopPolling = true;
			
			$this->sendMessagePollingFunction();
					
		}
		
		//funziona che cancella il buffer dei mex da inviare
		public function flushBufferSend() {
			
			$this->sendMessageBuffer = new HashMap(); //reset buffer messaggi da inviare
			$this->sendMessageBufferJData = new HashMap();
			
		}
		
		//corpo centrale della classe e decide cosa deve fare
		private function execute($strMessage) {
					
			if ($this->validate($strMessage)) {
		
				$objMessageJData = $this->getJDataMessage($strMessage);
				//echo 1;
				if ($objMessageJData->getValue(Jack::MESSAGE_TYPE) == Jack::MESSAGE_TYPE_DATA) {
				//echo 2;
					if (!$this->checkMessageAlreadyReceived($objMessageJData)) {
				
						$this->onReceive($objMessageJData);
					}
			
				} else {
				
					$this->checkAck($objMessageJData);
				}
		
			}
			
		}
		

		//verifica che il messaggio non sia già stato ricevuto (reinviato per non ricezione ack)
		private function checkMessageAlreadyReceived($objMessageJData) {
			
			//grezza validazione dei dati da sostituire con il metodo validate
			if (!$objMessageJData->containsKey(Jack::MESSAGE_ID)) { //se non presente id incorrerei in errori e quindi blocco elaborazione
				
				return true; //con true blocco l'elaborazione del messaggio come se fosse già stato ricevuto
			}
			// fine grezza validazione del messaggio
			
			
			$this->sendAck($objMessageJData); //invio ack per problema scadenza timer (invio anche se già ricevuto per perdita o ritardo ack precedente)
			
			if (!$this->objIdMessageReceived->containsKey($objMessageJData->getValue(Jack::MESSAGE_ID))) {
				
				$this->objIdMessageReceived->put($objMessageJData->getValue(Jack::MESSAGE_ID), 0); //è importante solo la chiave non il valore (cast Long dell'id)
				
				return false; //messaggio non già ricevuto
				
			} else {
				
				return true; //messaggio già ricevuto
				
			}
			
		}
				
		
		private function getJDataMessage($strMessage) {
			
			$objMessageJData = new JData();		
			
			
			$temp = "";
			$temp2 = "";
			
			$nChar = 0;
			
			$value;
			
			
			$strMessage = substr($strMessage, 2); //elimino 2 caratteri iniziali
			
			for($i = 0; $i < 2; $i++) {
				
				$temp = "";
			
			
				if ($this->startsWith($strMessage, Jack::MESSAGE_ID)) { //indicazione id 
					
					//echo "id</br>";
					//echo $strMessage;
					//echo "</br>";
					$strMessage = substr($strMessage, strlen(Jack::MESSAGE_ID) + 2); //elimino dal mex id + 2 caratteri (":)
					//echo $strMessage;
					//echo "</br>";
				
					for ($x = 0; $strMessage[$x] != ','; $x++) { //prelevo l'id e lo memorizzo in temp
						//echo $strMessage[$x];
						
						$temp = $temp . $strMessage[$x];
					}
					
					//echo "</br>";
					
					$strMessage = substr($strMessage, strlen($temp) + 2); //elimino dal mex la lunghezza dell'id + 2
					//echo $temp;
					//echo "</br>";
					//echo $strMessage;
					
					$objMessageJData->add(Jack::MESSAGE_ID, (float) $temp); //converto in long l'id
					
				} else if ($this->startsWith($strMessage, Jack::MESSAGE_TYPE_ACK)) { //indicazione ack  messaggio ack
				
					//echo "ack";
					
					$objMessageJData->add(Jack::MESSAGE_TYPE, Jack::MESSAGE_TYPE_ACK);
					
					if ($i < 1) //sono al primo giro e manca ancora l'id
						$strMessage =substr($strMessage, strlen(Jack::MESSAGE_TYPE_ACK) + 5); //elimino la lunghezza di ack + 5 caratteri
						
				} else if ($this->startsWith($strMessage, Jack::MESSAGE_TYPE_DATA)) { //indicazione values messaggio contenente dati
					
					//echo "data";
					
					$objMessageJData->add(Jack::MESSAGE_TYPE, Jack::MESSAGE_TYPE_DATA);
					
					$strMessage =substr($strMessage, strlen(Jack::MESSAGE_TYPE_DATA) + 5);
					
					//azzero le variabili prima di entrare nel ciclo
					$value = false;
					$temp = ""; 
					$temp2 = "";
					$nChar = 0;
					
					for ($x = 0; $strMessage[$x] != ']'; $x++) { //scorro i caratteri di message
						
						$nChar++; //serve per contare i carattri che elimenerò da message
						
						if ($strMessage[$x] == ',' || $strMessage[$x] == '}') { //store value nel JData						
												
							if ($temp2[0] == '"') { //stringa
								
								$objMessageJData->add($temp, substr($temp2, 1, strlen($temp2) -1));
								
							} else if ($this->contains($temp2, ".")) { //double
								
								$objMessageJData->add($temp, (double) $temp2);
								
							} else if ($temp2 == Jack::MESSAGE_BOOLEAN_TRUE or $temp2 == Jack::MESSAGE_BOOLEAN_FALSE) { // boolean
								
								if ($temp2 == Jack::MESSAGE_BOOLEAN_TRUE) { //true
									
									$objMessageJData->add($temp, true);
									
								} else { //false
									
									$objMessageJData->add($temp, false);
									
								}
								
							} else { //long
								
								$objMessageJData->add($temp, (float) $temp2);
							
							} //fine switch tipi
							
							//azzero i valori
							$value = false;
							$temp = "";
							$temp2 = "";
							 
						} else if ($strMessage[$x] == ':') { //passo da caratteri della chiave a caratteri del valore
							
							$value = true;
							
						} else if (!$value and $strMessage[$x] != '"') { //value = true caratteri CHIAVE
							
							$temp = $temp . $strMessage[$x];
							
						} else if ($value) { //caratteri del VALORE value = false
							
							$temp2 = $temp2 . $strMessage[$x];
						}
						
					} //fine for values 
					

					if ($i < 1) //manca ancora id
						$strMessage = substr($strMessage, $nChar +3);
						 
									
				} //fine values
				
				
			}
			
			//echo $objMessageJData->getValue("message_type");
			
			return $objMessageJData;
		}
		
		
		public function send($objMessageJData) {
			
			$intId = $this->getTimestamp(); //id = timestamp
					
			$strMessage = "{\"id\":" . $intId . ",\"values\":[{"; //intenstazione id + values
					
					
			for($i = 0; $i < $objMessageJData->length(); $i++) {
						
				$strMessage .= $objMessageJData->getKey(i) . ":"; //inerisco la chiave nel messaggio
							
				if (is_int($objMessageJData->getValue(i))) { //type integer
						
					$strMessage .= $objMessageJData->getValue(i);
							
				} else if (is_double($objMessageJData->getValue(i))) { //type double
						
					$strMessage .= $objMessageJData->getValue(i);
						
				} else if (is_bool($objMessageJData->getValue(i))) { //boolean traducon i valori impostati
							
					if ($objMessageJData->getValue(i)) {
								
						$strMessage .= Jack::MESSAGE_BOOLEAN_TRUE;
								
					} else {
								
						$strMessage .= Jack::MESSAGE_BOOLEAN_FALSE;
								
					}
							
				} else if (is_string($objMessageJData->getValue(i))) { //stringa aggiungo "" inzio e fine
							
					$strMessage .= "\"" . $objMessageJData->getValue(i) . "\"";
							
							
				} /*else { //nessun tipo predefinito
							
							messageString += message.getValue(i).toString();
				}*/
						
				$strMessage .= ","; //metto la virgola per separaere i valori
						
			}
					
					
			$strMessage = substr($strMessage, 0, strlen($strMessage) -1); //elimino l'ultima virgola
					
			$strMessage .= "}]}"; //messaggio in stringa creato
					
				
					
					
			$this->objSendMessageBuffer->put(id, messageString); //carico il mex nel buffer (sarà spedito automaticamente)
			$this->objSendMessageBufferJData->put(id, message);
					
		}
				
		
		
		//verifico l'ack
		private function checkAck($objMessageJData) {
					
			$intId = $objMessageJData->getValue(Jack::MESSAGE_ID);
					
			if ($this->objSendMessageBufferJData->length() > 0) { //verifico che esistano messaggi in attesa di conferma
						
				if ($this->objSendMessageBufferJData->containsKey($intId)) { //verifico che l'id conetnuto ack esista
							
					if ($this->objSendMessageBuffer->containsKey($intId)) {
						$this->objSendMessageBuffer->remove($intId); //elimino il messaggio (CONFERMO) non verrà più reinviato
					} 
							
					$this->onReceiveAck($this->objSendMessageBufferJData->getValue($intId)); //richiamo metodo astratto invocato al ricevimento di un ack
					
					$this->objSendMessageBufferJData->remove($intId);		
							
				}
			}
		}
				
		
		//creo ack e lo invio
		private function sendAck($objMessageJData) { //invio ack
			//echo "sendack jack";
			//echo "<br>";
			//echo $objMessageJData->getValue(Jack::MESSAGE_ID);
			//echo "<br>";
			
			$strMessage = "{\"" . Jack::MESSAGE_ID . "\":";
			
			$strMessage .= $objMessageJData->getValue(Jack::MESSAGE_ID);
			
			$strMessage .= ",\"" . Jack::MESSAGE_TYPE_ACK . "\":1}";
					
			$this->objSendAckBuffer->put($objMessageJData->getValue(Jack::MESSAGE_ID), $strMessage); //carico il mex nel buffer (sarà spedito automaticamente)	
					
			//echo $strMessage;
					
		}
				
		/*
		public function loop() { //luppa per simulare il thread

			$this->getMessagePollingFunction();
			
			$this->sendMessagePollingFunction();
	
		} */
	
		private function getMessagePollingFunction() { //funzione che sostituisce il thread per il get dei messaggi
		
			//if (!$this->stopPolling) {
				
				$strMessage = $this->objJTM->receive();
				
				if (strlen($strMessage) > 0) {
					
					$this->execute($strMessage);
					
				}
				
			//}
			
		}
		
		
		private function sendMessagePollingFunction() { //" " " per inviare i messaggi
		
			//if (!$this->stopPolling) {
			
				if ($this->objSendAckBuffer->moveToFirst()) { //invio ack
					
					//echo "length: " . $this->objSendAckBuffer->length();
					//echo "<br>";
				
					do {
					
						$this->objJTM->send($this->objSendAckBuffer->getValue());
						
						//$this->objSendAckBuffer->remove();
					
					} while ($this->objSendAckBuffer->moveToNext());
					
					
					$this->objSendAckBuffer = new HashMap();
					
					//echo "length: " . $this->objSendAckBuffer->length();
					//echo "<br>";
					
					
				}
				
				if ($this->objSendMessageBuffer->moveToFirst()) { //invio messaggi
					
					do {
						
						$intKey = $this->objSendMessageBuffer->getKey(); //prelevo la chiave (id)
						
						if ($this->objSendMessageTimer->containsKey($intKey)) { //controllo se il messaggio è già stato inviato (presenza del buffer)
						
							if ((time() - $this->objSendMessageTimer->getValue($intKey)) > $this->TIME_BEFORE_RESEND) { //controllo se è scaduto il tempo di attesa prima di reinviare il messaggio
								
								$this->objJTM->send($this->objSendMessageBuffer->getValue()); //invio il messaggio
								
								$this->objSendMessageTimer->remove(key);
								
								$this->objSendMessageTimer->put(key, millis());
								
							}
						
						
						} else { //messaggio da inviare per la prima volta
						
							$this->objJTM->send($this->objSendMessageBuffer->getValue()); //invio il messaggio
							
							if (!$this->SEND_ONE_TIME) {//controllo se non è da inviare una volta sola
								
								$this->objSendMessageTimer->put($intKey, time());
								
							} else { //messaggio da inviare una sola volta
							
								$this->objSendMessageBuffer->remove(key);
								
							}
						
						}
					
					} while ($this->objSendMessageBuffer->moveToNext());
					
				
				}
			//}
		
		}
		
		
		
		//FUNZIONI PER LE STRINGHE (DA PASSARE IN UNA CLASSE)
		private function startsWith($haystack, $needle) {
    		return !strncmp($haystack, $needle, strlen($needle));
		}

		private function endsWith($haystack, $needle) {
   			$length = strlen($needle);
    		if ($length == 0) {
        		return true;
    		}

    		return (substr($haystack, -$length) === $needle);
		}
		
		private function contains($haystack, $needle) {
			if (strstr($haystack, $needle, false) != NULL) {
				return true;	
			} else {
				return false;	
			}
		}
		//FINE FUNZIONI STRINGHE
		
	}