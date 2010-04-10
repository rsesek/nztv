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

// This file is meant to be used in a cronjob. It does all the work of pulling
// search results down and figuring out what to download.

$path = dirname(__FILE__);
chdir($path);
require './init.php';

$keeper = new BookKeeper();
$provider = GetProvider();

$shows = Show::FetchAll();
foreach ($shows as $show) {
  LogMessage("Beginning search for {$show->name}");

  $results = $provider->SearchForShow($show);
  foreach ($results as $episode) {
    // Skip this episode if it's too old.
    if (!$keeper->ShouldDownloadEpisode($episode)) {
      LogMessage("Skipping #{$episode->nzbid} '{$episode->title}' because it is too old");
      continue;
    }

    // We've already downloaded this episode.
    if ($episode->IsAlreadyDownloaded()) {
      LogMessage("Skipping #{$episode->nzbid} '{$episode->title}' because it has been downloaded previously");
      continue;
    }

    $file_name = config::$nzb_output_dir . '/' . $episode->nzbid . '_' . $episode->title . '.nzb';
    try {
      $provider->DownloadEpisode($episode, $file_name);
    } catch (DownloadException $e) {
      LogMessage("Could not get #{$episode->nzbid} '{$episode->title}'", LOG_ERR);
      continue;
    }

    // NZB files are never less than 1k, so it's probably a dud. We'll try
    // on a different execution run.
    if (filesize($file_name) < 1000) {
      LogMessage("Failed to download #{$episode->nzbid} '{$episode->title}'. Please run again.", LOG_WRN);
      continue;
    }

    $keeper->RecordDownload($episode);
    LogMessage("Downloaded #{$episode->nzbid} '{$episode->title}'");
  }
}
