<?php
session_start();
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
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['action'])) {
    $action = $input['action'];

    switch ($action) {
        case 'create':
            $name = $input['name'];
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            echo json_encode(["message" => "Kategori başarıyla eklendi."]);
            break;

        case 'read_all':
            $result = $conn->query("SELECT * FROM categories WHERE is_deleted = 0");
            $categories = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($categories);
            break;

        case 'read':
            $id = $input['id'];
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND is_deleted = 0");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            echo json_encode($result);
            break;

        case 'update':
            $id = $input['id'];
            $name = $input['name'];
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ? AND is_deleted = 0");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            echo json_encode(["message" => "Kategori başarıyla güncellendi."]);
            break;

        case 'delete':
            $id = $input['id'];
            $stmt = $conn->prepare("UPDATE categories SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["message" => "Kategori soft delete ile silindi."]);
            break;

        default:
            echo json_encode(["error" => "Geçersiz işlem."]);
    }
} else {
    echo json_encode(["error" => "İşlem belirtilmedi."]);
}

$conn->close();
