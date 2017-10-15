<?php
/*
* receives incoming chat requests
* main funciontality: abonneer op gemeente x en de-abonneer op gemeente y
* dit wordt weggeschreven in de subscribers tabel met subscriberid (= facebook id) en gemeente id (= gemeente.id)
*/


use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\DoctrineCache;
use BotMan\BotMan\Drivers\DriverManager;

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

//Init Caching
switch ($cacheType) {
    case "memcache":
        $memcache = new Memcache();
        $memcache->connect($memcacheConfig["host"], $memcacheConfig["port"]);

        $cacheDriver = new \Doctrine\Common\Cache\MemcacheCache();
        $cacheDriver->setMemcache($memcache);
        break;

    case "redis":
        $redis = new Redis();
        $redis->connect($redisConfig["host"], $redisConfig["port"]);

        $cacheDriver = new \Doctrine\Common\Cache\RedisCache();
        $cacheDriver->setRedis($redis);
        break;

    case "none":
        $cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
        break;
}

//Init Botman
DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookDriver::class);
$botman = BotManFactory::create($botmanConfig, new DoctrineCache($cacheDriver));

$botman->hears("abonneer op {naam}", function ($bot, $naam) {
    global $database;
    $amount = $database->count("gemeentes", "*", array("name" => $naam));

    if($amount == 0) {
        $bot->reply("Er zijn geen gemeentes met deze naam gevonden");
    } elseif($amount == 1) {
        $data = $database->get("gemeentes", "*",  array("name" => $naam));

        if($database->count("subscribers", "*", array("subscriberid" => $bot->getUser()->getId(), "gemeenteid" => $data["id"])) == 0) {
            $database->insert("subscribers", array(
                "subscriberid" => $bot->getUser()->getId(),
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
});

$botman->hears("de-abonneer op {naam}", function ($bot, $naam) {
    global $database;

    $amount = $database->count("gemeentes", "*", array("name" => $naam));

    if($amount == 0) {
        $bot->reply("Er zijn geen gemeentes met deze naam gevonden");
    } elseif($amount == 1) {
        $data = $database->get("gemeentes", "*",  array("name" => $naam));

        if($database->count("subscribers", "*", array("subscriberid" =>$bot->getUser()->getId(), "gemeenteid" => $data["id"])) == 1) {
            $database->delete("subscribers", array("subscriberid" => $bot->getUser()->getId(), "gemeenteid" => $data["id"]));

            $bot->reply("U bent gedeabonneerd op ".$data["name"]);
        } else {
            $bot->reply("U bent niet geabonneerd op ".$data["name"]);
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
});

$botman->fallback(function($bot){
    global $database,$botman;

    $user = $bot->getUser();

    $bot->reply("Hallo ".$user->getFirstName()." ".$user->getLastName());
    $bot->reply("Welkom bij de boekenjagers, hier kunt u uzelf aboneren op boeken in jouw regio");
    $bot->reply("Om dit te doen stuurt u dit naar mij: \"abonneer op <gemeentenaam>\" ");
    $bot->reply("Of als u geen zin meer heeft in boekjes \"de-abonneer op <gemeentenaam>\"");
});

$botman->listen();