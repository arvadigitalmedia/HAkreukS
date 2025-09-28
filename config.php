<?php

$weburl = "http://localhost/forbisnisemasperak/";

$dbhost     = "localhost";

$dbname     = "bisnisepi";

$dbuser     = "root";

$dbpassword = ""; # Jangan gunakan karakter $

define('SECRET', "c5JuOdQl3xpPml5uZRwc4rW7uPfIBnX4");

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') { 

	header("Location:".$weburl);

}

?>