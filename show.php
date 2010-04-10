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

class Show extends \phalanx\data\Model
{
  // phalanx\base\Struct
  protected $fields = array(
    'show_id',  /*int,serial*/
    'name',  /*string*/
    'search_url',  /*string*/
    'last_check',  /*time_t*/
    'last_season',  /*int*/
    'last_episode',  /*int*/
  );

  // phalanx\data\Model
  protected $table = 'shows';
  protected $condition = 'show_id = :show_id';
  protected $primary_key = 'show_id';

  static public /*array[Show]*/ FetchAll()
  {
    $shows = array();
    $query = $database_->query("SELECT * FROM shows");
    while ($show = $shows->FetchArray()) {
      $obj = new Show();
      $obj->SetFrom($show);
      $shows[] = $obj;
    }
    return $shows;
  }
}
