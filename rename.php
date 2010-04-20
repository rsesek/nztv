#!/usr/bin/env php
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

$path = dirname(__FILE__);
require "$path/functions.php";

// Format: SEARCH_URL % (show, season, episode)
define('SEARCH_URL', 'http://services.tvrage.com/tools/quickinfo.php?show=%s&ep=%dx%d');

// Takes in the name of a file or folder to lookup and rename.
if ($argc != 2)
  die('rename.php original-download-name');
$path     = $argv[1];
$original = basename($path);

// Regex match the original title.
$matches = array();
preg_match('/^(\d+_)?(.+) S?(\d+)(x|E)(\d+)/', $original, $matches);

if (count($matches) < 5)
  die("Could not parse '$original'");

// Create the search parameters.
$search = array();
$search['episode'] = intval(end($matches));
prev($matches);  // Skip the season/ep delimiter.
$search['season'] = intval(prev($matches));
$search['show']   = trim(prev($matches));

// Perform the search and split the data info into a map.
$data = file_get_contents(sprintf(SEARCH_URL, urlencode($search['show']), $search['season'], $search['episode']));
$info = array();
array_map(function ($s) {
    global $info;
    if (!empty($s)) {
      $parts = explode('@', $s);
      $info[$parts[0]] = $parts[1];
    }
  },
  explode("\n", $data)
);

// If we received the information we require, perform the rename.
if (isset($info['Show Name']) && isset($info['Episode Info'])) {
  $epinfo = explode('^', $info['Episode Info']);
  $new = $info['Show Name'] . ' - ' . $search['season'] . 'x' . $search['episode'] . ' - ' . $epinfo[1];

  // If this is a directory, store the old name.
  if (is_dir($path)) {
    file_put_contents($path . '/renamed.txt', $original);
  }

  // Perform the rename.
  rename($path, dirname($path) . '/' . SafeFileName($new));
}
