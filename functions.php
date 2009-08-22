<?php

// Prints a fatal error and exits.
function Fatal($error)
{
	print("$error\n");
	exit(1);
}

// Creates the database schema.
function InitDatabase(PDO $db)
{
	$stmt = $db->query("
		CREATE TABLE shows (
			show_id integer PRIMARY KEY AUTOINCREMENT,
			name varchar(50),
			search_url text,
			last_check integer,
			last_season integer,
			last_episode integer
		);
	");
	assert($stmt);
	
	$stmt = $db->query("
		CREATE TABLE downloads (
			nzbid integer PRIMARY KEY,
			show_id integer,
			title text,
			season integer,
			episode integer,
			timestamp integer
		);
	");
	assert($stmt);
}

function GetShowFromName($name)
{
	global $database_;
	
	$query = $database_->prepare("SELECT * FROM shows WHERE name = ?");
	$result = $query->execute(array($name));
	return $query->fetchObject()->show_id;
}

function CreateCURLHandler($url, $return = true)
{
	$rfp = curl_init($url);
	curl_setopt($rfp, CURLOPT_USERPWD, config::$newzbin_user . ':' . config::$newzbin_password);
	curl_setopt($rfp, CURLOPT_RETURNTRANSFER, $return);
	return $rfp;
}

function TokenizeTitle($title)
{
	$season = 0;
	$episode = 0;
	preg_match('/([0-9]+)x([0-9]+)/', $title, $matches);
	return array(intval($matches[1]), intval($matches[2]));
}
