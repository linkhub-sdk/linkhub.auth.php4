<?php

require_once 'linkhub.auth.php';

$ServiceID = 'POPBILL_TEST';
$LinkID = 'TESTER';
$SecretKey = 'yLyQfkJswMDmhh2suYsb4UMH1BAljaE4Vni3vBygvOw=';

$AccessID = '1231212312';
$Linkhub = new Linkhub($LinkID,$SecretKey);

$Token = $Linkhub->getToken($ServiceID,$AccessID, array('member','110'));

if($Token->isException) {
	echo $Token->__toString();
	exit();
}
else {
	echo 'Token is issued : '.substr($Token->session_token,0,20).' ...';
	echo chr(10);
}

$balance = $Linkhub->getBalance($Token->session_token,$ServiceID);
if($balance->isException) {
	echo $balance->__toString();
	exit();
}
else {
	echo 'remainPoint is '. $balance;
	echo chr(10);
}

$balance = $Linkhub->getPartnerBalance($Token->session_token,$ServiceID);
if($balance->isException) {
	echo $balance->__toString();
	exit();
}
else {
	echo 'remainPartnerPoint is '. $balance;
	echo chr(10);
}

?>
