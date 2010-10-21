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

class AddShowEvent extends \phalanx\events\Event
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

  public function WillFire()
  {
    if ($this->input->Count() != 3) {
      EventPump::Pump()->RaiseEvent(new ErrorEvent('add-show: [name] [season]x[episode] [url]'));
    }
  }

  public function Fire()
  {
    if (!$this->input->name) {
      EventPump::Pump()->PostEvent(new ErrorEvent('Name is required.'));
      return;
    }
    if (!$this->input->episode) {
      EventPump::Pump()->PostEvent(new ErrorEvent('Episode (SxE) is required.'));
      return;
    }
    if (!$this->input->feed_url) {
      EventPump::Pump()->PostEvent(new ErrorEvent('Feed URL is required.'));
      return;
    }

    @list($season, $episode) = explode('x', $this->input->episode);
    if (!$season || !$episode) {
      EventPump::Pump()->PostEvent(new ErrorEvent('Episode format is invalid (SxE).'));
      return;
    }

    $show = new Show();
    $show->name         = $this->input->name;
    $show->search_url   = $this->input->feed_url;
    $show->last_season  = $season;
    $show->last_episode = $episode;
    try {
      $show->Insert();
    } catch (\phalanx\data\ModelException $e) {
      EventPump::Pump()->PostEvent(new ErrorEvent('An error occurred while adding the show.'));
      return;
    }
    $str = 'Added ' . $show->name . ' at ' . $show->last_season . 'x' . $show->last_episode;
    EventPump::Pump()->PostEvent(new MessageEvent($str));
  }
}
