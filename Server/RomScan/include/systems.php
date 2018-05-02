<?php

// Pages to use as a reference to get the details for each system
## http://thegamesdb.net/api/GetPlatformsList.php (name)
## http://www.giantbomb.com/api/platforms/?api_key={$gbKey} (id)
## http://www.mobygames.com/codes/c,1/platformId,3/ (hover, it's the last number which is the id)
## http://uk.ign.com/search?q=dragon%27s%20lair&page=0&count=10&filter=games (end of url before "-#####")
## http://allgame.com/platforms.php (name) ## Site now dead
## http://api.archive.vg/2.0/Archive.getSystems/xml/{$archivevgKey}/ (short)
## http://www.gamefaqs.com/search/index.html?platform=38&game=Bubble (short system name in URL)
## http://www.rfgeneration.com/cgi-bin/search.pl (ID in search table)
## sqlite3 resources/openvgdb.sqlite - SELECT DISTINCT(TEMPsystemShortName) FROM RELEASES;

## SELECT * FROM tbl_Moby_Games LEFT JOIN tbl_Moby_Releases ON tbl_Moby_Games.id_Moby_Games = tbl_Moby_Releases.id_Moby_Games WHERE tbl_Moby_Games.Name = '(GAMENAME)' AND tbl_Moby_Releases.id_Moby_Platforms = (MOBYPLATFORMID);

$systemArr = array();

// Systems Array. One for each system.
$systemArr['Nintendo SNES'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Super Nintendo Entertainment System",
	'extensions' => 'sfc|smc',
	'assets' => 'Nintendo_SNES',
	'amid' => 'Nintendo Super Nintendo (SNES)',
	'lbid' => 'Super Nintendo Entertainment System',
	'tgdbid' => 'Super Nintendo (SNES)',
	'dbname' => 'snes',
	'gbid' => '9',
	'mobyid' => '15',
	'ignname' => 'snes',
	'allgamename' => 'Super Nintendo Entertainment System',
	'archivevgname' => 'snes',
	'gamefaqsid' => 'snes',
	'rfgenid' => '044',
	'ovgdb' => 'SNES',
	'nointroname' => 'Nintendo - Super Nintendo Entertainment System'
);

$systemArr['System Options'] = array(
	'folder' => "{$configFile['romBase']}/System",
	'extensions' => 'sh',
	'forceinclude' => true,
	'assets' => 'System',
	'amid' => 'System Options',
	'dbname' => 'system'
);

$systemArr['Future Pinball'] = array(
	'folder' => "{$configFile['romBase']}/Future Pinball",
	'extensions' => 'fpt',
	'assets' => 'Future_Pinball',
	'amid' => 'Future Pinball',
	'lbid' => 'Pinball',
	'tgdbid' => 'Pinball',
	'dbname' => 'futurepinball',
	'gbid' => '83',
	'nointroname' => ''
);

$systemArr['Nintendo Wii U'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Wii U",
	'extensions' => '',
	'assets' => 'Nintendo_Wii_U',
	'amid' => 'Nintendo Wii U',
	'lbid' => 'Nintendo Wii U',
	'tgdbid' => 'Nintendo Wii U',
	'dbname' => 'wiiu',
	'gbid' => '139',
	'mobyid' => '132',
	'ignname' => 'wii-u',
	'gamefaqsid' => 'wiiu',
	'nointroname' => ''
);

$systemArr['Nintendo WiiWare'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - WiiWare",
	'extensions' => 'wad',
	'dbname' => 'wiiware',
	'assets' => 'Nintendo_WiiWare',
	'amid' => 'Nintendo WiiWare',
	'lbid' => 'Nintendo Wii',
	'tgdbid' => 'Nintendo Wii',
	'gbid' => '36',
	'mobyid' => '82',
	'ignname' => 'wii',
	'allgamename' => 'Wii',
	'archivevgname' => 'wii',
	'gamefaqsid' => 'wii',
	'rfgenid' => '132',
	'nointroname' => ''
);

$systemArr['Daphne'] = array(
	'folder' => "{$configFile['romBase']}/Daphne",
	'extensions' => '',
	'assets' => 'Daphne',
	'assets2' => 'MAME',
	'amid' => 'Daphne',
	'lbid' => 'Arcade',
	'tgdbid' => 'Arcade',
	'dbname' => 'daphne',
	'gbid' => '84',
	'mobyid' => '143',
	'ignname' => 'arcade',
	'allgamename' => 'Arcade',
	'archivevgname' => 'arcade',
	'gamefaqsid' => 'arcade',
	'ovgdb' => 'MAME',
	'nointroname' => ''
);

$systemArr['ScummVM'] = array(
	'folder' => "{$configFile['romBase']}/PC - ScummVM",
	'extensions' => '',
	'assets' => 'ScummVM',
	'assets2' => 'Microsoft_DOS',
	'assets3' => 'Microsoft_Windows',
	'amid' => 'ScummVM',
	'lbid' => 'MS-DOS|Windows',
	'dbname' => 'scummvm',
	'tgdbid' => 'PC',
	'gbid' => '94',
	'mobyid' => '2|3',
	'ignname' => 'pc',
	'archivevgname' => '139',
	'gamefaqsid' => 'pc',
	'rfgenid' => '016',
	'ovgdb' => '',
	'nointroname' => ''
);

$systemArr['Nintendo Satellaview'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Satellaview",
	'extensions' => 'smc|sfc',
	'assets' => 'Nintendo_Satellaview',
	'amid' => 'Nintendo Satellaview',
	'lbid' => 'Nintendo Satellaview',
	'tgdbid' => 'Nintendo Satellaview',
	'dbname' => 'nsview',
	'gbid' => '98',
	'mobyid' => '15',
	'ignname' => 'snes',
	'allgamename' => '',
	'archivevgname' => 'snes',
	'gamefaqsid' => 'snes',
	'rfgenid' => '044',
	'ovgdb' => 'SNES',
	'nointroname' => 'Nintendo - Satellaview'
);

$systemArr['Arcade MAME'] = array(
	'folder' => "{$configFile['romBase']}/MAME",
	'extensions' => 'zip',
	'assets' => 'MAME',
	'amid' => 'Arcade',
	'lbid' => 'Arcade',
	'tgdbid' => 'Arcade',
	'dbname' => 'mame',
	'gbid' => '84',
	'mobyid' => '143',
	'ignname' => 'arcade',
	'allgamename' => 'Arcade',
	'archivevgname' => 'arcade',
	'gamefaqsid' => 'arcade',
	'ovgdb' => 'MAME',
	'nointroname' => ''
);

## Too much unmatched content
$systemArr['Sharp X68000'] = array(
	'folder' => "{$configFile['romBase']}/Sharp - X68000",
	'extensions' => 'dim',
	'forceinclude' => true,
	'assets' => 'Sharp_X68000',
	'amid' => 'Sharp X68000',
	'lbid' => 'Sharp X68000',
	'dbname' => 'x68000',
	'tgdbid' => 'Sharp X68000',
	'gbid' => '95',
	'mobyid' => '106',
	'ignname' => 'x68000',
	'archivevgname' => '',
	'gamefaqsid' => '',
	'rfgenid' => '100',
	'ovgdb' => '',
	'nointroname' => 'Sharp - X68000'

);

$systemArr['Lexaloffle PICO-8'] = array(
	'folder' => "{$configFile['romBase']}/Lexaloffle - PICO-8",
	'extensions' => 'png',
	'forceinclude' => true,
	'assets' => 'PICO-8',
	'amid' => 'PICO-8',
	'dbname' => 'pico8',
	'tgdbid' => 'PICO-8',
	'gbid' => '',
	'mobyid' => '',
	'ignname' => '',
	'archivevgname' => '',
	'gamefaqsid' => '',
	'rfgenid' => '',
	'ovgdb' => '',
	'nointroname' => ''

);

$systemArr['Philips Videopac Plus'] = array(
	// Seems to share most databases with the Odyssey 2
	'folder' => "{$configFile['romBase']}/Philips - Videopac+",
	'extensions' => 'bin',
	'assets' => 'Philips_Videopac_',
	'amid' => 'Philips Videopac Plus',
	'lbid' => 'Magnavox Odyssey 2',
	'dbname' => 'videopacplus',
	'tgdbid' => 'Magnavox Odyssey 2',
	'gbid' => '60',
	'mobyid' => '128',
	'ignname' => '',
	'archivevgname' => '',
	'gamefaqsid' => '',
	'rfgenid' => '009',
	'ovgdb' => 'Odyssey2',
	'nointroname' => 'Philips - Videopac+'
);

$systemArr['Magnavox Odyssey2'] = array(
	'folder' => "{$configFile['romBase']}/Magnavox - Odyssey2",
	'extensions' => 'bin',
	'assets' => 'Magnavox_Odyssey_2',
	'amid' => 'Magnavox Odyssey2',
	'lbid' => 'Magnavox Odyssey 2',
	'dbname' => 'odyssey2',
	'tgdbid' => 'Magnavox Odyssey 2',
	'gbid' => '60',
	'mobyid' => '78',
	'ignname' => '',
	'archivevgname' => '',
	'gamefaqsid' => '',
	'rfgenid' => '009',
	'ovgdb' => 'Odyssey2',
	'nointroname' => 'Magnavox - Odyssey2'
);


$systemArr['Commodore Amiga'] = array(
	'folder' => "{$configFile['romBase']}/Commodore - Amiga",
	'extensions' => 'lha',
	'forceinclude' => true,
	'assets' => 'Commodore_Amiga',
	'amid' => 'Commodore Amiga',
	'lbid' => 'Commodore Amiga',
	'dbname' => 'amiga',
	'tgdbid' => 'Amiga',
	'gbid' => '1',
	'mobyid' => '19',
	'ignname' => 'amiga',
	'archivevgname' => 'amiga',
	'gamefaqsid' => 'amiga',
	'rfgenid' => '030',
	'nointroname' => ''
);


$systemArr['Microsoft DOS'] = array(
	'folder' => "{$configFile['romBase']}/PC - DOS",
	'extensions' => 'sh',
	'assets' => 'Microsoft_DOS',
	'assets2' => 'Microsoft_Windows',
	'amid' => 'Microsoft DOS',
	'lbid' => 'MS-DOS',
	'dbname' => 'dos',
	'tgdbid' => 'PC',
	'gbid' => '94',
	'mobyid' => '2',
	'ignname' => 'pc',
	'archivevgname' => '139',
	'gamefaqsid' => 'pc',
	'rfgenid' => '016',
	'ovgdb' => '',
	'nointroname' => ''
);


$systemArr['PC Native'] = array(
	'folder' => "{$configFile['romBase']}/PC - Windows",
	'extensions' => 'sh',
	'assets' => 'Microsoft_Windows',
	'assets2' => 'Microsoft_DOS',
	'amid' => 'PC',
	'lbid' => 'Windows',
	'dbname' => 'pc',
	'forceinclude' => true,
	'tgdbid' => 'PC',
	'gbid' => '94',
	'mobyid' => '3',
	'ignname' => 'pc',
	'archivevgname' => '139',
	'gamefaqsid' => 'pc',
	'rfgenid' => '016',
	'ovgdb' => '',
	'nointroname' => ''
);

$systemArr['Commodore Amiga CD32'] = array(
	'folder' => "{$configFile['romBase']}/Commodore - Amiga CD32",
	'extensions' => 'cue',
	'forceinclude' => true,
	'assets' => 'Commodore_Amiga_CD32',
	'amid' => 'Commodore Amiga CD32',
	'lbid' => 'Commodore Amiga CD32',
	'dbname' => 'amigacd32',
	'tgdbid' => 'Amiga CD32',
	'gbid' => '39',
	'mobyid' => '56',
	'ignname' => 'cd32',
	'archivevgname' => 'cd32',
	'gamefaqsid' => 'cd32',
	'rfgenid' => '057',
	'ovgdb' => '',
	'nointroname' => ''
);

$systemArr['Sony Playstation'] = array(
	'folder' => "{$configFile['romBase']}/Sony - Playstation",
	'extensions' => 'chd',
	'assets' => 'Sony_Playstation',
	'amid' => 'Sony Playstation',
	'lbid' => 'Sony Playstation',
	'tgdbid' => 'Sony Playstation',
	'dbname' => 'psx',
	'gbid' => '22',
	'mobyid' => '6',
	'ignname' => 'ps',
	'allgamename' => 'PlayStation',
	'archivevgname' => 'ps',
	'gamefaqsid' => 'ps',
	'rfgenid' => '061',
	'ovgdb' => 'PSX',
	'nointroname' => 'Sony - PlayStation'
);

$systemArr['Sony Playstation 2'] = array(
	'folder' => "{$configFile['romBase']}/Sony - Playstation 2",
	'extensions' => 'iso',
	'assets' => 'Sony_Playstation_2',
	'amid' => 'Sony Playstation 2',
	'lbid' => 'Sony Playstation 2',
	'tgdbid' => 'Sony Playstation 2',
	'dbname' => 'ps2',
	'gbid' => '19',
	'mobyid' => '7',
	'ignname' => 'ps2',
	'allgamename' => 'PlayStation 2',
	'archivevgname' => 'ps2',
	'gamefaqsid' => 'ps2',
	'rfgenid' => '072',
	'ovgdb' => '',
	'nointroname' => ''
);

$systemArr['Sony Playstation 3'] = array(
	'folder' => "{$configFile['romBase']}/Sony - Playstation 3",
	'extensions' => '',
	'assets' => 'Sony_Playstation_3',
	'amid' => 'Sony Playstation 3',
	'lbid' => 'Sony Playstation 3',
	'tgdbid' => 'Sony Playstation 3',
	'dbname' => 'ps3',
	'gbid' => '35',
	'mobyid' => '81',
	'ignname' => 'ps3',
	'allgamename' => 'PlayStation 3',
	'archivevgname' => 'ps3',
	'gamefaqsid' => 'ps3',
	'rfgenid' => '131',
	'ovgdb' => '',
	'nointroname' => ''
);

$systemArr['Sony PSP'] = array(
	'folder' => "{$configFile['romBase']}/Sony - PSP",
	'extensions' => 'iso',
	'assets' => 'Sony_PSP',
	'assets2' => 'Sony_PSP_Minis',
	'amid' => 'Sony PSP',
	'lbid' => 'Sony PSP',
	'tgdbid' => 'Sony PSP',
	'dbname' => 'psp',
	'gbid' => '18',
	'mobyid' => '46',
	'ignname' => 'psp',
	'allgamename' => 'PlayStation Portable',
	'archivevgname' => 'psp',
	'gamefaqsid' => 'psp',
	'rfgenid' => '095',
	'ovgdb' => 'PSP',
	'nointroname' => 'Sony - PlayStation Portable'
);


/*
$systemArr['SNK Neo Geo CD'] = array(
	'folder' => "{$configFile['romBase']}/SNK - Neo Geo CD",
	'extensions' => 'iso|cue',
	'assets' => 'SNK_Neo_Geo_CD',
	'dbname' => 'neogeocd',
	'tgdbid' => 'NeoGeo',
	'gbid' => '59',
	'mobyid' => '54',
	'ignname' => 'ngcd',
	'archivevgname' => 'neogeocd',
	'gamefaqsid' => 'neogeocd',
	'rfgenid' => '066',
	'ovgdb' => '',
	'nointroname' => 'SNK - Neo Geo CD'
);
*/

$systemArr['SNK Neo Geo Pocket Color'] = array(
	'folder' => "{$configFile['romBase']}/SNK - Neo Geo Pocket Color",
	'extensions' => 'ngc',
	'assets' => 'SNK_Neo_Geo_Pocket_Color',
	'amid' => 'SNK Neo Geo Pocket Color',
	'lbid' => 'SNK Neo Geo Pocket Color',
	'dbname' => 'ngpc',
	'tgdbid' => 'Neo Geo Pocket Color',
	'gbid' => '80',
	'mobyid' => '53',
	'ignname' => 'ngpc',
	'archivevgname' => 'ngpc',
	'gamefaqsid' => 'ngpc',
	'rfgenid' => '070',
	'ovgdb' => 'NGPC',
	'nointroname' => 'SNK - Neo Geo Pocket Color'
);

/*
$systemArr['Amstrad CPC'] = array(
	'folder' => "{$configFile['romBase']}/Amstrad - CPC",
	'extensions' => 'dsk',
	'assets' => 'Amstrad_CPC',
	'dbname' => 'amstradcpc',
	'tgdbid' => 'Amstrad CPC',
	'gbid' => '11',
	'mobyid' => '60',
	'ignname' => 'cpc',
	'allgamename' => 'Amstrad CPC',
	'archivevgname' => 'cpc',
	'gamefaqsid' => 'cpc',
	'rfgenid' => '114',
	'ovgdb' => '',
	'nointroname' => ''
);
*/

$systemArr['Amstrad GX4000'] = array(
	'folder' => "{$configFile['romBase']}/Amstrad - GX4000",
	'extensions' => 'cpr',
	'amid' => 'Amstrad GX4000',
	'lbid' => 'Amstrad GX4000',
	'assets' => 'Amstrad_GX4000',
	'dbname' => 'amstradgx4000',
	'tgdbid' => 'Amstrad CPC',
	'gbid' => '11',
	'mobyid' => '60',
	'ignname' => 'cpc',
	'allgamename' => 'Amstrad CPC',
	'archivevgname' => 'cpc',
	'gamefaqsid' => 'cpc',
	'rfgenid' => '114',
	'ovgdb' => '',
	'nointroname' => ''
);

$systemArr['Atari 2600'] = array(
	'folder' => "{$configFile['romBase']}/Atari - 2600",
	'extensions' => 'a26',
	'assets' => 'Atari_2600',
	'amid' => 'Atari 2600',
	'lbid' => 'Atari 2600',
	'dbname' => 'atari2600',
	'tgdbid' => 'Atari 2600',
	'gbid' => '40',
	'mobyid' => '28',
	'ignname' => '2600',
	'allgamename' => 'Atari 2600',
	'archivevgname' => 'atari2600',
	'gamefaqsid' => 'atari2600',
	'rfgenid' => '005',
	'ovgdb' => '2600',
	'nointroname' => 'Atari - 2600'
);

$systemArr['Atari 5200'] = array(
	'folder' => "{$configFile['romBase']}/Atari - 5200",
	'extensions' => 'a52',
	'assets' => 'Atari_5200',
	'amid' => 'Atari 5200',
	'lbid' => 'Atari 5200',
	'tgdbid' => 'Atari 5200',
	'dbname' => 'atari5200',
	'gbid' => '67',
	'mobyid' => '33',
	'ignname' => '5200',
	'allgamename' => 'Atari 5200',
	'archivevgname' => 'atari5200',
	'gamefaqsid' => 'atari5200',
	'rfgenid' => '023',
	'ovgdb' => '5200',
	'nointroname' => 'Atari - 5200'
);

$systemArr['Atari 7800'] = array(
	'folder' => "{$configFile['romBase']}/Atari - 7800",
	'extensions' => 'a78',
	'assets' => 'Atari_7800',
	'amid' => 'Atari 7800',
	'lbid' => 'Atari 7800',
	'tgdbid' => 'Atari 7800',
	'dbname' => 'atari7800',
	'gbid' => '70',
	'mobyid' => '34',
	'ignname' => '7800',
	'allgamename' => 'Atari 7800',
	'archivevgname' => 'atari7800',
	'gamefaqsid' => 'atari7800',
	'rfgenid' => '032',
	'ovgdb' => '7800',
	'nointroname' => 'Atari - 7800'
);

$systemArr['Atari Jaguar'] = array(
	'folder' => "{$configFile['romBase']}/Atari - Jaguar",
	'extensions' => 'j64',
	'assets' => 'Atari_Jaguar',
	'amid' => 'Atari Jaguar',
	'lbid' => 'Atari Jaguar',
	'tgdbid' => 'Atari Jaguar',
	'dbname' => 'atarijaguar',
	'gbid' => '28',
	'mobyid' => '17',
	'ignname' => 'jaguar',
	'allgamename' => 'Atari Jaguar',
	'archivevgname' => 'jaguar',
	'gamefaqsid' => 'jaguar',
	'rfgenid' => '051',
	'ovgdb' => 'Jaguar',
	'nointroname' => 'Atari - Jaguar'
);

$systemArr['Atari Lynx'] = array(
	'folder' => "{$configFile['romBase']}/Atari - Lynx",
	'extensions' => 'lnx',
	'assets' => 'Atari_Lynx',
	'amid' => 'Atari Lynx',
	'lbid' => 'Atari Lynx',
	'dbname' => 'atarilynx',
	'tgdbid' => 'Atari Lynx',
	'gbid' => '7',
	'mobyid' => '18',
	'ignname' => 'lynx',
	'allgamename' => 'Atari Lynx',
	'archivevgname' => 'lynx',
	'gamefaqsid' => 'lynx',
	'rfgenid' => '038',
	'ovgdb' => 'Lynx',
	'nointroname' => 'Atari - Lynx'
);

/*
$systemArr['Atari ST'] = array(
	'folder' => "{$configFile['romBase']}/Atari - ST",
	'extensions' => 'st',
	'assets' => 'Atari_ST',
	'tgdbid' => 'Atari ST',
	'dbname' => 'atarist',
	'gbid' => '13',
	'mobyid' => '24',
	'ignname' => 'st',
	'allgamename' => 'Atari ST',
	'archivevgname' => 'ast',
	'gamefaqsid' => 'ast',
	'rfgenid' => '029',
	'ovgdb' => '',
	'nointroname' => 'Atari - ST'
);
*/

$systemArr['Coleco ColecoVision'] = array(
	'folder' => "{$configFile['romBase']}/Coleco - ColecoVision",
	'extensions' => 'col',
	'assets' => 'Coleco_Vision',
	'amid' => 'Coleco ColecoVision',
	'lbid' => 'ColecoVision',
	'dbname' => 'colecovision',
	'tgdbid' => 'Colecovision',
	'gbid' => '47',
	'mobyid' => '29',
	'ignname' => 'coleco',
	'allgamename' => 'ColecoVision',
	'archivevgname' => 'colecovision',
	'gamefaqsid' => 'colecovision',
	'rfgenid' => '024',
	'ovgdb' => 'ColecoVision',
	'nointroname' => 'Coleco - ColecoVision'
);

$systemArr['Commodore 64'] = array(
	'folder' => "{$configFile['romBase']}/Commodore - 64",
	'extensions' => 'zip',
	'assets' => 'Commodore_64',
	'amid' => 'Commodore 64',
	'lbid' => 'Commodore 64',
	'dbname' => 'c64',
	'tgdbid' => 'Commodore 64',
	'gbid' => '14',
	'mobyid' => '27',
	'ignname' => 'c64',
	'allgamename' => 'Commodore 64',
	'archivevgname' => 'c64',
	'gamefaqsid' => 'c64',
	'rfgenid' => '018',
	'ovgdb' => '',
	'nointroname' => 'Commodore - 64'
);

/*
$systemArr['Commodore 64GS'] = array(
	'folder' => "{$configFile['romBase']}/Commodore - 64GS",
	'extensions' => 'crt',
	'assets' => 'Commodore_64',
	'amid' => 'Commodore 64GS',
	'lbid' => 'Commodore 64',
	'dbname' => 'c64gs',
	'tgdbid' => 'Commodore 64',
	'gbid' => '14',
	'mobyid' => '27',
	'ignname' => 'c64',
	'allgamename' => 'Commodore 64',
	'archivevgname' => 'c64',
	'gamefaqsid' => 'c64',
	'rfgenid' => '018',
	'ovgdb' => '',
	'nointroname' => 'Commodore - 64GS'
);
*/

$systemArr['GCE Vectrex'] = array(
	'folder' => "{$configFile['romBase']}/GCE - Vectrex",
	'extensions' => 'vec',
	'assets' => 'GCE_Vectrex',
	'amid' => 'GCE Vectrex',
	'lbid' => 'GCE Vectrex',
	'dbname' => 'vectrex',
	'tgdbid' => 'Vectrex',
	'gbid' => '76',
	'mobyid' => '37',
	'ignname' => 'vectrex',
	'allgamename' => 'Vectrex',
	'archivevgname' => 'vectrex',
	'gamefaqsid' => 'vectrex',
	'rfgenid' => '017',
	'ovgdb' => 'Vectrex',
	'nointroname' => 'GCE - Vectrex'
);

$systemArr['Microsoft MSX'] = array(
	'folder' => "{$configFile['romBase']}/Microsoft - MSX",
	'extensions' => 'rom',
	'assets' => 'MSX',
	'amid' => 'Microsoft MSX',
	'lbid' => 'Microsoft MSX',
	'dbname' => 'msx',
	'tgdbid' => 'MSX',
	'gbid' => '15',
	'mobyid' => '57',
	'ignname' => 'msx',
	'allgamename' => 'MSX Series PC',
	'archivevgname' => 'msx',
	'gamefaqsid' => 'msx',
	'rfgenid' => '122',
	'ovgdb' => 'MSX',
	'nointroname' => 'Microsoft - MSX'
);

$systemArr['Microsoft MSX 2'] = array(
	'folder' => "{$configFile['romBase']}/Microsoft - MSX 2",
	'extensions' => 'rom',
	'assets' => 'MSX_2',
	'amid' => 'Microsoft MSX2',
	'lbid' => 'Microsoft MSX2',
	'dbname' => 'msx2',
	'tgdbid' => 'MSX2',
	'gbid' => '15',
	'mobyid' => '57',
	'ignname' => 'msx2',
	'allgamename' => 'MSX Series PC',
	'archivevgname' => 'msx2',
	'gamefaqsid' => 'msx2',
	'rfgenid' => '122',
	'ovgdb' => 'MSX2',
	'nointroname' => 'Microsoft - MSX 2'
);

$systemArr['Nintendo DS'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - DS",
	'extensions' => 'nds',
	'assets' => 'Nintendo_DS',
	'amid' => 'Nintendo DS',
	'lbid' => 'Nintendo DS',
	'tgdbid' => 'Nintendo DS',
	'dbname' => 'nds',
	'gbid' => '52',
	'mobyid' => '44',
	'ignname' => 'nds',
	'allgamename' => 'Nintendo DS',
	'archivevgname' => 'ds',
	'gamefaqsid' => 'ds',
	'rfgenid' => '087',
	'ovgdb' => 'DS',
	'nointroname' => 'Nintendo - Nintendo DS'
);

$systemArr['Nintendo 3DS'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - 3DS",
	'extensions' => '3ds|cia|cii',
	'assets' => 'Nintendo_3DS',
	'amid' => 'Nintendo 3DS',
	'lbid' => 'Nintendo 3DS',
	'tgdbid' => 'Nintendo 3DS',
	'dbname' => '3ds',
	'gbid' => '117',
	'mobyid' => '101',
	'ignname' => '3ds',
	'allgamename' => 'Nintendo 3DS',
	'archivevgname' => '3ds',
	'gamefaqsid' => '3ds',
	'rfgenid' => '182',
	'ovgdb' => '',
	'nointroname' => 'Nintendo - Nintendo 3DS'
);

$systemArr['Nintendo Game Boy'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Game Boy",
	'extensions' => 'gb',
	'assets' => 'Nintendo_Game_Boy',
	'amid' => 'Nintendo Game Boy',
	'lbid' => 'Nintendo Game Boy',
	'tgdbid' => 'Nintendo Game Boy',
	'dbname' => 'gb',
	'gbid' => '3',
	'mobyid' => '10',
	'ignname' => 'gb',
	'allgamename' => 'Game Boy',
	'archivevgname' => 'gameboy',
	'gamefaqsid' => 'gameboy',
	'rfgenid' => '037',
	'ovgdb' => 'GB',
	'nointroname' => 'Nintendo - Game Boy'
);

$systemArr['Nintendo Game Boy Color'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Game Boy Color",
	'extensions' => 'gbc',
	'assets' => 'Nintendo_Game_Boy_Color',
	'amid' => 'Nintendo Game Boy Color',
	'lbid' => 'Nintendo Game Boy Color',
	'tgdbid' => 'Nintendo Game Boy Color',
	'dbname' => 'gbc',
	'gbid' => '57',
	'mobyid' => '11',
	'ignname' => 'gbc',
	'allgamename' => 'Game Boy Color',
	'archivevgname' => 'gbc',
	'gamefaqsid' => 'gbc',
	'rfgenid' => '069',
	'ovgdb' => 'GBC',
	'nointroname' => 'Nintendo - Game Boy Color'
);

$systemArr['Nintendo Game Boy Advance'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Game Boy Advance",
	'extensions' => 'gba',
	'assets' => 'Nintendo_Game_Boy_Advance',
	'amid' => 'Nintendo Game Boy Advance',
	'lbid' => 'Nintendo Game Boy Advance',
	'tgdbid' => 'Nintendo Game Boy Advance',
	'dbname' => 'gba',
	'gbid' => '4',
	'mobyid' => '12',
	'ignname' => 'gba',
	'allgamename' => 'Game Boy Advance',
	'archivevgname' => 'gba',
	'gamefaqsid' => 'gba',
	'rfgenid' => '074',
	'ovgdb' => 'GBA',
	'nointroname' => 'Nintendo - Game Boy Advance'
);

$systemArr['Nintendo GameCube'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - GameCube",
	'extensions' => 'gcz',
	'assets' => 'Nintendo_GameCube',
	'amid' => 'Nintendo GameCube',
	'lbid' => 'Nintendo GameCube',
	'tgdbid' => 'Nintendo GameCube',
	'dbname' => 'gc',
	'gbid' => '23',
	'mobyid' => '14',
	'ignname' => 'gcn',
	'allgamename' => 'Nintendo GameCube',
	'archivevgname' => 'gamecube',
	'gamefaqsid' => 'gamecube',
	'rfgenid' => '076',
	'ovgdb' => '',
	'nointroname' => 'Nintendo - GameCube'
);

$systemArr['Nintendo N64'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Nintendo 64",
	'extensions' => 'n64|z64',
	'assets' => 'Nintendo_N64',
	'amid' => 'Nintendo 64',
	'lbid' => 'Nintendo 64',
	'tgdbid' => 'Nintendo 64',
	'dbname' => 'n64',
	'gbid' => '43',
	'mobyid' => '9',
	'ignname' => 'n64',
	'allgamename' => 'Nintendo 64',
	'archivevgname' => 'n64',
	'gamefaqsid' => 'n64',
	'rfgenid' => '064',
	'ovgdb' => 'N64',
	'nointroname' => 'Nintendo - Nintendo 64'
);

$systemArr['Nintendo NES'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Nintendo Entertainment System",
	'extensions' => 'nes',
	'assets' => 'Nintendo_NES',
	'amid' => 'Nintendo Entertainment System (NES)',
	'lbid' => 'Nintendo Entertainment System',
	'tgdbid' => 'Nintendo Entertainment System (NES)',
	'dbname' => 'nes',
	'gbid' => '21',
	'mobyid' => '22',
	'ignname' => 'nes',
	'allgamename' => 'Nintendo Entertainment System',
	'archivevgname' => 'nes',
	'gamefaqsid' => 'nes',
	'rfgenid' => '027',
	'ovgdb' => 'NES',
	'nointroname' => 'Nintendo - Nintendo Entertainment System'
);




$systemArr['Nintendo Wii'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Wii",
	'extensions' => 'wbfs',
	'dbname' => 'wii',
	'assets' => 'Nintendo_Wii',
	'amid' => 'Nintendo Wii',
	'lbid' => 'Nintendo Wii',
	'tgdbid' => 'Nintendo Wii',
	'gbid' => '36',
	'mobyid' => '82',
	'ignname' => 'wii',
	'allgamename' => 'Wii',
	'archivevgname' => 'wii',
	'gamefaqsid' => 'wii',
	'rfgenid' => '132',
	'ovgdb' => '',
	'nointroname' => 'Nintendo - Wii'
);


/*
$systemArr['Nintendo Wii VCN64'] = array(
	'folder' => "{$configFile['romBase']}/Nintendo - Wii VC N64",
	'extensions' => 'wad',
	'assets' => "Nintendo_N64",
	'tgdbid' => "Nintendo 64",
	'dbname' => 'n64',
	'esname' => 'wiivcn64',
	'gbid' => '43',
	'mobyid' => '9',
	'ignname' => 'n64',
	'allgamename' => 'Nintendo 64',
	'archivevgname' => 'n64',
	'gamefaqsid' => 'n64',
	'rfgenid' => '064',
	'ovgdb' => '',
	'nointroname' => ''
);
*/

$systemArr['Panasonic 3DO'] = array(
	'folder' => "{$configFile['romBase']}/Panasonic - 3DO",
	'extensions' => 'iso',
	'assets' => 'Panasonic_3DO',
	'amid' => 'Panasonic 3DO',
	'lbid' => '3DO Interactive Multiplayer',
	'dbname' => '3do',
	'tgdbid' => '3DO',
	'gbid' => '26',
	'mobyid' => '35',
	'ignname' => '3do',
	'allgamename' => '3DO',
	'archivevgname' => '3do',
	'gamefaqsid' => '3do',
	'rfgenid' => '052',
	'ovgdb' => '',
	'nointroname' => ''
);

/*
$systemArr['Philips CD-i'] = array(
	'folder' => "{$configFile['romBase']}/Philips - CD-i",
	'extensions' => 'iso|cue',
	'assets' => 'Philips_CD_i',
 	'lbid' => '',
	'dbname' => 'cdi',
	'tgdbid' => 'Philips CD-i',
	'gbid' => '27',
	'mobyid' => '73',
	'ignname' => 'cd-i',
	'allgamename' => 'Philips CD-i',
	'archivevgname' => 'cdi',
	'gamefaqsid' => 'cdi',
	'rfgenid' => '049',
	'ovgdb' => '',
	'nointroname' => 'Philips - Videopac+'
);
*/

$systemArr['Sega 32X'] = array(
	'folder' => "{$configFile['romBase']}/Sega - 32X",
	'extensions' => '32x',
	'assets' => 'Sega_32X',
	'amid' => 'Sega 32X',
	'lbid' => 'Sega 32X',
	'tgdbid' => 'Sega 32X',
	'dbname' => 'sega32x',
	'gbid' => '31',
	'mobyid' => '21',
	'ignname' => '32x',
	'allgamename' => 'Sega Genesis 32X',
	'archivevgname' => 'sega32x',
	'gamefaqsid' => 'sega32x',
	'rfgenid' => '055',
	'ovgdb' => '32X',
	'nointroname' => 'Sega - 32X'
);

$systemArr['Sega Dreamcast'] = array(
	'folder' => "{$configFile['romBase']}/Sega - Dreamcast",
	'extensions' => 'gdi',
	'assets' => 'Sega_Dreamcast',
	'amid' => 'Sega Dreamcast',
	'lbid' => 'Sega Dreamcast',
	'dbname' => 'dreamcast',
	'tgdbid' => 'Sega Dreamcast',
	'gbid' => '37',
	'mobyid' => '8',
	'ignname' => 'dc',
	'allgamename' => 'Dreamcast',
	'archivevgname' => 'dreamcast',
	'gamefaqsid' => 'dreamcast',
	'rfgenid' => '071',
	'ovgdb' => '',
	'nointroname' => 'Sega - Dreamcast'
);

$systemArr['Sega Game Gear'] = array(
	'folder' => "{$configFile['romBase']}/Sega - Game Gear",
	'extensions' => 'gg',
	'assets' => 'Sega_Game_Gear',
	'amid' => 'Sega Game Gear',
	'lbid' => 'Sega Game Gear',
	'tgdbid' => 'Sega Game Gear',
	'dbname' => 'gamegear',
	'gbid' => '5',
	'mobyid' => '25',
	'ignname' => 'gg',
	'allgamename' => 'Sega Game Gear',
	'archivevgname' => 'gamegear',
	'gamefaqsid' => 'gamegear',
	'rfgenid' => '045',
	'ovgdb' => 'GG',
	'nointroname' => 'Sega - Game Gear'
);

$systemArr['Sega Mega Drive'] = array(
	'folder' => "{$configFile['romBase']}/Sega - Genesis",
	'extensions' => 'md',
	'assets' => 'Sega_Genesis',
	'amid' => 'Sega Mega Drive',
	'lbid' => 'Sega Genesis',
	'tgdbid' => 'Sega Mega Drive',
	'dbname' => 'megadrive',
	'gbid' => '6',
	'mobyid' => '16',
	'ignname' => 'gen',
	'allgamename' => 'Sega Genesis',
	'archivevgname' => 'genesis',
	'gamefaqsid' => 'genesis',
	'rfgenid' => '040',
	'ovgdb' => 'MD',
	'nointroname' => 'Sega - Mega Drive - Genesis'
);

$systemArr['Sega Master System'] = array(
	'folder' => "{$configFile['romBase']}/Sega - Master System",
	'extensions' => 'sms',
	'assets' => 'Sega_Master_System',
	'amid' => 'Sega Master System',
	'lbid' => 'Sega Master System',
	'tgdbid' => 'Sega Master System',
	'dbname' => 'mastersystem',
	'gbid' => '8',
	'mobyid' => '26',
	'ignname' => 'sms',
	'allgamename' => 'Sega Master System',
	'archivevgname' => 'sms',
	'gamefaqsid' => 'sms',
	'rfgenid' => '031',
	'ovgdb' => 'SMS',
	'nointroname' => 'Sega - Master System - Mark III'
);

$systemArr['Sega Mega-CD'] = array(
	'folder' => "{$configFile['romBase']}/Sega - Mega-CD",
	'extensions' => 'chd',
	'assets' => 'Sega_CD',
	'amid' => 'Sega CD',
	'lbid' => 'Sega CD',
	'tgdbid' => 'Sega CD',
	'dbname' => 'segacd',
	'gbid' => '29',
	'mobyid' => '20',
	'ignname' => 'sega-cd',
	'allgamename' => 'Sega CD',
	'archivevgname' => 'segacd',
	'gamefaqsid' => 'segacd',
	'rfgenid' => '048',
	'ovgdb' => 'SCD',
	'nointroname' => 'Sega - Mega-CD - Sega CD'
);

$systemArr['Sega Saturn'] = array(
	'folder' => "{$configFile['romBase']}/Sega - Saturn",
	'extensions' => 'chd',
	'assets' => 'Sega_Saturn',
	'amid' => 'Sega Saturn',
	'lbid' => 'Sega Saturn',
	'tgdbid' => 'Sega Saturn',
	'dbname' => 'saturn',
	'gbid' => '42',
	'mobyid' => '23',
	'ignname' => 'saturn',
	'allgamename' => 'Sega Saturn',
	'archivevgname' => 'saturn',
	'gamefaqsid' => 'saturn',
	'rfgenid' => '060',
	'ovgdb' => 'Saturn',
	'nointroname' => 'Sega - Saturn'
);

/*
$systemArr['Sinclair ZX Spectrum'] = array(
	'folder' => "{$configFile['romBase']}/Sinclair - ZX Spectrum",
	'extensions' => 'z80',
	'assets' => 'Sinclair_ZX_Spectrum',
	'dbname' => 'zxspectrum',
 	'lbid' => '',
	'tgdbid' => 'Sinclair ZX Spectrum',
	'gbid' => '16',
	'mobyid' => '41',
	'ignname' => 'zx',
	'allgamename' => 'Sinclair ZX Spectrum',
	'archivevgname' => 'sinclair',
	'gamefaqsid' => 'sinclair',
	'rfgenid' => '123',
	'ovgdb' => '',
	'nointroname' => 'Sinclair - ZX Spectrum +3'
);
*/



// Clone of PC specifically for Touhou Project games
/*
$systemArr['Touhou Project'] = array(
	'folder' => "{$configFile['romBase']}/Team Shanghai Alice - Touhou Project",
	'extensions' => 'bat',
	'assets' => 'Touhou',
	'dbname' => 'touhou',
	'tgdbid' => 'PC',
	'gbid' => '94',
	'mobyid' => '3',
	'ignname' => 'pc',
	'allgamename' => 'IBM PC Compatible',
	'archivevgname' => 'winall',
	'gamefaqsid' => 'pc',
	'rfgenid' => '016',
	'ovgdb' => '',
	'nointroname' => ''
);
*/

$systemArr['NEC PC-FX'] = array(
	'folder' => "{$configFile['romBase']}/NEC - PC-FX",
	'extensions' => 'cue',
	'assets' => 'NEC_PC_FX',
	'amid' => 'NEC PC-FX',
	'lbid' => 'NEC PC-FX',
	'dbname' => 'pcfx',
	'tgdbid' => 'PC-FX',
	'gbid' => '75',
	'mobyid' => '59',
	'ignname' => 'pc-fx',
	'allgamename' => 'PC-FX',
	'archivevgname' => 'pcfx',
	'gamefaqsid' => 'pcfx',
	'rfgenid' => '106',
	'ovgdb' => '',
	'nointroname' => 'NEC - PC FX'
);

$systemArr['NEC TurboGrafx-16'] = array(
	'folder' => "{$configFile['romBase']}/NEC - TurboGrafx-16",
	'extensions' => 'pce',
	'assets' => 'NEC_TurboGrafx_16',
	'assets2' => 'NEC_PC_Engine',
	'amid' => 'NEC TurboGrafx 16',
	'lbid' => 'TurboGrafx-16',
	'dbname' => 'turbografx16',
	'tgdbid' => 'TurboGrafx 16',
	'gbid' => '55',
	'mobyid' => '40',
	'ignname' => 'tg16',
	'allgamename' => 'TurboGrafx-16',
	'archivevgname' => 'tg16',
	'gamefaqsid' => 'tg16',
	'rfgenid' => '039',
	'ovgdb' => 'PCE',
	'nointroname' => 'NEC - PC Engine - TurboGrafx 16'
);

// TurboGrafx-16 used for any missing TurboGrafx-CD lookups
$systemArr['NEC TurboGrafx-CD'] = array(
	'folder' => "{$configFile['romBase']}/NEC - TurboGrafx-CD",
	'extensions' => 'cue',
	'assets' => 'NEC_TurboGrafx_CD',
	'assets2' => 'NEC_PC_Engine_CD',
	'amid' => 'NEC TurboGrafx CD',
	'lbid' => 'TurboGrafx-CD',
	'dbname' => 'turbografxcd',
	'tgdbid' => 'TurboGrafx CD',
	'gbid' => '55',
	'mobyid' => '45',
	'ignname' => 'tgcd',
	'allgamename' => 'TurboGrafx-16 CD',
	'archivevgname' => 'turbocd',
	'gamefaqsid' => 'turbocd',
	'rfgenid' => '041',
	'ovgdb' => 'PCECD',
	'nointroname' => 'NEC - PC Engine CD - TurboGrafx CD'
);

/*
$systemArr['Doom'] = array(
	'folder' => "{$configFile['romBase']}/Doom",
	'extensions' => 'wad',
	'assets' => "Doom",
	'dbname' => 'doom',
	'tgdbid' => "PC",
	'gbid' => '94',
	'mobyid' => '3',
	'ignname' => 'pc',
	'archivevgname' => '139',
	'gamefaqsid' => 'pc',
	'rfgenid' => '016',
	'ovgdb' => '',
	'nointroname' => 'DOOM'
);
*/

$systemArr['Bandai WonderSwan'] = array(
	'folder' => "{$configFile['romBase']}/Bandai - WonderSwan",
	'extensions' => 'ws',
	'assets' => 'Bandai_WonderSwan',
	'amid' => 'Bandai WonderSwan',
	'lbid' => 'WonderSwan',
	'dbname' => 'wonderswan',
	'tgdbid' => 'WonderSwan',
	'gbid' => '65',
	'mobyid' => '48',
	'ignname' => 'ws',
	'archivevgname' => 'ws',
	'gamefaqsid' => 'ws',
	'rfgenid' => '091',
	'ovgdb' => 'WonderSwan',
	'nointroname' => 'Bandai - WonderSwan'
);

$systemArr['Bandai WonderSwan Color'] = array(
	'folder' => "{$configFile['romBase']}/Bandai - WonderSwan Color",
	'extensions' => 'wsc',
	'assets' => 'Bandai_WonderSwan_Color',
	'amid' => 'Bandai WonderSwan Color',
	'lbid' => 'WonderSwan Color',
	'dbname' => 'wonderswancolor',
	'tgdbid' => 'WonderSwan Color',
	'gbid' => '54',
	'mobyid' => '49',
	'ignname' => 'wsc',
	'archivevgname' => 'wsc',
	'gamefaqsid' => 'wsc',
	'rfgenid' => '092',
	'ovgdb' => 'WonderSwan Color',
	'nointroname' => 'Bandai - WonderSwan Color'
);

ksort($systemArr);

?>
