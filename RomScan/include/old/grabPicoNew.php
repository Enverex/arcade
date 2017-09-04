#!/bin/php

<?php

$userAgent = "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36";
$wgetLocal = "wget -q -U '{$userAgent}'";

function getPage($url) {
	global $userAgent;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$page = trim(curl_exec($curl));
	curl_close($curl);
	return $page;
}

// Create folders
shell_exec("mkdir -p '/mnt/store/Emulation/Games/Lexaloffle - PICO-8'; mkdir -p '/mnt/store/Emulation/Assets/Box/PICO-8'");

echo "\nGetting page...";
$thisPageContent = getPage("http://box.godzil.net/~godzil/pico8/");

preg_match_all('%<p>Title: (.+?)</p><p>Author: (.+?)</p><p>ID: #(\d+)#</p>%', $thisPageContent, $gameMatches, PREG_SET_ORDER);

foreach($gameMatches as $gameMatch) {
	// 1 Title, 2 Author, 3 ID

	// Get the name and actual ID
	$gameId = trim($gameMatch[3]);
	$gameIdPrefix = $gameId[0];

	// Prefix is 0 if ID is less than 10000 apparently
	if($gameId < 10000) $gameIdPrefix = 0;

	// Decode HTML characters
	$gameName = trim(html_entity_decode($gameMatch[1]));
	$authorName = trim(html_entity_decode($gameMatch[2]));

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

	// Exclude tests and libraries
	if(preg_match('%\b(test|library|wip)\b%', strtolower($gameName))) continue;

	// Exclude garbage names
	$cleanGameName = trim(preg_replace('%[^0-9a-zA-Z]+%', '', $gameName));
	if(empty($cleanGameName)) continue;

	// Games may contain special characters so escape them
	$gamePath = escapeshellarg("/mnt/store/Emulation/Games/Lexaloffle - PICO-8/{$gameName}.png");
	$imagePath = escapeshellarg("/mnt/store/Emulation/Assets/Box/PICO-8/{$gameName}.png");

	#echo "Found: {$gameName} ## {$authorName} ## {$gameIdPrefix}-{$gameId}\n";

	// Only download them if they don't already exist
	if(!file_exists($gamePath) || !file_exists($imagePath)) {
		echo "\nDownloading {$gameName}";
		shell_exec("{$wgetLocal} 'http://www.lexaloffle.com/bbs/cposts/{$gameIdPrefix}/{$gameId}.p8.png' -O {$gamePath}");
		shell_exec("{$wgetLocal} 'http://www.lexaloffle.com/bbs/thumbs/pico{$gameId}.png' -O {$imagePath}");
	}
}

?>
