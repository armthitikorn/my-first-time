<?php
header('Content-Type: application/json');

// ตรวจสอบว่าเมธอดที่ใช้เป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ตรวจสอบว่ามีการอัปโหลดไฟล์มาหรือไม่
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

// รับข้อมูลเพิ่มเติมจากโค้ด JavaScript
$userFullName = isset($_POST['userFullName']) ? $_POST['userFullName'] : 'unknown_user';
$question = isset($_POST['question']) ? $_POST['question'] : 'unknown_question';

// สร้างโฟลเดอร์สำหรับเก็บไฟล์ตามชื่อผู้ใช้และวันที่
$date = date('Y-m-d');
$folderName = "recordings/{$userFullName}_{$date}"; // <-- แก้ไขตรงนี้จาก uploads เป็น recordings

// สร้างโฟลเดอร์หากยังไม่มี
if (!is_dir($folderName)) {
    if (!mkdir($folderName, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create directory.']);
        exit;
    }
}

// กำหนดชื่อไฟล์ปลายทาง
$originalFileName = $_FILES['audio']['name'];
$destination = $folderName . '/' . basename($originalFileName);

// ย้ายไฟล์ที่อัปโหลดไปไว้ในโฟลเดอร์ปลายทาง
if (move_uploaded_file($_FILES['audio']['tmp_name'], $destination)) {
    // อัปโหลดสำเร็จ
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Audio uploaded successfully.']);
} else {
    // อัปโหลดไม่สำเร็จ
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
}
?>