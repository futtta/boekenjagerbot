<?php
/* Config voorbeeld */
//MySQL Database

//extra debug output in cronjob
$debug = true;

//Database host
$dbHost = "localhost";
//Database naam
$dbName = "boekenzoekers";
//Database username
$dbUsername = "boekenzoekers";
//Database password
$dbPassword = "databasepassword";

//BotManConfig
$botmanConfig = array(
    "facebook" => array(
        "token" => "YOUR-FACEBOOK-PAGE-TOKEN-HERE",
        "app_secret" => "YOUR-FACEBOOK-APP-SECRET-HERE",
        "verification" => "VERIFYTOKENFROMFACEBOOK"
    )
);

//Caching server type
//redis of memcache of none
$cacheType = "memcache";

$memcacheConfig = array("host" => "localhost", "port" => 11211);
$redisConfig = array("host" => "localhost", "port" => 6379);