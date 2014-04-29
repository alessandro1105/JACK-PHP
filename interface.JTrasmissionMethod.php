<?php
	
/*
	interfaccia per il mezzo di trasmissione usato dal protocollo Jack
*/

	interface JTrasmissionMethod {

		public function send($message); //metodo usato per inviare messaggi in formato stringa
		public function receive(); //funzione che restituisce il messaggio in formato stringa
		public function available(); //funzione che controlla la presenza di messaggi in attesa

	}