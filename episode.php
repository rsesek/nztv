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

require_once PHALANX_ROOT . '/data/model.php';

class Episode extends \phalanx\data\Model
{
  // phalanx\base\Struct
  protected $fields = array(
    'nzbid',  /*int*/
    'show_id',  /*int*/
    'title',  /*string*/
    'season',  /*int*/
    'episode',  /*int*/
    'timestamp',  /*time_t*/
  );

  // phalanx\data\Model
  protected $table = 'downloads';
  protected $condition = 'nzbid = :nzbid';
  protected $primary_key = 'nzbid';

  protected /*Show*/ $show = NULL;

  public /*Show*/ function show()
  {
    if (!$this->show) {
      $this->show = new Show($this->show_id);
      $this->show->FetchInto();
    }
    return $this->show;
  }

  public /*bool*/ function IsAlreadyDownloaded()
  {
    $ep = new Episode();
    $ep->set_condition('show_id = :show_id AND season = :season AND episode = :episode');
    $ep->show_id = $this->show()->show_id;
    $ep->season  = $this->season;
    $ep->episode = $this->episode;

    try {
      $result = $ep->Fetch();
      return ($result != NULL);
    } catch (\phalanx\data\ModelException $e) {
      return FALSE;
    }
  }
}
