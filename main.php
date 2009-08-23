<?php

namespace nztv;

require './init.php';

switch ($argv[0])
{
	// Add show takes the format of <name> <season>x<episode>
	case 'add-show':
		if ($argc != 4)
			Fatal('add-show: [name] [season]x[episode] [search url]');
		$params[0] = $argv[1];
		$epnum = explode('x', $argv[2]);
		if (count($epnum) != 2)
			Fatal("Invalid episode number $argv[2]");
		$params[1] = $epnum[0];
		$params[2] = $epnum[1];
		$params[3] = $argv[3];
		$stmt = $database_->prepare("INSERT INTO shows (name, last_season, last_episode, search_url) VALUES (?, ?, ?, ?)");
		$stmt->execute($params);
	break;
	
	case 'set-episode':
		if ($argc != 3)
			Fatal('set-episode: [name] [season]x[episode]');
		$id = GetShowFromName($argv[1]);
		if (!$id)
			Fatal("Bad show name '$argv[1]");
		$epnum = explode('x', $argv[2]);
		if (count($epnum) != 2)
			Fatal("Invalid episode number $argv[2]");
		$params[0] = $epnum[0];
		$params[1] = $epnum[1];
		$params[2] = $id;
		$stmt = $database_->prepare("UPDATE shows SET last_season = ?, last_episode = ? WHERE show_id = ?");
		$stmt->execute($params);
	break;
	
	case 'set-url':
		if ($argc != 3)
			Fatal('set-url: [name] [search url]');
		$id = GetShowFromName($argv[1]);
		if (!$id)
			Fatal("Bad show name '$argv[1]");
		$stmt = $database_->prepare("UPDATE shows SET search_url = ? WHERE show_id = ?");
		$stmt->execute(array($argv[2], $id));
	break;
	
	case 'remove-show':
		if ($argc != 2)
			Fatal('remove-show: [name]');
		$id = GetShowFromName($argv[1]);
		if (!$id)
			Fatal("Bad show name '$argv[1]");
		$stmt = $database_->prepare("DELETE FROM shows WHERE show_id = ?");
		$stmt->execute($id);
	break;
}

