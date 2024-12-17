<?php
require 'vendor/PHPMailer-master/src/Exception.php';
require 'vendor/PHPMailer-master/src/PHPMailer.php';
require 'vendor/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
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

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $data->name;
    $surname = $data->surname;
    $username = $data->username;
    $email = $data->email;
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $phoneNumber = $data->phoneNumber;
    $gender = $data->gender;
    $campaignEmail = $data->campaignEmail ? 1 : 0; // Boolean değeri 1/0 olarak kaydedilecek
    $memberCondition = $data->memberCondition ? 1 : 0;
    $verification_code = rand(100000, 999999);
    $is_active = 0;

    // Kullanıcı adı kontrolü
    $checkUsernameSql = "SELECT * FROM users WHERE username='$username'";
    $resultUsername = $conn->query($checkUsernameSql);

    if ($resultUsername->num_rows > 0) {

        $row = $resultUsername->fetch_assoc();
        if ($row['is_active'] != 0) {
            echo json_encode(["message" => "Username already exists.", "status" => "error"]);
            $conn->close();
            exit;
        }
    }

    // Email kontrolü
    $checkEmailSql = "SELECT * FROM users WHERE email='$email'";
    $resultEmail = $conn->query($checkEmailSql);

    if ($resultEmail->num_rows > 0) {
        $row = $resultEmail->fetch_assoc();

        if ($row['is_active'] == 0) {
            // Eğer hesap aktif değilse, doğrulama kodunu yenile
            $verification_code = rand(100000, 999999);
            $updateVerificationSql = "UPDATE users SET verification_code='$verification_code' WHERE email='$email'";

            if ($conn->query($updateVerificationSql) === TRUE) {
                sendVerificationCodeEmail($email, $verification_code);
                echo json_encode([
                    "message" => "Verification code resent for inactive account.",
                    "status" => "updated",
                    "userId" => $row['id']
                ]);
            } else {
                echo json_encode([
                    "message" => "Error: " . $conn->error,
                    "status" => "error"
                ]);
            }
        } else {
            // Eğer hesap zaten aktifse hata mesajı dön
            echo json_encode([
                "message" => "Email already exists and is active.",
                "status" => "error"
            ]);
        }
        $conn->close();
        exit;
    } else {
        $insertSql = "INSERT INTO users (name, surname, username, email, password, phone_number, gender, campaign_email, member_condition, verification_code, is_active)
            VALUES ('$name', '$surname', '$username', '$email', '$password', '$phoneNumber', '$gender', '$campaignEmail', '$memberCondition', '$verification_code', '$is_active')";

        if ($conn->query($insertSql) === TRUE) {
            $userId = $conn->insert_id; // Eklenen kullanıcının ID'sini al
            sendVerificationCodeEmail($email, $verification_code);
            echo json_encode(["message" => "Signup successful. Verification code sent.", "status" => "success",
                "userId" => $userId]);
        } else {
            echo json_encode(["message" => "Error: " . $conn->error, "status" => "error"]);
        }
    }
}


/**
 * E-posta doğrulama kodu gönderimi
 *
 * @param string $recipientEmail Alıcının e-posta adresi
 * @param string $verificationCode Gönderilecek doğrulama kodu
 */
function sendVerificationCodeEmail($recipientEmail, $verificationCode)
{

    $mail = new PHPMailer(true); // true parametresi istisnaları etkinleştirir

    try {
        // SMTP sunucu ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ahmeteren.karel@gmail.com'; // Gönderici e-posta adresi
        $mail->Password = 'gitjauhzxdruaoil';          // Gmail uygulama şifresi
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Gönderen ve alıcı bilgileri
        $mail->setFrom('ahmeteren.karel@gmail.com', 'Alde - Öğrencinin Evi');
        $mail->addAddress($recipientEmail);

        // E-posta içeriği
        $mail->isHTML(true);
        $mail->Subject = 'Hesap Doğrulama Kodu';
        $mail->Body    = "
            <h2>Hesap Doğrulama</h2>
            <p>Merhaba,</p>
            <p>Hesabınızı doğrulamak için gerekli kod aşağıdadır:</p>
            <h3 style='color:blue;'>$verificationCode</h3>
            <p>Lütfen bu kodu kullanarak hesabınızı aktif hale getirin.</p>
            <br>
            <p>Teşekkürler,<br><strong>Alde - Öğrencinin Evi</strong></p>
        ";
        $mail->AltBody = "Hesap doğrulama kodunuz: $verificationCode";

        // E-postayı gönder
        $mail->send();
    } catch (Exception $e) {
        error_log("E-posta gönderilemedi. Hata: {$mail->ErrorInfo}");
    }
}

$conn->close();
?>
