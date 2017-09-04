#!/bin/php

<?php

require_once('include/config.php');
require_once('include/database.php');
require_once('include/functions.php');

$userAgent = "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36";
$wgetLocal = "wget -q -U '{$userAgent}'";

function quoteTrim($text) {
	return trim(trim(trim($text), '\'"'));
}

// Create folders
$gameDir = "/mnt/store/Emulation/Games/Lexaloffle - PICO-8";
$assetDir = "/mnt/store/Emulation/Assets/PICO-8/Box";
shell_exec("mkdir -p '{$gameDir}'; mkdir -p '{$assetDir}'");

// Get the top 5 pages of games
$pageNumber = 1;
while($pageNumber <= 10) {
	echo "\nGetting page {$pageNumber}...\n";
	$thisPageContent = getPage("http://www.lexaloffle.com/bbs/?page={$pageNumber}&mode=carts&cat=7&sub=2&orderby=rating");
	$rawLines = preg_match('%var pdat=\[([\s\S]+?)\];%', $thisPageContent, $rawArrayLines);
	$rawArray = explode("\n", $rawArrayLines[1]);

	// 0 ID, 1 null, 2 Name, 3 thumb, 4 null, 5 null, 6 null, 7 null, 8 Author
	foreach($rawArray as $thisRow) {
		$thisRow = trim($thisRow);
		if(!$thisRow) continue;

		// Turn the JS array into something usable
		$thisRow = trim($thisRow, "[]");
		$thisRow = str_replace('`', '"', $thisRow);
		$rowArray = str_getcsv($thisRow, ',', '"');

		// Assign sane variable names
		$gameId = quoteTrim($rowArray[0]);
		$gameIdPrefix = $gameId[0];
		$gameName = quoteTrim($rowArray[2]);
		$authorName = quoteTrim($rowArray[8]);

		// Get some more info
		$gameActualPage = getPage("http://www.lexaloffle.com/bbs/?pid={$gameId}");
		preg_match('%Code </a> \| (\d+)-(\d+)-(\d+)%', $gameActualPage, $gameDate);
		$gameDate = strtotime("{$gameDate[1]}-{$gameDate[2]}-{$gameDate[3]}");

		// Decode HTML characters
		$gameName = trim(html_entity_decode($gameName));
		$authorName = trim(html_entity_decode($authorName));

		// Remove brackets from names
		$gameName = preg_replace('%\(.*?\)%', '', $gameName);
		$gameName = preg_replace('%\[.*?\]%', '', $gameName);

		// Slashes are bad
		$gameName = str_replace('/', ' - ', $gameName);
		$gameName = trim($gameName);

		// So are colons
		$gameName = str_replace(':', ' - ', $gameName);
		$gameName = trim($gameName);

		// Strip trailing numbers, chances are they are version numbers
		$gameName = preg_replace('%\s+[v0-9\.]+$%i', '', $gameName);
		$gameName = trim($gameName);

		// Games may contain special characters so escape them
		$gamePath = escapeshellarg("{$gameDir}/{$gameName}.png");
		$imagePath = escapeshellarg("{$assetDir}/{$gameName}.png");

		DBSimple("INSERT IGNORE INTO picoGames SET pico_gid = ?, pico_author = ?, pico_name = ?, pico_date = ?", array($gameId, $authorName, $gameName, $gameDate));

		// Only download them if they don't already exist
		if(!file_exists("{$gameDir}/{$gameName}.png")) {
			echo "Downloading {$gameName}\n";
			shell_exec("{$wgetLocal} 'http://www.lexaloffle.com/bbs/cposts/{$gameIdPrefix}/{$gameId}.p8.png' -O {$gamePath}");
		}

		if(!file_exists("{$assetDir}/{$gameName}.png")) {
			shell_exec("{$wgetLocal} 'http://www.lexaloffle.com/bbs/thumbs/pico{$gameId}.png' -O {$imagePath}");
		}
	}
	$pageNumber++;
}

?>
