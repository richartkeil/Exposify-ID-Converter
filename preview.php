<?php
require_once('./main.php');

function main($arguments) {
	if (!isset($arguments[1]) || !isset($arguments[2])) {
		dump('Das Skript muss in foldender Syntax aufgerufen werden:');
		dump('php -f preview.php path-to-old-sql-file path-to-new-sql-file');
		return;
	}
	watch($arguments[1], $arguments[2]);
}

main($argv);
