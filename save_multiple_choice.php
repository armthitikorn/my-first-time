<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(0);
ini_set('display_errors', 0);

try {
    if (!isset($_POST['score']) || !isset($_POST['answers']) || !isset($_POST['userFullName'])) {
        throw new Exception('Missing required data.');
    }

    $score = trim($_POST['score']);
    $answers = json_decode($_POST['answers'], true);
    $userFullName = trim($_POST['userFullName']);

    if ($answers === null) {
        throw new Exception('Invalid answers format.');
    }

    if (!is_numeric($score)) {
        throw new Exception('Score must be a number.');
    }

    $userFullName = preg_replace('/[^a-zA-Z0-9_\-ก-๙\s]/u', '', $userFullName);

    // กำหนดเส้นทาง
    $date = date('Y-m-d');
    $userFolder = "recordings/{$userFullName}_{$date}";
    $jsonFile = "{$userFolder}/score_data.json";

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($userFolder)) {
        mkdir($userFolder, 0777, true);
    }
    
    // โค้ดที่แก้ไข: อ่านข้อมูลเดิมก่อน
    $existingData = [];
    if (file_exists($jsonFile)) {
        $fileContent = file_get_contents($jsonFile);
        $existingData = json_decode($fileContent, true);
        if ($existingData === null) {
            $existingData = []; // กรณีไฟล์เสีย
        }
    }

    // เตรียมข้อมูลสำหรับบันทึก
    $dataToSave = array_merge($existingData, [
        'multiple_choice_score' => (int)$score,
        'multiple_choice_answers' => $answers,
    ]);

    // บันทึกข้อมูลลงไฟล์ JSON
    $file = fopen($jsonFile, "w");
    if ($file) {
        fwrite($file, json_encode($dataToSave, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        fclose($file);
        echo json_encode(['status' => 'success', 'message' => 'Multiple choice data saved successfully.']);
    } else {
        throw new Exception('Unable to save file.');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>