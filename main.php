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

// This is the heart of the command-line interface.
// TODO(rsesek): Refactor this mess.

require './init.php';

if (!isset($argv[0]))
  Fatal("Commands: add-show set-episode set-url remove-show");

switch ($argv[0])
{
  // Add show takes the format of <name> <season>x<episode>
  case 'add-show':
    if ($argc != 4)
      Fatal('add-show: [name] [season]x[episode] [search url]');
    $show = new Show();
    $show->name       = $argv[1];
    $show->search_url = $argv[3];

    $epnum = explode('x', $argv[2]);
    if (count($epnum) != 2)
      Fatal("Invalid episode number $argv[2]");
    $show->last_season  = $epnum[0];
    $show->last_episode = $epnum[1];

    $show->Insert();
  break;

  case 'set-episode':
    if ($argc != 3)
      Fatal('set-episode: [name] [season]x[episode]');
    $show = Show::FetchByName($argv[1]);
    if (!$show)
      Fatal("Bad show name '$argv[1]");
    $epnum = explode('x', $argv[2]);
    if (count($epnum) != 2)
      Fatal("Invalid episode number $argv[2]");
    $show->last_season  = $epnum[0];
    $show->last_episode = $epnum[1];
    $show->Update();
  break;

  case 'set-url':
    if ($argc != 3)
      Fatal('set-url: [name] [search url]');
    $show = Show::FetchByName($argv[1]);
    if (!$show)
      Fatal("Bad show name '$argv[1]");
    $show->search_url = $argv[2];
    $show->Update();
  break;

  case 'remove-show':
    if ($argc != 2)
      Fatal('remove-show: [name]');
    $show = Show::FetchByName($argv[1]);
    if (!$show)
      Fatal("Bad show name '$argv[1]");
    $show->Delete();
  break;

  case 'update-records':
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
        }
      }
    }
  break;
}
