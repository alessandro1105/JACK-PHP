<?php
/*
	require_once("class.Jack.php");

	new Jack();

*/


/*

	class Test123 {

	}

	class Test234 {

	}

*/

	require_once("class.Jack.php");

	$jdata = new JData();

	$jdata->add(null, "funziono!!!");

	echo $jdata->get("test");

	print_r($jdata->keys());