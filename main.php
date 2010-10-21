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

use \phalanx\events\CLIDispatcher as CLIDispatcher;
use \phalanx\events\CLIOutputHandler as CLIOutputHandler;
use \phalanx\events\EventPump as EventPump;

require './init.php';

require_once PHALANX_ROOT . '/base/functions.php';
require_once PHALANX_ROOT . '/events/event_pump.php';
require_once PHALANX_ROOT . '/events/cli_dispatcher.php';
require_once PHALANX_ROOT . '/events/cli_output_handler.php';

require './events/error_event.php';
require './events/message_event.php';

$dispatcher = new CLIDispatcher($argv);
$dispatcher->set_event_loader(function($name) {
  $name = str_replace('-', '_', $name);
  $path = "./events/{$name}_event.php";
  if (!file_exists($path)) {
    EventPump::Pump()->RaiseEvent(new ErrorEvent('Could not load file for event ' . $name));
    return;
  }
  require_once $path;
  return '\nztv\\' . \phalanx\base\UnderscoreToCamelCase($name) . 'Event';
});

$output_handler = new CLIOutputHandler();
EventPump::Pump()->set_output_handler($output_handler);

if (!isset($argv[1]))
  Fatal("Commands: add-show set-episode set-url remove-show update-records");

// Process the inital event.
$dispatcher->Start();

// Stop the pump now that all events have been run.
EventPump::Pump()->StopPump();
exit;

switch ($argv[0])
{
  case 'remove-show':
    if ($argc != 2)
      Fatal('remove-show: [name]');
    $show = Show::FetchByName($argv[1]);
    if (!$show)
      Fatal("Bad show name '$argv[1]'");
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

  case 'bump':
    if ($argc != 2)
      Fatal("bump: [name]");
    $show = Show::FetchByName($argv[1]);
    if (!$show)
      Fatal("Bad show name '$argv[1]'");
    $show->last_season++;
    $show->last_episode = 0;
    $show->Update();
  break;
}
