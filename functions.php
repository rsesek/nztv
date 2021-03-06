<?php
// NZTV
// Copyright (c) 2010 Blue Static
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

// Prints a fatal error and exits.
function Fatal($error)
{
  print("$error\n");
  exit(1);
}

// Creates the database schema.
function InitDatabase(\PDO $db)
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

const LOG_ERR = '! ERROR';
const LOG_MSG = 'Message';
const LOG_WRN = 'WARNING';
function LogMessage($msg, $level = LOG_MSG)
{
  $fp = fopen('./nztv.log', 'a');
  $date = date('Y-m-d H:i:s');
  fwrite($fp, "[$date] $level: $msg\n");
  fclose($fp);
}

function GetProvider()
{
  static $provider = NULL;
  if (!$provider) {
    $path = './provider_' . strtolower(\config::$provider) . '.php';
    require_once $path;
    $name = 'nztv\Provider' . \config::$provider;
    $provider = new $name();
  }
  return $provider;
}

function SafeFileName($str)
{
  return str_replace(array('/', ':'), '_', $str);
}
