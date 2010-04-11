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

class ProviderNZBMatrix implements Provider //,
{
  public /*array[Episode]*/ function SearchForShow(Show $show)
  {
    $rfp = curl_init($show->search_url . $this->_AuthParams());
    curl_setopt($rfp, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($rfp);
    curl_close($rfp);

    $episodes = array();

    $results = simplexml_load_string($result);
    foreach ($results->channel->item as $entry) {
      $nzb_url = $entry->link;
      preg_match('/id=([0-9]+)/', $nzb_url, $matches);
      $nzb_id = $matches[1];

      $episode = new Episode(array('nzbid' => $nzb_id));
      $episode->show_id = $show->show_id;
      $episode->title   = $entry->title;
      $this->TokenizeTitle($episode);
      $episodes[] = $episode;
    }
    return $episodes;
  }

  public function TokenizeTitle(Episode $episode)
  {
    preg_match('/S([0-9]+)E([0-9]+)/', $episode->title, $matches);
    if (count($matches) < 3)
      return;
    $episode->season  = intval($matches[1]);
    $episode->episode = intval($matches[2]);
  }

  // Throws DownloadException.
  public function DownloadEpisode(Episode $episode,
                                  /*string*/ $destination)
  {
    $fp = fopen($destination, 'w');
    $nzb_fp = curl_init('http://nzbmatrix.com/api-nzb-download.php?id=' . $episode->nzbid . $this->_AuthParams());
    curl_setopt($nzb_fp, CURLOPT_POST, true);
    curl_setopt($nzb_fp, CURLOPT_FILE, $fp);
    if (!curl_exec($nzb_fp)) {
      curl_close($nzb_fp);
      fclose($fp);
      throw new DownloadException('Could not download ' . $episode->title);
    }
    curl_close($nzb_fp);
    fclose($fp);
  }

  protected /*string*/ function _AuthParams()
  {
    return '&username=' . \config::$nzbmatrix_user . '&apikey=' . \config::$nzbmatrix_api;
  }
}
