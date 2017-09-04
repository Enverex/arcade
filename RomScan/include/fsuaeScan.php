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
		$newPDO = new PDO("mysql:{$pdoConnect}{$pdoServer};dbname={$pdoDatabase};charset=utf8", $pdoUser, $pdoPass, array(PDO::NULL_EMPTY_STRING, PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	}catch(PDOException $e) {
		error_log("DBERR [Connection failed][{$pdoServer}/{$pdoDatabase}] " . $e->getMessage());
	}

	return $newPDO;
}

function DBSimple($query, $vararray = array()) {
	global $db;

	// Convert blank to NULL
	foreach($vararray as $key => $value) { if(empty($value)) $vararray[$key] = NULL; }

	$stmt = NULL;
	$stmt = $db->prepare($query);
	$stmt->execute($vararray);
	return $stmt->rowCount();
}

function DBSingle($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	$stmt->execute($vararray);
	return $stmt->fetchColumn();
}

function DBSingleAssoc($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	$stmt->execute($vararray);
	return $stmt->fetch();
}

function DBAssoc($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	$stmt->execute($vararray);
	return $stmt->fetchAll();
}

function DBLastID() {
	global $db;
	return $db->lastInsertId();
}

$db = DBNewPDO('/var/run/mysqld/mysqld.sock', 'gamedb', 'gamedb', 'gamedb', 'socket');

$oagdDB = new PDO('sqlite:oagd.net.sqlite');
$gamesList = $oagdDB->query('SELECT * FROM game');
$gamesList = $gamesList->fetchall(PDO::FETCH_ASSOC);

function cleanHex($text) {
	return trim(preg_replace('%[^a-z0-9]%', '', strtolower($text)));
}

$previousTotal = DBSingle("SELECT COUNT(*) FROM fsuaeUUID");

foreach($gamesList as $thisGame) {
	## id, uuid (binary encoded), data (zlib compressed)
	$thisGame['uuid'] = bin2hex($thisGame['uuid']);
	if($thisGame['data'] = @zlib_decode($thisGame['data'])) {
		$jsonArray = json_decode($thisGame['data'], 1);
		if(stristr($jsonArray['variant_name'], 'WHDLoad')) {
			$jsonArray['file_list'] = json_decode($jsonArray['file_list'], 1);
			foreach($jsonArray['file_list'] as $thisFile) {
				if(stristr($thisFile['name'], '.slave')) {
					echo "{$thisFile['name']}\n";
					DBSimple("REPLACE INTO fsuaeUUID SET fsGameID = ?, fsVarID = ?, fsFileSum = ?", array(cleanHex($thisGame['uuid']), cleanHex($jsonArray['parent_uuid']), cleanHex($thisFile['sha1'])));
					$gtCount++;
				}
			}
		}
	}
}

$newTotal = DBSingle("SELECT COUNT(*) FROM fsuaeUUID");

$addedTotal = intval($newTotal - $previousTotal);

echo "\n\nFinished. {$gtCount} valid games processed. {$addedTotal} new hashes added.\n\n";

?>
