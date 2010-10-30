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
require_once './tasks/internal/download_episode.php';

class DownloadTask extends \phalanx\tasks\Task
{
  static public function InputList()
  {
    return array();
  }

  static public function OutputList()
  {
    return array();
  }

  public function Fire()
  {
    $keeper   = new BookKeeper();
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

        TaskPump::Pump()->QueueTask(new DownloadEpisodeTask($episode));
      }
    }
  }
}
