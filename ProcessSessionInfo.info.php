<?php namespace ProcessWire;

$info = array(
	'title' => 'Session Info',
	'summary' => 'Lists information about active sessions in a similar way to SessionHandlerDB, but for file-based sessions.',
	'version' => '0.2.1',
	'author' => 'Robin Sallis',
	'href' => 'https://github.com/Toutouwai/ProcessSessionInfo',
	'icon' => 'tachometer',
	'requires' => 'ProcessWire>=3.0.0, PHP>=7.0.0',
	'installs' => 'SessionExtras',
	'page' => array(
		'name' => 'session-info',
		'title' => 'Sessions',
		'parent' => 'access',
	),
);
