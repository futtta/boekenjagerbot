<?php
/*
* haal alle unique gemeenteid's uit de subscribers tabel (-> A)
* voor elke gemeenteid in (A):
* - haal gemeente-naam op uit gemeente tabel (-> A2)
* - haal alle susbscribers uit de subscribers tabel (-> B)
* - bevraag de boekenzoekers API (gemeente=A2&output=json&timeFrom=<timestamp laatste check>) (-> C)
* - voor elk resultaat (C) stuur een bericht naar elke subscriber (B) 
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

$memcache = new Memcache();
$memcache->connect("localhost", 11211);

$cacheDriver = new \Doctrine\Common\Cache\MemcacheCache();
$cacheDriver->setMemcache($memcache);

//Init Botman
DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookDriver::class);
$botman = BotManFactory::create($botmanConfig, new DoctrineCache($cacheDriver));


$data = $database->query("SELECT distinct `gemeenteid` FROM `subscribers`;")->fetchAll();
foreach($data as $row) {
    $gemeentes = $database->select("gemeentes", "name", array("id" => $row[0]));
    foreach($gemeentes as $gemeente){
        $apiData = getDataFROMAPI($gemeente[0]);

        $users = $database->select("subscribers", "subscriberid", array("gemeenteid" => $row[0]));
        foreach($users as $user){
            if(is_array($apiData)) {
                foreach ($apiData as $apiRow) {
                    echo "Er is een boek gedropt:" . $apiRow["fbURL"];
                    $botman->say("Er is een boek gedropt:" . $apiRow["fbURL"], $user[0]);
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
function getDataFROMAPI($gemeente)
{
    return json_decode(fetchUrl("https://boekenzoekers.thunderbug.be/?output=json&gemeente=".$gemeente."&timeFrom=1507468666"), true);
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