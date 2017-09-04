<?php

// Absolute path
define('RS_PATH', '/mnt/store/Files/RomScan');

// Defaults
define('BACKUPS_CREATE', false);
$debug = 0;
define('DEBUG', false);
$showDuplicates = 1;
$mode = (isset($argv[1]) ? trim($argv[1]) : '');
$genPlatform = (isset($argv[2]) ? trim($argv[2]) : 'linux');
$deleteMissing = 0;
define('FORCE_IMAGESCAN', true);
define('ATTRACT_THEME', 'arcade');

// Static Vars
setlocale(LC_CTYPE, 'en_GB');
chdir(RS_PATH);
$userAgent = "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36";
$wgetLocal = "wget -q -U '{$userAgent}'";
ini_set('error_reporting', E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
$wosDelay = 20;
$wosPause = 1;
$wosTotal = 0;
$timeNow = time();
$romCount = null;
$gamefaqTimer = null;
$thegamedbTest = null;
$archivevgTest = null;
$localImageArray = null;
$mameArray = null;
$cpcPowerArray = null;
$cliWidth = round(exec('tput cols') - (exec('tput cols') * 0.2)) - 10;

// Scraper Resources
define('MAME_WANT',	RS_PATH.'/resources/mame.want');
define('MAME_SKIP',	RS_PATH.'/resources/mame.skipped');
define('MAME_DAT',	RS_PATH.'/resources/mame.dat');
define('MAME_INFO',	RS_PATH.'/resources/mame.info');
define('HOL_CACHE',	RS_PATH.'/resources/holCache.txt');
define('CPC_DB',	RS_PATH.'/resources/cpcPowerDB.txt');

// Frontend Resources
define('ATTRACT_SKEL',		RS_PATH.'/resources/attract.skel');
define('ATTRACT_VARS',		RS_PATH.'/resources/attract-system.skel');
define('ATTRACT_GPATH',		RS_PATH.'/generated/AM');
define('ESTATION_GPATH',	RS_PATH.'/generated/ES');
define('RETROXMB_GPATH',	RS_PATH.'/generated/XMB');

?>
