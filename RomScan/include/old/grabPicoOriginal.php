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

// Get the top 5 pages of games
$pageNumber = 1;
while($pageNumber <= 5) {
	echo "\nGetting page {$pageNumber}...";
	$thisPageContent = getPage("http://www.lexaloffle.com/bbs/?page={$pageNumber}&mode=carts&cat=7&sub=2&orderby=rating");
	print_r($thisPageContent);
	die;
	preg_match_all('%<a href="\?pid=(\d+)\&tid=(?:\d+)\&autoplay=1#pp">[\s]+<font style="color:#555;font-weight:bold">(.+?)</font>[\s]+</a>[\s]+<br>[\s]+<a href="/bbs/(?:.+?)">[\s]+<font style="color:#888">by (.+?)</font>%', $thisPageContent, $gameMatches, PREG_SET_ORDER);
	print_r($gameMatches);
	die;
	unset($gameMatches[0]);
	foreach($gameMatches as $gameMatch) {
		// Get the name and actual ID
		$gameId = trim($gameMatch[1]);
		$gameIdPrefix = $gameId[0];

		// Decode HTML characters
		$gameName = trim(html_entity_decode($gameMatch[2]));
		$authorName = trim(html_entity_decode($gameMatch[3]));

		// Remove brackets from names
		$gameName = preg_replace('%\(.*?\)%', '', $gameName);
		$gameName = preg_replace('%\[.*?\]%', '', $gameName);

		// Slashes are bad
		$gameName = str_replace('/', ' - ', $gameName);
		$gameName = trim($gameName);

		// Strip trailing numbers, chances are they are version numbers
		$gameName = preg_replace('%\s+[v0-9\.]+$%i', '', $gameName);
		$gameName = trim($gameName);

		// Games may contain special characters so escape them
		$gamePath = escapeshellarg("/mnt/store/Emulation/Games/Lexaloffle - PICO-8/{$gameName}.png");
		$imagePath = escapeshellarg("/mnt/store/Emulation/Assets/Box/PICO-8/{$gameName}.png");

		// Only download them if they don't already exist
		if(!file_exists("/mnt/store/Emulation/Games/Lexaloffle - PICO-8/{$gameName}.png") || !file_exists("/mnt/store/Emulation/Assets/Box/PICO-8/{$gameName}.png")) {
			echo "\nDownloading {$gameName}";
			shell_exec("{$wgetLocal} 'http://www.lexaloffle.com/bbs/cposts/{$gameIdPrefix}/{$gameId}.p8.png' -O {$gamePath}");
			shell_exec("{$wgetLocal} 'http://www.lexaloffle.com/bbs/thumbs/pico{$gameId}.png' -O {$imagePath}");
		}
	}
	$pageNumber++;
}

?>
