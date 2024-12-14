<?php

$servername = "localhost";
$username = "root";
$password = "321cba";
$dbname = "alde";

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
echo "Başarıyla bağlandı";
?>