<?php
# Diva
define('DIVA_RESTORED',		0);
define('DIVA_RESTORE',		1);
define('DIVA_RESTORING',	2);
define('DIVA_ARCHIVED',		3);
define('DIVA_ARCHIVE',		4);
define('DIVA_ARCHIVING',	5);
define('DIVA_ERROR',		6);
define('DIVA_RESTORE_PATH', 'z:/archive/');

$state_code_map = array(
	'archived' => DIVA_ARCHIVED,
	'restored' => DIVA_RESTORED,
	'error' => DIVA_ERROR
);

$action_name_map = array(
	DIVA_ARCHIVE => 'archive',
	DIVA_RESTORE => 'restore'
);

$update_state_map = array(
	DIVA_ARCHIVE => DIVA_ARCHIVING,
	DIVA_RESTORE => DIVA_RESTORING
);
?>