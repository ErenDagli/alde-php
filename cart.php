<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "321cba";
$dbname = "alde";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

switch ($action) {
    // 1. Sepete ürün ekleme
    case "add_to_cart":
        $userId = $data['user_id'];
        $productId = $data['product_id'];
        $quantity = $data['quantity'];

        // Aynı üründen varsa güncelle
        $sql = "SELECT * FROM cart WHERE user_id = $userId AND product_id = $productId";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $sql = "UPDATE cart SET quantity = quantity + $quantity WHERE user_id = $userId AND product_id = $productId";
        } else {
            $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, $quantity)";
        }

        if ($conn->query($sql) === TRUE) {
            // Yeni miktarı kontrol et
            $checkSql = "SELECT quantity FROM cart WHERE user_id = $userId AND product_id = $productId";
            $checkResult = $conn->query($checkSql);

            if ($checkResult->num_rows > 0) {
                $row = $checkResult->fetch_assoc();
                if ($row['quantity'] <= 0) {
                    // Miktar 0 veya daha küçükse ürünü sil
                    $deleteSql = "DELETE FROM cart WHERE user_id = $userId AND product_id = $productId";
                    $conn->query($deleteSql);
                }
            }

            echo json_encode(["status" => "success", "message" => "Cart updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;


    // 2. Sepeti listeleme
    case "view_cart":
        $userId = $data['user_id'];
        $sql = "SELECT c.id as cart_id, p.id as product_id, p.name, p.price, c.quantity, p.image_path 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = $userId";

        $result = $conn->query($sql);
        $cart = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cart[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $cart]);
        } else {
            echo json_encode(["status" => "error", "message" => "Cart is empty"]);
        }
        break;

    case "total_cart_price":
        $userId = $data['user_id'];
        $sql = "SELECT SUM(c.quantity * p.price) AS total_price 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = $userId";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalPrice = $row['total_price'] ?? 0;
            echo json_encode(["status" => "success", "total_price" => $totalPrice]);
        } else {
            echo json_encode(["status" => "success", "total_price" => 0]);
        }
        break;

    case "cart_count":
        $userId = $data['user_id'];
        $sql = "SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = $userId";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $totalItems = $row['total_items'] ?? 0;
            echo json_encode(["status" => "success", "total_items" => $totalItems]);
        } else {
            echo json_encode(["status" => "success", "total_items" => 0]);
        }
        break;

    // 4. Sepeti temizler
    case "clear_cart":
        $userId = $data['user_id'];
        $sql = "DELETE FROM cart WHERE user_id = $userId";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Cart cleared"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}

$conn->close();
?>
