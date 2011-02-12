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

use \phalanx\tasks\TaskPump as TaskPump;

require_once PHALANX_ROOT . '/tasks/task.php';

class DownloadEpisodeTask extends \phalanx\tasks\Task
{
  static public function InputList()
  {
    return array();
  }

  static public function OutputList()
  {
    return array();
  }

  protected $episode = NULL;

  public function __construct(Episode $episode)
  {
    $this->episode = $episode;
  }

  public function Fire()
  {
    $keeper   = new BookKeeper();
    $provider = GetProvider();
    $episode  = $this->episode;

    // We've already downloaded this episode.
    if ($episode->IsAlreadyDownloaded()) {
      LogMessage("Skipping #{$episode->nzbid} '{$episode->title}' because it has been downloaded previously");
      return;
    }

    $title     = SafeFileName($episode->title);
    $basename  = $title . '_' . $episode->nzbid . '.nzb';
    $basename  = \config::DownloadFileCallback($basename);
    $file_name = \config::$nzb_output_dir . '/' . $basename;
    if ($provider instanceof ProviderNZBMatrix)
      $file_name .= '.gz';  // NZBMatrix returns gZIP files rather than raw NZBs.
    try {
      $provider->DownloadEpisode($episode, $file_name);
    } catch (DownloadException $e) {
      LogMessage("Could not get #{$episode->nzbid} '{$episode->title}'", LOG_ERR);
      $this->Cancel();
      return;
    }

    // NZB files are never less than 1k, so it's probably a dud. We'll try
    // on a different execution run.
    if (filesize($file_name) < 1000) {
      LogMessage("Failed to download #{$episode->nzbid} '{$episode->title}'. Please run again.", LOG_WRN);
      unlink($file_name);
      $this->Cancel();
      return;
    }

    $keeper->RecordDownload($episode);
    LogMessage("Downloaded #{$episode->nzbid} '{$episode->title}'");

    $provider->RateLimitDownload($episode);
  }
}
