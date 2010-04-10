<?php
// NZTV
// Copyright (c) 2009 Blue Static
// Authored by Robert Sesek <rsesek@bluestatic.org>
// 
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or any later version.
// 
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.

namespace nztv;

// This file imports functions and sets up the database. Standard
// initialization stuff, ya know?

require './bookeeper.php';
require './config.php';
require './functions.php';
require './provider.php';

// Don't need the name of the program.
array_shift($argv);
$argc--;

// Load the database.
$new_db = false;
if (!file_exists(\config::$database_path)) {
  echo "Database does not exist at '" . \config::$database_path . "'. Creating.\n";
  $new_db = true;
}
$database_ = new \PDO('sqlite:' . \config::$database_path);

if ($new_db) {
  InitDatabase($database_);
}
