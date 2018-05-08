<?php

function randArray($array) {
	uasort($array, function ($a, $b) { return mt_rand(0, 1) > 0 ? 1 : -1; });
	return $array;
}

function xmlSafe($text) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function isBlacklistedUrl($url) {
	DBSingle("SELECT time FROM blacklistUrls WHERE url = ? LIMIT 1", array($url));
}

function blacklistUrl($url) {
	DBSimple("INSERT IGNORE INTO blacklistUrls (url, time) VALUES (?, ?)", array($url, time()));
}

// No newlines or semi-colons in AttractMode text
function makeAttractSafe($text) {
	$text = str_replace("\n", '', $text);
	$text = str_replace(";", ' -', $text);
	return trim($text);
}

function tidyRomName($name) {
	// Delete everything after the first bracket or parentheses
	$romNiceName = trim(preg_replace('%[\(|\[].*%', '', $name));
	// Correct name to "The Something" rather than "Something, The"
	if(preg_match('%, The(\s|$)%', $romNiceName)) { $romNiceName = 'The '.preg_replace('%, The(?!:[\s|$])%', '', $romNiceName); }
	if(preg_match('%, An(\s|$)%', $romNiceName)) { $romNiceName = 'An '.preg_replace('%, An(?!:[\s|$])%', '', $romNiceName); }
	if(preg_match('%, A(\s|$)%', $romNiceName)) { $romNiceName = 'A '.preg_replace('%, A(?!:[\s|$])%', '', $romNiceName); }
	$romNiceName = trim(preg_replace('%[ ]{2,}%', ' ', $romNiceName));

	return $romNiceName;
}

function getAmigaWhdloadGames() {
	// Create array of WHDLoaded Amiga games (array key is the game name)
	$amigaGameArray = json_decode(json_encode(simplexml_load_string(file_get_contents("resources/whdload.xml"))), 1)['game'];
	foreach($amigaGameArray as $thisAmigaGame) {
		unset($thisAmigaGame['@attributes'], $thisAmigaGame['cloneof'], $thisAmigaGame['genre-amiga'], $thisAmigaGame['rating'], $thisAmigaGame['crc']);
		$amigaSafeName = safeName($thisAmigaGame['description']);
		$amigaWhdloadArray[$amigaSafeName] = $thisAmigaGame;
	}
	return $amigaWhdloadArray;
}

function getx68kXmlGames() {
	// Create array of x68000 from MAME XML data file
	$x68kXmlGameArray = json_decode(json_encode(simplexml_load_string(file_get_contents("resources/x68k_flop.xml"))), 1)['software'];
	foreach($x68kXmlGameArray as $thisx68kGame) {
		$gameName = trim($thisx68kGame['description']);
		$safeName = safeName($gameName);
		$x68kGameArray[$safeName]['name'] = $gameName;
		// Sanitise year
		$realYear = intval(str_replace('?', '', $thisx68kGame['year']));
		if($realYear > 1900 && $realYear < 2100) $x68kGameArray[$safeName]['year'] = $realYear;
		// Get English publisher name
		preg_match('%(?:.+?) \((.+?)\)$%', $thisx68kGame['publisher'], $pubMatch);
		$x68kGameArray[$safeName]['publisher'] = $pubMatch[1];
	}
	return $x68kGameArray;
}

function getMameDatArray() {
	// Get MAME game real names
	$mameDatArray = explode("\n", cleanShell("mame -ll"));
	foreach($mameDatArray as $mameDatLine) {
		preg_match('%(\w+)\s+"(.+)"%', $mameDatLine, $mameDatParts);
		if($mameDatParts) {
			// Dual name, use the first part
			if(strstr($mameDatParts[2], '/')) $mameDatParts[2] = trim(explode('/', $mameDatParts[2])[0]);
			$mameArray[$mameDatParts[1]] = $mameDatParts[2];
		}
	}
	return $mameArray;
}

function htmlDecode($text) {
	return html_entity_decode($text, ENT_COMPAT, 'UTF-8');
}

function toUTF($text) {
	$encoding = mb_detect_encoding($text);
	if(!$encoding) $encoding = 'iso-8859-1';
	return iconv($encoding, 'UTF-8//TRANSLIT', $text);
}

function cleanShell($cmd) {
	return trim(shell_exec("{$cmd} 2>&1"));
}

function getPage($pageURL) {
	global $userAgent;

	$pageKey = md5($pageURL);
	## Try the new cache
	$page = DBSingle("SELECT cachePage FROM pageCache WHERE cacheKey = ? LIMIT 1", array($pageKey));
	## Try the old cache instead
	#if(!$page) $page = DBSingle("SELECT cachePage FROM pageCacheOld WHERE cacheKey = ? LIMIT 1", array($pageKey));

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
		$targetDomain = parse_url($pageURL)['host'];
		$referrer = "http://{$targetDomain}";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $pageURL);
		#curl_setopt($curl, CURLOPT_PROXY, 'aventurine.xnode.org:8000');
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_REFERER, $referrer);
		curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$page = toUTF(trim(curl_exec($curl)));
		$error = trim(curl_error($curl));
		curl_close($curl);

		// Report any errors
		if(
			$error ||
			stristr($page, 'Internal Server Error') ||
			stristr($page, 'The following error was encountered while trying to retrieve the URL')
		) {
			if(DEBUG) echo "\n[Page Grabber] cURL Failure - {$pageURL} ({$error})\n";
			return;
		}

		// Probably a blocker page
		if(stristr($page, 'CAPTCHA') || stristr($page, 'Blocked IP Address')) {
			unset($page);
			die("\n\n[Page Grabber] CAPTCHA failure [{$pageURL}]\n\n");
		}

		// Rate limited
		if(stristr($page, 'Rate limit exceeded')) {
			unset($page);
			if(DEBUG) echo "\n[Page Grabber] Hitting Rate Limit on {$targetDomain}\n";
			return;
		}

		// Site internal error
		if(stristr($page, 'Please try again in a few minutes')) {
			unset($page);
			if(DEBUG) echo "\n[Page Grabber] Site error page\n";
			return;
		}

		if($page) DBSimple("INSERT INTO pageCache SET cachePath = ?, cachePage = ?, cacheTime = ?, cacheKey = ?", array($pageURL, $page, time(), $pageKey));
	}

	if($page) return $page;
}

function cleanWhitespace($text) {
	$text = trim(preg_replace('%[\pZ\xA0\s]+%u', ' ', $text));
	$text = trim(preg_replace('%[\pC]+%u', '', $text));
	if(!$text) $text = null;
	return $text;
}

function updateGameDetails($sourceScraper, $gameName, $dbGameName, $platformName, $releaseDate = null, $genre = null, $description = null, $score = null, $developer = null, $publisher = null, $players = null, $mamestate = null, $override = null) {
	if(!$dbGameName || !$platformName) return false;

	// The value of this rating, is just too damn high!
	if(!is_numeric($score)) unset($score);
	if($score && ($score > 5 || $score < 0.1)) {
		echo "\n[DB System] {$gameName} - Invalid rating score of {$score} provided by {$sourceScraper}.\n";
		unset($score);
	}

	// Invalid player count
	if(!$players) unset($players);

	// Clean up Genre
	if($genre) $genre = trim(preg_replace('%\s*[\||/]\s*%', ', ', $genre));

	// Nonsense date, blank it
	if($releaseDate && (!is_numeric($releaseDate) || $releaseDate < 5000)) {
		echo "\n[DB System] {$gameName} - Nonsensical date provided by {$sourceScraper}.\n";
		unset($releaseDate);
	}

	// Description is too short to be viable, bin it
	if(strlen($description) < 80) unset($description);

	if($dbRow = DBSingle("SELECT gameName FROM games WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($dbGameName, $platformName))) {
		// Update specific game information elements
		if(isset($releaseDate))		DBSimple("UPDATE games SET gameReleaseDate = ?	WHERE ".(!$override ? "gameReleaseDate IS NULL AND" : "")."	gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($releaseDate, $dbGameName, $platformName));
		if(isset($genre))			DBSimple("UPDATE games SET gameGenre = ? 		WHERE ".(!$override ? "gameGenre IS NULL AND" : "")."		gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($genre), $dbGameName, $platformName));
		if(isset($description))		DBSimple("UPDATE games SET gameDescription = ?	WHERE ".(!$override ? "gameDescription IS NULL AND" : "")."	gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($description), $dbGameName, $platformName));
		if(isset($score))			DBSimple("UPDATE games SET gameRating = ? 		WHERE ".(!$override ? "gameRating IS NULL AND" : "")."		gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($score, $dbGameName, $platformName));
		if(isset($developer))		DBSimple("UPDATE games SET gameDeveloper = ? 	WHERE ".(!$override ? "gameDeveloper IS NULL AND" : "")."	gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($developer), $dbGameName, $platformName));
		if(isset($publisher))		DBSimple("UPDATE games SET gamePublisher = ? 	WHERE ".(!$override ? "gamePublisher IS NULL AND" : "")."	gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($publisher), $dbGameName, $platformName));
		if(isset($players))			DBSimple("UPDATE games SET gamePlayers = ?		WHERE ".(!$override ? "gamePlayers IS NULL AND" : "")."		gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($players), $dbGameName, $platformName));
		if(isset($mamestate))		DBSimple("UPDATE games SET gameMameState = ? 	WHERE ".(!$override ? "gameMameState IS NULL AND" : "")."	gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($mamestate), $dbGameName, $platformName));

		// Name has changed but we still matched, update that too
		if($dbRow != $gameName) {
			DBSimple("UPDATE games SET gameName = ? WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(cleanWhitespace($gameName), $dbGameName, $platformName));
			if(DEBUG) echo "\n[DB System] Game name updated for {$gameName}.\n";
		}

		// Mark as scanned by specific scanner (specifically ones which aren't likely to ever be updated)
		if($sourceScraper == 'MobyGamesAPI')	DBSimple("UPDATE games SET gameMobyChecked = ? 	WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array(1, $dbGameName, $platformName));
	}else{
		$dbRow = DBSimple("INSERT INTO games SET
			gameName = ?,
			gameMatchName = ?,
			gamePlatform = ?,
			gameReleaseDate = ?,
			gameGenre = ?,
			gameDescription = ?,
			gameRating = ?,
			gameDeveloper = ?,
			gamePublisher = ?,
			gamePlayers = ?,
			gameMameState = ?",
		array(
			cleanWhitespace($gameName),
			cleanWhitespace($dbGameName),
			cleanWhitespace($platformName),
			$releaseDate,
			cleanWhitespace($genre),
			cleanWhitespace($description),
			$score,
			cleanWhitespace($developer),
			cleanWhitespace($publisher),
			cleanWhitespace($players),
			cleanWhitespace($mamestate)
		));
	}

	if($dbRow && DEBUG) echo "\n[DB System] Rows updated by {$sourceScraper}.\n";
}

function blankToNull($array) {
	foreach($array as $key => $value) {
		if($value == '') {
			$array[$key] = null;
		}
	}

	return $array;
}

function isImage($filePath) {
	if(@is_array(getimagesize($filePath))){
		return true;
	}else{
		return false;
	}
}

function getImage($gameName, $imageURL, $assetFolder, $imgFolder = 'Box') {
	global $wgetLocal;

	// Don't bother trying blacklisted images
	if(isBlacklistedUrl($imageURL)) return false;

	// Save the image to temp for analysis
	$tempFile = "resources/tmp/".md5($imageURL);
	$referrer = 'http://'.parse_url($imageURL)['host'];
	cleanShell("{$wgetLocal} --referer={$referrer}/ ".escapeshellarg($imageURL)." -O '{$tempFile}'");

	// Check it downloaded ok
	if(file_exists($tempFile)) {
		if(isImage($tempFile) && filesize($tempFile) > 5000) {
			// Find the real filetype
			$fileExt = image_type_to_extension(exif_imagetype($tempFile), false);
			#$fileReport = cleanShell("file \"{$tempFile}\"");

			// Because why
			if($fileExt == 'jpeg') $fileExt = 'jpg';

			// Image isn't PNG or JPG, convert to PNG.
			if($fileExt != 'jpg' && $fileExt != 'png') {
				echo "\n[Image Manager] Converting image format (from {$fileExt}). " . cleanShell("mogrify -strip -format png \"{$tempFile}\"") . "\n";
				$fileExt = 'png';
			}

			// Move the image to the correct box-art folder
			$targetFile = EXTRAASSET_ROOT."/{$assetFolder}/{$imgFolder}/{$gameName}.{$fileExt}";

			rename($tempFile, $targetFile);
			if($fileExt == 'png') cleanShell("optipng -o7 \"{$targetFile}\" 2>&1");
			if($fileExt == 'jpg') cleanShell("exiv2 rm \"{$targetFile}\" 2>&1");
			return $targetFile;
		}else{
			if(DEBUG) echo "\n[Image Manager] Image too small. Probably garbage ({$imageURL}).\n";
			blacklistUrl($imageURL);
		}
	}else{
		if(DEBUG) echo "\n[Image Manager] Image failed to download ({$imageURL}).\n";
		blacklistUrl($imageURL);
	}

	// Delete the temp file as we only reach this point if it failed
	unlink($tempFile);
	return false;
}

function getTGDBWheelImage($gameName, $tgdbGameID, $assetFolder) {
	return getImage($gameName, "http://thegamesdb.net/banners/clearlogo/{$tgdbGameID}.png", $assetFolder, $imgFolder = 'Wheel');
}

function getTGDBSnapImage($gameName, $tgdbGameID, $assetFolder) {
	return getImage($gameName, "http://thegamesdb.net/banners/screenshots/{$tgdbGameID}-1.jpg", $assetFolder, $imgFolder = 'Snap');
}

function matchImage($folderPath, $gameName) {
	if(!is_dir($folderPath)) return;

	global $localImageArray;

	if(!isset($localImageArray[$folderPath]['files'])) {
		// Get a list of the image folder contents
		$localImageArray[$folderPath]['files'] = scandir($folderPath);

		// Copy the array to make a normalised version
		$fileArray = $localImageArray[$folderPath]['files'];

		// Loop through and clean each name
		$cleanFileArray = array();
		foreach($fileArray as $ff) {
			$ff = preg_replace('%\(.*%', '', $ff);
			$ff = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $ff);
			$ff = strtolower($ff);
			$ff = preg_replace("%\.(mp4|mpg|mkv|jpg|jpeg|png|bmp|tiff|svg)(\n|$)%", '', $ff);
			$ff = preg_replace("%[^a-z0-9\n]%", '', $ff);
			$ff = trim($ff);
			// Add to new clean array
			$cleanFileArray[] = $ff;
		}

		unset($fileArray);

		// Flip the array so that the image names become the keys
		$localImageArray[$folderPath]['simple'] = array_flip($cleanFileArray);
		unset($cleanFileArray);
	}

	// Remove brackets from the game name as well
	$gameName = safeName(preg_replace('%\(.*%', '', $gameName));

	// Check if the image name is in the array, if it is, return the original image name from the unmodified array
	if(isset($localImageArray[$folderPath]['simple'][$gameName])) {
		return "{$folderPath}/{$localImageArray[$folderPath]['files'][$localImageArray[$folderPath]['simple'][$gameName]]}";
	}
}

function detailsComplete($romSafeName, $platform) {
	$array = DBSingleAssoc("SELECT * FROM games WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($romSafeName, $platform));
	DBSimple("UPDATE games SET gamePing = ? WHERE gameID = ? LIMIT 1", array(START_TIME, $array['gameID']));

	if(!$array['gameDeveloper']) return false;
	if(!$array['gamePublisher']) return false;
	if(!$array['gameDescription']) return false;
	if(!$array['gameGenre']) return false;
	if(!$array['gameReleaseDate']) return false;
	if(!$array['gameRating']) return false;
	if(!$array['gamePlayers']) return false;
	if($platform == 'mame' && !$array['gamePlayers']) return false;
	if($platform == 'mame' && !$array['gameMameState']) return false;

	return true;
}

function missingCount($romSafeName, $platform) {
	$thisGame = DBSingleAssoc("SELECT * FROM games WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($romSafeName, $platform));

	$failCount = 0;
	if(!$thisGame['gameReleaseDate']) $failCount++;
	if(!$thisGame['gameRating']) $failCount++;
	if(!$thisGame['gameGenre']) $failCount++;
	if(!$thisGame['gameDeveloper']) $failCount++;
	if(!$thisGame['gamePublisher']) $failCount++;
	if(!$thisGame['gamePlayers']) $failCount++;

	return $failCount;
}

function needsScan($thisRom, $setParts, $reqPlatform = false) {
	if($reqPlatform && $setParts['assets'] != $reqPlatform) return false;
	if(!detailsComplete($thisRom['safeName'], $setParts['dbname'])) return true;
	return false;
}

function findImage($pathArray, $nameArray) {
	// Search for each name in order in each folder in order
	foreach($pathArray as $thisPath) {
		foreach($nameArray as $thisName) {
			$pathMatch = matchImage($thisPath, $thisName);
			if($pathMatch) return $pathMatch;
		}
	}
}

function getGameInfo($thisRom, $setParts, $thisSet) {
	global $romImage, $romWheelImage, $romSnapImage;
	$echoArray = $boxImgPaths = $snapImgPaths = $logoImgPaths = $gameNames = array();

	// Debug timer
	#$gsStart = microtime(1);

	// Find out current information set
	$gameDBOArr = DBSingleAssoc("SELECT * FROM games WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($thisRom['safeName'], $setParts['dbname']));
	if(!$gameDBOArr) $newGame = 1;

	$romImage = $romWheelImage = $romSnapImage = null;
	if(!FORCE_IMAGESCAN) {
		$romImage = $gameDBOArr['gameImage'];
		$romWheelImage = $gameDBOArr['gameWheelImage'];
		$romSnapImage = $gameDBOArr['gameSnapImage'];
	}

	$gameNames[] = $thisRom['name'];
	$gameNames[] = $thisRom['safeName'];

	// Mark game as already scanned on slow scrapers to speed up future scanning
	if($gameDBOArr['gameMobyChecked']) $skipMoby = true;
	

	##### Start Existing Image Location Assignment #############################

	// Different image order preference for MAME
	if($setParts['assets'] == 'MAME') {
		// Box Art
		$boxImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Advert";
		$boxImgPaths[] = ASSET_ROOT."/{$setParts['assets']}-Extra/flyer";
		$boxImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Title";
		$boxImgPaths[] = ASSET_ROOT."/{$setParts['assets']}-Extra/titles";
		// Wheel Art
		$logoImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Logos";
		$logoImgPaths[] = ASSET_ROOT."/{$setParts['assets']}-Extra/logo";
		#$logoImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Marquee";
		#$logoImgPaths[] = ASSET_ROOT."/{$setParts['assets']}-Extra/marquees";
	}

	// Find a box image
	$boxImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Box";

	// Fall back to 3D box images (they don't look as good)
	#$boxImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Box_3D";

	// Find a snap video or image using MAME's own Extras
	if($setParts['assets'] == 'MAME') {
		$snapImgPaths[] = ASSET_ROOT."/{$setParts['assets']}-Extra/videosnaps";
		$snapImgPaths[] = ASSET_ROOT."/{$setParts['assets']}-Extra/snap";
	}

	// Find a gameplay video or screenshot image
	#$snapImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Video_MP4_HI_QUAL";
	$snapImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Snap";

	// Find a wheel image
	$logoImgPaths[] = ASSET_ROOT."/{$setParts['assets']}/Logos";

	// If extra name / location is provided, check that too
	$al = 2;
	while($setParts['assets' . $al]) {
		$boxImgPaths[]  = ASSET_ROOT."/{$setParts['assets' . $al]}/Box";
		$boxImgPaths[]  = ASSET_ROOT."/{$setParts['assets' . $al]}/Box_3D";
		#$snapImgPaths[] = ASSET_ROOT."/{$setParts['assets' . $al]}/Video_MP4_HI_QUAL";
		$snapImgPaths[] = ASSET_ROOT."/{$setParts['assets' . $al]}/Snap";
		$logoImgPaths[] = ASSET_ROOT."/{$setParts['assets' . $al]}/Logos";
		$al++;
	}

	// Check for versions we've already downloaded
	$boxImgPaths[] = EXTRAASSET_ROOT."/{$setParts['assets']}/Box";
	$snapImgPaths[] = EXTRAASSET_ROOT."/{$setParts['assets']}/Snap";
	$logoImgPaths[] = EXTRAASSET_ROOT."/{$setParts['assets']}/Wheel";

	if(!$romImage) $romImage = findImage($boxImgPaths, $gameNames);
	if(!$romSnapImage) $romSnapImage = findImage($snapImgPaths, $gameNames);
	if(!$romWheelImage) $romWheelImage = findImage($logoImgPaths, $gameNames);

	##### End Existing Image Location Assignment #############################

	// Make the box folder in case it doesn't already exist before we try to work on it
	if(!$romImage) { $thisRom['missingImage'] = 1; }
	else{ $thisRom['missingImage'] = false; }

	// Make the snap folder in case it doesn't already exist before we try to work on it
	if(!$romSnapImage) { $thisRom['missingSnapImage'] = 1; }
	else{ $thisRom['missingSnapImage'] = false; }

	// Make the wheel folder in case it doesn't already exist before we try to work on it
	if(!$romWheelImage) { $thisRom['missingWheelImage'] = 1; }
	else{ $thisRom['missingWheelImage'] = false; }

	// Extra checks requested
	if(FORCE_IMAGESCAN || isset($newGame)) { $thisRom['imageChecks'] = 1; }
	else{ $thisRom['imageChecks'] = false; }

	if(needsScan($thisRom, $setParts)) { $thisRom['missingDetails'] = true; }else{ $thisRom['missingDetails'] = false; }

	// Only do lookups if the game doesn't already exist or we've explicitly requested additional checks
	if(!$gameDBOArr || $thisRom['missingDetails']) {
		if(DEBUG) $echoArray[] = "[{$thisSet}] [{$thisRom['niceName']}] Looking up details";

		// Good platform specific scrapers first, they tend to be more accurate than general scrapers
		if(needsScan($thisRom, $setParts)) launchboxDbScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['lbid'], $setParts['assets']);
		if(needsScan($thisRom, $setParts, 'MAME')) mameBinaryCheck($thisRom['niceName'], $thisRom['safeName'], $thisRom['name']);
		#if(needsScan($thisRom, $setParts, 'MAME')) mamedbScrape($thisRom['niceName'], $thisRom['safeName'], $thisRom['name']);
		#if(needsScan($thisRom, $setParts, 'MAME')) mameFileScrape($thisRom['niceName'], $thisRom['safeName'], $thisRom['name']);
		if(needsScan($thisRom, $setParts, 'Commodore_Amiga')) holScrape($thisRom['niceName'], $thisRom['safeName'], $setParts['dbname']);
		if(needsScan($thisRom, $setParts, 'Commodore_Amiga_CD32')) holScrape($thisRom['niceName'], $thisRom['safeName'], $setParts['dbname']);
		if(needsScan($thisRom, $setParts, 'Doom')) doomworldScraper($thisRom['safeName'], $thisRom['niceName']);
		if(needsScan($thisRom, $setParts, 'PICO-8')) lexaloffleScraper($thisRom['niceName'], $thisRom['safeName']);

		// General scrapers
		#if(needsScan($thisRom, $setParts)) openvgdbScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['ovgdb'], $setParts['assets']);
		if(needsScan($thisRom, $setParts)) thegamesdbScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['tgdbid'], $setParts['assets']);
		if(needsScan($thisRom, $setParts) && !$skipMoby) mobyLocalScrape($thisRom['safeName'], $thisRom['niceName'], $setParts['dbname'], $setParts['mobyid']);
		#if(needsScan($thisRom, $setParts)) archivevgScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['archivevgname'], $setParts['assets']);
		#if(needsScan($thisRom, $setParts) && !$skipMoby) mobyScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['mobyid'], $setParts['assets']);
		#if(needsScan($thisRom, $setParts)) allgamecomScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['allgamename'], $setParts['assets']);
		#if(needsScan($thisRom, $setParts)) ignScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['ignname'], $setParts['assets']);
		#if(needsScan($thisRom, $setParts)) rfgenScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['rfgenid'], $setParts['assets']);
		#if(needsScan($thisRom, $setParts)) giantbombScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['gbid'], $setParts['assets']);
		#if(needsScan($thisRom, $setParts)) gamefaqsScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['gamefaqsid'], $setParts['assets']);

		// Normal platform specific scrapers
		if(needsScan($thisRom, $setParts, 'Amstrad_CPC')) amstradcpcwikiScraper($thisRom['niceName'], $thisRom['safeName']);
		if(needsScan($thisRom, $setParts, 'Commodore_Amiga')) amigaWhdloadXmlScraper($thisRom['safeName'], $thisRom['niceName']);
		if(needsScan($thisRom, $setParts, 'Amstrad_CPC')) cpcpowerFileScraper($thisRom['safeName'], $thisRom['niceName']);
		if(needsScan($thisRom, $setParts, 'Sharp_X68000')) sharp68kXmlScraper($thisRom['safeName'], $thisRom['niceName']);
		if(needsScan($thisRom, $setParts, 'GCE_Vectrex')) vectrexgdbScraper($thisRom['safeName'], $thisRom['niceName']);
		if(needsScan($thisRom, $setParts, 'Philips_CD_i')) pcdicomScraper($thisRom['safeName'], $thisRom['niceName'], $setParts['assets']);
		if(needsScan($thisRom, $setParts, 'NEC_PC_FX')) pcfxwikiScraper($thisRom['niceName'], $thisRom['safeName']);
		if(needsScan($thisRom, $setParts, 'Sinclair_ZX_Spectrum')) wosScraper($thisRom['safeName'], $thisRom['niceName'], $setParts['assets']);
		if(needsScan($thisRom, $setParts, 'Sinclair_ZX_Spectrum')) zxspectrumwikiScraper($thisRom['safeName'], $thisRom['niceName']);
		if(needsScan($thisRom, $setParts, 'Touhou')) touhouwikiaScraper($thisRom['safeName'], $thisRom['niceName'], $setParts['assets']);

		// Give up and store empty game in the DB
		if(needsScan($thisRom, $setParts)) updateGameDetails('Dummy', $thisRom['niceName'], $thisRom['safeName'], $setParts['dbname']);
	}

	if(DEBUG) if($thisRom['missingDetails'] && detailsComplete($thisRom['safeName'], $setParts['dbname'])) $echoArray[] = "[{$thisSet}] [{$thisRom['niceName']}] All information found.";

	// Search local LBDB for artwork regardless
	if((!$romImage || !$romSnapImage || !$romWheelImage) && isset($setParts['lbid'])) launchboxDbScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['lbid'], $setParts['assets']);

	// Check for external images
	if($thisRom['imageChecks'] && !$romImage) {
		if(!$romImage) thegamesdbScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['tgdbid'], $setParts['assets'], true);
		#if(!$romImage) archivevgScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['archivevgname'], $setParts['assets'], true);
		#if(!$romImage && !$skipMoby) mobyScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['mobyid'], $setParts['assets'], true);
		#if(!$romImage) ignScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['ignname'], $setParts['assets'], true);
		#if(!$romImage) allgamecomScraper($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['allgamename'], $setParts['assets'], true);
		#if(!$romImage) giantbombScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['gbid'], $setParts['assets'], true);

		// Get the wheel artwork (handled by the TGDB scraper)
		if(!$romWheelImage) thegamesdbScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['tgdbid'], $setParts['assets'], false, true);
		// Get the snap artwork (handled by the TGDB scraper)
		if(!$romSnapImage) thegamesdbScrape($thisRom['safeName'], $setParts['dbname'], $thisRom['niceName'], $setParts['tgdbid'], $setParts['assets'], false, false, true);
	}

	// Give up on finding an image
	if(DEBUG) if(!$romImage) $echoArray[] = "[{$thisSet}] [{$thisRom['niceName']}] No Images Found.";

	// Update image path and name
	DBSimple("UPDATE games SET gameImage = ?, gameWheelImage = ?, gameSnapImage = ?, gameName = ? WHERE gameMatchName = ? AND gamePlatform = ?", array($romImage, $romWheelImage, $romSnapImage, $thisRom['niceName'], $thisRom['safeName'], $setParts['dbname']));

	// Read the game info back from the DB
	$gameDBArr = DBSingleAssoc("SELECT * FROM games WHERE gameMatchName = ? AND gamePlatform = ? LIMIT 1", array($thisRom['safeName'], $setParts['dbname']));

	// Report what new details we've found
	$foundItems = array();
	if(!$gameDBOArr['gameReleaseDate'] && $gameDBArr['gameReleaseDate']) $foundItems[] = 'Year';
	if(!$gameDBOArr['gameGenre'] && $gameDBArr['gameGenre']) $foundItems[] = 'Genre';
	if(!$gameDBOArr['gameDescription'] && $gameDBArr['gameDescription']) $foundItems[] = 'Description';
	if(!$gameDBOArr['gameRating'] && $gameDBArr['gameRating']) $foundItems[] = 'Rating';
	if(!$gameDBOArr['gameDeveloper'] && $gameDBArr['gameDeveloper']) $foundItems[] = 'Developer';
	if(!$gameDBOArr['gamePublisher'] && $gameDBArr['gamePublisher']) $foundItems[] = 'Publisher';
	if(!$gameDBOArr['gamePlayers'] && $gameDBArr['gamePlayers']) $foundItems[] = 'Players';
	if(!$gameDBOArr['gameMameState'] && $gameDBArr['gameMameState']) $foundItems[] = 'MAME State';
	if($foundItems) $echoArray[] = "[{$thisSet}] [{$thisRom['niceName']}] New Details: ".implode(', ', $foundItems).".";

	// Report if we've found any images
	$foundImages = array();
	if($thisRom['missingImage'] && $romImage) $foundImages[] = "Box";
	if($thisRom['missingSnapImage'] && $romSnapImage) $foundImages[] = "Screenshot";
	if($thisRom['missingWheelImage'] && $romWheelImage) $foundImages[] = "Logo";
	if($foundImages) $echoArray[] = "[{$thisSet}] [{$thisRom['niceName']}] New Images: ".implode(', ', $foundImages).".";

	// Print any details
	if($echoArray) echo "\n".implode("\n", $echoArray)."\n";

	// Debug timer printout for profiling
	#echo round(microtime(1) - $gsStart, 3) . ' ' . PHP_EOL;
	return $gameDBArr['gameID'];
}

function romScan($romFile, $thisSet, $setParts, $mameGameArr) {
	global $timeNow, $mameArray, $romImage, $romWheelImage, $romSnapImage;

	$thisRom['file'] = trim($romFile);

	// This is a folder (and not a symlink) - abort
	if($thisRom['file'] == '.' || $thisRom['file'] == '..') return;
	#if(is_dir("{$setParts['folder']}/{$thisRom['file']}") && !is_link("{$setParts['folder']}/{$thisRom['file']}")) return;

	// Get file information
	$fileInfo = pathinfo($thisRom['file']);
	$thisRom['ext'] = strtolower(trim($fileInfo['extension']));
	$thisRom['name'] = basename($thisRom['file'], ".{$thisRom['ext']}");

	// Make sure it fits the extensions we're looking for
	if($setParts['extensions']) {
		if(!preg_match("%({$setParts['extensions']})%", $thisRom['ext'])) return;
	}

	// Special handling for Doom / PRBoom
	if($setParts['assets'] == 'Doom') {
		// This is the PRBoom control file, skip it
		if($thisRom['name'] == 'prboom') return;
	}

	// Special handling for MAME
	if($setParts['assets'] == 'MAME') {
		// Only add wanted files
		if(!isset($mameGameArr[$thisRom['name']])) return;

		// Game appears to be non-English, note and skip
		if(strstr($mameArray[$thisRom['name']], '(Japan')) {
			#if(DEBUG) echo "\n[{$thisSet}] [{$mameArray[$thisRom['name']]}] Skipping {$thisRom['name']} as non-English.\n";
			file_put_contents(MAME_SKIP, "{$thisRom['name']} ## {$mameArray[$thisRom['name']]}\n", FILE_APPEND);
			#return;
		}

		// Replace short name (aerofgts) with real name (Aero Fighters)
		$thisRom['realName'] = $mameArray[$thisRom['name']];
	}else{
		$thisRom['realName'] = $thisRom['name'];
	}

	// This is the BIN part of a BIN/CUE pair, skip it
	if(($thisRom['ext'] == 'bin' || $thisRom['ext'] == 'iso') && file_exists(preg_replace('%\s*\(Track 0?1\)%', '', "{$setParts['folder']}/{$thisRom['name']}.cue"))) return;

	// This is the MDS part of a MDS/MDF pair, skip it
	if($thisRom['ext'] == 'mds' && file_exists("{$setParts['folder']}/{$thisRom['name']}.mdf")) return;

	// Not the first disk of a set, skip
	preg_match('%(?:\(|\[)(?:Track|Side|Part|Dis(?:c|k)) (\d+)%', $thisRom['name'], $diskNumber);
	if($diskNumber) if($diskNumber[1] > 1) return;

	// Create clean versions of the name
	$thisRom['niceName'] = tidyRomName($thisRom['realName']);
	$thisRom['safeName'] = safeName($thisRom['niceName']);

	// Set the real path to the game
	$thisRom['fullPath'] = "{$setParts['folder']}/{$thisRom['file']}";

	// Go get info about the game and perform lookups if needed
	$thisRom['gameID'] = getGameInfo($thisRom, $setParts, $thisSet);

	// Set the emulator system name to the Database name unless it's explicitly overriden
	if(!isset($setParts['esname'])) $setParts['esname'] = $setParts['dbname'];

	// Store raw info about the file in the DB (File + Platform is the key)
	DBSimple("INSERT IGNORE INTO gameFiles SET gameFile = ?, gameFileUpdateTime = ?, gameFilePath = ?, gameFilePlatform = ?, gameID = ?", array($thisRom['file'], $timeNow, $thisRom['fullPath'], $setParts['esname'], $thisRom['gameID']));
}

// Take a name and turn it into a clean, normalised string
function safeName($name) {
	$name = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $name);
	$name = strtolower($name);
	$name = preg_replace('%[^a-z0-9]%', '', $name);
	$name = trim($name);
	return $name;
}

function cutText($text, $cutLength) {
	$internalBuffer = 50;
	$allowedOverflow = 40;
	// Shorten description to around 250 characters (if it's over 260 characters, cut to the nearest new sentence)
	if(strlen($text) > ($cutLength + $allowedOverflow)) {
		// Find the end of a sentence near the cutoff, go back 50 characters to give us a buffer
		$descCutPoint = strpos($text, '. ', ($cutLength - $internalBuffer));
		// Add one to maintain the trailing period
		if($descCutPoint) $descCutPoint++;
		// If we didn't find a position or it was too far ahead, just cut and elipsis the text
		if(!$descCutPoint || $descCutPoint > ($cutLength + $allowedOverflow)) {
			$text = substr($text, 0, $cutLength) . '...';
		}else{
			$text = substr($text, 0, $descCutPoint) . '...';
		}
	}

	return ucfirst(trim($text));
}

function numToFloat($number) {
	// Emulation Station needs the rating as a float on a scale of 0.0 to 1.0
	return number_format(round($number / 5, 1), 1);
}

function getMobyPage($dbGameName, $gameName, $mgid) {
	$page = getPage("http://www.mobygames.com/search/quick?ajax=1&sFilter=1&offset=0&q=".rawurlencode($gameName)."&p={$mgid}&sG=on");
	if($page) {
		$pageArray = json_decode($page, 1);
		$pageData = trim(html_entity_decode($pageArray['resultHTML'], ENT_QUOTES, 'UTF-8'));
		preg_match('%href="/game/(.+?)"%', $pageData, $gamePageAddr);

		// Match the names, make sure it's not completely wrong ## Game: <a href="/game/frogger">Frogger</a> (<em>a.k.a.</em> The Official Frogger) ## Game: <a href="/game/windows/office">The Office</a>
		preg_match('%Game: <a href="/game/(?:.+?)">(.+?)</a>(?: \(<em>a\.k\.a\.</em> (.+?)\))?%', $pageData, $gameNameMatch);

		if(!nameCompare($dbGameName, $gameNameMatch[1]) && !nameCompare($dbGameName, $gameNameMatch[2])) return false;

		$gamePageAddr = "http://www.mobygames.com/game/".trim($gamePageAddr[1]);
		$page = trim(html_entity_decode(getPage($gamePageAddr), ENT_QUOTES, 'UTF-8'));

		if($page) return $page;
	}
}

function findFile($path) {
	return @glob($path)[0];
}

function integerToRoman($integer) {
	// Convert the integer into an integer (just to make sure)
	$integer = intval($integer);
	$result = '';

	if($integer > 1000000000) {
		echo "\nCrazy Int {$integer}\n";
		die;
		return 'ROMANNUMFAILURE';
	}

	// Create a lookup array that contains all of the Roman numerals.
	$lookup = array('M' => 1000,
	'CM' => 900,
	'D' => 500,
	'CD' => 400,
	'C' => 100,
	'XC' => 90,
	'L' => 50,
	'XL' => 40,
	'X' => 10,
	'IX' => 9,
	'V' => 5,
	'IV' => 4,
	'I' => 1);

	foreach($lookup as $roman => $value){
		// Determine the number of matches
		$matches = intval($integer / $value);

		// Add the same number of characters to the string
		$result .= str_repeat($roman, $matches);

		// Set the integer to be the remainder of the integer and the value
		$integer = $integer % $value;
	}

	// The Roman numeral should be built, return it
	return $result;
}

function nameCompare($name, $otherName, $returnPercent = 0) {
	if(!$name || !$otherName) return false;
	$acceptableMatchPercent = 98;

	$name = tidyRomName($name);
	$otherName = tidyRomName($otherName);
	$normalName = $name;

	$name = trim(preg_replace('%[^a-z0-9]%', '', strtolower($name)));
	$otherName = trim(preg_replace('%[^a-z0-9]%', '', strtolower($otherName)));
	similar_text($name, $otherName, $matchPercent);
	if($matchPercent > $acceptableMatchPercent) {
		if($returnPercent) {
			return $matchPercent;
		}else{
			return true;
		}
	}

	// Convert to Roman Numerals and try again
	preg_match_all('%(?:^|[^\d])(\d+)(?:[^\d]|$)%', $normalName, $numberBlocks, PREG_SET_ORDER);

	if($numberBlocks) {
		foreach($numberBlocks as $thisNumBlock) {
			$thisRoman = integerToRoman(trim($thisNumBlock[1]));
			$normalName = str_replace($thisNumBlock[1], $thisRoman, $normalName);
		}

		similar_text($normalName, $otherName, $matchPercent);
		if($matchPercent > $acceptableMatchPercent) {
			if($returnPercent) {
				return $matchPercent;
			}else{
				return true;
			}
		}
	}
}

function getGiantbombPage($page) {
	$gbPage = getPage($page);
	if($gbPage) $gbRaw = json_decode($gbPage, true);

	// Failed for some reason, wait a bit and try again as it may be rate limiting
	$gbLoopCount = 0;
	$gbSleepTime = $gbBaseSleepTime = 30;
	while(!$gbPage && $gbLoopCount <= 5) {
		if(DEBUG) echo "\n[GiantBomb Scraper] Failed. Sleeping for {$gbSleepTime} seconds.";
		sleep($gbSleepTime);
		$gbPage = getPage($page);
		if($gbPage) {
			$gbRaw = json_decode($gbPage, true);
			$gbLoopCount++;
			$gbSleepTime = $gbBaseSleepTime + $gbSleepTime;
		}
	}

	if(!$gbRaw) {
		if(DEBUG) echo "\n[GiantBomb Scraper] Giving up on {$page}";
	}else{
		return $gbRaw;
	}
}

function trimWebSpaces($code) {
	return trim(str_replace("\n", '', $code));
}

function getMameGameXml($romName) {
	$gameXml = DBSingle("SELECT mameXml FROM mameGameXml WHERE gameMatchName = ? LIMIT 1", array($romName));

	if(!$gameXml) {
		$gameXml = @simplexml_load_string(cleanShell("mame -listxml '{$romName}' 2>/dev/null"));
		if($gameXml) {
			$gameXml = json_encode($gameXml);
			if($gameXml) DBSimple("INSERT INTO mameGameXml SET gameMatchName = ?, mameXml = ?", array($romName, $gameXml));
		}else{
			echo "\n[MAME Binary XML] Game information failure for {$romName}.\n";
		}
	}

	if($gameXml) return json_decode($gameXml, true)['machine'][0];
}

function populateLaunchboxDb() {
	// If the LaunchboxDB is older than 2 days old, refresh
	if(time() - @filemtime('resources/launchboxDB.zip') > 86400) {
		echo "\n[Launchbox Database] Downloading new database.";
		cleanShell("wget 'http://gamesdb.launchbox-app.com/Metadata.zip' -O resources/launchboxDB.zip 2>&1");

		// Catch failed downloads
		if(@filesize('resources/launchboxDB.zip') < 5000000) {
			echo "\n[Launchbox Database] Download too small. Aborting update.\n";
			return false;
		}

		echo "\n[Launchbox Database] Extracting.";
		cleanShell("7z e -y -oresources resources/launchboxDB.zip Metadata.xml 2>&1");

		if(file_exists('resources/Metadata.xml')) {
			echo "\n[Launchbox Database] Truncating internal database.";
			DBSimple("TRUNCATE TABLE launchboxDb");
			echo "\n[Launchbox Database] Processing and importing.\n";

			// Get the data array if we don't already have it
			$launchboxRaw = json_decode(json_encode(simplexml_load_string(file_get_contents('resources/Metadata.xml'), null, LIBXML_NOCDATA)), true);
			$launchboxArray = $launchboxRaw['Game'];
			$launchboxImageArray = $launchboxRaw['GameImage'];

			// Free up some RAM as these vars are huge
			unset($launchboxRaw);

			foreach($launchboxImageArray as $thisLaunchImage) {
				$lbImageArray[$thisLaunchImage['DatabaseID']][$thisLaunchImage['Type']] = $thisLaunchImage['FileName'];
			}
			// Free RAM again
			unset($launchboxImageArray);

			foreach($launchboxArray as $thisLaunchboxGame) {
				unset($gameFrontImage);
				if($thisLaunchboxGame['Platform']) {
					if(empty($thisLaunchboxGame['Genres'])) {
						// Null rather than broken/empty array
						unset($thisLaunchboxGame['Genres']);
					}else{
						// Convert delimeter from semi-colons to commas
						$thisLaunchboxGame['Genres'] = trim(str_replace(';', ',', $thisLaunchboxGame['Genres']));
					}

					$thisLaunchboxGame = array_map('trim', $thisLaunchboxGame);

					// Use the front box art, otherwise fall back to advertisement flyer
					if($lbImageArray[$thisLaunchboxGame['DatabaseID']]['Box - Front']) {
						$gameFrontImage = $lbImageArray[$thisLaunchboxGame['DatabaseID']]['Box - Front'];
					}elseif($lbImageArray[$thisLaunchboxGame['DatabaseID']]['Advertisement Flyer - Front']) {
						$gameFrontImage = $lbImageArray[$thisLaunchboxGame['DatabaseID']]['Advertisement Flyer - Front'];
					}

					DBSimple(
						"INSERT IGNORE INTO launchboxDb SET lbdb_id = ?, lbdb_name = ?, lbdb_safename = ?, lbdb_year = ?, lbdb_players = ?, lbdb_rating = ?, lbdb_description = ?, lbdb_platform = ?, lbdb_developer = ?, lbdb_publisher = ?, lbdb_genre = ?, lbdb_boxart = ?, lbdb_logoart = ?, lbdb_snapart = ?",
						array($thisLaunchboxGame['DatabaseID'], $thisLaunchboxGame['Name'], safeName($thisLaunchboxGame['Name']), $thisLaunchboxGame['ReleaseYear'], $thisLaunchboxGame['MaxPlayers'], round($thisLaunchboxGame['CommunityRating'], 1), $thisLaunchboxGame['Overview'], $thisLaunchboxGame['Platform'], $thisLaunchboxGame['Developer'], $thisLaunchboxGame['Publisher'], $thisLaunchboxGame['Genres'], $gameFrontImage, $lbImageArray[$thisLaunchboxGame['DatabaseID']]['Clear Logo'], $lbImageArray[$thisLaunchboxGame['DatabaseID']]['Screenshot - Gameplay'])
					);
				}
			}

			return true;
		}else{
			echo "\n[Launchbox Database] Error downloading/extracting database.\n";
			return false;
		}
	}
}

function cloneCheck($romName) {
	$parentName = getMameGameXml($romName)['@attributes']['cloneof'];
	if($parentName) return $parentName;
}

function translateWinPath($romPath) {
	global $windowsTranslation;
	return str_replace('/', '\\', str_replace(key($windowsTranslation), current($windowsTranslation), $romPath));
}

function cleanUnixtime($dateString) {
	// Too small, probably just a year
	if($dateString > 1800 && $dateString < 3000) {
		$dateString = "1/1/{$dateString}";
	}

	// Set current date to "1/1/2000" (946684800) and adjust based on the game's date
	if($dateString) {
		$unixDate = strtotime(trim(strip_tags($dateString)), 946684800);
		if($unixDate) return $unixDate;
	}
}

function dateToES($dateString) {
	if($dateString) return date('Ymd\T000000', cleanUnixtime($dateString));
}

?>
