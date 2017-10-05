<?php
/*
* receives incoming chat requests
* main funciontality: abonneer op gemeente x en de-abonneer op gemeente y
* dit wordt weggeschreven in de subscribers tabel met subscriberid (= facebook id) en gemeente id (= gemeente.id)
*/

use Mpociot\BotMan\BotManFactory;

$botmanConfig = array();

require_once ("inc/config.php");
require __DIR__ . "/vendor/autoload.php";

//Init Database
$database = new Medoo\Medoo(
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

    $user = $bot->getUser();
    $amount = $database->count("gemeentes", "*", array("name" => $naam));

    if($amount == 0) {
        $bot->reply("Er zijn geen gemeentes met deze naam gevonden");
    } elseif($amount == 1) {
        $data = $database->get("gemeentes", "*",  array("name" => $naam));

        if($database->count("gemeentes", "*", array("subscriberid" => $user->getId(), "gemeenteid" => $data["id"])) == 0) {
            $database->insert("gemeentes", array(
                "subscriberid" => $user->getId(),
                "gemeenteid" => $data["id"]
            ));

            $bot->reply("U bent geabonneerd op gemeente ".$data["name"]);
        } else {
            $bot->reply("U bent al geabonneerd op ".$data["name"]);
        }

    } else {
        $reply = "Kies uit deze gemeentes: ";

        $data = $database->select("gemeentes", "*",  array("name[~]" => $naam));

        foreach($data as $key => $row){
            if($key > 0){
                $reply .= ", ";
            }

            $reply .= $row["name"];

            if($key > 5){
                $reply .= "...";
                break;
            }
        }

        $bot->reply($reply);
    }

    //In table gemeentes zoeken op row
    //Indien meer dan 1 gevonden, eerste 5 mogelijkheden geven
    //Indien geen gevonden error geven
});

$botman->listen();