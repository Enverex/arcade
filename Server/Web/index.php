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
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

function DBAssoc($query, $vararray = array()) {
	global $db;
	$stmt = NULL;
	$stmt = $db->prepare($query);
	$stmt->execute($vararray);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function DBLastID() {
	global $db;
	return $db->lastInsertId();
}

function xmlSafe($text) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function arrayToXML($inputArray, &$newXML) {
	foreach($inputArray as $key => $value) {
		if(is_array($value)) {
			if(!is_numeric($key)) {
				$subnode = $newXML->addChild($key);
				arrayToXML($value, $subnode);
            }else{
				$subnode = $newXML->addChild("item{$key}");
				arrayToXML($value, $subnode);
            }
		}else{
			$newXML->addChild($key, xmlSafe("$value"));
		}
	}
}

$db = DBNewPDO('/var/run/mysqld/mysqld.sock', 'gamedb', 'gamedb', 'gamedb', 'socket');

## AJAX Stuff
if(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {}

if($_GET['api'] && ($_GET['name'] || $_GET['simplename'] || $_GET['id'])) {
	if($_GET['simplename']) {
		$searchResult = DBAssoc("SELECT * FROM games WHERE gameMatchName LIKE ? ORDER BY gamePlatform,gameName", array("%{$_GET['simplename']}%"));
	}elseif($_GET['name']) {
		$searchResult = DBAssoc("SELECT * FROM games WHERE gameName LIKE ? ORDER BY gamePlatform,gameName", array("%{$_GET['name']}%"));
	}elseif($_GET['id']) {
		$searchResult = DBAssoc("SELECT * FROM games WHERE gameID = ? LIMIT 1", array($_GET['id']));
	}

	$gameXML = new SimpleXMLElement("<?xml version=\"1.0\"?><gameResults></gameResults>");
	arrayToXML($searchResult, $gameXML);

	$dom = new DOMDocument("1.0");
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($gameXML->asXML());
	echo $dom->saveXML();

	exit;
}

echo "
	<html>
	<head>
	<title>Emerald Arcade Search</title>
	<link rel='shortcut icon' href='favicon.ico' />
	<style>
		html,body			{ height: 100%; padding: 0; margin: 0; }
		html				{ background: #202020 url(img/bg.png) repeat; box-sizing: border-box; }
		body				{ text-align: center; background-image: url(img/pacman.png); background-repeat: no-repeat; background-position: center; color: #eee; font: 9pt arial; box-sizing: border-box; }
		td, th				{ padding: 2px 12px 2px 12px; }
		input				{ font: inherit; padding: 2px 6px 2px 6px; margin: 2px; border: 1px solid silver; background: white; }
		tr:nth-child(even) 	{ background: #e9e9e9; }
		.gameNameCell		{ text-align: right; }
		.consoleIconCell	{ text-align: center; }
		.gameSearchForm		{ font: 14pt Arial; }
		.emptyHead			{ margin: 0; padding: 0; }
		.goodCell			{ background: #00E400; width: 4px !important; padding: 0 0 0 0 !important; }
		.badCell			{ background: #ED674D; width: 4px !important; padding: 0 0 0 0 !important; }
		.siteBox			{ display: inline-block; text-align: left; max-width: 1000px; background: #f5f5f5; color: #000; box-shadow: 0 0 2px 4px #888; text-align: left; font: 8pt arial; padding: 12px; margin: 8px; }
		.lightText			{ color: grey; }

		.rating_bar {
			/*this class creats 5 stars bar with empty stars each star is 16 px, it means 5 stars will make 80px together */
			width: 16px;
			/*height of empty star*/
			height: 16px;
			/*background image with stars */
			background: url(img/stars.png);
			/*which will be repeated horizontally */
			background-repeat: repeat-x;
			/* as we are using sprite image, we need to position it to use right star, 
			//0 0 is for empty */
			background-position: 0 0;
			/* align inner div to the left */
			text-align: left;
		}

		.rating {
			/* height of full star is the same, we won't specify width here */
			height: 16px;
			/* background image with stars */
			background: url(img/stars.png);
			/* now we will position background image to use 16px from top, 
			//which means use full stars */
			background-position: 0 -16px;
			/* and repeat them horizontally */
			background-repeat: repeat-x;
		}
	</style>
	</head>
	<body>
	<br/><br/>
";

$normalTableHead = "Hover over a game's name for the description.<br/>
	<table class='siteBox'>
	<tr><th>Game</th><th class='emptyHead'></th><th class='emptyHead'></th><th class='emptyHead'></th><th>Platform</th><th>Year</th><th><div title='Rating' class='rating_bar'><div class='rating' style='width: 100%;'></div></div></th><th><img title='Number of Players' src='img/players.gif'/></th><th>Genre</th><th>Developer</th><th>Publisher</th></tr>
";

foreach(DBAssoc("SELECT DISTINCT(gameFilePlatform) FROM gameFiles") as $thisPlat) {
	$inusePlatforms[$thisPlat['gameFilePlatform']] = '1';
}

// Create array of platforms
foreach(DBAssoc("SELECT DISTINCT(platformName),gamePlatform FROM `platforms` ORDER BY platformName ASC") as $dbPlatforms) {
	if($inusePlatforms[$dbPlatforms['gamePlatform']]) $platformArray[$dbPlatforms['gamePlatform']] = $dbPlatforms['platformName'];
}

// Create dropdown list of systems
$systemDropList .= "<option value=''></option>";
foreach($platformArray as $thisPlatShortname => $thisPlatform) {
	$systemDropList .= "<option value='{$thisPlatShortname}'>{$thisPlatform}</option>";
}

echo "
	<form method='get' class='gameSearchForm'>
		Search Library &nbsp; <input type='text' id='searchGame' name='searchGame' /> <span class='lightText'>or</span> Show Games On &nbsp; <select name='gameTable'>{$systemDropList}</select> &nbsp; <input type='submit' value='Go' />
	</form>
";

function gameRow($thisGame) {
	global $platformArray;
	$wheelImgState = $boxImgState = $snapImgState = 'badCell';

	if($thisGame['gameRating']) {
		$ratingStarWidth = round($thisGame['gameRating'] * 18);
		$ratingStars = "<div class='rating_bar' title='{$thisGame['gameRating']} out of 5'><div  class='rating' style='width: {$ratingStarWidth}%;'></div></div>";
	}

	$safeDescription = htmlentities($thisGame['gameDescription'], ENT_QUOTES, 'UTF-8');
	if($thisGame['gameReleaseDate']) $gameYear = date('Y', $thisGame['gameReleaseDate']);

	if($thisGame['gameWheelImage']) $wheelImgState = 'goodCell';
	if($thisGame['gameImage']) $boxImgState = 'goodCell';
	if($thisGame['gameSnapImage']) $snapImgState = 'goodCell';

	return "
		<tr>
			<td title='{$safeDescription}'>{$thisGame['gameName']}</td>
			<td class='{$wheelImgState}'></td>
			<td class='{$boxImgState}'></td>
			<td class='{$snapImgState}'></td>
			<td class='consoleIconCell'><img src='img/{$thisGame['gameFilePlatform']}.png' title='{$platformArray[$thisGame['gameFilePlatform']]}' data-1='{$thisGame['gameFilePlatform']}' data-2='' /></td>
			<td>{$gameYear}</td><td class='consoleIconCell'>{$ratingStars}</td>
			<td class='consoleIconCell'>{$thisGame['gamePlayers']}</td>
			<td>{$thisGame['gameGenre']}</td>
			<td>{$thisGame['gameDeveloper']}</td>
			<td>{$thisGame['gamePublisher']}</td>
		</tr>
	";
}

if($_GET['searchGame']) {
	$_GET['searchGame'] = trim($_GET['searchGame']);
	$gameDetails = DBAssoc("SELECT * FROM gameFiles NATURAL JOIN games WHERE gameName LIKE ? OR gameFile LIKE ? ORDER BY gameName+0<>0 DESC, gameName+0, gameName, gamePlatform", array("%{$_GET['searchGame']}%", "%{$_GET['searchGame']}%"));
	if($gameDetails) {
		echo $normalTableHead;
		foreach($gameDetails as $thisGame) { echo gameRow($thisGame); }
		echo "</table>";
	}
}

function getPage($pageURL) {
	global $userAgent;
	$page = DBSingle("SELECT cachePage FROM pageCache WHERE cachePath = ? LIMIT 1", array($pageURL));

	// Page not in cache, go get it
	if(!$page) {
		// Throttle calls to WoS because they block you after X connections
		if(stristr($pageURL, 'worldofspectrum.org')) {
			global $wosTimer, $wosTotal, $wosDelay, $wosPause;
			$wosTotal++;
			$wosTR = (time() - $wosTimer);
			if($wosTR < $wosDelay && $wosTotal > 10) {
				$wosTTW = $wosDelay - $wosTR;
				if(!$wosPause) return false;
				sleep($wosTTW);
				$wosTotal = 0;
			}
			$wosTimer = time();
		}

		// Auto-generate referrer to make the site like us more
		$referrer = 'http://'.parse_url($pageURL)['host'];

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $pageURL);
		#curl_setopt($curl, CURLOPT_PROXY, 'aventurine.xnode.org:8000');
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_REFERER, $referrer);
		curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$page = trim(curl_exec($curl));
		$error = trim(curl_error($curl));
		curl_close($curl);

		// Report any errors
		#if($error || stristr($page, 'The following error was encountered while trying to retrieve the URL')) die("\n[Page Grabber] cURL Failure: {$error}\n");

		// Probably a blocker page
		if(stristr($page, 'CAPTCHA') || stristr($page, 'Blocked IP Address')) {
			unset($page);
			die("\n\n[Page Grabber] CAPTCHA failure [{$pageURL}]\n\n");
		}
		if($page) DBSimple("INSERT INTO pageCache SET cachePath = ?, cachePage = ?, cacheTime = ?", array($pageURL, $page, time()));
	}

	if($page) return $page;
}

if($_GET['cloneCheck']) {
	$gameDetails = DBAssoc("SELECT * FROM games WHERE gamePlatform = 'mame' ORDER BY gameName");
	echo "<table class='siteBox'><tr><th class='gameNameCell'>Game</th><th>Clone Of</th></tr>";

	foreach($gameDetails as $thisGame) {
		$romName = str_replace('.zip', '', $thisGame['gameFile']);

		$gamePage = trim(preg_replace("%([\t]*)%", '', getPage("http://www.mamedb.com/game/{$romName}")));

		if($gamePage) {
			// Clone check
			unset($cloneCheck);
			preg_match("%<b>Name:&nbsp</b>(?:.+?)(?:sample|clone) of: <a href='/(?:.+?)'>(.+?)</a>%", $gamePage, $cloneCheck);
		}

		unset($noCD);
		if(strstr($cloneCheck[0], 'NO CD')) $noCD = 1;

		if($cloneCheck[1] && $cloneCheck[1] != $romName && !$noCD) {
			echo "
				<tr>
					<td class='gameNameCell'>{$romName}</td>
					<td class='gameNameCell'>{$cloneCheck[1]}</td>
				</tr>
			";
		}
	}

	echo "</table>";
}

if($_GET['noMatch']) {
	$gameDetails = DBAssoc("SELECT * FROM games WHERE gameGenre IS NULL AND gameDescription IS NULL AND gameDeveloper IS NULL AND gamePublisher IS NULL ORDER BY gamePlatform,gameName");
	if($gameDetails) {
		echo $normalTableHead;
		foreach($gameDetails as $thisGame) { echo gameRow($thisGame); }
		echo "</table>";
	}
}

function percentToColor($value) {
	// Calculate first and second color (Inverse relationship)
	$first = ($value / 80) * 255;
	$second = (1 - ($value / 80)) * 255;

	// Find the influence of the middle color (yellow if 1st and 2nd are red and green)
	$diff = abs($first - $second);
	$influence = (255 - $diff) / 2;
	$first = intval($first + $influence);
	$second = intval($second + $influence);

	// Return RGB values
	return "{$first}, {$second}, 0";
}

function getMissingTotals($system) {
	$gameTotal = DBSingle("SELECT COUNT(*) FROM games WHERE gameFilePlatform = ? ORDER BY gamePlatform,gameName", array($system));

	$missingFields = DBSingle("SELECT base.ngameReleaseDate + base.ngameGenre + base.ngameDescription + base.ngameRating + base.ngameDeveloper + base.ngamePublisher AS total_nulls FROM ( SELECT Sum(ISNULL(gameReleaseDate)) AS ngameReleaseDate, Sum(ISNULL(gameGenre)) AS ngameGenre, Sum(ISNULL(gameDescription)) AS ngameDescription, Sum(ISNULL(gameRating)) AS ngameRating, Sum(ISNULL(gameDeveloper)) AS ngameDeveloper, Sum(ISNULL(gamePublisher)) AS ngamePublisher FROM games WHERE gamePlatform = ? ) AS base", array($system));

	$totalGames = $gameTotal;
	$totalFields = ($totalGames * 6);
	$missingPerc = round(($missingFields / $totalFields) * 100);

	return array('totalGames' => $totalGames, 'totalFields' => $totalFields, 'totalMissingFields' => $missingFields, 'missingFieldsPercent' => $missingPerc);
}

function cleanWhitespace($text) {
	$text = trim(preg_replace('%[\pZ\xA0\s]+%u', ' ', $text));
	$text = trim(preg_replace('%[\pC]+%u', '', $text));
	return $text;
}

if($_GET['cleanText']) {
	$gameArray = DBAssoc("SELECT * FROM games");

	foreach($gameArray as $thisGame) {
		DBSimple("UPDATE games SET gameDescription = ?, gameDeveloper = ?, gamePublisher = ? WHERE gameID = ?", array(cleanWhitespace($thisGame['gameDescription']), cleanWhitespace($thisGame['gameDeveloper']), cleanWhitespace($thisGame['gamePublisher']), cleanWhitespace($thisGame['gameID'])));
		$updateCount++;
	}

	echo "<div class='siteBox'>{$updateCount} Fields updated.</div>";
}

if($_GET['completionTable']) {
	$systemArray = DBAssoc("SELECT * FROM platforms ORDER BY platformName");

	echo "<table class='siteBox'><tr><th class='gameNameCell'>System</th><th>Games</th><th>Total Fields</th><th>Missing Fields</th><th>Missing %</th></tr>";
	foreach($systemArray as $thisSystem) {
		$missingArray = getMissingTotals($thisSystem['gamePlatform']);

		## No games, skip it
		if($missingArray['totalGames'] === 0) {
			#DBSimple("DELETE FROM platforms WHERE platformName = ? LIMIT 1", array($thisSystem['platformName']));
			continue;
		}

		$percCellCol = percentToColor($missingArray['missingFieldsPercent']);
		echo "
			<tr>
				<td class='gameNameCell'>{$thisSystem['platformName']}</td>
				<td>{$missingArray['totalGames']}</td>
				<td>{$missingArray['totalFields']}</td>
				<td>{$missingArray['totalMissingFields']}</td>
				<td style='background-color: rgba({$percCellCol}, 0.4);'>{$missingArray['missingFieldsPercent']}</td>
			</tr>
		";
	}
	echo "</table><br/>";
}

if($_GET['completeTable']) {
	$gameDetails = DBAssoc("SELECT * FROM games WHERE gameFilePlatform = ? ORDER BY gameName", array($_GET['completeTable']));

	echo "<table class='siteBox'><tr><th class='gameNameCell'>Game</th><th>Ye</th><th>Ra</th><th>Ge</th><th>De</th><th>Pu</th><th>Pl</th></tr>";

	foreach($gameDetails as $thisGame) {
		echo "
			<tr>
				<td class='gameNameCell'>{$thisGame['gameName']}</td>
				<td ".($thisGame['gameReleaseDate'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gameRating'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gameGenre'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gameDeveloper'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gamePublisher'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gamePlayers'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
			</tr>
		";
	}

	echo "</table>";
}

if($_GET['gameTable']) {
	$gameDetails = DBAssoc("SELECT * FROM gameFiles NATURAL JOIN games WHERE gameFilePlatform = ? ORDER BY gameName+0<>0 DESC, gameName+0, gameName, gamePlatform", array($_GET['gameTable']));
	echo $normalTableHead;
	foreach($gameDetails as $thisGame) { echo gameRow($thisGame); }
	echo "</table>";
}

echo "<script>document.getElementById('searchGame').focus();</script></body></html>";

if($_GET['badTable']) {
	$gameDetails = DBAssoc("SELECT * FROM games ORDER BY gamePlatform,gameName");

	$missingFields = DBSingle("SELECT base.ngameReleaseDate + base.ngameGenre + base.ngameDescription + base.ngameRating + base.ngameDeveloper + base.ngamePublisher AS total_nulls FROM ( SELECT Sum(ISNULL(gameReleaseDate)) AS ngameReleaseDate, Sum(ISNULL(gameGenre)) AS ngameGenre, Sum(ISNULL(gameDescription)) AS ngameDescription, Sum(ISNULL(gameRating)) AS ngameRating, Sum(ISNULL(gameDeveloper)) AS ngameDeveloper, Sum(ISNULL(gamePublisher)) AS ngamePublisher FROM games ) AS base");

	$totalGames = count($gameDetails);
	$totalFields = ($totalGames * 6);
	$missingPerc = round(($missingFields / $totalFields) * 100);

	echo "<div class='siteBox'>{$totalGames} games in the library. {$missingFields} of {$totalFields} fields ({$missingPerc}%, excluding players) are missing.</div><br/><br/>";

	echo "<table class='siteBox'><tr><th class='gameNameCell'>Game</th><th>Platform</th><th>Ye</th><th>Ra</th><th>Ge</th><th>De</th><th>Pu</th><th>Pl</th></tr>";

	foreach($gameDetails as $thisGame) {
		$failCount = 0;
		if(!$thisGame['gameReleaseDate']) $failCount++;
		if(!$thisGame['gameRating']) $failCount++;
		if(!$thisGame['gameGenre']) $failCount++;
		if(!$thisGame['gameDeveloper']) $failCount++;
		if(!$thisGame['gamePublisher']) $failCount++;
		if(!$thisGame['gamePlayers']) $failCount++;
		if($failCount < 5) continue;

		echo "
			<tr>
				<td class='gameNameCell'>{$thisGame['gameName']}</td>
				<td>{$thisGame['gameFilePlatform']}</td>
				<td ".($thisGame['gameReleaseDate'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gameRating'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gameGenre'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gameDeveloper'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gamePublisher'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
				<td ".($thisGame['gamePlayers'] != '' ? "class='goodCell'" : "class='badCell'")."></td>
			</tr>
		";
	}

	echo "</table>";
}

echo "<script>document.getElementById('searchGame').focus();</script></body></html>";

?>
