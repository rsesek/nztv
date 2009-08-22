#!/usr/bin/env php
<?php

$path = dirname(__FILE__);
chdir($path);
require './init.php';

$shows = $database_->query("SELECT * FROM shows");
while ($show = $shows->fetchObject())
{
	$params = $database_->prepare("SELECT * FROM search_params WHERE show_id = ?");
	$params->execute(array($show->show_id));
	while ($param = $params->fetchObject())
	{
		print_r($param);
	}
}

