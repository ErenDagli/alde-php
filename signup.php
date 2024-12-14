<?php
header("Access-Control-Allow-Origin: *"); // Tüm kaynaklardan erişime izin verir
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // İzin verilen HTTP metotlarını belirtir
header("Access-Control-Allow-Headers: Content-Type");
$servername = "localhost";
$username = "root";
$password = "321cba";
$dbname = "alde";

// MySQL bağlantısı
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// JSON verisini al
$data = json_decode(file_get_contents("php://input"));

$user = $data->username;
$email = $data->email;
$pass = password_hash($data->password, PASSWORD_DEFAULT); // Şifreyi hash'le

// Veritabanına kaydet
$sql = "INSERT INTO users (username, email, password) VALUES ('$user', '$email', '$pass')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["message" => "Signup successful", "status" => "success"]);
} else {
    echo json_encode(["message" => "Error: " . $conn->error, "status" => "error"]);
}

$conn->close();
?>
