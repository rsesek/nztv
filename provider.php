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

interface Provider
{
  public /*array[Episode]*/ function SearchForShow(Show $show);
  public function TokenizeTitle(Episode $episode);
  public function RateLimitDownload(Episode $episode);

  // Throws DownloadException.
  public /*bool*/ function DownloadEpisode(Episode $episode,
                                           /*string*/ $destination);
}

class DownloadException extends \Exception
{}
