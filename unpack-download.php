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


if ($argc == 8 && $argv[7] != 0) {
  print("ERROR: Post processing error.\n");
  exit(1);
}

$directory_name = $argv[1];

define('INVOKE_ACTIONS', TRUE);

$file_extensions = array(
  '.avi',
  '.mkv',
  '.ts',
);

$remove_file_extensions = array(
  '.nfo',
  '.nzb',
  '.sfv',
  '.srr',
  '.txt',
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

// This function will try to match |$title| to a directory in |$root|.
function FuzzyMatchTitle($root, $title) {
  $tokenizer = function($s) {
    return explode(' ', strtoupper($s));
  };
  $word_filter = function($e) {
    return !in_array($e, array(
      'A',
      'AN',
      'THE',
    ));
  };

  // Iterate through each of the directories in |$root| and try to match it to
  // the source.
  $source_words = $tokenizer($title);
  $it = new DirectoryIterator($root);
  foreach ($it as $file) {
    if (!$file->IsDir() || $file->IsDot())
      continue;

    $dirname_words = $tokenizer($file->GetFilename());

    // Compute the difference. In order to a meaningful match, do a symmetric
    // set difference. That is, all the objects that are either in just the
    // source or just the target. Words that are common between the two are
    // excluded.
    $delta1 = array_diff($source_words, $dirname_words);
    $delta2 = array_diff($dirname_words, $source_words);
    $delta = array_merge($delta1, $delta2);
    // After computing the delta, filter out any words in a title that are
    // generally irrelevant.
    $delta = array_filter($delta, $word_filter);

    // If the delta set is empty, then the titles match.
    if (count($delta) == 0) {
      return $file->GetPathname();
    }
  }

  // No match was found.
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

    $filename = $file->GetFilename();
    $extension = strtolower(substr($filename, strrpos($filename, '.')));
    $pathname = $file->GetPathname();

    if (in_array($extension, $file_extensions)) {
      // If this is a movie file, send out a service request to Armadillo to
      // get the full episode title.
      $new_name = GetNewFilename($filename);
      if (!$new_name)
        print 'No name for: ' . $filename . "\n";

      // Create new fully qualified path.
      $new_path = dirname(realpath($file->GetPath()));

      // If set up to sort into show and season folders, move this there.
      if (isset($_SERVER['NZTV_UNPACK_SORTED'])) {
        // Explode name of |Show - SxE - Title|.
        $showname_parts = explode(' - ', $new_name);
        if (count($showname_parts) >= 2) {
          $show_season = explode('x', $showname_parts[1]);
          $title_folder = FuzzyMatchTitle($_SERVER['NZTV_UNPACK_SORTED'],
                                          $showname_parts[0]);
          // If the show uses episodic naming and a title folder was found, then
          // schedule the move into the proper season folder.
          if (count($show_season) == 2 && $title_folder) {
            $test_new_path = $title_folder . '/' .
                             sprintf('Season %d', $show_season[0]);
            // Final sanity check that the location of the move has been found.
            if (file_exists($test_new_path) && is_dir($test_new_path)) {
              $new_path = $test_new_path;
            }
          }
        }
      }

      // Create the full name.
      $new_pathname = $new_path . '/' . $new_name;

      // Create thunk.
      $actions[] = function() use ($pathname, $new_pathname) {
        print realpath($pathname) . ' -> ' . $new_pathname . "\n";
        if (INVOKE_ACTIONS)
          rename(realpath($pathname), $new_pathname);
      };
    } else if (in_array($extension, $remove_file_extensions) ||
               $filename == '.DS_Store') {
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
