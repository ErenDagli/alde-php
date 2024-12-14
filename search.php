<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "321cba";
$dbname = "alde";

// Veritabanı bağlantısı
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Gelen JSON verisini al
$data = json_decode(file_get_contents("php://input"), true);

// JSON verisinin geçerli olup olmadığını kontrol et
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
    exit;
}

$action = $data['action'] ?? '';

switch ($action) {
    case "search_products":
        // Arama sorgusu kontrolü
        $searchQuery = $data['query'] ?? '';

        if (!empty($searchQuery)) {
            // Arama terimini güvenli hale getirme (XSS ve SQL enjeksiyon koruması)
            $searchQuery = '%' . $conn->real_escape_string($searchQuery) . '%';

            // Ürünleri arama terimi ile sorgula
            $sql = "SELECT id, name, price FROM products WHERE name LIKE ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                echo json_encode(["status" => "error", "message" => "SQL preparation failed"]);
                exit;
            }

            $stmt->bind_param("s", $searchQuery);
            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            echo json_encode(["status" => "success", "data" => $products]);
        } else {
            echo json_encode(["status" => "error", "message" => "Query parameter is missing"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}

$conn->close();
?>
