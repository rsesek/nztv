#!/usr/bin/env php
<?php
// NZTV
// Copyright (c) 2011 Blue Static
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

// Arguments passed to this script by SABnzbd:
//
// 1 - The final directory of the job (full path)
// 2 - The original name of the NZB file
// 3 - Clean version of the job name (no path info and ".nzb" removed)
// 4 - Indexer's report number (if supported)
// 5 - User-defined category
// 6 - Group that the NZB was posted in e.g. alt.binaries.x
// 7 - Status of post processing. 0 = OK, 1=failed verification, 2=failed unpack, 3=1+21

/*
if ($argc <= 7) {
  print("ERROR: Invalid number of arguments.\n");
  exit(1);
}
*/

$directory_name = $argv[1];

define('INVOKE_ACTIONS', FALSE);

$file_extensions = array(
  '.avi',
  '.mkv',
);

$remove_file_extensions = array(
  '.nfo',
  '.nzb',
  '.sfv',
  '.srr',
);

function GetNewFilename($filename) {
  $handle = curl_init('http://localhost:8084/service');
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($handle,
              CURLOPT_POSTFIELDS,
              'action=tv_rename&' .
                  'path=' . urlencode($filename));
  $response = curl_exec($handle);
  $data = json_decode($response);
  if (!$data->error)
    return $data->path;
  print 'ERROR: ' . $data->error;
  return NULL;
}

function Main($directory_name) {
  global $file_extensions, $remove_file_extensions;

  $actions = array();
  $directory_is_empty = TRUE;

  $it = new DirectoryIterator($directory_name);
  foreach ($it as $file) {
    if ($file->IsDot())
      continue;

    $extension = strtolower(substr($file->GetFilename(), -4));
    $pathname = $file->GetPathname();

    if (in_array($extension, $file_extensions)) {
      // If this is a movie file, send out a service request to Armadillo to
      // get the full episode title.
      $new_name = GetNewFilename($file->GetFilename());
      if (!$new_name)
        print 'No name for: ' . $file->GetFilename() . "\n";

      // Create new fully qualified path.
      $new_path = dirname(realpath($file->GetPath()));
      $new_pathname = $new_path . '/' . $new_name;

      // Create thunk.
      $actions[] = function() use ($pathname, $new_pathname) {
        print realpath($pathname) . ' -> ' . $new_pathname . "\n";
        if (INVOKE_ACTIONS)
          rename(realpath($pathname), $new_pathname);
      };
    } else if (in_array($extension, $remove_file_extensions) ||
               $file->GetFilename() == '.DS_Store') {
      // If these are files we know to be safe to delete, do so with a thunk.
      $actions[] = function() use ($pathname) {
        print 'REMOVE: ' . $pathname . "\n";
        if (INVOKE_ACTIONS)
          unlink(realpath($pathname));
      };
    } else {
      // Any other types of files prevent us from unlinking the directory.
      $directory_is_empty = FALSE;
    }
  }

  // If the directory is empty, thunk its removal.
  if ($directory_is_empty) {
    $pathname = realpath($directory_name);
    $actions[] = function() use ($pathname) {
      print 'REMOVE DIR: ' . $pathname . "\n";
      if (INVOKE_ACTIONS)
        rmdir($pathname);
    };
  }

  // Go through and execute all of the work that has been scheduled.
  foreach ($actions as $action)
    $action();
}

Main($directory_name);
