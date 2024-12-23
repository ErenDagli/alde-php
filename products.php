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
$action = isset($_POST['action']) ? $_POST['action'] : ($input['action'] ?? null);

if ($action) {
    switch ($action) {
        case 'create':
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $category_id = $_POST['category_id'];

            // Dosya yükleme işlemi
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileTmpPath = $_FILES['image']['tmp_name'];
                $fileName = basename($_FILES['image']['name']);
                $uploadFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
                    $imagePath = $uploadFilePath;
                } else {
                    echo json_encode(["message" => "Resim yüklenirken hata oluştu."]);
                    exit;
                }
            } else {
                $imagePath = NULL;
            }

            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdis", $name, $description, $price, $category_id, $imagePath);
            $stmt->execute();
            echo json_encode(["message" => "Ürün başarıyla eklendi."]);
            break;

        case 'create_bulk':
            $products = $input['products'];
            $errors = [];
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id) VALUES (?, ?, ?, ?)");

            foreach ($products as $product) {
                $name = $product['name'];
                $description = $product['description'];
                $price = $product['price'];
                $category_id = $product['category_id'];

                $stmt->bind_param("ssdi", $name, $description, $price, $category_id);

                if (!$stmt->execute()) {
                    $errors[] = ["product" => $product, "error" => $stmt->error];
                }
            }

            if (empty($errors)) {
                echo json_encode(["message" => "Tüm ürünler başarıyla eklendi."]);
            } else {
                echo json_encode(["message" => "Bazı ürünler eklenirken hata oluştu.", "errors" => $errors]);
            }
            break;

        case 'read_all':
            $result = $conn->query("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE categories.is_deleted = 0");
            $products = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($products);
            break;
        case 'read_by_category':
            $category_id = $input['category_id'];
            $stmt = $conn->prepare("
        SELECT 
            products.*, 
            categories.name AS category_name,
            COUNT(product_reviews.id) AS review_count,
            AVG(product_reviews.rating) AS average_rating
        FROM products
        LEFT JOIN categories ON products.category_id = categories.id
        LEFT JOIN product_reviews ON products.id = product_reviews.product_id
        WHERE products.category_id = ? 
        AND categories.is_deleted = 0
        GROUP BY products.id
    ");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode($result);
            break;
        case 'read':
            $id = $input['id'];
            $stmt = $conn->prepare("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE products.id = ? AND categories.is_deleted = 0");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            echo json_encode($result);
            break;

        case 'update':
            $id = $input['id'];
            $name = $input['name'];
            $description = $input['description'];
            $price = $input['price'];
            $category_id = $input['category_id'];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileTmpPath = $_FILES['image']['tmp_name'];
                $fileName = basename($_FILES['image']['name']);
                $uploadFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
                    $imagePath = $uploadFilePath;
                } else {
                    echo json_encode(["message" => "Resim yüklenirken hata oluştu."]);
                    exit;
                }
            } else {
                $imagePath = NULL;
            }

            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $description, $price, $category_id, $imagePath, $id);
            $stmt->execute();
            echo json_encode(["message" => "Ürün başarıyla güncellendi."]);
            break;

        case 'delete':
            $id = $input['id'];
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["message" => "Ürün başarıyla silindi."]);
            break;

        default:
            echo json_encode(["error" => "Geçersiz işlem."]);
    }
} else {
    echo json_encode(["error" => "İşlem belirtilmedi."]);
}

$conn->close();
