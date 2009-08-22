#!/usr/bin/env php
<?php

$path = dirname(__FILE__);
chdir($path);
require './init.php';

$shows = $database_->query("SELECT * FROM shows");
while ($show = $shows->fetchObject())
{
	LogMessage("Beginning search for {$show->name}");
	
	$rfp = CreateCURLHandler($show->search_url);
	$result = curl_exec($rfp);
	curl_close($rfp);
	
	$results = simplexml_load_string($result);
	foreach ($results->entry as $entry)
	{
		$nzb_url = $entry->link[1]['href'];
		list($season, $episode) = TokenizeTitle($entry->title);
		
		preg_match('#/post/([0-9]+)/nzb#', $nzb_url, $matches);
		$nzb_id = $matches[1];
		
		// Skip this episode if it's too old.
		if ($season < $show->last_season || ($season == $show->last_season && $episode <= $show->last_episode))
		{
			LogMessage("Skipping #$nzb_id '{$entry->title}' because it is too old");
			continue;
		}
		
		$other_eps = $database_->prepare("SELECT * FROM downloads WHERE show_id = ? AND season = ? AND episode = ?");
		$other_eps->execute(array($show->show_id, $season, $episode));
		// We've already downloaded this episode.
		if ($other_eps->fetchObject())
		{
			LogMessage("Skipping #$nzb_id '{$entry->title}' because it has been downloaded previously");
			continue;
		}
		
		
		$fp = fopen(config::$nzb_output_dir . '/' . $nzb_id . '.nzb', 'w');
		$nzb_fp = curl_init('http://www.newzbin.com/api/dnzb/');
		curl_setopt($nzb_fp, CURLOPT_POST, true);
		curl_setopt($nzb_fp, CURLOPT_POSTFIELDS, 'username=' . config::$newzbin_user . '&password=' . config::$newzbin_password . '&reportid=' . $nzb_id);
		curl_setopt($nzb_fp, CURLOPT_FILE, $fp);
		if (!curl_exec($nzb_fp))
		{
			LogMessage("Could not get #$nzb_id '{$entry->title}'", LOG_ERR);
			curl_close($nzb_fp);
			fclose($fp);
			continue;
		}
		curl_close($nzb_fp);
		fclose($fp);
		
		// Record the download.
		$stmt = $database_->prepare("
			INSERT INTO downloads
				(nzbid, show_id, title, season, episode, timestamp)
			VALUES
				(?, ?, ?, ?, ?, ?)
		");
		$stmt->execute(array($nzb_id, $show->show_id, $entry->title, $season, $episode, time()));
		LogMessage("Downloaded #$nzb_id '{$entry->title}'");
		
		// If this is the next episode, update the |last_episode|.
		if ($season == $show->last_season && $episode-1 == $show->last_episode)
		{
			$stmt = $database_->prepare("UPDATE shows SET last_episode = ? WHERE show_id = ?");
			$stmt->execute(array($episode, $show->show_id));
		}
	}
}

