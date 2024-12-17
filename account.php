<?php
require 'vendor/PHPMailer-master/src/Exception.php';
require 'vendor/PHPMailer-master/src/PHPMailer.php';
require 'vendor/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();
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

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['action'])) {
    $action = $input['action'];
    $module = $input['module'];

    switch ($module) {
        case 'favorites':
            handleFavorites($action, $input, $conn);
            break;
        case 'addresses':
            handleAddresses($action, $input, $conn);
            break;
        case 'orders':
            handleOrders($action, $input, $conn);
            break;
        case 'returns':
            handleReturns($action, $input, $conn);
            break;
        case 'users': // Şifre işlemleri için 'users' modülünü ekledik
            if ($action === 'change_password') {
                handleChangePassword($input, $conn);
            } elseif ($action === 'forgot_password') {
                handleForgotPassword($input, $conn);
            } elseif ($action === 'verification') {
                handleVerification($input, $conn);
            }
            break;
        default:
            echo json_encode(["error" => "Invalid module."]);
    }
} else {
    echo json_encode(["error" => "No action specified."]);
}

$conn->close();

function handleFavorites($action, $input, $conn) {
    switch ($action) {
        case 'create':
            $user_id = $input['user_id'];
            $product_id = $input['product_id'];

            // Önce mevcut bir kayıt var mı kontrol edin
            $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Kayıt varsa sil
                $row = $result->fetch_assoc();
                $deleteStmt = $conn->prepare("DELETE FROM favorites WHERE id = ?");
                $deleteStmt->bind_param("i", $row['id']);
                $deleteStmt->execute();
                echo json_encode(["message" => "Favorite removed successfully."]);
            } else {
                // Kayıt yoksa ekle
                $insertStmt = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
                $insertStmt->bind_param("ii", $user_id, $product_id);
                $insertStmt->execute();
                echo json_encode(["message" => "Favorite added successfully."]);
            }
            break;
        case 'read':
            $user_id = $input['user_id'];

            // Favori ürünleri products tablosu ile birleştirip alın
            $stmt = $conn->prepare("SELECT 
            f.id AS favorite_id, 
            p.id AS product_id, 
            p.name AS product_name, 
            p.price AS product_price, 
            p.description AS product_description
        FROM favorites as f JOIN products as p ON f.product_id = p.id 
        WHERE 
            f.user_id = ?
    ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // Sonuçları JSON formatında döndür
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;

        case 'delete':
            $id = $input['id'];
            $stmt = $conn->prepare("DELETE FROM favorites WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["message" => "Favorite deleted successfully."]);
            break;

        case 'count':
            $user_id = $input['user_id'];
            $stmt = $conn->prepare("SELECT COUNT(*) AS favorite_count FROM favorites WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            echo json_encode(["favorite_count" => $row['favorite_count']]);
            break;
    }
}
function handleAddresses($action, $input, $conn) {
    switch ($action) {
        case 'create':
            // Gelen verileri al
            $user_id = $input['user_id'];
            $address_title = $input['addressTitle'];
            $name_surname = $input['nameSurname'];
            $phone_number = $input['phoneNumber'];
            $city = $input['city'];
            $district = $input['district'];
            $address = $input['address'];

            // Veritabanına ekleme
            $stmt = $conn->prepare("
                INSERT INTO addresses (user_id, address_title, name_surname, phone_number, city, district, address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssss", $user_id, $address_title, $name_surname, $phone_number, $city, $district, $address);
            $stmt->execute();

            echo json_encode(["message" => "Address added successfully."]);
            break;

        case 'read':
            $user_id = $input['user_id'];

            // Veritabanından kullanıcıya ait adresleri al
            $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;

        case 'update':
            // Gelen verileri al
            $id = $input['id'];
            $address_title = $input['addressTitle'];
            $name_surname = $input['nameSurname'];
            $phone_number = $input['phoneNumber'];
            $city = $input['city'];
            $district = $input['district'];
            $address = $input['address'];

            // Adresi güncelle
            $stmt = $conn->prepare("
                UPDATE addresses 
                SET address_title = ?, name_surname = ?, phone_number = ?, city = ?, district = ?, address = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssssi", $address_title, $name_surname, $phone_number, $city, $district, $address, $id);
            $stmt->execute();

            echo json_encode(["message" => "Address updated successfully."]);
            break;

        case 'delete':
            $id = $input['id'];

            // Adresi sil
            $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(["message" => "Address deleted successfully."]);
            break;


        case 'getCities':
            // Tüm şehirleri al
            $sql = "SELECT city_id, name FROM city";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $cities = [];
                while ($row = $result->fetch_assoc()) {
                    $cities[] = $row;
                }
                echo json_encode(['status' => 'success', 'cities' => $cities]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No cities found.']);
            }
            break;

        case 'getDistricts':
            $city_id = $input['city_id'];

            // İlçeleri şehre göre al
            $stmt = $conn->prepare("SELECT district_id, name FROM district WHERE city_id = ?");
            $stmt->bind_param("i", $city_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $districts = [];
                while ($row = $result->fetch_assoc()) {
                    $districts[] = $row;
                }
                echo json_encode(['status' => 'success', 'districts' => $districts]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No districts found for the selected city.']);
            }
            break;
        case 'getDistrictsByName':
            $city_name = $input['city_name']; // Şehir adı alındı

            // Şehir adı ile ilçeleri sorgula
            $stmt = $conn->prepare("
        SELECT d.district_id, d.name 
        FROM district d
        JOIN city c ON d.city_id = c.city_id
        WHERE c.name = ?");  // Şehir adı ile ilçeleri filtrele
            $stmt->bind_param("s", $city_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $districts = [];
                while ($row = $result->fetch_assoc()) {
                    $districts[] = $row;
                }
                echo json_encode(['status' => 'success', 'districts' => $districts]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No districts found for the selected city.']);
            }
            break;
        case 'getAddressById': // Yeni case: ID ile adresi getir
            $id = $input['id'];

            // Veritabanından ID'ye göre adresi al
            $stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'address' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Address not found.']);
            }
            break;

        default:
            echo json_encode(["message" => "Invalid action."]);
            break;
    }
}
function handleOrders($action, $input, $conn) {
    switch ($action) {
        case 'create':
            // Gelen sipariş bilgilerini al
            $user_id = $input['user_id'];
            $order_date = date('Y-m-d H:i:s'); // Sipariş tarihi
            $total_price = $input['total_price']; // Toplam fiyat
            $order_status = 'pending'; // Sipariş durumu (ör: pending, completed)

            // Sipariş ana kaydını ekle
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, order_date, total_price, order_status)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("isds", $user_id, $order_date, $total_price, $order_status);
            $stmt->execute();

            // Sipariş ID'sini al
            $order_id = $conn->insert_id;

            // Sipariş içeriği (order_items) ekleme
            if (isset($input['items']) && is_array($input['items'])) {
                $stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($input['items'] as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $price = $item['price']; // Birim fiyat

                    $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                    $stmt->execute();
                }
            }

            echo json_encode(["message" => "Order added successfully.", "order_id" => $order_id]);
            break;
        case 'read_detail':
            $order_id = $input['order_id'];

            // Sipariş detaylarını çekmek için sorgu
            $stmt = $conn->prepare("
                SELECT 
                    p.name, 
                    od.quantity, 
                    od.price 
                FROM order_items od
                LEFT JOIN products p ON od.product_id = p.id
                WHERE od.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // Sonuçları döndür
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'read':
            $user_id = $input['user_id'];
            $result = $conn->query("SELECT * FROM orders WHERE user_id = $user_id");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;

        default:
            echo json_encode(["message" => "Invalid action."]);
            break;
    }
}

function handleReturns($action, $input, $conn) {
    switch ($action) {
        case 'create':
            $user_id = $input['user_id'];
            $order_id = $input['order_id'];
            $reason = $input['reason'];
            $stmt = $conn->prepare("INSERT INTO return_requests (user_id, order_id, reason) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $order_id, $reason);
            $stmt->execute();
            echo json_encode(["message" => "Return request created successfully."]);
            break;
        case 'read':
            $user_id = $input['user_id'];
            $result = $conn->query("SELECT * FROM return_requests WHERE user_id = $user_id");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
    }
}

/**
 * Şifre değiştirme fonksiyonu
 */
function handleChangePassword($input, $conn) {
    $user_id = $input['user_id'];
    $current_password = $input['current_password'];
    $new_password = $input['new_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_password_hashed, $user_id);
        $update_stmt->execute();
        echo json_encode(["message" => "Password changed successfully."]);
    } else {
        echo json_encode(["error" => "Current password is incorrect."]);
    }
}

/**
 * Şifremi unuttum fonksiyonu
 */
function handleForgotPassword($input, $conn) {
    $email = $input['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $temporary_password = generateTemporaryPassword();
        $temporary_password_hashed = password_hash($temporary_password, PASSWORD_BCRYPT);

        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $temporary_password_hashed, $user['id']);
        $update_stmt->execute();

        // E-postayı gönderme (örnek kod)
        sendTemporaryPasswordEmail($email, $temporary_password);

        echo json_encode(["message" => "A temporary password has been sent to your email address."]);
    } else {
        echo json_encode(["error" => "Email address not found."]);
    }
}
/**
 * Hesabı doğrulama ve aktif etme fonksiyonu
 */
function handleVerification($input, $conn) {
    $userId = $input['userId'];
    $verificationCode = $input['verificationCode'];

    $stmt = $conn->prepare("SELECT id, verification_code, is_active FROM users WHERE id = ? AND verification_code = ?");
    $stmt->bind_param("is", $userId, $verificationCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if ($user['is_active'] == 1) {
            // Kullanıcı zaten aktif
            echo json_encode(["message" => "Account is already active.", "status" => "error"]);
        } else {
            // Kullanıcıyı aktif hale getir
            $update_stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $userId);
            if ($update_stmt->execute()) {
                echo json_encode(["message" => "Account has been activated successfully.", "status" => "success"]);
            } else {
                echo json_encode(["message" => "Failed to activate account.", "status" => "error"]);
            }
        }
    } else {
        // Kullanıcı bulunamazsa
        echo json_encode(["message" => "Invalid user ID or verification code.", "status" => "error"]);
    }
}

/**
 * Geçici şifre oluşturma
 */
function generateTemporaryPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_password = '';
    for ($i = 0; $i < $length; $i++) {
        $random_password .= $characters[rand(0, $characters_length - 1)];
    }
    return $random_password;
}

/**
 * Geçici şifre e-posta gönderimi (örnek)
 */

function sendTemporaryPasswordEmail($recipientEmail, $temporaryPassword)
{// Başlangıç
    echo "Starting email function...\n";

    $mail = new PHPMailer(true); // true parametresi istisnaları etkinleştirir

    try {
        echo "Configuring SMTP...\n";
        // SMTP sunucu ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ahmeteren.karel@gmail.com';
        $mail->Password = 'gitjauhzxdruaoil'; // Gmail şifresi veya uygulama şifresi
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 2;

        echo "Setting email details...\n";
        // Gönderen e-posta adresi ve alıcı
        $mail->setFrom(mb_encode_mimeheader('ahmeteren.karel@gmail.com', 'UTF-8'), mb_encode_mimeheader('Alde - Öğrencinin Evi','UTF-8', 'B'));
        $mail->addAddress(mb_encode_mimeheader($recipientEmail, 'UTF-8'));
        $mail->AddCustomHeader('Content-Type', 'text/html; charset=UTF-8');
        // E-posta içeriği
        $mail->Charset = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = mb_encode_mimeheader('Geçici Şifre', 'UTF-8', 'B');
        $mail->Body    = "<p>Geçici şifreniz: <strong>$temporaryPassword</strong></p><p>Lütfen giriş yaptıktan sonra şifrenizi değiştirin.</p>";
        echo "Sending email...\n";
        // E-postayı gönder
        $mail->send();
        echo "E-mail sent successfully.\n";
    } catch (Exception $e) {
        echo "E-mail could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
    }
}

