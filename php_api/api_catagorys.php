<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $catStmt = $conn->prepare("SELECT * FROM categorys ORDER BY category_id ASC");
        $catStmt->execute();
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $categories, "categories" => $categories]);
        exit;
    }

    if ($action === 'add') {
        $category_name = trim($_POST['category_name'] ?? '');
        if ($category_name === '') {
            http_response_code(400);
            echo json_encode(["error" => "กรุณาระบุชื่อประเภทสินค้า"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO categorys (category_name) VALUES (?)");
        $stmt->execute([$category_name]);
        echo json_encode(["message" => "เพิ่มประเภทสินค้าเรียบร้อย"]);
        exit;
    }

    if ($action === 'update') {
        $category_id = $_POST['category_id'] ?? '';
        $category_name = trim($_POST['category_name'] ?? '');
        if ($category_id === '' || $category_name === '') {
            http_response_code(400);
            echo json_encode(["error" => "กรุณาระบุรหัสและชื่อประเภทสินค้า"]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE categorys SET category_name=? WHERE category_id=?");
        $stmt->execute([$category_name, $category_id]);
        echo json_encode(["message" => "แก้ไขประเภทสินค้าเรียบร้อย"]);
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['category_id'] ?? '';
        if ($id === '') {
            http_response_code(400);
            echo json_encode(["error" => "ไม่พบรหัสประเภทสินค้า"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM categorys WHERE category_id=?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "ลบประเภทสินค้าเรียบร้อย"]);
        exit;
    }

    http_response_code(400);
    echo json_encode(["error" => "คำสั่งไม่ถูกต้อง"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
