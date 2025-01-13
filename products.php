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

        // Dosyaları yüklemek için diziyi hazırlıyoruz
        $imagePaths = [];

        if (isset($_FILES['images']) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Çoklu dosya yüklemesi yapıyoruz
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                $fileName = basename($_FILES['images']['name'][$key]);
                $uploadFilePath = $uploadDir . $fileName;

                // Dosyayı yükle
                if (move_uploaded_file($tmpName, $uploadFilePath)) {
                    $imagePaths[] = $uploadFilePath; // Yüklenen dosya yolunu diziye ekle
                } else {
                    echo json_encode(["message" => "Resim yüklenirken hata oluştu."]);
                    exit;
                }
            }
        }

        // Eğer resimler yüklendiyse, dosya yollarını virgülle ayırarak sakla
        $imagePathsString = !empty($imagePaths) ? implode(',', $imagePaths) : NULL;

        // Veritabanına ürün bilgilerini ekle
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $name, $description, $price, $category_id, $imagePathsString);
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
            if ($category_id == -1) {
                // Son eklenen 15 ürünü getiren sorgu
                $stmt = $conn->prepare("
            SELECT 
                products.*, 
                categories.name AS category_name,
                COUNT(product_reviews.id) AS review_count,
                AVG(product_reviews.rating) AS average_rating
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id
            LEFT JOIN product_reviews ON products.id = product_reviews.product_id
            WHERE categories.is_deleted = 0 AND products.is_deleted = 0
            GROUP BY products.id
            ORDER BY products.id DESC
            LIMIT 15
        ");
            } else {
                // Belirli bir kategoriye göre ürünleri getiren sorgu
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
            AND categories.is_deleted = 0 AND products.is_deleted = 0
            GROUP BY products.id
        ");
                $stmt->bind_param("i", $category_id);
            }
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
            $id = $_POST['id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $category_id = $_POST['category_id'];

            // Yeni görseller için dizi
            $newImagePaths = [];

            // Mevcut görselleri al (existing_images[])
            $existingImages = isset($_POST['existing_images']) ? json_decode($_POST['existing_images'], true) : [];

            // Eğer yeni görseller yüklendiyse
            if (isset($_FILES['images']) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Çoklu dosya yüklemesi
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    $fileName = basename($_FILES['images']['name'][$key]);
                    $uploadFilePath = $uploadDir . $fileName;

                    // Dosyayı yükle
                    if (move_uploaded_file($tmpName, $uploadFilePath)) {
                        $newImagePaths[] = $uploadFilePath; // Yeni yüklenen dosya yolunu ekle
                    } else {
                        echo json_encode(["message" => "Resim yüklenirken hata oluştu."]);
                        exit;
                    }
                }
            }

            // Yeni görseller ve mevcut görselleri birleştir
            $allImages = array_merge($existingImages, $newImagePaths);
            // Görselleri virgülle ayırarak kaydediyoruz
            $imagePathsString = !empty($allImages) ? implode(',', $allImages) : NULL;

            // Eğer image_path boşsa, yeni görselleri direkt kaydediyoruz
            if (empty($imagePathsString)) {
                $imagePathsString = NULL; // Eğer hiçbir görsel yoksa NULL yap
            }

            // Ürün bilgilerini ve yeni görselleri güncelle
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $description, $price, $category_id, $imagePathsString, $id);
            $stmt->execute();

            echo json_encode(["message" => "Ürün başarıyla güncellendi."]);
            break;


        case 'delete':
            $id = $input['id'];

            // Öncelikle ürünün mevcut durumunu kontrol et
            $stmt = $conn->prepare("SELECT is_deleted FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->store_result();  // Sonuçları bellek içi tut
            $stmt->bind_result($is_deleted);
            $stmt->fetch();

            if ($is_deleted == 1) {
                // Eğer ürün silinmişse (is_deleted = 1), aktif yapıyoruz
                $stmt = $conn->prepare("UPDATE products SET is_deleted = 0 WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(["message" => "Ürün başarıyla aktif hale getirildi."]);
            } else {
                // Eğer ürün aktifse (is_deleted = 0), siliyoruz (is_deleted = 1 yapıyoruz)
                $stmt = $conn->prepare("UPDATE products SET is_deleted = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(["message" => "Ürün başarıyla silindi."]);
            }
            break;

        default:
            echo json_encode(["error" => "Geçersiz işlem."]);
    }
} else {
    echo json_encode(["error" => "İşlem belirtilmedi."]);
}

$conn->close();
