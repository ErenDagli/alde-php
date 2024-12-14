<?php
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

    switch ($action) {
        case 'create':
            $product_id = $input['product_id'];
            $user_id = $input['user_id'];
            $rating = $input['rating'];
            $comment = $input['comment'];

            $stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
            $stmt->execute();
            echo json_encode(["message" => "Review added successfully."]);
            break;

        case 'read':
            $product_id = $input['product_id'];
            $result = $conn->query("SELECT product_reviews.*, users.username 
                                    FROM product_reviews 
                                    JOIN users ON product_reviews.user_id = users.id 
                                    WHERE product_reviews.product_id = $product_id");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;

        case 'update':
            $id = $input['id'];
            $rating = $input['rating'];
            $comment = $input['comment'];

            $stmt = $conn->prepare("UPDATE product_reviews SET rating = ?, comment = ? WHERE id = ?");
            $stmt->bind_param("isi", $rating, $comment, $id);
            $stmt->execute();
            echo json_encode(["message" => "Review updated successfully."]);
            break;

        case 'delete':
            $id = $input['id'];
            $stmt = $conn->prepare("DELETE FROM product_reviews WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["message" => "Review deleted successfully."]);
            break;

        case 'average_rating':
            $product_id = $input['product_id'];
            $result = $conn->query("SELECT AVG(rating) AS average_rating FROM product_reviews WHERE product_id = $product_id");
            $row = $result->fetch_assoc();
            echo json_encode($row);
            break;

        default:
            echo json_encode(["error" => "Invalid action."]);
    }
} else {
    echo json_encode(["error" => "No action specified."]);
}

$conn->close();
