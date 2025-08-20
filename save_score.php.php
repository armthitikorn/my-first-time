<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$userFullName = isset($_POST['userFullName']) ? $_POST['userFullName'] : 'unknown_user';
$quizType = isset($_POST['quizType']) ? $_POST['quizType'] : 'unknown_quiz';
$score = isset($_POST['score']) ? $_POST['score'] : 0;
$date = date('Y-m-d H:i:s');

// กำหนดชื่อไฟล์ CSV
$csvFile = 'quiz_scores.csv';

// ตรวจสอบว่าไฟล์มีอยู่หรือไม่
$file_exists = file_exists($csvFile);

// เปิดไฟล์เพื่อเขียนข้อมูล
$file = fopen($csvFile, 'a');

// ถ้าไฟล์ยังไม่มี ให้เขียน Header ก่อน
if (!$file_exists) {
    fputcsv($file, ['timestamp', 'user_full_name', 'quiz_type', 'score']);
}

// เขียนข้อมูลใหม่
fputcsv($file, [$date, $userFullName, $quizType, $score]);

fclose($file);

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Score saved successfully.']);
?>