<?php
header("Access-Control-Allow-Origin: *"); // Tüm kaynaklardan erişime izin verir
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // İzin verilen HTTP metotlarını belirtir
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
$fullPath = realpath(__DIR__ . "/uploads/" . basename($_GET['path']));

if ($fullPath && file_exists($fullPath)) {
    header("Content-Type: " . mime_content_type($fullPath));
    readfile($fullPath);
    exit;
} else {
    // Hata logu yaz
    $logMessage = sprintf(
        "[%s] Resim bulunamadı. Yol: %s, İstek: %s\n",
        date("Y-m-d H:i:s"),
        $fullPath ?: "Belirlenemedi",
        json_encode($_GET)
    );
    error_log($logMessage, 3, __DIR__ . '/error.log'); // Hataları error.log dosyasına yaz

    // 404 cevabı döndür
    http_response_code(404);
    echo "Resim bulunamadı.";
    exit;
}
