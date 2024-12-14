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
$pass = $data->password;

// Kullanıcı adıyla kullanıcıyı bul
$sql = "SELECT * FROM users WHERE username='$user'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Şifre doğrulaması
    if (password_verify($pass, $row['password'])) {
        // Başarılı giriş
        echo json_encode([
            "message" => "Login successful",
            "status" => "success",
            "userId" => $row['id'], // Kullanıcının ID'si
            "username" => $row['username'] // Kullanıcının kullanıcı adı
        ]);
    } else {
        // Şifre hatası
        echo json_encode(["message" => "Invalid password", "status" => "error"]);
    }
} else {
    // Kullanıcı bulunamadı
    echo json_encode(["message" => "User not found", "status" => "error"]);
}

$conn->close();
?>
