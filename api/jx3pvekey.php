<?php
	require_once 'PHP_AES_CBC.php';
	$time = time();
	$encryptString = $time;
	$encryptObj = new MagicCrypt();
	$result = $encryptObj->encrypt($encryptString);
	echo $result;

	