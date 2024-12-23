<?php
header("Access-Control-Allow-Origin: *"); // Tüm kaynaklardan erişime izin verir
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // İzin verilen HTTP metotlarını belirtir
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
require 'vendor/iyzipay/IyzipayBootstrap.php';
IyzipayBootstrap::init();
use Iyzipay\Model\ThreedsPayment;
use Iyzipay\Request\CreateThreedsPaymentRequest;

// İyzico ayarları
$options = new \Iyzipay\Options();
$options->setApiKey("sandbox-rj7O67WzIiWwCj78pAfoBV5OkiKxdCTG");
$options->setSecretKey("sandbox-js0Kphi37ztD3eu0R6YCRYdIeVRBl2KV");
$options->setBaseUrl("https://sandbox-api.iyzipay.com");

// Callback verilerini alın
$postData = $_POST;

// 3D Secure Ödeme Tamamlama
$request = new CreateThreedsPaymentRequest();
$request->setConversationId($postData["conversationId"]);
$request->setPaymentId($postData["paymentId"]);

$response = ThreedsPayment::create($request, $options);

// Yanıt kontrolü
if ($response->getStatus() === "success") {
    echo "Ödeme başarılı!";
} else {
    echo "Hata: " . $response->getErrorMessage();
}
