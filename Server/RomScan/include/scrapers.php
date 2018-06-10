<?php

function igdbScraper($dbGameName, $gameName, $platformName, $assetFolder, $igdbid) {
	// Platform not supported by IGDB, abort
	if(!$igdbid) return;
	global $extraAssetRoot, $romImage, $romWheelImage, $romSnapImage;

	$dataJson = getPage("https://api-2445582011268.apicast.io/games/?search=".rawurlencode($gameName)."&fields=*&filter[release_dates.platform][eq]={$igdbid}");
	if($dataJson) $gamesArray = json_decode($dataJson, 1);

	if($gamesArray) {
		// Convert single result into array to save on code
		if(isset($gamesArray['name'])) {
			$arrayTemp = $gamesArray;
			unset($gamesArray);
			$gamesArray[0] = $arrayTemp;
			unset($arrayTemp);
		}

		// Build a confidence list
		$nameMatchArray = array();
		foreach($gamesArray as $thisGameArray) {
			$matchPercent = nameCompare($dbGameName, $thisGameArray['name'], 1);
			if($matchPercent) {
				if(DEBUG) echo "\n[IGDB API Scraper] [{$gameName}] {$thisGameArray['name']} matches with {$matchPercent}% confidence.\n";
				$nameMatchArray[$matchPercent] = $thisGameArray;
				// We have a 100% match, no point carrying on
				if($matchPercent == 100) break;
			}
		}

		// No matches, exit
		if(!isset($nameMatchArray)) return;

		// Sort by order of confidence and select the top game
		arsort($nameMatchArray);
		reset($nameMatchArray);
		$gameArray = current($nameMatchArray);
		unset($nameMatchArray);
		if(DEBUG) echo "\n[IGDB API Scraper] {$gameName} found.\n";

		## Publishers, Developers, Genres and Release Dates are always multidimensional arrays, even when they're single entries.
		## Images (covers, screenshots) aren't. They flatten down into a single array.
		if($gameArray) {
			// Score is out of 100
			if(is_numeric($gameArray['total_rating'])) $score = round($gameArray['total_rating'] / 20, 1);

			// Description is a single large block
			$description = trim($gameArray['summary']);

			// Publisher needs to be looked up as it only provides an ID
			if($gameArray['publishers'][0]) {
				$dataJson = getPage("https://api-2445582011268.apicast.io/companies/{$gameArray['publishers'][0]}");
				$publisher = json_decode($dataJson, 1)[0]['name'];
			}

			// Developer needs to be looked up as it only provides an ID
			if($gameArray['developers'][0]) {
				$dataJson = getPage("https://api-2445582011268.apicast.io/companies/{$gameArray['developers'][0]}");
				$developer = json_decode($dataJson, 1)[0]['name'];
			}

			// Genre(s) need to be looked up as it only provides an ID (doing one lookup per ID with caching should actually use less API calls in the long-run)
			if($gameArray['genres'][0]) {
				$genreArray = array();
				foreach($gameArray['genres'] as $thisGenreId) { $genreArray[] = json_decode(getPage("https://api-2445582011268.apicast.io/genres/{$thisGenreId}"), 1)[0]['name']; }
				if($genreArray) $genres = implode(', ', $genreArray);
			}

			// Find the release date by cycling through the listed platforms till we find the right one
			if($gameArray['release_dates'][0]) {
				foreach($gameArray['release_dates'] as $thisReleaseArray) {
					if($thisReleaseArray['platform'] == $igdbid) {
						if($thisReleaseArray['date']) {
							// Date is unixtime with miliseconds
							$releasedate = round($thisReleaseArray['date'] / 1000);
							break;
						}
					}
				}
			}

			// Find the images based on array type
			if($gameArray['cover']['url']) { $coverImage = $gameArray['cover']['cloudinary_id']; }else{ $coverImage = $gameArray['cover'][0]['cloudinary_id']; }
			if($gameArray['screenshots']['url']) { $screenshotImage = $gameArray['screenshots']['cloudinary_id']; }else{ $screenshotImage = $gameArray['screenshots'][0]['cloudinary_id']; }

			// Get box art
			if(!$romImage && isset($coverImage)) $romImage = getImage($gameName, "https://images.igdb.com/igdb/image/upload/t_original/{$coverImage}.jpg", $assetFolder);
			// Get the screenshot/snap art
			if(!$romSnapImage && isset($screenshotImage)) $romSnapImage = getImage($gameName, "https://images.igdb.com/igdb/image/upload/t_original/{$coverImage}.jpg", $assetFolder, 'Snap');
			## (doesn't support wheel art)

			if(DEBUG) echo "\n[IGDB Scraper] [{$gameName}] Info found. Adding to database";
			updateGameDetails('IGDB', $gameName, $dbGameName, $platformName, $releasedate, $genres, $description, $score, $developer, $publisher);
			return true;
		}
	}
}

function launchboxDbScraper($dbGameName, $platformName, $gameName, $launchboxPlatforms, $assetFolder) {
	// Unsupported platform, abort
	if(!$launchboxPlatforms) return;
	global $romImage, $romWheelImage, $romSnapImage;

	$lbImagePath = 'http://images.launchbox-app.com/';

	// Handle multiple lookup platforms
	$launchboxPlatforms = explode('|', $launchboxPlatforms);
	foreach($launchboxPlatforms as $launchboxPlatform) {
		$matchArray = DBSingleAssoc("SELECT * FROM launchboxDb WHERE lbdb_safename = ? AND lbdb_platform = ? LIMIT 1", array($dbGameName, $launchboxPlatform));

		// Match found, add to DB
		if($matchArray) {
			// Get box art
			if(!$romImage && isset($matchArray['lbdb_boxart'])) $romImage = getImage($gameName, $lbImagePath . $matchArray['lbdb_boxart'], $assetFolder);
			// Get the screenshot/snap art
			if(!$romSnapImage && isset($matchArray['lbdb_snapart'])) $romSnapImage = getImage($gameName, $lbImagePath . $matchArray['lbdb_snapart'], $assetFolder, 'Snap');
			// Get the logo art
			if(!$romWheelImage && isset($matchArray['lbdb_logoart'])) $romWheelImage = getImage($gameName, $lbImagePath . $matchArray['lbdb_logoart'], $assetFolder, 'Wheel');

			if(DEBUG) echo "\n[Launchbox Scraper] [{$gameName}] Info found. Adding to database";
			updateGameDetails('Launchbox', $gameName, $dbGameName, $platformName, cleanUnixtime($matchArray['lbdb_year']), $matchArray['lbdb_genre'], htmlDecode($matchArray['lbdb_description']), $matchArray['lbdb_rating'], $matchArray['lbdb_developer'], $matchArray['lbdb_publisher'], $matchArray['lbdb_players']);
			return true;
		}
	}
}

function mobyLocalScrape($dbGameName, $gameName, $platformName, $mgids) {
	if(!$mgids) return;

	$gameDetails = DBSingleAssoc("SELECT * FROM moby_Games LEFT JOIN moby_Releases ON moby_Games.id_Moby_Games = moby_Releases.id_Moby_Games WHERE moby_Games.Name = ? AND moby_Releases.id_Moby_Platforms = ? LIMIT 1", array($gameName, $mgids));

	// Check alternate names
	if(!$gameDetails) {
		$gameDetails = DBSingleAssoc("SELECT * FROM moby_Games_Alternate_Titles LEFT JOIN moby_Releases ON moby_Games_Alternate_Titles.id_Moby_Games = moby_Releases.id_Moby_Games WHERE Alternate_Title = ? AND moby_Releases.id_Moby_Platforms = ? LIMIT 1", array($gameName, $mgids));
	}

	if($gameDetails) {
		if($gameDetails['Description'])	$description = trim($gameDetails['Description']);
		if($gameDetails['Year'])		$releasedate = trim($gameDetails['Year']);
		if($gameDetails['MobyScore'])	$rating = trim($gameDetails['MobyScore']);

		if($gameDetails['Publisher_id_Moby_Companies']) {
			$publisherDetails = DBSingleAssoc("SELECT * FROM moby_Companies WHERE id_Moby_Companies = ? LIMIT 1", array($gameDetails['Publisher_id_Moby_Companies']));
			if($publisherDetails) $publisher = trim($publisherDetails['Name']);
		}

		if($gameDetails['Developer_id_Moby_Companies']) {
			$developerDetails = DBSingleAssoc("SELECT * FROM moby_Companies WHERE id_Moby_Companies = ? LIMIT 1", array($gameDetails['Developer_id_Moby_Companies']));
			if($developerDetails) $developer = trim($developerDetails['Name']);
		}

		if($description || $year || $rating || $publisher || $developer) {
			if(DEBUG) echo "\n[MobyGames Local Scraper] [{$gameName}] Info found. Adding to database.";
			updateGameDetails('MobyGamesAPI', $gameName, $dbGameName, $platformName, cleanUnixtime($releasedate), null, $description, $rating, $developer, $publisher);
			return true;
		}
	}
}

function mobyScrape($dbGameName, $platformName, $gameName, $mgids, $assetFolder, $getImageOnly = null) {
	if(!$mgids) return;
	global $romImage;

	$mgids = explode('|', $mgids);
	foreach($mgids as $mgid) {
		$page = getMobyPage($dbGameName, $gameName, $mgid);

		if($page) {
			if(!$romImage) {
				preg_match('%src="http://pics\.mobygames\.com/images/covers/small/(.+?)"%', $page, $remoteImage);
				if($remoteImage) {
					$remoteImage = "http://pics.mobygames.com/images/covers/large/".trim($remoteImage[1]);
					$romImage = getImage($gameName, $remoteImage, $assetFolder);
					if(DEBUG) if($romImage) echo "\n[MobyGames Scraper] [{$gameName}] Grabbed image.";
				}
			}
			if($getImageOnly) return;

			preg_match('%Published by</div><div(?:.+?)>([\s|\S]+?)</div>%', $page, $publisher); $publisher = trim(strip_tags($publisher[1]));
			preg_match('%Developed by</div><div(?:.+?)>([\s|\S]+?)</div>%', $page, $developer); $developer = trim(strip_tags($developer[1]));
			preg_match('%Genre</div><div(?:.+?)>([\s|\S]+?)</div>%', $page, $genre); $genre = trim(strip_tags($genre[1]));
			preg_match('%Released</div><div(?:.+?)>([\s|\S]+?)</div>%', $page, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);

			// Get the page and clean it up
			preg_match('%<h2>Description</h2>([\s|\S]+?)<div%', $page, $description);
			$description = $description[1];
			$description = preg_replace('%<br/?>%', ' ', $description);
			$description = strip_tags($description);
			$description = preg_replace('%[\s]+%', ' ', $description);
			$description = preg_replace('%[ ]{2,}%', ' ', $description);
			$description = trim($description);

			// Only process the score if it's a number to avoid false zero ratings
			## <div class="fr scoreBoxMed scoreMed">3.5</div>
			preg_match('%class="fr scoreBoxMed(?:.+?)">(.+?)</div>%', $page, $score);
			if(is_numeric($score[1])) { $score = round($score[1], 1); }else{ unset($score); }

			if($releaseDate || $genre || $description || $score ||$developer || $publisher) {
				if(DEBUG) echo "\n[MobyGames Scraper] [{$gameName}] Info found. Adding to database";
				updateGameDetails('MobyGames', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, $score, $developer, $publisher);
				return true;
			}
		}
	}
}

function mobygamesApiScraper($dbGameName, $platformName, $gameName, $mgid, $assetFolder, $getImageOnly = null) {
	$xmlRaw = getPage("https://api.mobygames.com/v1/games?format=normal&limit=50&platform={$mgid}&title={$gameName}");
	if($xmlRaw) {
		$gameXML = @simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
		if($gameXML) {
			$gamesArray = json_decode(json_encode($gameXML), true);
			$gamesArray = $gamesArray['games'];
		}else{
			echo "\n[MobyGames API Scraper] [{$gameName}] XML Failure.\n";
		}
	}

	// No matches
	if(!$gamesArray) return;

	// Convert single result into array to save on code
	if(isset($gamesArray['title'])) {
		$arrayTemp = $gamesArray;
		unset($gamesArray);
		$gamesArray[0] = $arrayTemp;
		unset($arrayTemp);

		// Build a confidence list
		foreach($gamesArray as $gameKey => $thisGameArray) {
			$matchPercent = nameCompare($dbGameName, $thisGameArray['title'], 1);
			if($matchPercent && DEBUG) echo "\n[MobyGames API Scraper] [{$gameName}] {$thisGameArray['title']} ({$thisGameArray['game_id']}) matches with {$matchPercent}% confidence.\n";
			if($matchPercent) $nameMatchArray[$gameKey] = $matchPercent;
		}
	}

	// No matches, exit
	if(!isset($nameMatchArray)) return;

	// Sort by order of confidence and select the top game
	arsort($nameMatchArray); reset($nameMatchArray);
	$mobyGameId = key($nameMatchArray);
	if(DEBUG) echo "\n[MobyGames API Scraper] {$gameName} found.\n";
	unset($nameMatchArray);

	// This shouldn't happen, but check anyway
	if(!isset($mobyGameId)) return;

	// Pull just this game out of the array
	$gameArray = $gamesArray[$mobyGameId];

	// Get box art
	if(!$romImage && isset($gameArray['sample_cover']['image'])) $romImage = getImage($gameName, $gameArray['sample_cover']['image'], $assetFolder);
	if($grabImageOnly) return $romImage;

	// Get the screenshot/snap art
	if(!$romSnapImage && isset($gameArray['sample_screenshots'][0]['image'])) $romSnapImage = getImage($gameName, $gameArray['sample_screenshots'][0]['image'], $assetFolder, 'Snap');
	if($getSnapArtOnly) return $romSnapImage;

	// Tidy up description
	$description = trim(preg_replace('%[ ]{2,}%', ' ', preg_replace('%[\s]+%', ' ', strip_tags(preg_replace('%<br/?>%', ' ', $gameArray['description'])))));

	// Create list of genres
	if($gamesArray['genres']) {
		foreach($gamesArray['genres'] as $thisGenre) {
			$genreArray[] = $thisGenre['genre_name'];
		}
		$genre = trim(implode(', ', $genreArray));
	}

	// Release date
	if(isset($gameArray['platforms'][0]['first_release_date'])) {
		$releaseDate = cleanUnixtime($gameArray['platforms'][0]['first_release_date']);
	}

	if($releaseDate || $genre || $description) {
		if(DEBUG) echo "\n[MobyGames API Scraper] [{$gameName}] Info found. Adding to database.";
		updateGameDetails('MobyGamesAPI', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, null, null, null, null);
		return true;
	}
}

## API Scraper
function giantbombScrape($dbGameName, $platformName, $gameName, $gbids, $assetFolder, $grabImageOnly = null) {
	## Too much garbage. Disable.
	return;

	if(!$gbids) return;
	global $extraAssetRoot, $romImage;

	// Handle multiple lookup platforms
	$gbids = explode('|', $gbids);
	foreach($gbids as $gbid) {
		$gbResult = getGiantbombPage("http://www.giantbomb.com/api/search/?api_key=".KEY_GIANTB."&format=json&platform={$gbid}&resources=game&limit=1&query=".rawurlencode($gameName));

		// Process response
		if($gbResult) {
			// Find result that actually match
			foreach($gbResult['results'] as $resultID => $thisResult) {
				$gbName = $thisResult['name'];
				if(nameCompare($dbGameName, $gbName)) {

					if(!$romImage) {
						// Grab game image
						$remoteImage = $thisResult['image']['super_url'];
						if($remoteImage) {
							$romImage = getImage($gameName, $remoteImage, $assetFolder);
							if(DEBUG) if($romImage) echo "\n[GiantBomb Scraper] [{$gameName}] Grabbed image.";
						}
					}

					if($grabImageOnly) return;

					// Follow the URL to the game page
					$gbGameURL = $thisResult['api_detail_url'];
					if($gbGameURL) {
						$gbPageArray = getGiantbombPage("{$gbGameURL}?api_key=".KEY_GIANTB."&format=json&platform={$gbid}");
						$gbPageArray = $gbPageArray['results'];

						// Example description
						// <h2>Overview</h2><p style="">Sega Rally 2 was developed by <a href="/sega-am5-rd-division/3010-6928/" data-ref-id="3010-6928">Sega AM5</a> for the Model 3 arcade hardware. It was introduced to arcades in February 1998. Sega Rally 2 was ported to <a href="/dreamcast/3045-37/" data-ref-id="3045-37">Dreamcast</a> by <a href="/smilebit/3010-493/" data-ref-id="3010-493">Smilebit</a>. It was released in Japan on January 28, 1999, shortly after the November launch of the Dreamcast. It debuted in Europe on October 14, 1999 and its American debut was on November 27.</p><h3>Gameplay</h3><p style="">The gameplay in Sega Rally 2 revolves around making it to the next checkpoint in a arcade style race to the finish. There are six different locations in the game, with 17 different tracks in total. Each of the tracks is littered with different surfaces, such as gravel, snow, pavement, and ice. Nighttime driving and weather effects are also new additions to the series.</p><h3>Cars</h3><p style="">Each of the cars are real-world manufactured cars. Three of the cars also appeared in <a href="/sega-rally-championship/3030-2162/" data-ref-id="3030-2162">Sega Rally Championship</a>. Each of the cars can be upgraded with modified parts.</p>

						// Use the description if we can make less of a mess out of it, otherwise use "deck"
						preg_match('%<h2>Overview</h2><p style="">(.+?)</p>%', $gbPageArray['description'], $descPart1);
						preg_match('%<h3>Gameplay</h3><p style="">(.+?)</p>%', $gbPageArray['description'], $descPart2);
						$fullDesc = "{$descPart1[1]} {$descPart2[1]}";
						$description = trim(preg_replace('%[ ]{2,}%', ' ', preg_replace('%[\s]+%', ' ', strip_tags(preg_replace('%<br/?>%', ' ', $gbPageArray['fullDesc'])))));

						// Looks like we didn't get anything useful, use deck instead
						if(!$description) $description = trim(preg_replace('%[ ]{2,}%', ' ', preg_replace('%[\s]+%', ' ', strip_tags(preg_replace('%<br/?>%', ' ', $gbPageArray['deck'])))));

						// Create list of genres
						if($gbPageArray['genres']) {
							foreach($gbPageArray['genres'] as $thisGenre) {
								$genreArray[] = $thisGenre['name'];
							}
							$genre = implode(', ', $genreArray);
						}

						// Create list of developers
						if($gbPageArray['developers']) {
							foreach($gbPageArray['developers'] as $thisDev) {
								$devArray[] = $thisDev['name'];
							}
							if($devArray) $developer = implode(', ', $devArray);
						}

						// Create list of publishers
						if($gbPageArray['publishers']) {
							foreach($gbPageArray['publishers'] as $thisPub) {
								$pubArray[] = $thisPub['name'];
							}
							$publisher = implode(', ', $pubArray);
						}

						// Release date
						$releaseDate = cleanUnixtime($gbPageArray['original_release_date']);

						if($releaseDate || $genre || $description || $developer || $publisher) {
							if(DEBUG) echo "\n[GiantBomb Scraper] [{$gameName}] Info found. Adding to database";
							updateGameDetails('GiantBomb', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, null, $developer, $publisher);
							return true;
						}
					}
				}
			}
		}
	}
}

// OpenVGDB Scraper
function openvgdbScrape($dbGameName, $platformName, $gameName, $ovgdbId, $assetFolder, $grabImageOnly = null) {
	// No support for this platform, abort
	if(!$ovgdbId) return;

	$gameDetails = vgdbQuery("SELECT * FROM RELEASES WHERE releaseTitleName = ? AND TEMPsystemShortName = ? COLLATE NOCASE LIMIT 1", array($gameName, $ovgdbId));

	// Get boxart
	if(!$romImage) {
		if($gameDetails['releaseCoverFront']) {
			$romImage = getImage($gameName, $gameDetails['releaseCoverFront'], $assetFolder);
			if(DEBUG) echo "\n[OpenVGDB Scraper] [{$gameName}] Grabbed image.";
		}
	}
	if($grabImageOnly) return $romImage;

	// Create list of genres
	if($gameDetails['releaseGenre']) {
		$genreArray = explode(',', $gameDetails['releaseGenre']);
		$genreArray = array_slice($genreArray, 0, 3);
		$genreArray = array_map('trim', $genreArray);
		$genre = implode(', ', $genreArray);
	}

	// Convert release date to unixtime
	$releaseDate = strtotime($gameDetails['releaseDate'], 946684800);

	if($releaseDate || $genre || $gameDetails['releaseDescription'] || $gameDetails['releaseDeveloper'] || $gameDetails['releasePublisher']) {
		if(DEBUG) echo "\n[OpenVGDB Scraper] [{$gameName}] Info found. Adding to database.";
		updateGameDetails('OpenVGDB', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $gameDetails['releaseDescription'], null, $gameDetails['releaseDeveloper'], $gameDetails['releasePublisher']);
		return true;
	}
}

## API Scraper
function thegamesdbScrape($dbGameName, $platformName, $gameName, $tgdbid, $assetFolder, $grabImageOnly = null, $getWheelArtOnly = null, $getSnapArtOnly = null) {
	// It's always down, don't even bother
	return;

	if(!$tgdbid) return;
	global $thegamedbTest, $extraAssetRoot, $romImage, $romWheelImage, $romSnapImage;

	// Make sure it's available as it's down most of the time (timeout of 5 seconds)
	if(!$thegamedbTest) $thegamedbTest = cleanShell("curl -m 5 -s -o /dev/null -w '%{http_code}' 'http://thegamesdb.net/api/GetPlatformsList.php'");

	// Initial connection test failed, skip this scraper
	if($thegamedbTest != 200) return false;

	// Get the raw list of games
	#$xmlRaw = getPage("http://thegamesdb.net/api/GetGamesList.php?name=".rawurlencode(trim(preg_replace('%[^0-9A-Za-z\ ]%', ' ', $gameName)))."&platform=".rawurlencode($tgdbid));
	$xmlRaw = getPage("http://thegamesdb.net/api/GetGamesList.php?name=".rawurlencode($gameName)."&platform=".rawurlencode($tgdbid));
	if($xmlRaw) {
		$gameXML = @simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
		if($gameXML) {
			$gamesArray = json_decode(json_encode($gameXML), true);
			$gamesArray = $gamesArray['Game'];
		}else{
			echo "\n[TheGamesDB Scraper] [{$gameName}] XML Failure.\n";
		}
	}

	// No matches
	if(!$gamesArray) return;

	// Convert single result into array to save on code
	if(isset($gamesArray['GameTitle'])) {
		$arrayTemp = $gamesArray;
		unset($gamesArray);
		$gamesArray[0] = $arrayTemp;
		unset($arrayTemp);

		// Build a confidence list
		foreach($gamesArray as $thisGameArray) {
			$matchPercent = nameCompare($dbGameName, $thisGameArray['GameTitle'], 1);
			if($matchPercent && DEBUG) echo "\n[TheGamesDB Scraper] [{$gameName}] {$thisGameArray['GameTitle']} ({$thisGameArray['id']}) matches with {$matchPercent}% confidence.\n";
			if($matchPercent && $thisGameArray['id']) $nameMatchArray[$thisGameArray['id']] = $matchPercent;
		}
	}

	// No matches, exit
	if(!isset($nameMatchArray)) return;

	// Sort by order of confidence and select the top game
	arsort($nameMatchArray); reset($nameMatchArray);
	$tgdbGameID = key($nameMatchArray);
	if(DEBUG) echo "\n[TheGamesDB Scraper] [{$gameName}] ID of {$tgdbGameID} found.\n";
	unset($nameMatchArray);

	// Get the game details
	if($tgdbGameID) {
		if(DEBUG) echo "\n[TheGamesDB Scraper] [{$gameName}] Grabbing game page.";
		$xmlRaw = getPage("http://thegamesdb.net/api/GetGame.php?id={$tgdbGameID}");
		$gameXML = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
		$gamesArray = json_decode(json_encode($gameXML), true);
		$gamesArray = $gamesArray['Game'];
	}

	// Process response
	if($tgdbGameID && $gamesArray) {
		if(DEBUG) echo "\n[TheGamesDB Scraper] [{$gameName}] Processing game page.";

		// Get the wheel art
		if(!$romWheelImage) $romWheelImage = getTGDBWheelImage($gameName, $tgdbGameID, $assetFolder);
		if($getWheelArtOnly) return;

		// Get the screenshot/snap art
		if(!$romSnapImage) $romSnapImage = getTGDBSnapImage($gameName, $tgdbGameID, $assetFolder);
		if($getSnapArtOnly) return;

		if(!$romImage) {
			// Grab game image
			if(is_array($gamesArray['Images']['boxart'])) {
				foreach($gamesArray['Images']['boxart'] as $thisBoxart) {
					if(strstr($thisBoxart, 'boxart/original/front')) $remoteImage = "http://thegamesdb.net/banners/".$thisBoxart;
				}
			}else{
				if($gamesArray['Images']['boxart']) $remoteImage = "http://thegamesdb.net/banners/".$gamesArray['Images']['boxart'];
			}

			if($remoteImage) {
				$romImage = getImage($gameName, $remoteImage, $assetFolder);
				if(DEBUG && $romImage) echo "\n[TheGamesDB Scraper] [{$gameName}] Grabbed image.";
			}
		}
		if($grabImageOnly) return $romImage;

		// Tidy up description
		$description = trim(preg_replace('%[ ]{2,}%', ' ', preg_replace('%[\s]+%', ' ', strip_tags(preg_replace('%<br/?>%', ' ', $gamesArray['Overview'])))));

		// Create list of genres
		if($gamesArray['Genres']) {
			if(is_array($gamesArray['Genres']['genre'])) {
				$genre = implode(', ', $gamesArray['Genres']['genre']);
			}else{
				$genre = trim($gamesArray['Genres']['genre']);
			}
		}

		// Rating is out of 10. Half it.
		if($gamesArray['Rating']) $gamesArray['Rating'] = $gamesArray['Rating'] / 2;

		// Convert release date to unixtime
		$releaseDate = cleanUnixtime($gamesArray['ReleaseDate']);

		if($releaseDate || $genre || $description || $gamesArray['Rating'] || $gamesArray['Developer'] || $gamesArray['Publisher'] || $gamesArray['Players']) {
			if(DEBUG) echo "\n[TheGamesDB Scraper] [{$gameName}] Info found. Adding to database.";
			updateGameDetails('TheGamesDB', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, $gamesArray['Rating'], $gamesArray['Developer'], $gamesArray['Publisher'], $gamesArray['Players']);
			return true;
		}
	}
}

## API Scraper
function theoldgamesdbScrape($dbGameName, $platformName, $gameName, $tgdbid, $assetFolder, $grabImageOnly = null) {
	// Disable for now, it's a bit useless
	return false;

	if(!$tgdbid) return;
	global $thegamedbTest, $extraAssetRoot, $romImage;

	// Make sure it's available as it's down most of the time
	if(!$thegamedbTest) $thegamedbTest = cleanShell("curl -s -o /dev/null -w '%{http_code}' 'http://thegamesdb.net/api/GetPlatformsList.php'");

	// Initial connection test failed, skip this scraper
	if($thegamedbTest != 200) return false;

	$xmlRaw = getPage("http://thegamesdb.net/api/GetGame.php?name=".rawurlencode(trim(preg_replace('%[^0-9A-Za-z\ ]%', ' ', $gameName)))."&platform={$tgdbid}");
	$gameXML = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
	$gamesArray = json_decode(json_encode($gameXML), true);

	// Process response
	if($gamesArray) {
		// Array returned without levels, create one to simplify things
		if(!is_array($gamesArray['Game'][0])) {
			$tmp = $gamesArray['Game'];
			unset($gamesArray['Game']);
			$gamesArray['Game'][0] = $tmp;
		}

		// Find result that actually match
		foreach($gamesArray['Game'] as $resultID => $thisResult) {
			$remoteName = $thisResult['GameTitle'];

			if(nameCompare($dbGameName, $remoteName)) {

				if(!$romImage) {
					// Grab game image
					if(is_array($thisResult['Images']['boxart'])) {
						foreach($thisResult['Images']['boxart'] as $thisBoxart) {
							if(strstr($thisBoxart, 'boxart/original/front')) $remoteImage = "http://thegamesdb.net/banners/".$thisBoxart;
						}
					}else{
						if($thisResult['Images']['boxart']) $remoteImage = "http://thegamesdb.net/banners/".$thisResult['Images']['boxart'];
					}

					if($remoteImage) {
						$romImage = getImage($gameName, $remoteImage, $assetFolder);
						if(DEBUG && $romImage) echo "\n[TheGamesDB Scraper] [{$gameName}] Grabbed image.";
					}
				}
				if($grabImageOnly) return $romImage;

				// Tidy up description
				$description = trim(preg_replace('%[ ]{2,}%', ' ', preg_replace('%[\s]+%', ' ', strip_tags(preg_replace('%<br/?>%', ' ', $thisResult['Overview'])))));

				// Create list of genres
				if($thisResult['Genres']) {
					if(is_array($thisResult['genre']))	{
						foreach($thisResult['genre'] as $thisGenre) {
							$genreArray = trim($thisGenre);
						}
						if($genreArray) $genre = implode(', ', $genreArray);
					}else{
						$genre = trim($thisResult['genre']);
					}
				}

				// Rating is out of 10. Half it.
				if($thisResult['Rating']) $thisResult['Rating'] = $thisResult['Rating'] / 2;

				$releaseDate = cleanUnixtime($thisResult['ReleaseDate']);

				if($releaseDate || $genre || $description || $thisResult['Rating'] || $thisResult['Developer'] || $thisResult['Publisher'] || $thisResult['Players']) {
					if(DEBUG) echo "\n[TheGamesDB Scraper] [{$gameName}] Info found. Adding to database.";
					updateGameDetails('TheGamesDB', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, $thisResult['Rating'], $thisResult['Developer'], $thisResult['Publisher'], $thisResult['Players']);
					return true;
				}
			}
		}
	}
}

## API Scraper
function archivevgScraper($dbGameName, $platformName, $gameName, $archivevgName, $assetFolder, $grabImageOnly = null) {
	if(!$archivevgName) return;
	global $archivevgTest, $extraAssetRoot, $romImage;

	// Make sure it's available as it's down often now
	if(!$archivevgTest) $archivevgTest = cleanShell("curl -m 5 -s -o /dev/null -w '%{http_code}' 'http://api.archive.vg/2.0/Archive.search/xml/IOIRMXXNTIKE2ONAQASUPFJOZJPQXASP'");

	// Initial connection test failed, skip this scraper
	if($archivevgTest != 200) return false;

	$xmlRaw = trim(html_entity_decode(getPage("http://api.archive.vg/2.0/Archive.search/xml/".KEY_ARCVG."/".rawurlencode($gameName)), ENT_QUOTES, 'UTF-8'));
	$gameXML = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
	$gamesArray = json_decode(json_encode($gameXML), true);

	// Process response
	if($gamesArray) {
		// Empty result set
		if(!$gamesArray['games']['game']) return;

		// Array returned without levels, create one to simplify things
		if(!is_array($gamesArray['games']['game'][0])) {
			$tmp = $gamesArray['games']['game'];
			unset($gamesArray['games']['game']);
			$gamesArray['games']['game'][] = $tmp;
		}

		// Find result that actually match
		foreach($gamesArray['games']['game'] as $resultID => $thisResult) {
			$remoteName = $thisResult['title'];
			if(nameCompare($dbGameName, $remoteName) && $thisResult['system'] == $archivevgName) {
				if(!$romImage) {
					$remoteImage = $thisResult['box_front'];
					if($remoteImage) {
						$romImage = getImage($gameName, $remoteImage, $assetFolder);
						if(DEBUG) if($romImage) echo "\n[archiveVG Scraper] [{$gameName}] Grabbed image.";
					}
				}
				if($grabImageOnly) return $romImage;

				// Tidy up description
				if($thisResult['description']) {
					$description = trim(preg_replace('%[ ]{2,}%', ' ', preg_replace('%[\s]+%', ' ', strip_tags(preg_replace('%<br/?>%', ' ', $thisResult['description'])))));
					// Generic fake description, delete it
					if(strlen($description) < 200 && strstr($description, ' which was released in ')) unset($description);
				}

				if($thisResult['genre']) $genre = str_replace(' >', ',', $thisResult['genre']);

				// Scraper reports rating as $thisResult['rating'] but no games seem populated so unable to figure out "out of 10" or not

				if($genre || $description || $thisResult['developer']) {
					if(DEBUG) echo "\n[archiveVG Scraper] [{$gameName}] Info found. Adding to database.";
					updateGameDetails('archiveVG', $gameName, $dbGameName, $platformName, null, $genre, $description, null, $thisResult['developer']);
					return true;
				}
			}
		}
	}
}

function ignScraper($dbGameName, $platformName, $gameName, $ignPlatformName, $assetFolder, $grabImageOnly = null) {
	if(!$ignPlatformName) return;
	global $romImage;

	# http://uk.ign.com/search?q=SEARCHQUERYHERE&page=0&count=20&type=object&objectType=game&filter=games
	$ignSearchPage = getPage("http://uk.ign.com/search?q=".rawurlencode($gameName)."&page=0&count=20&type=object&objectType=game&filter=games");

	if($ignSearchPage) {
		// Examples
		# href="http://uk.ign.com/games/<name>/<platform>"
		# href="http://uk.ign.com/games/sega-genesis-ultimate-collection/ps3-14286205"
		preg_match_all('%href="(http\://uk\.ign\.com/games/(.+?)/(.+?))"%', $ignSearchPage, $ignGameMatches, PREG_SET_ORDER);

		foreach($ignGameMatches as $thisMatch) {
			## $thisMatch[1] is the game page URL, $thisMatch[2] is the game's name, $thisMatch[3] is the game's platform
			if(preg_match("%{$ignPlatformName}-\d+%", $thisMatch[3]) && nameCompare($thisMatch[2], $gameName)) {
				$ignGamePage = trim(trimWebSpaces(getPage($thisMatch[1])));

				if($ignGamePage) {
					if(!$romImage) {
						preg_match('%<meta property="og\:image" content="(.+?)" />%', $ignGamePage, $remoteImage); $remoteImage = trim(strip_tags($remoteImage[1]));
						// It's the IGN logo, skip it
						if(strstr($remoteImage, 'ign-logo')) unset($remoteImage);
						if(isset($remoteImage)) {
							$romImage = getImage($gameName, $remoteImage, $assetFolder);
							if(DEBUG && $romImage) echo "\.[IGN Scraper] [{$gameName}] Grabbed image.";
						}
					}
					if($grabImageOnly) return $romImage;

					preg_match('%<strong>Developer</strong>\:(.+?)</div>%', $ignGamePage, $developer); $developer = trim(strip_tags($developer[1]));
					if($developer == 'Unknown') $developer = null;

					preg_match('%<strong>Publisher</strong>\:(.+?)</div>%', $ignGamePage, $publisher); $publisher = trim(strip_tags($publisher[1]));
					if($publisher == 'Unknown') $publisher = null;

					preg_match('%<strong>Genre</strong>\:(.+?)</div>%', $ignGamePage, $genre); $genre = trim(strip_tags($genre[1]));
					if($genre == 'Other') $genre = null;

					preg_match('%<div class="gameInfo">(?:[\s\S]*?)<p>(.+?)</p>%', $ignGamePage, $description); $description = trim(strip_tags($description[1]));

					// Get the release date and turn it into just a year
					preg_match('%<strong>Release Date</strong>\:(.+?)</div>%', $ignGamePage, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);

					// Get the rating
					preg_match('%<div class="smallPrint">Community</div>(?:[\s\S]*?)</div>(?:[\s\S]*?)<div class="ratingValue">(.+?)</div>%', $ignGamePage, $rating); $rating = trim(strip_tags($rating[1]));
					// Rating needs to be out of 5 not 10
					if(is_numeric($rating)) $rating = round($rating / 2, 1);

					if($releaseDate || $genre || $description || $rating || $developer || $publisher) {
						if(DEBUG) echo "\n[IGN Scraper] [{$gameName}] Info found. Adding to database.";
						updateGameDetails('IGN', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, $rating, $developer, $publisher);
						return true;
					}
				}
			}
		}
	}
}

function allgamecomScraper($dbGameName, $platformName, $gameName, $allgameName, $assetFolder, $grabImageOnly = null) {
	// Site appears to have shut down, no point running
	return;

	if(!$allgameName) return;
	global $extraAssetRoot, $romImage;

	$allGamePage = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://www.allgame.com/search.php?game=".rawurlencode($gameName)."&sort=relevance_desc")));

	if($allGamePage) {
		preg_match_all('%<tr(?:.+?)?<a href="(game.php\?id=\d+)">(.+?)</a>(?:.+?)?<a href="platform.php\?id=\d+">(.+?)</a>(?:.+?)?</tr>%', $allGamePage, $allgameGameMatches, PREG_SET_ORDER);
		## $thisMatch[1] is game URL, $thisMatch[2] is game name, $thisMatch[3] is platform name
		foreach($allgameGameMatches as $thisMatch) {
			if($thisMatch[3] == $allgameName && nameCompare($thisMatch[2], $gameName)) {
				$allGamePageActual = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://www.allgame.com/{$thisMatch[1]}")));

				if($allGamePageActual) {
					if(!$romImage) {
						preg_match('%src="(.+?)" alt="Cover%', $allGamePageActual, $remoteImage); $remoteImage = trim(strip_tags($remoteImage[1]));
						if($remoteImage) {
							$romImage = getImage($gameName, $remoteImage, $assetFolder);
							if(DEBUG && $romImage) echo "\n[AllGame Scraper] [{$gameName}] Grabbed image.";
						}
					}
					if($grabImageOnly) return $romImage;

					// Get the page and clean it up
					preg_match('%<h2 class="title">Synopsis</h2>(?:.+?)(?:<p class="author">(?:.+?)</p>)?(?:.+?)<p>(.+?)</p>%', $allGamePageActual, $description);
					$description = $description[1];
					$description = preg_replace('%<br/?>%', ' ', $description);
					$description = strip_tags($description);
					$description = preg_replace('%[\s]+%', ' ', $description);
					$description = preg_replace('%[ ]{2,}%', ' ', $description);
					$description = trim($description);

					// Genres and Styles
					preg_match_all('%<li><a href="(?:genre|style)\.php\?id=\d+">(.+?)</a></li>%', $allGamePageActual, $genreArray, PREG_SET_ORDER);
					foreach($genreArray as $thisGenre) { $genres[] = $thisGenre[1]; }
					$genre = implode(', ', $genres);

					preg_match('%<h3>Release Date</h3><p>(.+?)</p>%', $allGamePageActual, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);

					preg_match('%<h3>Developer</h3><p><a href="company\.php\?id=\d+">(.+?)</a>%', $allGamePageActual, $developer); $developer = trim(strip_tags($developer[1]));
					preg_match('%<h3>Publisher</h3><p><a href="company\.php\?id=\d+">(.+?)</a></p>%', $allGamePageActual, $publisher); $publisher = trim(strip_tags($publisher[1]));

					// Scale is 1 to 9
					preg_match('%AllGame Rating</h3><p><a href="/pages/a_ratings\.php#(.+?)">%', $allGamePageActual, $rating); $rating = trim(strip_tags($rating[1]));
					if($rating) $rating = round(($rating / 2) + 0.5, 1);

					if($releaseDate || $genre || $description || $rating || $developer || $publisher) {
						if(DEBUG) echo "\n[AllGame Scraper] [{$gameName}] Info found. Adding to database.";
						updateGameDetails('AllGame', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, $rating, $developer, $publisher);
						return true;
					}
				}
			}
		}
	}
}

function gamefaqsScraper($dbGameName, $platformName, $gameName, $gamefaqsPlatform, $assetFolder, $grabImageOnly = null) {
	if(!$gamefaqsPlatform) return;
	global $romImage;

	// Search Method
	# http://www.gamefaqs.com/search/index.html?platform=PLATFORMID&game=GAMENAME
	#$searchPage = getPage("http://www.gamefaqs.com/search/index.html?platform=0&mobile=1&sort=4&game=".rawurlencode($gameName));

	// Full game-list method
	$gameInitial = strtolower($gameName[0]);
	if(!ctype_alpha($gameInitial)) $gameInitial = '0';

	$searchPage = getPage("http://www.gamefaqs.com/{$gamefaqsPlatform}/list-{$gameInitial}?page=0");
	$nextPage = 1;
	while($nextPage) {
		$nextPageAddr = "/{$gamefaqsPlatform}/list-{$gameInitial}?page={$nextPage}";
		if(strstr($searchPage, $nextPageAddr)) {
			$searchPage .= getPage("http://www.gamefaqs.com{$nextPageAddr}");
			$nextPage++;
		}else{
			unset($nextPage);
		}
	}

	if($searchPage) {
		// Example - href="/cpc/940296-cosmic-shock-absorber"
		preg_match_all('%href="(/(.+?)/(?:.+?))">(.+?)</a>%', $searchPage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			## $thisMatch[1] is the game page URL, $thisMatch[2] is the game's platform, $thisMatch[3] is the game's name
			if($gamefaqsPlatform == $thisMatch[2] && nameCompare($thisMatch[3], $gameName)) {
				$gamePage = trim(trimWebSpaces(getPage("http://www.gamefaqs.com{$thisMatch[1]}")));

				if($gamePage) {
					if(!$romImage) {
						preg_match('%class="boxshot" src="(.+?)"%', $gamePage, $remoteImage); $remoteImage = trim(strip_tags($remoteImage[1]));
						$remoteImage = str_replace('thumb', 'front', $remoteImage);
						// It's the placeholder, skip it
						if(strstr($remoteImage, 'noboxshot')) unset($remoteImage);
						if(strstr($remoteImage, 'images/platform')) unset($remoteImage);

						if($remoteImage) {
							$romImage = getImage($gameName, $remoteImage, $assetFolder);
							if(DEBUG && $romImage) echo "\n[GameFAQs Scraper] [{$gameName}] Grabbed image.";
						}
					}
					if($grabImageOnly) return $romImage;

					// Try for a Developer / Publisher match first
					preg_match('%<li><a href="/features/company/(?:.+?)">(.+?)</a> / <a href="/features/company/(?:.+?)">(.+?)</a></li>%', $gamePage, $devMatch);
					if($devMatch) {
						$developer = trim(strip_tags($devMatch[1]));
						$publisher = trim(strip_tags($devMatch[2]));
					}

					// No match for Dev / Pub, try for same dev / pub
					if(!$developer || !$publisher) {
						preg_match('%developed and published by (.+?), which%', $gamePage, $devMatch);
						if($devMatch) {
							$developer = trim(strip_tags($devMatch[1]));
							$publisher = $developer;
						}
					}

					// Description
					preg_match('%<div class="desc">(.+?)</div>%', $gamePage, $description); $description = trim(strip_tags($description[1]));
					// Generic fake description, delete it
					if(strlen($description) < 200 && strstr($description, ' which was released in ')) unset($description);

					// Get genre from crazy encoding at the top of the page
					preg_match('%genre\&quot;\:&quot;(.+?)\&quot;%', $gamePage, $genre); $genre = trim(str_replace(',', ', ', strip_tags($genre[1])));

					// Get the release date and turn it into just a year
					preg_match('%<li>Release\: <a href="(?:.+?)">(.+?) &raquo;</a></li>%', $gamePage, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);

					// Get the rating
					preg_match('%gamespace-game\'\]\);">(.+?) / 5</a>%', $gamePage, $rating); $rating = trim(strip_tags($rating[1]));

					if($releaseDate || $genre || $description || $rating || $developer || $publisher) {
						if(DEBUG) echo "\n[GameFAQs Scraper] [{$gameName}] Info found. Adding to database";
						updateGameDetails('GameFAQs', $gameName, $dbGameName, $platformName, $releaseDate, $genre, $description, $rating, $developer, $publisher);
						return true;
					}
				}
			}
		}
	}
}

function sharp68kXmlScraper($dbGameName, $gameName) {
	global $x68kGameArray;

	$safeName = safeName($gameName);
	$gameArray = $x68kGameArray[$safeName];

	if($gameArray) {
		if($gameArray['year'] || $gameArray['publisher']) {
			if(DEBUG) echo "\n[X68000 XML Scraper] [{$gameName}] Info found. Adding to database - {$gameArray['year']} | {$gameArray['publisher']}\n";
			updateGameDetails('68kXML', $gameName, $dbGameName, 'x68000', cleanUnixtime($gameArray['year']), null, null, null, null, $gameArray['publisher']);
			return true;
		}
	}
}

function amigaWhdloadXmlScraper($dbGameName, $gameName) {
	global $amigaWhdloadArray;

	$basicGameName = safeName($gameName);
	$matchedGame = $amigaWhdloadArray[$basicGameName];

	if($matchedGame) {
		if(DEBUG) echo "\n[amigaWhdload Xml File Scraper] [{$gameName}] Info found.\n";

		// Remove empty arrays
		if(is_array($matchedGame['year'])) $matchedGame['year'] = null;
		if(is_array($matchedGame['genre'])) $matchedGame['genre'] = null;
		if(is_array($matchedGame['manufacturer'])) $matchedGame['manufacturer'] = null;

		updateGameDetails('amigaWhdloadXml', $gameName, $dbGameName, 'amiga', cleanUnixtime($matchedGame['year']), $matchedGame['genre'], null, null, $matchedGame['manufacturer']);
		return true;
	}
}

function cpcpowerFileScraper($dbGameName, $gameName) {
	global $cpcPowerArray;

	foreach($cpcPowerArray as $thisGameName => $thisGameParts) {
		if(nameCompare($thisGameName, $gameName)) {
			if($thisGameParts['year'] || $thisGameParts['dev']) {
				if(DEBUG) echo "\n[cpcPower File Scraper] [{$gameName}] Info found. Adding to database - {$thisGameParts['year']} | {$thisGameParts['dev']}\n";
				updateGameDetails('cpcPower', $gameName, $dbGameName, 'amstradcpc', cleanUnixtime($thisGameParts['year']), null, null, null, $thisGameParts['dev']);
				return true;
			}
		}
	}
}


function rfgenScraper($dbGameName, $platformName, $gameName, $rfgenPlatform, $assetFolder) {
	if(!$rfgenPlatform) return;

	$searchPage = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://www.rfgeneration.com/cgi-bin/search.pl?type=S&sortby=YEAR&search=%20Search%20&console={$rfgenPlatform}&title=".rawurlencode($gameName))));
	# Showing results 1 to 50 of 103 total results
	preg_match('%Showing results (.+?) to (.+?) of (.+?) total results%', $searchPage, $pageSearchNumbers);
	// $pageSearchNumbers[1] - First Result, $pageSearchNumbers[2] - Last Result, $pageSearchNumbers[3] - Total.
	while($pageSearchNumbers[2] < $pageSearchNumbers[3]) {
		$nextSearchPage = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://www.rfgeneration.com/cgi-bin/search.pl?type=S&sortby=YEAR&firstresult=".($pageSearchNumbers[1] + 50)."&search=%20Search%20&console={$rfgenPlatform}&title=".rawurlencode($gameName))));
		preg_match('%Showing results (.+?) to (.+?) of (.+?) total results%', $nextSearchPage, $pageSearchNumbers);
		$searchPage .= $nextSearchPage;
	}

	if($searchPage) {
		preg_match_all('%<tr class=\'windowbg2\'><td>(?:.+?)</td><td><center><img (?:.+?) title="(?:U|GB|E)"></center></td><td>(?:.+?)</td><td><a href=\'(.+?)\'>(.+?)</a></td>%', $searchPage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			## 1 - URL, 2 - Game Name
			if(nameCompare(strip_tags($thisMatch[2]), $gameName)) {
				$gamePage = trim(preg_replace("%(\n|\r|\t)%", '', (getPage("http://www.rfgeneration.com/cgi-bin/{$thisMatch[1]}"))));

				if($gamePage) {
					preg_match('%Genre\:</td><td style="font-weight\: bold;">(.*?)</td>%', $gamePage, $genre); $genre = trim(str_replace('/', ', ', strip_tags($genre[1])));
					preg_match('%Year\:</td><td style="font-weight\: bold;">(.*?)</td>%', $gamePage, $releaseDate); $releaseDate = cleanUnixtime(strip_tags($releaseDate[1]));
					preg_match('%Developer\:</td><td style="font-weight\: bold;">(.*?)</td>%', $gamePage, $developer); $developer = trim(strip_tags($developer[1]));
					preg_match('%Publisher\:</td><td style="font-weight\: bold;">(.*?)</td>%', $gamePage, $publisher); $publisher = trim(strip_tags($publisher[1]));

					// Rating is out of 100
					preg_match('%<b>(.+?)\%</b>%', $gamePage, $rating); $rating = trim(strip_tags($rating[1]));
					if($rating) $rating = round($rating / 20, 1);

					preg_match('%Players\:</td><td style="font-weight\: bold;">(.*?)</td>%', $gamePage, $players); $players = trim(strip_tags($players[1]));
					$players = explode('-', $players);
					$players = trim(end($players));

					if($releaseDate || $genre || $developer || $publisher || $players) {
						if(DEBUG) echo "\n[rfGen Scraper] [{$gameName}] Info found. Adding to database - $releaseDate || $genre || $developer || $publisher || $players\n";
						updateGameDetails('rfGen', $gameName, $dbGameName, $platformName, $releaseDate, $genre, null, $rating, $developer, $publisher, $players);
						return true;
					}
				}
			}
		}
	}
}


function vectrexgdbScraper($dbGameName, $gameName) {
	$searchPage = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://web.archive.org/web/20110119092325/http://vgdb.vectrex.com/index.pl")));
	if($searchPage) {
		preg_match_all('%href="(vec\.pl\?vgdbcode=VGDB\d+)">(.+?)</a>%', $searchPage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			## 1 - URL, 2 - Game Name
			if(nameCompare(strip_tags($thisMatch[2]), $gameName)) {
				$gamePage = trim(preg_replace("%(\n|\r|\t)%", '', (getPage("http://web.archive.org/web/20110319163634/http://vgdb.vectrex.com/{$thisMatch[1]}"))));
				if($gamePage) {
					preg_match('%<b>Genre</b> \:&nbsp;</font><b><font color="#000000"  size="2">(.*?)</font></b><font size="2"><br>%', $gamePage, $genre); $genre = trim(strip_tags($genre[1]));
					preg_match('%<b>Year</b> <b>of \(c\)</b> \: (.*?)<br>%', $gamePage, $releaseDate); $releaseDate = cleanUnixtime(strip_tags($releaseDate[1]));
					preg_match('%<b>Programmer</b> \: </font>(.*?)<br>%', $gamePage, $developer); $developer = trim(strip_tags($developer[1]));
					preg_match('%<b>Publisher </b>\: (.*?)<br>%', $gamePage, $publisher); $publisher = trim(strip_tags($publisher[1]));
					preg_match('%<b>For</b> \: (.*?) player<br>%', $gamePage, $players); $players = trim(strip_tags($players[1]));

					if($releaseDate || $genre || $developer || $publisher || $players) {
						if(DEBUG) echo "\n[VectrexGDB Scraper] [{$gameName}] Info found. Adding to database - $releaseDate || $genre || $developer || $publisher || $players\n";
						updateGameDetails('VectrexGDB', $gameName, $dbGameName, 'vectrex', $releaseDate, $genre, null, null, $developer, $publisher, $players);
						return true;
					}
				}
			}
		}
	}
}


function pcdicomScraper($dbGameName, $gameName, $assetFolder) {
	global $romImage;

	$searchPage = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://www.philipscdi.com/php/_Games_list.php")));
	if($searchPage) {
		preg_match_all('%href="(_Games_view\.php\?editid1=\d+)">(.+?)</a>%', $searchPage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			## 1 - URL, 2 - Game Name
			if(nameCompare(strip_tags($thisMatch[2]), $gameName)) {
				$gamePage = trim(preg_replace("%(\n|\r|\t)%", '', (getPage("http://www.philipscdi.com/php/{$thisMatch[1]}"))));
				if($gamePage) {
					if(!$romImage) {
						preg_match('%src="../../images/Game/(\d+)_f.jpg"%', $gamePage, $idMatch);
						if($idMatch[1]) $remoteImage = "http://www.philipscdi.com/images/Game/{$idMatch[1]}_f.jpg";
						if($remoteImage) {
							$romImage = getImage($gameName, $remoteImage, $assetFolder);
							if($romImage) echo "\n[PhilipsCDI.com Scraper] [{$gameName}] Grabbed image - {$remoteImage}\n";
						}
					}
					if($grabImageOnly) return $romImage;

					preg_match('%Genre</font></b></td><td width="50\%"><font face="Arial" size="2">(.*?)</font></td></tr>%', $gamePage, $genre); $genre = trim(strip_tags($genre[1]));
					preg_match('%Released</font></b></td><td width="50\%"><font face="Arial" size="2">(.*?)</font></td></tr>%', $gamePage, $releaseDate); $releaseDate = cleanUnixtime(strip_tags($releaseDate[1]));
					preg_match('%Developer</font></b></td><td width="50\%"><font face="Arial" size="2">(.*?)</font></td></tr>%', $gamePage, $developer); $developer = trim(strip_tags($developer[1]));
					preg_match('%Publisher</font></b></td><td width="50\%"><font face="Arial" size="2">(.*?)</font></td></tr>%', $gamePage, $publisher); $publisher = trim(strip_tags($publisher[1]));
					preg_match('%Players</font></b></td><td width="50\%"><font face="Arial" size="2">(.*?)</font></td></tr>%', $gamePage, $players); $players = trim(strip_tags($players[1]));
					if($players) {
						$players = explode('-', $players);
						$players = trim(end($players));
					}

					if($releaseDate || $genre || $developer || $publisher || $players) {
						if(DEBUG) echo "\n[PhilipsCDI.com Scraper] [{$gameName}] Info found. Adding to database - $releaseDate || $genre || $developer || $publisher || $players\n";
						updateGameDetails('PhilipsCDI.com', $gameName, $dbGameName, 'cdi', $releaseDate, $genre, null, null, $developer, $publisher, $players);
						return true;
					}
				}
			}
		}
	}
}

function touhouwikiaScraper($dbGameName, $gameName, $assetFolder) {
	global $romImage;

	// Remove the game number from the start and replace spaces with underscores
	$wikiName = trim(substr($gameName, strpos($gameName, '-') + 1)); 
	$wikiName = str_replace(' ', '_', $wikiName);

	$gamePage = trim(preg_replace("%([\t|\n]*)%", '', getPage("http://touhou.wikia.com/wiki/{$wikiName}")));

	if($gamePage) {
		if(!$romImage) {
			preg_match('%<td style="text-align\: center; border\: 1px solid #999999;" colspan="2"> <a href="(.+?)"%', $gamePage, $remoteImage); $remoteImage = trim(strip_tags($remoteImage[1]));
			if($remoteImage) {
				$romImage = getImage($gameName, $remoteImage, $assetFolder);
				if(DEBUG && $romImage) echo "\n[Touhou Wikia Scraper] [{$gameName}] Grabbed image.\n";
			}
		}
		if($grabImageOnly) return $romImage;

		preg_match("%<span class=\"mw-headline\" id=\"Gameplay\">[\s]*Gameplay[\s]*</span></h2>(?:<dl><dd><i>Main article\:(?:.+?)</dd></dl>)?<p>(.+?)?</p>%", $gamePage, $description); $description = trim(strip_tags($description[1]));
		# Full: March 14, 2010 or Full: March 14th, 2010
		preg_match("%Released</th><td(?:.+?)?>(?:.+?)?Full\: ([A-Za-z]+ \d+(?:[A-Za-z]*)?, \d+)%", $gamePage, $releaseDate); $releaseDate = cleanUnixtime(trim(strip_tags($releaseDate[1])));
		preg_match("%Developer</th><td(?:.+?)?>(.+?)</td>%", $gamePage, $developer); $developer = trim(strip_tags($developer[1]));
		preg_match("%Publisher</th><td(?:.+?)?>(.+?)</td>%", $gamePage, $publisher); $publisher = trim(strip_tags($publisher[1]));
		preg_match("%Genre</th><td(?:.+?)?>(.+?)</td>%", $gamePage, $genre); $genre = trim(strip_tags($genre[1]));

		if($description || $releaseDate || $genre || $developer || $publisher) {
			if(DEBUG) echo "\n[Touhou Wikia Scraper] [{$gameName}] Info found. Adding to database.\n";
			updateGameDetails('TouhouWikia', $gameName, $dbGameName, 'touhou', $releaseDate, $genre, $description, null, $developer, $publisher);
			return true;
		}
	}
}

function pcfxwikiScraper($gameName, $dbGameName) {
	$gamePage = trim(preg_replace("%([\t|\n]*)%", '', getPage("http://en.wikipedia.org/wiki/List_of_PC-FX_games")));

	if($gamePage) {
		# Name, Release Date, Developer, Publisher
		preg_match_all('%<tr><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td></tr>%', $gamePage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			$thisMatch = array_map('strip_tags', $thisMatch);
			$thisMatch = array_map('trim', $thisMatch);

			if(nameCompare($thisMatch[1], $gameName)) {
				if($thisMatch[2]) $releaseDate = cleanUnixtime($thisMatch[2]);

				if($releaseDate || $thisMatch[3] || $thisMatch[4]) {
					if(DEBUG) echo "\n[Wiki PCFX Scraper] [{$gameName}] Info found. Adding to database.\n";
					updateGameDetails('WikiPCFX', $gameName, $dbGameName, 'pcfx', $releaseDate, null, null, null, $thisMatch[3], $thisMatch[4]);
					return true;
				}
			}
		}
	}
}

function lexaloffleScraper($gameName, $dbGameName) {
	$lexDbResult = DBSingleAssoc("SELECT * FROM picoGames WHERE pico_name = ? LIMIT 1", array($gameName));

	if($lexDbResult) {
		if(DEBUG) echo "\n[Lexaloffle PICO-8 Scraper] [{$gameName}] Info found. Adding to database.\n";
		updateGameDetails('LexPICO8', $gameName, $dbGameName, 'pico8', $lexDbResult['pico_date'], null, null, null, $lexDbResult['pico_author'], 'Lexaloffle', 1);
		return true;
	}
}

function amstradcpcwikiScraper($gameName, $dbGameName) {
	$gamePage = trim(preg_replace("%([\t|\n]*)%", '', getPage("http://en.wikipedia.org/wiki/List_of_Amstrad_CPC_games")));

	if($gamePage) {
		# Name, Release Date, Publisher
		preg_match_all('%<tr><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td></tr>%', $gamePage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			$thisMatch = array_map('strip_tags', $thisMatch);
			$thisMatch = array_map('trim', $thisMatch);

			if(nameCompare($thisMatch[1], $gameName)) {
				if($thisMatch[2]) $releaseDate = cleanUnixtime($thisMatch[2]);

				if($releaseDate || $thisMatch[3]) {
					if(DEBUG) echo "\n[Wiki AmstradCPC Scraper] [{$gameName}] Info found. Adding to database.\n";
					updateGameDetails('WikiAmstradCPC', $gameName, $dbGameName, 'amstradcpc', $releaseDate, null, null, null, null, $thisMatch[3]);
					return true;
				}
			}
		}
	}
}

function doomworldScraper($dbGameName, $gameName) {
	$urlGameName = rawurlencode($gameName);
	$gamePage = trim(preg_replace("%([\t|\n]*)%", '', getPage("http://www.doomworld.com/idgames/index.php?search=1&field=title&word={$urlGameName}&sort=score&order=desc&page=1")));

	if($gamePage) {
		## Create array of search result rows
		preg_match_all('%<tr><td class=wadlisting_name(.+?)</tr></table>%', $gamePage, $searchGameMatches, PREG_SET_ORDER);
		if(!$searchGameMatches) return;

		## Work through each row
		foreach($searchGameMatches as $thisGameRow) {
			$thisGameRow = $thisGameRow[0];

			preg_match('%<td class=wadlisting_name><a href=.+?>(.+?)</a>%', $thisGameRow, $searchGameName);

			if(nameCompare($searchGameName[1], $gameName)) {
				preg_match('%class\=wadlisting_description>(.+?)?</td>%', $thisGameRow, $description); $description = trim(strip_tags($description[1]));
				preg_match('%Author\:</td><td class\=wadlisting_field><a href=(?:.+?)>(.+?)?</a></td>%', $thisGameRow, $developer); $developer = trim(strip_tags($developer[1]));
				preg_match('%Date\:</td><td class\=wadlisting_field>(.+?)?</td>%', $thisGameRow, $releaseDate); $releaseDate = cleanUnixtime(strip_tags($releaseDate[1]));
				// Count the number of stars as it's not listed as an actual number!
				$rating = substr_count($thisGameRow, '/star.gif');

				if($description || $developer || $releaseDate || $rating) {
					if(DEBUG) echo "\n[Doom World Scraper] [{$gameName}] Info found.\n";
					updateGameDetails('DoomWorld', $gameName, $dbGameName, 'doom', $releaseDate, 'Action, Shooter', $description, $rating, $developer, 'id Software', '1');
					return true;
				}
			}
		}
	}
}

function wosScraper($dbGameName, $gameName, $assetFolder) {
	// Weird site insists on putting 3D at the end, e.g. "3D Chess" becomes "Chess, 3D"
	if(preg_match('%^3D %', $gameName)) { $gameName = preg_replace('%^3D %', '', $gameName).', 3D'; }
	if(preg_match('%^The %', $gameName)) { $gameName = preg_replace('%^The %', '', $gameName).', The'; }
	if(preg_match('%^A %', $gameName)) { $gameName = preg_replace('%^A %', '', $gameName).', A'; }

	// Full game-list method
	$gameInitial = strtolower($gameName[0]);
	if(!ctype_alpha($gameInitial)) $gameInitial = '1';
	$searchPage = getPage("http://www.worldofspectrum.org/games/{$gameInitial}.html");

	if($searchPage) {
		preg_match_all('%<A HREF="/infoseekid\.cgi\?id=(.+?)">(.+?)</A>%', $searchPage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			## 1 - Game ID, 2 - Game Name
			if(nameCompare($thisMatch[2], $gameName)) {
				$gamePage = trim(preg_replace("%(\n|\r|\t)%", '', getPage("http://www.worldofspectrum.org/infoseekid.cgi?id={$thisMatch[1]}&loadpics=2")));
				if($gamePage) {
					global $romImage;
					if(!$romImage) {
						preg_match('%<IMG SRC="(pics/inlays/(?:.+?)/(?:.+?))"%', $allGamePageActual, $remoteImage); $remoteImage = trim($remoteImage[1]);
						if($remoteImage) {
							$romImage = getImage($gameName, "http://www.worldofspectrum.org/{$remoteImage}", $assetFolder);
							if($romImage) echo "\n[WoS Scraper] [{$gameName}] Grabbed image.";
						}
					}
					if($grabImageOnly) return $romImage;

					preg_match('%Type</FONT><TD WIDTH="100\%"><FONT FACE="Arial,Helvetica">(.*?)</FONT>%', $gamePage, $genre); $genre = trim(str_replace('/', ', ', strip_tags($genre[1])));
					preg_match('%Year of release</FONT><TD WIDTH="100\%"><FONT FACE="Arial,Helvetica">(.*?)</FONT>%', $gamePage, $releaseDate); $releaseDate = cleanUnixtime(strip_tags($releaseDate[1]));
					preg_match('%Author\(s\)</FONT><TD WIDTH="100\%"><FONT FACE="Arial,Helvetica">(.*?)</FONT>%', $gamePage, $developer); $developer = tidyRomName(trim(strip_tags($developer[1])));
					preg_match('%Publisher</FONT><TD WIDTH="100\%"><FONT FACE="Arial,Helvetica">(.*?)</FONT>%', $gamePage, $publisher); $publisher = tidyRomName(trim(strip_tags($publisher[1])));
					preg_match('%Number of players</FONT><TD WIDTH="100\%"><FONT FACE="Arial,Helvetica">(.*?)</FONT>%', $gamePage, $players); $players = trim(strip_tags($players[1]));
					$players = trim($players)[0];
					preg_match('%Score</FONT><TD WIDTH="100\%"><FONT FACE="Arial,Helvetica">(.*?) <FONT%', $gamePage, $rating); $rating = trim(strip_tags($rating[1]));
					if($rating) $rating = round($rating / 2, 1);


					if($releaseDate || $genre || $rating || $developer || $publisher || $players) {
						if(DEBUG) echo "\n[WoS Scraper] [{$gameName}] Info found. Adding to database - $releaseDate || $genre || $rating || $developer || $publisher || $players\n";
						updateGameDetails('WoS', $gameName, $dbGameName, 'zxspectrum', $releaseDate, $genre, null, $rating, $developer, $publisher, $players);
						return true;
					}
				}
			}
		}
	}
}

function zxspectrumwikiScraper($gameName, $dbGameName) {
	$gamePage = trim(preg_replace("%([\t|\n]*)%", '', getPage("http://en.wikipedia.org/wiki/List_of_ZX_Spectrum_games")));

	if($gamePage) {
		# Name, Publisher, Developer, Licenced From, Release Date.
		preg_match_all('%<tr><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td></tr>%', $gamePage, $searchGameMatches, PREG_SET_ORDER);

		foreach($searchGameMatches as $thisMatch) {
			$thisMatch = array_map('strip_tags', $thisMatch);
			$thisMatch = array_map('trim', $thisMatch);

			if(nameCompare($thisMatch[1], $gameName)) {
				if($thisMatch[5]) $releaseDate = cleanUnixtime($thisMatch[2]);

				if($releaseDate || $thisMatch[2] || $thisMatch[3]) {
					if(DEBUG) echo "\n[Wiki ZXSpectrum Scraper] [{$gameName}] Info found. Adding to database.\n";
					updateGameDetails('WikiZXSpectrum', $gameName, $dbGameName, 'zxspectrum', $releaseDate, null, null, null, $thisMatch[3], $thisMatch[2]);
					return true;
				}
			}
		}
	}
}

function holScrape($gameName, $dbGameName, $platformName) {
	global $fullHolPage;

	if(!$fullHolPage) $fullHolPage = file_get_contents(HOL_CACHE);

	if(!$fullHolPage) {
		$holBasePage = 'http://hol.abime.net/hol_search.php?&tri=S_gamename&order=ASC';
		$holSearchLimit = 0;
		while($holSearchLimit <= 6600) {
			$currentHolPage = getPage("http://hol.abime.net/hol_search.php?&tri=S_gamename&order=ASC&limitstart={$holSearchLimit}");
			## Actual table data is between <!-- Game Listing Begin --> and <!-- Game Listing End -->
			preg_match('%<\!-- Game Listing Begin -->([\s\S]+)\<\!-- Game Listing End -->%', $currentHolPage, $holUseful);
			$fullHolPage .= trim($holUseful[1]);
			$holSearchLimit = $holSearchLimit + 100; // Each page is 100 results
		}

		file_put_contents(HOL_CACHE, $fullHolPage);
	}

	// <tr bgcolor="#153F79"><td align="center"><a href="http://hol.abime.net/hol_popup_picture.php?url=pic_full/dbs/2301-2400/2358_dbs1.png&width=640&height=256&zoom=1&title=3D World Tennis - Double Barrel Screenshot" target="_blank" onClick="return enlarge('http://hol.abime.net/pic_full/dbs/2301-2400/2358_dbs1.png','event','center','640','256','3D World Tennis - Double Barrel Screenshot','1')"><img src="http://hol.abime.net/pic_preview/dbs/2301-2400/2358_dbs1.png" border="0" width="60" height="24" title="3D World Tennis - Double Barrel Screenshot"></a></td><td align="left" nowrap><!--b--><a href="http://hol.abime.net/2358">3D World Tennis</a><!--e--></td><td align="left" nowrap><a href="http://hol.abime.net/hol_search.php?&N_ref_hardware=9">ECS</a> <a href="http://hol.abime.net/hol_search.php?&N_ref_hardware=11">OCS</a> <br></td><td align="left"><a href="http://hol.abime.net/hol_search.php?&Y_released=1992">1992</a></td><td align="left"><a href="http://hol.abime.net/hol_search.php?&N_ref_developer=1"></a></td><td align="left"><a href="http://hol.abime.net/hol_search.php?&N_ref_publisher=539">Simulmondo</a><br></td></tr>
	$holPageArray = explode("\n", $fullHolPage);
	foreach($holPageArray as $holRow) {
		preg_match('%hol\.abime\.net/\d+">(.+?)</a>%', $holRow, $holGameName);
		if(nameCompare($gameName, htmlDecode($holGameName[1]))) {
			preg_match('%Y_released\=\d*">(\d*)</a>%', $holRow, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);
			preg_match('%N_ref_developer\=\d*">(.*?)</a>%', $holRow, $developer); $developer = htmlDecode(trim($developer[1]));
			preg_match('%N_ref_publisher\=\d*">(.*?)</a>%', $holRow, $publisher); $publisher = htmlDecode(trim($publisher[1]));

			if($releaseDate || $developer || $publisher) {
				if(DEBUG) echo "\n[HOL Scraper] [{$gameName}] Info found - {$releaseDate} {$developer} {$publisher}\n";
				updateGameDetails('HOL', $gameName, $dbGameName, $platformName, $releaseDate, null, null, null, $developer, $publisher);
				return true;
			}
		}
	}
}

##########
## MAME ##
##########
function mameBinaryCheck($gameName, $dbGameName, $romName) {
	$gameXml = getMameGameXml($romName);

	if($gameXml) {
		if($gameXml['year']) $year = strtotime("2nd Jan {$gameXml['year']}");
		$manufacturer = $gameXml['manufacturer'];
		$status = $gameXml['driver']['@attributes']['status'];
		$players = $gameXml['input']['@attributes']['players'];

		if($year || $manufacturer || $status || $players) {
			if(DEBUG) echo "\n[MAME Binary Scraper] [{$gameName}] Info found. Adding to database - Year: {$year}, Developer: {$manufacturer}, Players: {$players}";
			updateGameDetails('MameBinary', $gameName, $dbGameName, 'mame', $year, null, null, null, $manufacturer, null, $players, null, $status);
			return true;
		}
	}
}

function mamedbScrape($gameName, $dbGameName, $romName) {
	$gamePage = trim(preg_replace("%([\t]*)%", '', getPage("http://www.mamedb.com/game/{$romName}")));

	if($gamePage) {
		// Score is out of 10, half to get 5
		preg_match("%<b>Score\:&nbsp;</b>(.+?) %", $gamePage, $rating); $rating = trim($rating[1]);
		if($rating) $rating = round($rating / 2, 1);

		// Developer and Publisher always seem to be the same for MAME games (don't check status, it's out of date)
		preg_match("%<b>Year\:&nbsp</b>(?:.+?)?<a href='/year/(?:.+?)'>(.+?)</a><br/>%", $gamePage, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);
		preg_match("%<b>Manufacturer\:&nbsp</b>(?:.+?)?<a href='/manufacturer/(?:.+?)'>(.+?)</a><br/>%", $gamePage, $developer); $developer = trim($developer[1]);
		preg_match("%<b>Category\:&nbsp;</b><a href='(?:.+?)'>(.+?)</a><br/>%", $gamePage, $genre); $genre = trim($genre[1]);
		preg_match("%<b>Players\:&nbsp;</b>(.+?)<br/>%", $gamePage, $players); $players = trim($players[1]);

		// Clone check
		preg_match("%<b>Name\:&nbsp</b>(?:.+?)(?:sample|clone) of\: <a href='/(?:.+?)'>(.+?)</a>%", $gamePage, $cloneCheck);
		if($cloneCheck[1] && $cloneCheck[1] != $romName && !strstr($cloneCheck[0], 'NO CD')) echo "\n[MameDB Scraper] [{$gameName}] Warning: {$romName} is a clone of {$cloneCheck[1]}\n";

		if($releaseDate || $genre || $rating || $developer || $players) {
			if(DEBUG) echo "\n[MameDB Scraper] [{$gameName}] Info found. Adding to database";
			updateGameDetails('MameDB', $gameName, $dbGameName, 'mame', $releaseDate, $genre, null, $rating, $developer, $developer, $players, null, true);
			return true;
		}
	}
}

function mameFileScrape($gameName, $dbGameName, $romName) {
	// Get game info from MAME game data file
	$mameInfoArray = explode("*#*#*#*\n********************************************************************\n*#*#*#*", trim(file_get_contents(MAME_INFO)));

	// Example content
	/*
	Game Filename: typhoon
	Take control of a powerful helicopter and blast enemy strongholds in this superb shooter from the good folks at Konami. Typhoon features some impressive graphics filled with rotation and scaling effects (some may get a little dizzy, though!), a solid soundtrack and excellent game control. Highly recommended for any shoot'em up enthusiast!
	- TECHNICAL -
	*/

	foreach($mameInfoArray as $thisMameBlock) {
		preg_match("%Game\: (.+?)\n%", $thisMameBlock, $mameGameName); $mameGameName = trim($mameGameName[1]);
		preg_match("%Game Filename\: (.+?)\n(?:((?!- )[\s\S]+?)(?:\n- )?)?%", $thisMameBlock, $mameRomName);
		if(nameCompare($gameName, $mameGameName) || nameCompare($romName, $mameRomName[1])) {
			$description = trim($mameRomName[2]);
 
			preg_match('%Developer\: (.+?)\n%', $thisMameBlock, $developer); $developer = trim($developer[1]);
			preg_match('%Publisher\: (.+?)\n%', $thisMameBlock, $publisher); $publisher = trim($publisher[1]);
			preg_match('%Release Year\: (.+?)\n%', $thisMameBlock, $releaseDate); $releaseDate = cleanUnixtime($releaseDate[1]);
			preg_match('%Genre\: (.+?)\n%', $thisMameBlock, $genre); $genre = trim($genre[1]);
			if($genre == '[Not Classified]') unset($genre);

			if($releaseDate || $genre || $description || $developer || $publisher) {
				if(DEBUG) echo "\n[MAME File Scraper] [{$gameName}] Info found. Adding to database";
				updateGameDetails('MameFile', $gameName, $dbGameName, 'mame', $releaseDate, $genre, $description, null, $developer, $publisher);
				return true;
			}
		}
	}
}

?>
