<?php
date_default_timezone_set('America/Chicago');

require_once 'eligius.class.php';

//set up the username which is a mining address, i just picked a random pool account
$username = '1FxkyjDb5CBMmYevvvCEfuNAVdHuKSJspi';

//construct new eligius with mining address as username
$eligius = new eligius($username);

//--basic api requests
	$data = $eligius->get_user_payout();
	print_r($data);

	$data = $eligius->get_user_hashrate();
	print_r($data);

	$data = $eligius->get_blocks(5);
	print_r($data);

	$data = $eligius->get_user_accepted();
	print_r($data);

	$data = $eligius->get_pool_hashrate();
	print_r($data);
//==basic api requests

//see other functions in eligius.class.php
?>
