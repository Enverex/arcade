<?php

$thisInitPath = realpath(dirname(__FILE__));
require_once($thisInitPath.'/config.php');
require_once($thisInitPath.'/database.php');
require_once($thisInitPath.'/functions.php');
require_once($thisInitPath.'/scrapers.php');
unset($thisInitPath);

?>
