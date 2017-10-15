<?php

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\DoctrineCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Facebook\FacebookDriver;

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

//Update Lastcheck Time
$time = $database->get("gemeentes", "name", array("zipcode" => 9999));
$database->update("gemeentes", array("name" => time()), array("zipcode" => 9999));


$data = $database->query("SELECT distinct `gemeenteid` FROM `subscribers`;")->fetchAll();
foreach($data as $row) {
    $gemeentes = $database->select("gemeentes", "name", array("id" => $row[0]));
    foreach($gemeentes as $gemeente){
        $apiData = getDataFROMAPI($gemeente,$time);

        $users = $database->select("subscribers", "subscriberid", array("gemeenteid" => $row[0]));
        foreach($users as $user){
            if(is_array($apiData)) {
                foreach ($apiData as $apiRow) {
                    if($debug) {
                        echo "Er is een boek gedropt: " . $apiRow["fbURL"] . "(".$user.")<br />";
                    }
                    $botman->say("Er is een boek gedropt: " . $apiRow["fbURL"], $user, FacebookDriver::class);
                    break;
                }
            }
        }
    }
}

/**
 * Get Data from the API
 * @param $gemeente string Gemeentenaam
 * @return array Data from API
 */
function getDataFROMAPI($gemeente,$time)
{
    return json_decode(fetchUrl("https://boekenzoekers.thunderbug.be/?output=json&gemeente=".$gemeente."&timeFrom=".$time), true);
}

/**
 * Get the data from the URL using CURL
 * @param $url String url
 * @return mixed Data
 */
function fetchUrl($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;
}