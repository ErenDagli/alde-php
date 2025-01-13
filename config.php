<?php
$servername = "localhost"; // Genellikle localhost, ama GoDaddy sunucusunun adresi olabilir.
$username = "aldealidemirhan";
$password = "1565478965aAalde";
$dbname = "alde";

// Veritabanı bağlantısını oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
echo "Bağlantı başarılı!";
?>