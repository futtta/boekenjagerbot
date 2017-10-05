<?php
/*
* receives incoming chat requests
* main funciontality: abonneer op gemeente x en de-abonneer op gemeente y
* dit wordt weggeschreven in de subscribers tabel met subscriberid (= facebook id) en gemeente id (= gemeente.id)
*/

use Mpociot\BotMan\BotManFactory;

$botmanConfig = array();

require_once ("inc/config.php");
require_once ("vendor/autoload.php");

// create an instance
$botman = BotManFactory::create($botmanConfig);
$botman->verifyServices($botmanVerify);

/*$botman->hears("call me {name}", function ($bot, $name) {
    $bot->reply("Your name is: ".$name);
});*/

$botman->hears("abonneer op {naam}", function ($bot, $naam) {
   $bot->reply("U bent geabonneerd op gemeente ".$naam);
});

$botman->listen();