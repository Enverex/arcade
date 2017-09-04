<?php

#####################################################################################################################
## Copyright: Benjamin Hodgetts (Enverex) <ben@xnode.org>
## Description: Game image and detail scraper for Emulation Station.
## This is designed to be ran on a Linux server (e.g. a NAS) that holds all the ROM files.
## Requirements: PHP, SDLMame, cURL, ImageMagick, WGet.
## ToDo: Still very specific to my setup, need to make the code more "generic".
## Scrapers: mameDB, Hall of Light, DoomWorld, TheGamesDB, Archive.VG, MobyGames, AllGame (defunct), IGN,
##			OpenVGDB, RFGeneration, GiantBomb, GameFAQs, AmstradCPC Wiki, cpcPower, VectrexGDB, PCDI, PCFXWiki,
##			World of Spectrum, ZXSpectrum Wiki, TouhouWiki.
#####################################################################################################################

## Usage: ./romScan.php (linux/windows) (scan/generate)

// System Init
require_once('include/init.php');

// Target System Config File Setup
$configFile = parse_ini_file("resources/config-{$genPlatform}.ini");
if(!$configFile) die("Unable to find configuration file.");
define('ASSET_ROOT', $configFile['assetRoot']);
define('EXTRAASSET_ROOT', $configFile['extraAssetRoot']);
define('MESS_PATH', $configFile['messPath']);

require_once('include/systems.php');
$mameArray = array();
$amigaWhdloadArray = array();
$thegamedbTest = '';

// What to do?
if($mode == 'scan') {
	doScan();
}elseif($mode == 'generate') {
	doGenerate();
}else{
	doScan();
	doGenerate();
}


function doScan() {
	global $configFile, $systemArr, $windowsTranslation, $mameArray, $amigaWhdloadArray;

	// Uncomment this to import the Launchbox DB
	populateLaunchboxDb();

	// Create backup of the games table
	if(BACKUPS_CREATE) {
		echo "\nCreating backup...\n";
		@mkdir("backups", 0777, true);
		cleanShell("mysqldump -ugamedb -pgamedb gamedb games -ce --hex-blob | lzma -9 > \"backups/$(date +%y%m%d%H%M%S).sql.xz\"");
	}

	// List of all the MAME games that we actually want included (rather than all of them)
	$mameGameArr = explode("\n", trim(strtolower(file_get_contents(MAME_WANT))));
	$mameGameArr = array_map('trim', $mameGameArr);

	// Sort it alphabetically, remove dupes and save back to original file for easy future editing
	sort($mameGameArr);
	$mameGameArr = array_unique($mameGameArr);
	file_put_contents(MAME_WANT, trim(implode("\n", $mameGameArr)));
	@unlink(MAME_SKIP);

	// Create array of WHDLoaded Amiga games (array key is the game name)
	$amigaGameArray = json_decode(json_encode(simplexml_load_string(file_get_contents("resources/whdload.xml"))), 1)['game'];
	foreach($amigaGameArray as $thisAmigaGame) {
		unset($thisAmigaGame['@attributes'], $thisAmigaGame['cloneof'], $thisAmigaGame['genre-amiga'], $thisAmigaGame['rating'], $thisAmigaGame['crc']);
		$amigaSafeName = safeName($thisAmigaGame['description']);
		$amigaWhdloadArray[$amigaSafeName] = $thisAmigaGame;
	}
	unset($amigaGameArray, $amigaSafeName);

	// Make sure we have all the MAME games that we actually want
	echo "\n=============================\n Verifying MAME Games \n=============================\n\n";
	foreach($mameGameArr as $mameGameKey => $thisMameGame) {
		unset($parentName);
		if(!file_exists("/mnt/store/Emulation/Games/MAME/{$thisMameGame}.zip")) {
			echo "[MAME ROM Verification] ROM file {$thisMameGame} doesn't exist, trying to link with parent.".PHP_EOL;
			$parentName = cloneCheck($thisMameGame);
			if(isset($parentName) && file_exists("/mnt/store/Emulation/Games/MAME/{$parentName}.zip")) {
				echo "[MAME ROM Verification] Linking to parent ROM {$parentName}.".PHP_EOL;
				cleanShell("ln -s /mnt/store/Emulation/Games/MAME/{$parentName}.zip /mnt/store/Emulation/Games/MAME/{$thisMameGame}.zip");
			}else{
				echo "[MAME ROM Verification] Parent ROM ({$parentName}) does not exist.".PHP_EOL;
			}
		}
	}

	// Flip array keys and values for easy matching
	$mameGameArr = array_flip($mameGameArr);

	// Get MAME game real names
	if(!file_exists(MAME_DAT)) cleanShell("sdlmame -ll > ".MAME_DAT);
	$mameDatArray = explode("\n", trim(file_get_contents(MAME_DAT)));
	foreach($mameDatArray as $mameDatLine) {
		preg_match('%(\w+)\s+"(.+)"%', $mameDatLine, $mameDatParts);
		if($mameDatParts) {
			// Dual name, use the first part
			if(strstr($mameDatParts[2], '/')) $mameDatParts[2] = trim(explode('/', $mameDatParts[2])[0]);
			$mameArray[$mameDatParts[1]] = $mameDatParts[2];
		}
	}

	// Create array of games from the CPC-Power database
	$cpcPowerFileArray = explode("\n", trim(file_get_contents(CPC_DB)));
	foreach($cpcPowerFileArray as $cpcPowerGame) {
		## Name;Year;Developer
		$cpcPowerGameParts = explode(';', $cpcPowerGame);
		$cpcPowerArray[$cpcPowerGameParts[0]]['year'] = $cpcPowerGameParts[1];
		$cpcPowerArray[$cpcPowerGameParts[0]]['dev'] = $cpcPowerGameParts[2];
	}

	echo "\n\n====================\nStarting Scan...\n====================\n\n";

	DBSimple("TRUNCATE TABLE gameFiles");

	foreach($systemArr as $thisSet => $setParts) {
		echo "\n[{$thisSet}] Starting scan...\n";

		DBSimple("INSERT IGNORE INTO platforms (platformName, gamePlatform) VALUES (?, ?)", array($thisSet, $setParts['dbname']));

		// Get list of files in this system ROM folder
		if(is_dir($setParts['folder'])) {
			$thisFolder = scandir($setParts['folder']);
			$romCount = count($thisFolder);

			// Create folders
			@mkdir(ASSET_ROOT."/Live/{$setParts['assets']}/Box", 0777, true);
			@mkdir(ASSET_ROOT."/Live/{$setParts['assets']}/Snap", 0777, true);
			@mkdir(ASSET_ROOT."/Live/{$setParts['assets']}/Wheel", 0777, true);
			@mkdir(EXTRAASSET_ROOT."/{$setParts['assets']}/Box", 0777, true);
			@mkdir(EXTRAASSET_ROOT."/{$setParts['assets']}/Snap", 0777, true);
			@mkdir(EXTRAASSET_ROOT."/{$setParts['assets']}/Wheel", 0777, true);

			$progressSymbol = '';
			foreach($thisFolder as $romID => $romFile) {
				// Skip parent folders
				if($romFile == '.' || $romFile == '..') continue;

				// Display Progress
				$progressPerc = round((($romID +1) / $romCount) * 100);
				$progressRem = 100 - $progressPerc;

				$progressBar = str_repeat(' ', $progressPerc);
				$progressSpace = str_repeat('•', $progressRem);

				if($progressSymbol == 'c') { $progressSymbol = 'C'; }else{ $progressSymbol = 'c'; }

				#echo "\033[0K\033[1K\r[{$thisSet}] Progress: [{$progressBar}{$progressSymbol}{$progressSpace}]";
				echo "[{$thisSet}] Progress: [{$progressBar}{$progressSymbol}{$progressSpace}]\r";

				romScan($romFile, $thisSet, $setParts, $mameGameArr);
			}
		}
	}

	echo "\n\n====================\nScan Completed\n====================\n\n";

	// Find how many games no-longer seem to exist
	$lastUpdateTime = DBSingle("SELECT MAX(gameFileUpdateTime) FROM gameFiles");
	$oldGames = DBSingle("SELECT COUNT(*) FROM gameFiles WHERE gameFileUpdateTime != ?", array($lastUpdateTime));
	echo "\n{$oldGames} games no-longer seem to exist.";

	// Sanity check of 300
	/*
	if($deleteMissing && $lastUpdateTime && $oldGames < 4000) {
		echo "\nDeleting {$oldGames} old database entries for games that no-longer seem to exist.";
		DBSimple("DELETE FROM games WHERE gameUpdateTime != ?", array($lastUpdateTime));
	}
	*/
}

function doGenerate() {
	global $configFile, $systemArr, $windowsTranslation;
	$systemListEs = $systemListAm = '';
	if(isset($configFile['linuxTrPath'])) $windowsTranslation = array($configFile['linuxTrPath'] => $configFile['windowsTrPath']);

	foreach($systemArr as $thisSet => $setParts) {
		unset($platformExtList);
		$gameListEs = $gameListAm = $gameListRa = '';

		echo "\n[{$thisSet}] [{$setParts['dbname']}] Creating game list files.";

		// Set emulator, use RetroArch if a core has been set
		if(isset($setParts['retrocore'])) {
			// DLL for Windows, SO for Linux
			if(isset($windowsTranslation)) {
				$retroCoreExt = 'dll';
			}else{
				$retroCoreExt = 'so';
			}

			if(!isset($setParts['retroops'])) $setParts['retroops'] = '';
			if(isset($setParts['messname'])) {
				$emulatorCommand = "{$configFile['retroArch']}";
				$emulatorArgs = "-f {$setParts['retroops']} -L {$configFile['retroCores']}/{$setParts['retrocore']}_libretro.{$retroCoreExt} \"{$setParts['messname']} -rompath \\\"".MESS_PATH."\\\" -{$setParts['messtype']} \\\"ROMGOESHERE\\\"\"";
			}else{
				$emulatorCommand = "{$configFile['retroArch']}";
				$emulatorArgs = "-f {$setParts['retroops']} -L {$configFile['retroCores']}/{$setParts['retrocore']}_libretro.{$retroCoreExt} \"ROMGOESHERE\"";
			}
		}else{
			if(!isset($windowsTranslation) && isset($setParts['lemcom'])) {
				$emulatorCommand = $setParts['lemcom'];
				$emulatorArgs = $setParts['lemarg'];
			}else{
				$emulatorCommand = $setParts['emcom'];
				$emulatorArgs = $setParts['emarg'];
			}
		}

		$esRomPath = $setParts['folder'];

		// Convert to Windows Path if requested
		if(isset($windowsTranslation)) {
			$esRomPath = translateWinPath($esRomPath);
		}

		// Extension list
		$platformExtBits = explode('|', $setParts['extensions']);
		$platformExtList = '';
		foreach($platformExtBits as $thisPlatformExt) {
			$platformExtList .= ".{$thisPlatformExt} ";
		}
		$platformExtList = trim($platformExtList);

		// We have a system that uses another systems artwork, switch the selection
		if(isset($setParts['esname'])) { $dbPlatform = $setParts['esname']; }else{ $dbPlatform = $setParts['esname'] = $setParts['dbname']; }

		// Create the list of games
		foreach(DBAssoc("SELECT * FROM gameFiles NATURAL JOIN games WHERE gameDisabled IS NULL AND gameFilePlatform = ? ORDER BY gameName+0<>0 DESC, gameName+0, gameName", array($dbPlatform)) as $gameArray) {
			// Don't add games that are missing descriptions unless forced (likely to be obscure or really bad)
			if(!isset($setParts['forceinclude'])) $setParts['forceinclude'] = false;
			if(!$gameArray['gameDescription'] && !$setParts['forceinclude']) continue;

			// Game doesn't work in MAME yet, skip it
			if($gameArray['gameMameState'] == 'preliminary') continue;

			// Tidy up
			$gameArray['gameDescription'] = cutText($gameArray['gameDescription'], 400);

			// Translate Linux paths to Windows paths
			if(isset($windowsTranslation)) {
				$gameArray['gameFilePath'] = translateWinPath($gameArray['gameFilePath']);
				$gameArray['gameImage'] = translateWinPath($gameArray['gameImage']);
			}

			$gameListEs .= "<game>\n\t<name>".xmlSafe($gameArray['gameName'])."</name>\n\t<path>{$gameArray['gameFilePath']}</path>\n\t<desc>".xmlSafe($gameArray['gameDescription'])."</desc>\n\t<publisher>".xmlSafe($gameArray['gamePublisher'])."</publisher>\n\t<developer>".xmlSafe($gameArray['gameDeveloper'])."</developer>\n\t<releasedate>".($gameArray['gameReleaseDate'] ? dateToES($gameArray['gameReleaseDate']) : '')."</releasedate>\n\t<image>{$gameArray['gameImage']}</image>\n\t<genre>".xmlSafe($gameArray['gameGenre'])."</genre>\n\t<rating>".numToFloat($gameArray['gameRating'])."</rating>\n\t<players>{$gameArray['gamePlayers']}</players>\n</game>\n\n";

			// #Name;Title;Emulator;CloneOf;Year;Manufacturer;Category;Players;Rotation;Control;Status;DisplayCount;DisplayType;AltRomname;AltTitle;Extra
			$gameListAm .= pathinfo($gameArray['gameFilePath'], PATHINFO_FILENAME).";{$gameArray['gameName']};{$setParts['esname']};;".($gameArray['gameReleaseDate'] ? date('Y', $gameArray['gameReleaseDate']) : '').";{$gameArray['gameDeveloper']};{$gameArray['gameGenre']};{$gameArray['gamePlayers']};;;;;;;".pathinfo($gameArray['gameImage'], PATHINFO_FILENAME).";".makeAttractSafe($gameArray['gameDescription'])."\n";

			// Full Game Path, Game Name, Full Core Path, System Name, CRC/Detect, Playlist Name
			if(isset($setParts['nointroname'])) $gameListRa .= "{$gameArray['gameFilePath']}\n{$gameArray['gameName']}\nDETECT\n{$thisSet}\n0|crc\n{$setParts['nointroname']}.lpl\n";
		}

		## EMULATION STATION ##
		@mkdir(ESTATION_GPATH, 0777, true);
		$emulatorArgsEs = str_replace('ROMGOESHERE', '%ROM_RAW%', $emulatorArgsEs);
		$systemListEs .= "<system>\n\t<name>{$setParts['esname']}</name>\n\t<fullname>{$thisSet}</fullname>\n\t<path>{$esRomPath}</path>\n\t<extension>{$platformExtList}</extension>\n\t<command>{$emulatorCommand} {$emulatorArgsEs}</command>\n\t<platform>{$setParts['dbname']}</platform>\n\t<theme>{$setParts['dbname']}</theme>\n</system>\n\n";
		@mkdir(ESTATION_GPATH."/.emulationstation/gamelists/{$setParts['esname']}", 0777, true);
		file_put_contents(ESTATION_GPATH."/.emulationstation/gamelists/{$setParts['esname']}/gamelist.xml", "<?xml version='1.0' encoding='UTF-8'?>\n\n<gameList>\n\n{$gameListEs}</gameList>\n");

		## ATTRACT MODE ##
		$emulatorArgsAm = str_replace('ROMGOESHERE', '[romfilename]', $emulatorArgsAm);
		$systemListAm .= "list	{$thisSet}\n	layout		".ATTRACT_THEME."\n	romlist		{$setParts['dbname']}\n\n";
		require(ATTRACT_VARS);
		@mkdir(ATTRACT_GPATH."/emulators", 0777, true);
		file_put_contents(ATTRACT_GPATH."/emulators/{$setParts['dbname']}.cfg", $systemFile);
		@mkdir(ATTRACT_GPATH."/romlists", 0777, true);
		file_put_contents(ATTRACT_GPATH."/romlists/{$setParts['dbname']}.txt", $gameListAm);

		## RETROARCH ##
		@mkdir(RETROXMB_GPATH, 0777, true);
		if(isset($setParts['nointroname'])) file_put_contents(RETROXMB_GPATH."/{$setParts['nointroname']}.lpl", $gameListRa);
	}

	echo "\n\n[All] Creating emulator system files.\n";

	// Save the Systems file. ES uses one big file, AttractMode uses one file per emulator. Retroarch doesn't have one.
	// ES
	$systemListEs = trim($systemListEs);
	file_put_contents(ESTATION_GPATH."/.emulationstation/es_systems.cfg", "<systemList>\n{$systemListEs}\n</systemList>");
	// AM
	$systemListAm = trim($systemListAm);
	file_put_contents(ATTRACT_GPATH."/attract.cfg", "{$systemListAm}\n\n" . file_get_contents(ATTRACT_SKEL));
}

// Clean up the database
cleanShell("mysqlcheck -ugamedb -pgamedb -o --auto-repair --databases gamedb");

echo "\nAll done.\n\n";

?>
