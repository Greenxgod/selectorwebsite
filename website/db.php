<?php
$mysqli = new mysqli('localhost', 'root', '', 'crm_system');
if ($mysqli->connect_error) {
    die("Ошибка подключения к БД: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>