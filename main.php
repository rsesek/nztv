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

use \phalanx\tasks\CLIDispatcher as CLIDispatcher;
use \phalanx\tasks\CLIOutputHandler as CLIOutputHandler;
use \phalanx\tasks\TaskPump as TaskPump;

require './init.php';

require_once PHALANX_ROOT . '/base/functions.php';
require_once PHALANX_ROOT . '/tasks/task_pump.php';
require_once PHALANX_ROOT . '/tasks/cli_dispatcher.php';
require_once PHALANX_ROOT . '/tasks/cli_output_handler.php';

require './tasks/internal/error.php';
require './tasks/internal/message.php';

$dispatcher = new CLIDispatcher($argv);
$dispatcher->set_task_loader(function($name) {
  $name = str_replace('-', '_', $name);
  $path = "./tasks/{$name}.php";
  if (!file_exists($path)) {
    TaskPump::Pump()->RunTask(new ErrorTask('Could not load file for task ' . $name));
    return;
  }
  require_once $path;
  return '\nztv\\' . \phalanx\base\UnderscoreToCamelCase($name) . 'Task';
});

$output_handler = new CLIOutputHandler();
TaskPump::Pump()->set_output_handler($output_handler);

if (!isset($argv[1]))
  Fatal("Commands: add-show set-episode set-url remove-show update-records");

// Process the inital task.
$dispatcher->Start();

// Stop the pump now that all tasks have been run.
TaskPump::Pump()->StopPump();
