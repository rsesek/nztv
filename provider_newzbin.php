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

class ProviderNewzbin implements Provider //,
{
  public /*array[Episode]*/ function SearchForShow(Show $show)
  {
    $rfp = curl_init($show->search_url);
    curl_setopt($rfp, CURLOPT_USERPWD, \config::$newzbin_user . ':' . \config::$newzbin_password);
    curl_setopt($rfp, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($rfp);
    curl_close($rfp);

    $episodes = array();

    $results = simplexml_load_string($result);
    foreach ($results->entry as $entry) {
      $nzb_url = $entry->link[1]['href'];
      preg_match('#/post/([0-9]+)/nzb#', $nzb_url, $matches);
      $nzb_id = $matches[1];

      $episode = new Episode($nzb_id);
      $episode->show_id = $show->show_id;
      $episode->title   = $entry->title;
      $this->TokenizeTitle($episode);
      $episodes[] = $episode;
    }
    return $episodes;
  }

  public function TokenizeTitle(Episode $episode)
  {
    preg_match('/([0-9]+)x([0-9]+)/', $episode->title, $matches);
    $episode->season  = intval($matches[1]);
    $episode->episode = intval($matches[2]);
  }

  // Throws DownloadException.
  public function DownloadEpisode(Episode $episode,
                                  /*string*/ $destination)
  {
    $fp = fopen($destination, 'w');
    $nzb_fp = curl_init('http://www.newzbin.com/api/dnzb/');
    curl_setopt($nzb_fp, CURLOPT_POST, true);
    curl_setopt($nzb_fp, CURLOPT_POSTFIELDS, 'username=' . \config::$newzbin_user . '&password=' . \config::$newzbin_password . '&reportid=' . $episode->nzbid);
    curl_setopt($nzb_fp, CURLOPT_FILE, $fp);
    if (!curl_exec($nzb_fp)) {
      curl_close($nzb_fp);
      fclose($fp);
      throw new DownloadException('Could not download ' . $episode->title);
    }
    curl_close($nzb_fp);
    fclose($fp);
  }
}
