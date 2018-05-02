<?php

#####################################################################################################################
## Copyright: Benjamin Hodgetts (Enverex) <ben@xnode.org>
## Description: Game image and detail scraper for Emulation Station.
## This is designed to be ran on a Linux server (e.g. a NAS) that holds all the ROM files.
## Requirements: PHP, Mame, cURL, ImageMagick, WGet.
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
define('START_TIME', time());
define('ASSET_ROOT', $configFile['assetRoot']);
define('EXTRAASSET_ROOT', $configFile['extraAssetRoot']);
define('MESS_PATH', $configFile['messPath']);

require_once('include/systems.php');
$mameArray = array();
$amigaWhdloadArray = array();
$x68kGameArray = array();
$localImageArray = array();
$thegamedbTest = '';
$romImage = $romWheelImage = $romSnapImage = '';

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
	global $configFile, $systemArr, $windowsTranslation, $mameArray, $amigaWhdloadArray, $x68kGameArray;

	// Uncomment this to import the Launchbox DB
	echo "\n\n=============================\nUpdating Launchbox Remote Database\n=============================\n";
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

	$amigaWhdloadArray = getAmigaWhdloadGames();
	$x68kGameArray = getx68kXmlGames();

	// Make sure we have all the MAME games that we actually want
	echo "\n\n=============================\nVerifying MAME Games\n=============================\n";
	// Link the CHDs to the main ROM folder
	$mameChdArray = scandir('/mnt/store/Emulation/Games/MAME-CHD');
	foreach($mameChdArray as $thisChd) {
		if(!file_exists("/mnt/store/Emulation/Games/MAME/{$thisChd}")) {
			echo "[MAME ROM Verification] CHD {$thisChd} not present. Linking from CHD folder." . PHP_EOL;
			symlink("/mnt/store/Emulation/Games/MAME-CHD/{$thisChd}", "/mnt/store/Emulation/Games/MAME/{$thisChd}");
		}
	}

	// Make sure all clones link to their parents so MAME knows which game we're really launching
	foreach($mameGameArr as $mameGameKey => $thisMameGame) {
		unset($parentName);
		// Check if ROM archive of CHD folder exist
		if(!file_exists("/mnt/store/Emulation/Games/MAME/{$thisMameGame}.zip") && !file_exists("/mnt/store/Emulation/Games/MAME/{$thisMameGame}")) {
			echo "[MAME ROM Verification] ROM {$thisMameGame} doesn't exist, trying to link with parent.".PHP_EOL;

			$parentName = cloneCheck($thisMameGame);
			if(isset($parentName)) {
				// Figure out if the parent is a ROM or CHD
				if(is_dir("/mnt/store/Emulation/Games/MAME-CHD/{$parentName}")) {
					$parentPath = "/mnt/store/Emulation/Games/MAME-CHD/{$parentName}";
				}else{
					$parentPath = "/mnt/store/Emulation/Games/MAME/{$parentName}.zip";
				}

				// Create link to parent if the parent actually exists
				if(file_exists($parentPath)) {
					echo "[MAME ROM Verification] Linking to parent ROM {$parentName}.".PHP_EOL;
					// Symlink folders to folders, files to .zip files
					if(is_dir($parentPath)) {
						symlink($parentPath, "/mnt/store/Emulation/Games/MAME/{$thisMameGame}");
					}else{
						symlink($parentPath, "/mnt/store/Emulation/Games/MAME/{$thisMameGame}.zip");
					}
				}else{
					echo "[MAME ROM Verification] Parent ROM ({$parentName}) does not exist.".PHP_EOL;
				}
			}else{
				echo "[MAME ROM Verification] ROM ({$parentName}) does not exist and has no parent!".PHP_EOL;
			}
		}
	}

	// Flip array keys and values for easy matching
	$mameGameArr = array_flip($mameGameArr);
	$mameArray = getMameDatArray();

	// Create array of games from the CPC-Power database
	$cpcPowerFileArray = explode("\n", trim(file_get_contents(CPC_DB)));
	foreach($cpcPowerFileArray as $cpcPowerGame) {
		## Name;Year;Developer
		$cpcPowerGameParts = explode(';', $cpcPowerGame);
		$cpcPowerArray[$cpcPowerGameParts[0]]['year'] = $cpcPowerGameParts[1];
		$cpcPowerArray[$cpcPowerGameParts[0]]['dev'] = $cpcPowerGameParts[2];
	}

	echo "\n\n====================\nStarting Game Scan\n====================\n";

	DBSimple("TRUNCATE TABLE gameFiles");

	foreach($systemArr as $thisSet => $setParts) {
		echo "\n[{$thisSet}] Starting scan...\n";

		DBSimple("INSERT IGNORE INTO platforms (platformName, gamePlatform) VALUES (?, ?)", array($thisSet, $setParts['dbname']));

		// Get list of files in this system ROM folder
		if(is_dir($setParts['folder'])) {
			$thisFolder = scandir($setParts['folder']);
			$romCount = count($thisFolder);

			// Create folders
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

	// Nuke the existing live links
	echo "\n\n=============================\nClearing Existing Live Links\n=============================\n";
	cleanShell("rm -rf /mnt/store/Emulation/Assets/Live");

	echo "\n\n=============================\nFinal Content Generation\n=============================\n";
	foreach($systemArr as $thisSet => $setParts) {
		unset($platformExtList);
		$gameListEs = $gameListAm = $gameListRa = '';

		echo "\n[{$thisSet}] [{$setParts['dbname']}] Creating game lists and asset links.";

		@mkdir(ASSET_ROOT."/Live/{$setParts['assets']}/Box", 0777, true);
		@mkdir(ASSET_ROOT."/Live/{$setParts['assets']}/Snap", 0777, true);
		@mkdir(ASSET_ROOT."/Live/{$setParts['assets']}/Wheel", 0777, true);

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
		$platformExtList = implode(';', $platformExtBits);

		// We have a system that uses another systems artwork, switch the selection
		if(isset($setParts['esname'])) { $dbPlatform = $setParts['esname']; }else{ $dbPlatform = $setParts['esname'] = $setParts['dbname']; }

		// Create the list of games
		foreach(DBAssoc("SELECT * FROM gameFiles NATURAL JOIN games WHERE gameDisabled IS NULL AND gameFilePlatform = ? ORDER BY gameName+0<>0 DESC, gameName+0, gameName", array($dbPlatform)) as $gameArray) {
			// Don't add games that are missing descriptions unless forced (likely to be obscure or really bad)
			if(!isset($setParts['forceinclude'])) $setParts['forceinclude'] = false;
			if(!$gameArray['gameDescription'] && !$setParts['forceinclude']) continue;

			// Game doesn't work in MAME yet, skip it
			if($gameArray['gameMameState'] == 'preliminary') continue;

			// The type of artwork we use
			$linkValuesArray = array(
				'Box' => $gameArray['gameImage'],
				'Wheel' => $gameArray['gameWheelImage'],
				'Snap' => $gameArray['gameSnapImage']
			);

			// Make the live links for the images
			foreach($linkValuesArray as $linkType => $linkImage) {
				if(!$linkImage || empty($gameArray['gameName'])) continue;
				$linkExtension = pathinfo($linkImage)['extension'];
				$gameFileName = pathinfo($gameArray['gameFile'])['filename'];
				if(!symlink($linkImage, ASSET_ROOT."/Live/{$setParts['assets']}/{$linkType}/{$gameFileName}.{$linkExtension}")) {
					echo "\nFailed to create symlink for $linkImage";
				}
			}

			// Trim game description
			$gameArray['gameDescription'] = cutText($gameArray['gameDescription'], 350);

			// Translate Linux paths to Windows paths
			if(isset($windowsTranslation)) {
				$gameArray['gameFilePath'] = translateWinPath($gameArray['gameFilePath']);
				$gameArray['gameImage'] = translateWinPath($gameArray['gameImage']);
			}

			$gameListEs .= "<game>\n\t<name>".xmlSafe($gameArray['gameName'])."</name>\n\t<path>{$gameArray['gameFilePath']}</path>\n\t<desc>".xmlSafe($gameArray['gameDescription'])."</desc>\n\t<publisher>".xmlSafe($gameArray['gamePublisher'])."</publisher>\n\t<developer>".xmlSafe($gameArray['gameDeveloper'])."</developer>\n\t<releasedate>".($gameArray['gameReleaseDate'] ? dateToES($gameArray['gameReleaseDate']) : '')."</releasedate>\n\t<image>".str_replace('Emulation/Assets', 'Emulation/Assets/Live', $gameArray['gameImage'])."</image>\n\t<genre>".xmlSafe($gameArray['gameGenre'])."</genre>\n\t<rating>".numToFloat($gameArray['gameRating'])."</rating>\n\t<players>{$gameArray['gamePlayers']}</players>\n</game>\n\n";

			// #Name;Title;Emulator;CloneOf;Year;Manufacturer;Category;Players;Rotation;Control;Status;DisplayCount;DisplayType;AltRomname;AltTitle;Extra
			$gameListAm .= pathinfo($gameArray['gameFilePath'], PATHINFO_FILENAME).";{$gameArray['gameName']};{$setParts['esname']};;".($gameArray['gameReleaseDate'] ? date('Y', $gameArray['gameReleaseDate']) : '').";{$gameArray['gameDeveloper']};{$gameArray['gameGenre']};{$gameArray['gamePlayers']};;;;;;;".pathinfo($gameArray['gameImage'], PATHINFO_FILENAME).";".makeAttractSafe($gameArray['gameDescription'])."\n";

			// Full Game Path, Game Name, Full Core Path, System Name, CRC/Detect, Playlist Name
			if(isset($setParts['nointroname'])) $gameListRa .= "{$gameArray['gameFilePath']}\n{$gameArray['gameName']}\nDETECT\n{$thisSet}\n0|crc\n{$setParts['nointroname']}.lpl\n";
		}

		## EMULATION STATION ##
		// Generate game list
		@mkdir(ESTATION_GPATH, 0777, true);
		$emulatorArgsEs = str_replace('ROMGOESHERE', '%ROM_RAW%', $emulatorArgsEs);
		$systemListEs .= "<system>\n\t<name>{$setParts['esname']}</name>\n\t<fullname>{$thisSet}</fullname>\n\t<path>{$esRomPath}</path>\n\t<extension>{$platformExtList}</extension>\n\t<command>eLord {$setParts['dbname']} \"%ROM_RAW%\"</command>\n\t<platform>{$setParts['dbname']}</platform>\n\t<theme>{$setParts['dbname']}</theme>\n</system>\n\n";
		@mkdir(ESTATION_GPATH."/.emulationstation/gamelists/{$setParts['esname']}", 0777, true);
		file_put_contents(ESTATION_GPATH."/.emulationstation/gamelists/{$setParts['esname']}/gamelist.xml", "<?xml version='1.0' encoding='UTF-8'?>\n\n<gameList>\n\n{$gameListEs}</gameList>\n");
		// Generate theme file
		$systemName = $thisSet;
		$esThemeXml = file_get_contents(ESTATION_SKEL);
		$systemCompanyName = strtok($systemName, ' ');
		$systemName = substr(strstr($systemName, ' '), 1);
		$esThemeXml = str_replace('REPLACE_SYSTEMNAME1', $systemCompanyName, $esThemeXml);
		$esThemeXml = str_replace('REPLACE_SYSTEMNAME2', $systemName, $esThemeXml);
		@mkdir(ESTATION_GPATH."/.emulationstation/themes/romscan/{$setParts['esname']}", 0777, true);
		file_put_contents(ESTATION_GPATH."/.emulationstation/themes/romscan/{$setParts['esname']}/theme.xml", $esThemeXml);

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

	## Save the Systems file. EmulationStation uses one big file, AttractMode uses one file per emulator. Retroarch doesn't have one.
	// EmulationStation
	$systemListEs = trim($systemListEs);
	file_put_contents(ESTATION_GPATH."/.emulationstation/es_systems.cfg", "<systemList>\n{$systemListEs}\n</systemList>");
	// AttractMode
	$systemListAm = trim($systemListAm);
	file_put_contents(ATTRACT_GPATH."/attract.cfg", "{$systemListAm}\n\n" . file_get_contents(ATTRACT_SKEL));
}

// Clean up the database
cleanShell("mysqlcheck -ugamedb -pgamedb -o --auto-repair --databases gamedb");

echo "\nAll done.\n\n";

?>
