#!/bin/php
<?php

$gameExt = 'zip';

$gameListRaw = trim(shell_exec("mame -listfull"));
$gameArray = explode("\n", $gameListRaw);

// First line is the header, ignore it
array_shift($gameArray);

// Echo the header
echo "clrmamepro (\n\tname \"Arcade\"\n)\n\n";

// Echo each game
foreach($gameArray as $thisGame) {
	preg_match('%^(.+?)\s+"(.+?)"$%', trim($thisGame), $gameParts);
	$gameParts[2] = trim(str_replace('"', '', $gameParts[2]));
	## Get the CRC32 hash of the file
	$gameHash = @hash_file('crc32', "/mnt/store/Emulation/Games/MAME/{$gameParts[1]}.{$gameExt}");
	## File doesn't exist, skip
	if(!$gameHash) continue;
	## Get the file size
	$gameSize = filesize("/mnt/store/Emulation/Games/MAME/{$gameParts[1]}.{$gameExt}");
	## Output game
	echo "game (\n\tname \"{$gameParts[2]}\"\n\trom ( name \"{$gameParts[1]}.{$gameExt}\" size {$gameSize} crc {$gameHash} )\n)\n\n";
}

?>
