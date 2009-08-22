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
			last_download integer,
			last_check integer,
			last_season integer,
			last_episode integer
		);
	");
	assert($stmt);
	
	$stmt = $db->query("
		CREATE TABLE search_params (
			show_id integer,
			key varchar(50),
			value varchar(50),
			PRIMARY KEY (show_id, key)
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
