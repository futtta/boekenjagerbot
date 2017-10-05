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

//Init Database
$database = new \Medoo\Medoo(
    array(
        "database_type" => "mysql",
        "database_name" => $dbName,
        "server" => $dbHost,
        "username" => $dbUsername,
        "password" => $dbPassword
    )
);

//Init Botman
$botman = BotManFactory::create($botmanConfig);
$botman->verifyServices($botmanVerify);

/*$botman->hears("call me {name}", function ($bot, $name) {
    $bot->reply("Your name is: ".$name);
});*/

$botman->hears('Hello', function($bot) {
    $user = $bot->getUser();
    $bot->reply('Hello '.$user->getFirstName().' '.$user->getLastName());
    $bot->reply('Your username is: '.$user->getUsername());
    $bot->reply('Your ID is: '.$user->getId());
});

$botman->hears("abonneer op {naam}", function ($bot, $naam) {
    global $database;

    //In table gemeentes zoeken op row
    //Indien meer dan 1 gevonden, eerste 5 mogelijkheden geven
    //Indien geen gevonden error geven
    $bot->reply("U bent geabonneerd op gemeente ".$naam);
});

$botman->listen();