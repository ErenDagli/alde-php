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


use Iyzipay\Model\Payment;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Model\BasketItem;

// JSON verisini al ve decode et
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        "status" => "failure",
        "message" => "Geçersiz ödeme bilgileri."
    ]);
    exit;
}
$shippingCost = floatval($data["shippingCost"]); // shippingCost'u sayıya çeviriyoruz

// Ürün fiyatını da sayıya çeviriyoruz
$totalPrice = floatval($data["totalPrice"]);
error_log(print_r($data, true)); // Gelen JSON verisini log'a yazdırır

// Ödeme ve kargo ücretini toplam tutara ekle
$totalPriceWithShipping = $totalPrice;
// İyzico ayarları
$options = new \Iyzipay\Options();
$options->setApiKey("sandbox-rj7O67WzIiWwCj78pAfoBV5OkiKxdCTG");
$options->setSecretKey("sandbox-js0Kphi37ztD3eu0R6YCRYdIeVRBl2KV");
$options->setBaseUrl("https://sandbox-api.iyzipay.com");

// Ödeme isteği
$request = new CreatePaymentRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setConversationId("123456789");
$request->setPrice($totalPriceWithShipping);
$request->setPaidPrice($totalPriceWithShipping);
$request->setCurrency(\Iyzipay\Model\Currency::TL);
$request->setInstallment(1);

// Kart bilgileri
$paymentCard = new \Iyzipay\Model\PaymentCard();
$paymentCard->setCardHolderName($data["cardHolderName"]);
$paymentCard->setCardNumber($data["cardNumber"]);
$paymentCard->setExpireMonth($data["expiryMonth"]);
$paymentCard->setExpireYear($data["expiryYear"]);
$paymentCard->setCvc($data["cvc"]);
$paymentCard->setRegisterCard(0);
$request->setPaymentCard($paymentCard);

// Kullanıcı bilgileri
$buyer = new \Iyzipay\Model\Buyer();
$buyer->setId("BY123");
$buyer->setName(explode(" ", $data["billingAddress"]["contactName"])[0]); // Ad
$buyer->setSurname(explode(" ", $data["billingAddress"]["contactName"])[1] ?? ""); // Soyad
$buyer->setEmail("test@email.com");
$buyer->setIdentityNumber("11111111111");
$buyer->setRegistrationAddress($data["billingAddress"]["address"]);
$buyer->setCity($data["billingAddress"]["city"]);
$buyer->setCountry($data["billingAddress"]["country"]);
$request->setBuyer($buyer);

// Fatura adresi
$billingAddress = new \Iyzipay\Model\Address();
$billingAddress->setContactName($data["billingAddress"]["contactName"]);
$billingAddress->setCity($data["billingAddress"]["city"]);
$billingAddress->setCountry($data["billingAddress"]["country"]);
$billingAddress->setAddress($data["billingAddress"]["address"]);
$billingAddress->setZipCode($data["billingAddress"]["zipCode"]);
$request->setBillingAddress($billingAddress);

// Teslimat adresi
$shippingAddress = new \Iyzipay\Model\Address();
$shippingAddress->setContactName($data["shippingAddress"]["contactName"]);
$shippingAddress->setCity($data["shippingAddress"]["city"]);
$shippingAddress->setCountry($data["shippingAddress"]["country"]);
$shippingAddress->setAddress($data["shippingAddress"]["address"]);
$shippingAddress->setZipCode($data["shippingAddress"]["zipCode"]);
$request->setShippingAddress($shippingAddress);

// BasketItems'ı oluştur
$basketItems = [];
foreach ($data["basketItems"] as $item) {
    $basketItem = new BasketItem();
    $basketItem->setId($item["id"]);
    $basketItem->setName($item["name"]);
    $basketItem->setCategory1($item["category1"]);
    $basketItem->setItemType($item["itemType"]);
    $basketItem->setPrice($item["price"]);
    $basketItems[] = $basketItem;
}
$basketItemShipping = new BasketItem();
$basketItemShipping->setId("SHIPPING");
$basketItemShipping->setName("Shipping Fee");
$basketItemShipping->setCategory1("Shipping");
$basketItemShipping->setItemType("PHYSICAL");
$basketItemShipping->setPrice($shippingCost);
$basketItems[] = $basketItemShipping;
$request->setBasketItems($basketItems);

error_log(print_r($request, true)); // Gelen JSON verisini log'a yazdırır
// Ödeme talebi gönder
$payment = Payment::create($request, $options);

// Yanıtı kontrol et
if ($payment->getStatus() === "success") {
    echo json_encode([
        "status" => "success",
        "message" => "Ödeme başarılı",
        "paymentId" => $payment->getPaymentId(),
    ]);
} else {
    echo json_encode([
        "status" => "failure",
        "message" => $payment->getErrorMessage(),
    ]);
}
