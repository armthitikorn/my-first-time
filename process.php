<?php
// เริ่มการใช้งาน Session
session_start();

// กำหนดให้ PHP ใช้การเข้ารหัสแบบ UTF-8
header('Content-Type: text/html; charset=utf-8');

// กำหนดชื่อไฟล์ CSV ที่จะบันทึกข้อมูล
$filename = 'quiz_results.csv';

// กำหนดคำตอบที่ถูกต้อง
$correct_answers = [
    'page2' => [
        1 => 'B', 2 => 'A', 3 => 'D', 4 => 'A', 5 => 'A', 6 => 'D', 7 => 'B', 8 => 'A', 9 => 'B', 10 => 'B',
        11 => 'A', 12 => 'D', 13 => 'A', 14 => 'C', 15 => 'B', 16 => 'C', 17 => 'D', 18 => 'A', 19 => 'C', 20 => 'B',
        21 => 'A', 22 => 'C', 23 => 'D', 24 => 'B', 25 => 'A', 26 => 'C', 27 => 'D', 28 => 'B', 29 => 'C', 30 => 'A',
        31 => 'B', 32 => 'A', 33 => 'C', 34 => 'D', 35 => 'B', 36 => 'A', 37 => 'C', 38 => 'D', 39 => 'B', 40 => 'C',
        41 => 'B', 42 => 'C', 43 => 'A', 44 => 'D', 45 => 'B', 46 => 'A', 47 => 'C', 48 => 'B', 49 => 'D', 50 => 'C'
    ],
    'page3' => [
        1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'A', 6 => 'B', 7 => 'C', 8 => 'D', 9 => 'A', 10 => 'B',
        11 => 'C', 12 => 'D', 13 => 'A', 14 => 'B', 15 => 'C', 16 => 'D', 17 => 'A', 18 => 'B', 19 => 'C', 20 => 'D'
    ]
];

// ฟังก์ชันสำหรับบันทึกข้อมูลลงในไฟล์ CSV อย่างปลอดภัย
function save_to_csv($data, $filename, $headers) {
    $temp_filename = $filename . '.tmp';
    
    // อ่านข้อมูลทั้งหมดจากไฟล์เดิม
    $all_data = [];
    if (file_exists($filename)) {
        if (($handle = fopen($filename, 'r')) !== FALSE) {
            $existing_headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) === count($existing_headers)) {
                    $all_data[] = array_combine($existing_headers, $row);
                }
            }
            fclose($handle);
        }
    } else {
        $all_data[] = [];
    }

    // อัปเดตข้อมูลของ Session ที่ตรงกัน
    $found = false;
    foreach ($all_data as &$row) {
        if (isset($row['SessionID']) && $row['SessionID'] === session_id()) {
            $row = array_merge($row, $data);
            $found = true;
            break;
        }
    }

    // ถ้าไม่พบ ให้เพิ่มบรรทัดใหม่
    if (!$found) {
        $data['SessionID'] = session_id();
        $all_data[] = $data;
    }

    // เขียนข้อมูลทั้งหมดกลับไปในไฟล์ชั่วคราว
    $handle = fopen($temp_filename, 'w');
    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, $headers);
    foreach ($all_data as $row) {
        fputcsv($handle, array_values($row));
    }
    fclose($handle);
    
    // เปลี่ยนชื่อไฟล์ชั่วคราวเป็นชื่อไฟล์จริง
    rename($temp_filename, $filename);
}

// กำหนดส่วนหัว
$header_fields = ["SessionID", "Fname", "Lname", "Position", "Campaign", "Page"];
for ($i = 1; $i <= 50; $i++) { $header_fields[] = "Q" . $i; }
$header_fields[] = "Score_Page2";
for ($i = 1; $i <= 20; $i++) { $header_fields[] = "Page3_Q" . $i; }
$header_fields[] = "Score_Page3";
$header_fields[] = "Total_Score";
$header_fields[] = "Timestamp";

// รับข้อมูลจากฟอร์ม
$page = isset($_POST['page']) ? $_POST['page'] : '';
$timestamp = date("Y-m-d H:i:s");

// กระบวนการสำหรับ Page 1
if ($page == 'page1') {
    $_SESSION['quiz_data'] = [
        "Fname" => isset($_POST['fname']) ? $_POST['fname'] : '',
        "Lname" => isset($_POST['lname']) ? $_POST['lname'] : '',
        "Position" => isset($_POST['position']) ? $_POST['position'] : '',
        "Campaign" => isset($_POST['campaign']) ? $_POST['campaign'] : '',
        "Page" => $page,
        "Timestamp" => $timestamp
    ];
    save_to_csv($_SESSION['quiz_data'], $filename, $header_fields);
    header('Location: page2.html');
    exit();
}

// กระบวนการสำหรับ Page 2
if ($page == 'page2') {
    $answers = [];
    $score = 0;
    for ($i = 1; $i <= 50; $i++) {
        $answer = isset($_POST["q{$i}"]) ? $_POST["q{$i}"] : '';
        $answers["Q{$i}"] = $answer;
        if ($answer == $correct_answers['page2'][$i]) {
            $score++;
        }
    }
    
    $_SESSION['quiz_data']['Page'] = $page;
    $_SESSION['quiz_data'] = array_merge($_SESSION['quiz_data'], $answers);
    $_SESSION['quiz_data']['Score_Page2'] = $score;
    $_SESSION['quiz_data']['Total_Score'] = $score;
    $_SESSION['quiz_data']['Timestamp'] = $timestamp;
    
    save_to_csv($_SESSION['quiz_data'], $filename, $header_fields);

    if ($score >= 40) {
        header('Location: page3.html');
    } else {
        header('Location: page4.html');
    }
    exit();
}

// กระบวนการสำหรับ Page 3
if ($page == 'page3') {
    $answers = [];
    $score_page3 = 0;
    for ($i = 1; $i <= 20; $i++) {
        $answer = isset($_POST["q{$i}"]) ? $_POST["q{$i}"] : '';
        $answers["Page3_Q{$i}"] = $answer;
        if ($answer == $correct_answers['page3'][$i]) {
            $score_page3++;
        }
    }
    
    $_SESSION['quiz_data']['Page'] = $page;
    $_SESSION['quiz_data'] = array_merge($_SESSION['quiz_data'], $answers);
    $_SESSION['quiz_data']['Score_Page3'] = $score_page3;
    $_SESSION['quiz_data']['Total_Score'] += $score_page3;
    $_SESSION['quiz_data']['Timestamp'] = $timestamp;
    
    save_to_csv($_SESSION['quiz_data'], $filename, $header_fields);
    
    header('Location: page4.html');
    exit();
}
?>