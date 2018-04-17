<?php

function DBNewPDO($pdoServer, $pdoDatabase, $pdoUser, $pdoPass, $pdoType = 'tcp') {
	// Socket or TCP/IP
	if($pdoType == 'socket') {
		$pdoConnect = 'unix_socket=';
	}else{
		$pdoConnect = 'host=';
	}

	// Connect or Error
	try {
		$newPDO = new PDO("mysql:{$pdoConnect}{$pdoServer};dbname={$pdoDatabase};charset=utf8", $pdoUser, $pdoPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", PDO::NULL_EMPTY_STRING, PDO::ATTR_TIMEOUT => 10, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => FALSE, PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING));
	}catch(PDOException $e) {
		error_log("DBERR [Connection failed][{$pdoServer}/{$pdoDatabase}] " . $e->getMessage());
	}

	return $newPDO;
}

function DBSimple($query, $vararray = array()) {
	global $db;

	$vararray = blankToNull($vararray);

	$stmt = NULL;
	$stmt = $db->prepare($query);
	if (!$stmt) {
		echo "\nPDO::errorInfo():\n";
		print_r($db->errorInfo());
	}
	$stmt->execute($vararray);
	return $stmt->rowCount();
}

function DBSingle($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	if (!$stmt) {
		echo "\nPDO::errorInfo():\n";
		print_r($db->errorInfo());
	}
	$stmt->execute($vararray);
	return $stmt->fetchColumn();
}

function DBSingleAssoc($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	if (!$stmt) {
		echo "\nPDO::errorInfo():\n";
		print_r($db->errorInfo());
	}
	$stmt->execute($vararray);
	return $stmt->fetch();
}

function DBAssoc($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	if (!$stmt) {
		echo "\nPDO::errorInfo():\n";
		print_r($db->errorInfo());
	}
	$stmt->execute($vararray);
	return $stmt->fetchAll();
}

function DBLastID() {
	global $db;
	return $db->lastInsertId();
}

$db = DBNewPDO('/var/run/mysqld/mysqld.sock', 'gamedb', 'gamedb', 'gamedb', 'socket');

// OpenVGDB SQLite Handler
$openVgdbHandle = new PDO("sqlite:resources/openvgdb.sqlite", NULL, NULL, array(PDO::ATTR_PERSISTENT => TRUE));

function vgdbQuery($query, $vararray = array()) {
	global $openVgdbHandle;
	$stmt = NULL;
	$stmt = $openVgdbHandle->prepare($query);
	if (!$stmt) {
		echo "\nPDO::errorInfo():\n";
		print_r($openVgdbHandle->errorInfo());
	}
	$stmt->execute($vararray);
	return $stmt->fetch();
}

// Mobygames SQLite Handler (no-longer needed - imported into MySQL for performance reasons)
/*
$mobySqlite = new PDO("sqlite:resources/moby.db", NULL, NULL, array(PDO::ATTR_PERSISTENT => TRUE));
$mobySqlite->exec('pragma synchronous = off;');
$mobySqlite->exec('pragma journal_mode = MEMORY;');

function mobyQuery($query, $vararray = array()) {
	global $mobySqlite;
	$stmt = NULL;
	$stmt = $mobySqlite->prepare($query);
	if (!$stmt) {
		echo "\nPDO::errorInfo():\n";
		print_r($mobySqlite->errorInfo());
	}
	$stmt->execute($vararray);
	return $stmt->fetch();
}
*/

?>
