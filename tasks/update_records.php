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

class UpdateRecordsTask extends \phalanx\tasks\Task
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
    $shows = Show::FetchAll();
    foreach ($shows as $show) {
      $episode = $show->GetLatestEpisode();
      if (!$episode)
        continue;  // This show hasn't been downloaded yet.
      if ($episode->season > $show->last_season ||
          ($episode->season == $show->last_season &&
              $episode->episode > $show->last_episode)) {
        print("{$show->name} has {$show->last_season}x{$show->last_episode} as latest," .
              " but most recent download is {$episode->season}x{$episode->episode}. Update" .
              " [Y/n]? ");
        $fp = fopen('php://stdin', 'r');
        $c  = fgetc($fp);
        fclose($fp);
        if (strtolower($c) == 'y') {
          $show->last_season  = $episode->season;
          $show->last_episode = $episode->episode;
          $show->Update();
          TaskPump::Pump()->QueueTask(new MessageTask(
              "Updated {$show->name} to {$show->last_season}x{$show->last_episode}"));
        }
      }
    }
  }
}
