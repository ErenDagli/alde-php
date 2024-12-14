<?php
//require_once 'autoload.php'; // Autoload dosyasını dahil edin
//require_once __DIR__ . '/autoload.php';
spl_autoload_register(function ($class) {
    $prefix = 'Iyzipay\\';
    $base_dir = __DIR__ . '/vendor/iyzipay/src/Iyzipay/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    } else {
        echo "Sınıf dosyası bulunamadı: $file\n"; // Hata için ek çıktı
    }
});

// İyzico API ayarlarını yapılandırın
$options = new \Iyzipay\Options();
$options->setApiKey("sandbox-rj7O67WzIiWwCj78pAfoBV5OkiKxdCTG");
$options->setSecretKey("sandbox-js0Kphi37ztD3eu0R6YCRYdIeVRBl2KV");
$options->setBaseUrl("https://sandbox-api.iyzipay.com");

// Ödeme formunu başlatmak için istek oluştur
$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setConversationId("123456789"); // Ödeme işlemi için benzersiz bir ID
$request->setPrice("100.0"); // Ürün tutarı
$request->setPaidPrice("100.0"); // Ödenecek toplam tutar
$request->setCurrency(\Iyzipay\Model\Currency::TL);
$request->setBasketId("B67832"); // Sepet ID'si
$request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
$request->setCallbackUrl("http://localhost/callback.php"); // İşlem tamamlandığında yönlendirme yapılacak URL

// Kullanıcı bilgilerini ekle
$buyer = new \Iyzipay\Model\Buyer();
$buyer->setId("BY789"); // Kullanıcı ID'si
$buyer->setName("John");
$buyer->setSurname("Doe");
$buyer->setEmail("john.doe@example.com");
$buyer->setIdentityNumber("11111111111");
$buyer->setRegistrationAddress("İstanbul, Türkiye");
$buyer->setIp(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
$buyer->setCity("Istanbul");
$buyer->setCountry("Turkey");
$request->setBuyer($buyer);

// Teslimat adresini ekle
$shippingAddress = new \Iyzipay\Model\Address();
$shippingAddress->setContactName("John Doe");
$shippingAddress->setCity("Istanbul");
$shippingAddress->setCountry("Turkey");
$shippingAddress->setAddress("Teslimat adresi");
$request->setShippingAddress($shippingAddress);

// Fatura adresini ekle
$billingAddress = new \Iyzipay\Model\Address();
$billingAddress->setContactName("John Doe");
$billingAddress->setCity("Istanbul");
$billingAddress->setCountry("Turkey");
$billingAddress->setAddress("Fatura adresi");
$request->setBillingAddress($billingAddress);

// Sepet içeriğini tanımla
$basketItems = [];
$item1 = new \Iyzipay\Model\BasketItem();
$item1->setId("BI101");
$item1->setName("Telefon");
$item1->setCategory1("Elektronik");
$item1->setPrice("100.0");
$item1->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
$basketItems[] = $item1;

$request->setBasketItems($basketItems);

// İstek gönder ve yanıtı al
$checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, $options);

// Ödeme formu HTML kodunu ekrana yazdır
echo $checkoutFormInitialize->getCheckoutFormContent();
