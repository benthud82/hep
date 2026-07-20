<?php

//Connect to Live Server
$dbtype = "mysql";
$dbhost = "localhost"; // Host name 
$dbuser = "root"; // Mysql username 
$dbpass = ""; // Mysql password 
$dbname = "hep"; // Database name 
$conn1 = new PDO("{$dbtype}:host={$dbhost};dbname={$dbname};charset=utf8", $dbuser, $dbpass, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));