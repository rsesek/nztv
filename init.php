<?php

require './config.php';
require './functions.php';

// Don't need the name of the program.
array_shift($argv);
$argc--;

// Load the database.
$new_db = false;
if (!file_exists(config::$database_path))
{
	echo "Database does not exist at '" . config::$database_path . "'. Creating.\n";
	$new_db = true;
}
$database_ = new PDO('sqlite:' . config::$database_path);

if ($new_db)
	InitDatabase($database_);
