<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// ปิดการแสดงผล Error สำหรับผู้ใช้
error_reporting(0);
ini_set('display_errors', 0);

try {
    // ตรวจสอบข้อมูล POST
    if (empty($_POST['userFullName']) || empty($_POST['essayAnswers'])) {
        throw new Exception('Missing required data.');
    }

    $user_name = preg_replace('/[^a-zA-Z0-9_\-ก-๙\s]/u', '', $_POST['userFullName']); // ป้องกันตัวอักษรผิดปกติ
    $essay_answers = json_decode($_POST['essayAnswers'], true);

    if ($essay_answers === null) {
        throw new Exception('Invalid essay answers format.');
    }

    $date = date('Y-m-d');

    // ตรวจสอบข้อมูลแบบทดสอบปรนัยใน session
    if (!isset($_SESSION['quiz_data']) || empty($_SESSION['quiz_data'])) {
        throw new Exception('Multiple choice data not found in session.');
    }

    $quiz_data = $_SESSION['quiz_data'];

    // สร้างชื่อโฟลเดอร์และ path
    $dir_name = $user_name . '_' . $date;
    $upload_dir = __DIR__ . '/recordings/' . $dir_name;
    $file_path = $upload_dir . '/score_data.json';

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create upload directory.');
        }
    }

    // เตรียมข้อมูลที่จะบันทึก
    $data_to_save = [
        'user_name' => $user_name,
        'multiple_choice_score' => isset($quiz_data['multiple_choice_score']) ? $quiz_data['multiple_choice_score'] : 0,
        'multiple_choice_answers' => isset($quiz_data['multiple_choice_answers']) ? $quiz_data['multiple_choice_answers'] : [],
        'essay_answers' => $essay_answers,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // บันทึกไฟล์ JSON
    if (file_put_contents($file_path, json_encode($data_to_save, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save data to file.');
    }

    // ลบ session หลังบันทึก
    session_destroy();

    echo json_encode(['status' => 'success', 'message' => 'All data saved successfully.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
