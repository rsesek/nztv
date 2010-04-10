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

// This class keeps track of two things: (1) What we have downloaded in the
// past, with specific references to title names and NZB-site ID. (2) A list
// of shows to download, query parameters, and last-retrieved information.
class BookKeeper
{
  public /*bool*/ function ShouldDownloadEpisode(Episode $episode)
  {
    $show = $episode->show();
    return ($episode->season >= $show->last_season ||
        ($episode->season == $show->last_season &&
            $episode->episode > $show->last_episode));
  }

  public function RecordDownload(Episode $episode)
  {
    $episode->Insert();
    $show = $episode->show();

    // If this is the next episode, update the |last_episode|.
    if ($episode->season == $show->last_season &&
        $episode->episode-1 == $show->last_episode) {
      $episode->show()->last_episode = $episode->episode;
      $episode->show()->Update();
    }
  }
}
