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

class BumpTask extends \phalanx\tasks\Task
{
  static public function InputList()
  {
    return array(
      '_id',
    );
  }

  static public function OutputList()
  {
    return array();
  }

  public function Fire()
  {
    $show = Show::FetchByName($this->input->_id);
    if (!$show) {
      TaskPump::Pump()->QueueTask(new ErrorTask('Could not find show named "' . $this->input->_id . '"'));
      return;
    }

    $show->last_season++;
    $show->last_episode = 0;

    try {
      $show->Update();
    } catch (\phalanx\data\ModelException $e) {
      TaskPump::Pump()->QueueTask(new ErrorTask('An error occurred while updating the record.'));
      return;
    }
    TaskPump::Pump()->QueueTask(
        new MessageTask(
            'Updated ' . $show->name . ' to ' .
            $show->last_season . 'x' . $show->last_episode));
  }
}
