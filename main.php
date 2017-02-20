<?php

/**
 * Convert all IDs using the Segment API.
 *
 * @param  string  $oldDataPath  The path to the old .sql file.
 * @param  string  $newDataPath  The path to the new .sql file.
 * @return void
 */
function convert($oldDataPath, $newDataPath, $segmentKey) {
	// autoload Segment through composer
	require __DIR__ . '/vendor/autoload.php';
	// init Segment
	Segment::init($segmentKey);
	// set the timezone
	date_default_timezone_set('Europe/Berlin');

	$teamArray = getTeamArray($oldDataPath, $newDataPath);
	$userArray = getUserArray($oldDataPath, $newDataPath);

	dump(PHP_EOL . "\e[4mConverting all teams\e[24m" . PHP_EOL);
	convertData($teamArray);

	dump(PHP_EOL . "\e[4mConverting all users\e[24m" . PHP_EOL);
	convertData($userArray);
}

/**
 * Get a preview of all changed IDs and their corresponding keys.
 *
 * @param  string  $oldDataPath  The path to the old .sql file.
 * @param  string  $newDataPath  The path to the new .sql file.
 * @return void
 */
function watch($oldDataPath, $newDataPath) {
	$teamArray = getTeamArray($oldDataPath, $newDataPath);
	$userArray = getUserArray($oldDataPath, $newDataPath);

	dump(PHP_EOL . "\e[4mIDs of all teams\e[24m" . PHP_EOL);
	previewData($teamArray);

	dump(PHP_EOL . "\e[4mIDs of all users\e[24m" . PHP_EOL);
	previewData($userArray);
}

/**
 * Get the part of the .sql file which contains the important data.
 *
 * @param  string  $dataPath  The path to the .sql file.
 * @param  string  $table  The name of the table which should be extracted.
 * @return string
 */
function getDataPartOfSqlFile($dataPath, $table)
{
	$data = file_get_contents($dataPath);
	$data = explode('LOCK TABLES `' . $table . '` WRITE;', $data)[1];
	$data = explode('/*!40000 ALTER TABLE `' . $table . '` ENABLE KEYS */;', $data)[0];
	return explode('VALUES', $data)[1];
}

/**
 * Print a preview of the array of the data.
 *
 * @param  array  $dataArray  Format:
 *                            [
 *                              key(eg. email or slug) => [
 *                                'old' => old ID,
 *                                'new' => new ID
 *                              ]
 *                            ]
 * @return void
 */
function previewData($dataArray) {
	foreach ($dataArray as $key => $data) {
		if (!isset($data['old']) || !isset($data['new'])) {
			dump("\e[31m\e[5mX \e[25mWATCH OUT! There is something missing here! This entry will be skipped.\e[39m");
		}
		$oldId = str_pad(isset($data['old']) ? $data['old'] : '', 3, ' ', STR_PAD_LEFT);
		$newId = str_pad(isset($data['new']) ? $data['new'] : '', 6, ' ', STR_PAD_LEFT);
		dump("{$oldId} => {$newId} | {$key}");
	}
}

/**
 * Convert the array using the Segment API.
 *
 * @param  array  $dataArray  Format:
 *                            [
 *                              key(eg. email or slug) => [
 *                                'old' => old ID,
 *                                'new' => new ID
 *                              ]
 *                            ]
 * @return void
 */
function convertData($dataArray) {
	foreach ($dataArray as $key => $data) {
		if (!isset($data['old']) || !isset($data['new'])) {
			dump("\e[37mSkipped entity \e[39m{$key}\e[37m.");
			continue;
		}
		Segment::alias(array(
			"previousId" => $data['old'],
			"userId"     => $data['new']
		));
		dump("\e[37mEntity \e[39m{$key} \e[37mhas been aliased from \e[39m{$data['old']} \e[37mto \e[39m{$data['new']}\e[37m.");
	}
	// make text default color again
	dump("\e[39m");
}

/**
 *
 * TEAM PART
 *
 */

/**
 * Get a team array with the changed IDs from two .sql files.
 *
 * @param  string  $oldDataPath  The path to the old file.
 * @param  string  $newDataPath  The path to the new file.
 * @return array
 */
function getTeamArray($oldDataPath, $newDataPath) {
	$oldData = getDataPartOfSqlFile($oldDataPath, 'teams');
	$newData = getDataPartOfSqlFile($newDataPath, 'teams');
	$teamArray = [];
	extractTeamData($oldData, function($id, $slug) use (&$teamArray) {
		$teamArray[$slug] = ['old' => $id];
	});
	extractTeamData($newData, function($id, $slug) use (&$teamArray) {
		$teamArray[$slug]['new'] = $id;
	});
	return $teamArray;
}

/**
 * Extract the id and the key from the team part of a .sql file.
 *
 * @param  string  $dataToProcess  The part of the file.
 * @param  callable  $target  A callback to which the id and key are passed.
 * @return void
 */
function extractTeamData($dataToProcess, callable $target) {
	// get all lines
	$lines = explode(PHP_EOL, $dataToProcess);
	foreach ($lines as $line) {
		// remove empty lines
		if ($line == '') {
			continue;
		}
		// extract slug and old id
		$lineData = explode(',\'', $line);
		$slug = trim($lineData[2], "'");
		$id = trim($lineData[0], "\t('");
		// pass the data to the target callback
		$target($id, $slug);
	}
}

/**
 *
 * USER PART
 *
 */

/**
 * Get a iser array with the changed IDs from two .sql files.
 *
 * @param  string  $oldDataPath  The path to the old file.
 * @param  string  $newDataPath  The path to the new file.
 * @return array
 */
function getUserArray($oldDataPath, $newDataPath) {
	$oldData = getDataPartOfSqlFile($oldDataPath, 'users');
	$newData = getDataPartOfSqlFile($newDataPath, 'users');
	$userArray = [];
	extractUserData($oldData, function($id, $email) use (&$userArray) {
		$userArray[$email] = ['old' => $id];
	});
	extractUserData($newData, function($id, $email) use (&$userArray) {
		$userArray[$email]['new'] = $id;
	});
	return $userArray;
}

/**
 * Extract the id and the key from the user part of a .sql file.
 *
 * @param  string  $dataToProcess  The part of the file.
 * @param  callable  $target  A callback to which the id and key are passed.
 * @return void
 */
function extractUserData($dataToProcess, callable $target) {
	// get all lines
	$lines = explode(PHP_EOL, $dataToProcess);
	foreach ($lines as $line) {
		// remove empty lines
		if ($line == '') {
			continue;
		}
		// extract slug and old id
		$lineData = explode(',\'', $line);
		$email = trim($lineData[2], "'");
		$id = trim($lineData[0], "\t('");
		// pass the data to the target callback
		$target($id, $email);
	}
}

/**
 * Dump output to the console.
 *
 * @param  string  $output
 * @return void
 */
function dump($output) {
	echo $output . PHP_EOL;
}
