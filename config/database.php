<?php

date_default_timezone_set("Asia/Jakarta");

$host = "localhost";
$user = "root";
$password = "";
$database = "absensi_fonnte";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+07:00'");

?>