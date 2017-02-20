<?php
require_once('./main.php');

function main($arguments) {
	if (!isset($arguments[1]) || !isset($arguments[2]) || !isset($arguments[3])) {
		dump('Das Skript muss in foldender Syntax aufgerufen werden:');
		dump('php -f preview.php path-to-old-sql-file path-to-new-sql-file segment-write-key');
		return;
	}
	convert($arguments[1], $arguments[2], $arguments[3]);
}

main($argv);
