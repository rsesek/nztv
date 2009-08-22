#!/usr/bin/env php
<?php

$path = dirname(__FILE__);
chdir($path);
require './init.php';

$shows = $database_->query("SELECT * FROM shows");
while ($show = $shows->fetchObject())
{
	$rfp = CreateCURLHandler($show->search_url);
	$result = curl_exec($rfp);
	curl_close($rfp);
	
	$results = simplexml_load_string($result);
	foreach ($results->entry as $entry)
	{
		$nzb_url = $entry->link[1]['href'];
		list($season, $episode) = TokenizeTitle($entry->title);
		
		// Skip this episode if it's too old.
		if ($season < $show->last_season || ($season == $show->last_season && $episode <= $show->last_episode))
			continue;
		
		$other_eps = $database_->prepare("SELECT * FROM downloads WHERE show_id = ? AND season = ? AND episode = ?");
		$other_eps->execute(array($show->show_id, $season, $episode));
		// We've already downloaded this episode.
		if ($other_eps->fetchObject())
		{
			echo "Already downloaded {$entry->title}";
			continue;
		}
		
		preg_match('#/post/([0-9]+)/nzb#', $nzb_url, $matches);
		$nzb_id = $matches[1];
		
		$fp = fopen(config::$nzb_output_dir . '/' . $nzb_id . '.nzb', 'w');
		$nzb_fp = CreateCURLHandler($nzb_url, false);
		curl_setopt($nzb_fp, CURLOPT_FILE, $fp);
		if (!curl_exec($nzb_fp))
		{
			echo "Could not get {$entry->title} (#$nzb_id)";
			curl_close($nzb_fp);
			fclose($fp);
			continue;
		}
		curl_close($nzb_fp);
		fclose($fp);
		
		$stmt = $database_->prepare("
			INSERT INTO downloads
				(nzbid, show_id, title, season, episode, timestamp)
			VALUES
				(?, ?, ?, ?, ?, ?)
		");
		$stmt->execute(array($nzb_id, $show->show_id, $entry->title, $season, $episode, time()));
		echo "getting {$entry->title}";
	}
}

