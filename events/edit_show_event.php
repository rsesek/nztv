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

use \phalanx\events\EventPump as EventPump;

require_once PHALANX_ROOT . '/events/event.php';

class EditShowEvent extends \phalanx\events\Event
{
  static public function InputList()
  {
    return array(
      'name',
      'episode',
      'feed_url'
    );
  }

  static public function OutputList()
  {
    return array();
  }

  public function Fire()
  {
    $show = Show::FetchByName($this->input->name);
    if (!$show) {
      EventPump::Pump()->PostEvent(new ErrorEvent('Could not find show named ' . $this->input->name));
      return;
    }

    if ($this->input->episode) {
      @list($season, $episode) = explode('x', $this->input->episode);
      if (!$season || !$episode) {
        EventPump::Pump()->PostEvent(new ErrorEvent('Episode format is invalid (SxE).'));
        return;
      }
      $show->last_season  = $season;
      $show->last_episode = $episode;
    }

    if ($this->input->feed_url) {
      $show->search_url = $this->input->feed_url;
    }

    try {
      $show->Update();
    } catch (\phalanx\data\ModelException $e) {
      EventPump::Pump()->PostEvent(new ErrorEvent('An error occurred while adding the show.'));
      return;
    }
    EventPump::Pump()->PostEvent(new MessageEvent('Updated ' . $show->name));
  }
}
