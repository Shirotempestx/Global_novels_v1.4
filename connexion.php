<?php
$host = "localhost";
$dbname = "global_novels";
$username = "root";
$password = "";
$dns = "mysql:host=$host;dbname=$dbname";
try {
    $conn = new PDO($dns,$username,$password);
    

}
catch (PDOException $e)
{
die("impossible de se connecter a la base de donnée $dvname:" . $e->getMessage());
}


?>