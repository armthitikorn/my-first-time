<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data_path = 'recordings';
$base_url = 'http://localhost:8080/insurance_quiz/recordings/';

$search_user = isset($_GET['user']) ? trim(strtolower($_GET['user'])) : null;
$search_date = isset($_GET['date']) ? trim($_GET['date']) : null;

if (!is_dir($data_path)) {
    echo json_encode(["error" => "Recordings directory not found."]);
    exit();
}

$entries = scandir($data_path);
$data = [];

foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    $entry_path = $data_path . '/' . $entry;

    if (!is_dir($entry_path)) {
        continue;
    }

    $pos = strrpos($entry, '_');
    if ($pos !== false) {
        $user_name = trim(substr($entry, 0, $pos));
        $entry_date = trim(substr($entry, $pos + 1));
    } else {
        $user_name = $entry;
        $entry_date = '';
    }
    
    // ตรวจสอบชื่อผู้ใช้และวันที่
    if ($search_user && strpos(mb_strtolower($user_name, 'UTF-8'), $search_user) === false) {
        continue;
    }
    if ($search_date && $search_date !== $entry_date) {
        continue;
    }

    $user_recordings = [];
    $score = null;
    $essay_answer = null;

    $files = scandir($entry_path);
    foreach ($files as $file) {
        // This is the missing part
        if ($file === '.' || $file === '..') {
            continue;
        }

        // ตรวจสอบไฟล์ .json
if ($file === 'score_data.json') {
    $json_content = file_get_contents($entry_path . '/' . $file);
    $json_data = json_decode($json_content, true);
    if ($json_data) {
        $score = $json_data['multiple_choice_score'] ?? null; // แก้ไขชื่อตัวแปร
        // รวมคำตอบอัตนัยทั้งหมดเป็น String เดียว
        $essay_answers_array = $json_data['essay_answers'] ?? [];
        $essay_answer = implode(' ', $essay_answers_array);
    }
}
        
        // ตรวจสอบไฟล์ .webm หรือ .wav
        elseif (strpos($file, '.webm') !== false || strpos($file, '.wav') !== false) {
            $user_recordings[] = [
                'filename' => $file,
                'path' => $base_url . urlencode($entry) . '/' . urlencode($file)
            ];
        }
    }

    $data[] = [
        'user' => $user_name,
        'date' => $entry_date,
        'score' => $score,
        'essay_answer' => $essay_answer,
        'recordings' => $user_recordings
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>