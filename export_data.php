<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// กำหนดหัวข้อและรูปแบบของไฟล์
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Quiz Data');

// กำหนดหัวตาราง
$sheet->setCellValue('A1', 'ชื่อผู้เข้าสอบ');
$sheet->setCellValue('B1', 'คะแนนปรนัย');
$sheet->setCellValue('C1', 'คำตอบปรนัย');
$sheet->setCellValue('D1', 'คำตอบอัตนัย');
$sheet->getStyle('A1:D1')->getFont()->setBold(true);

// เริ่มเขียนข้อมูลตั้งแต่แถวที่ 2
$row = 2;

// กำหนดเส้นทาง
$recordingsDir = __DIR__ . '/recordings';
if (is_dir($recordingsDir)) {
    $userFolders = glob($recordingsDir . '/*');
    foreach ($userFolders as $userFolder) {
        if (is_dir($userFolder)) {
            $jsonFile = $userFolder . '/score_data.json';
            $essayFile = $userFolder . '/essay_data.txt'; // แก้ชื่อไฟล์ตามที่บันทึก
            
            // อ่านข้อมูล JSON
            $scoreData = [];
            if (file_exists($jsonFile)) {
                $scoreData = json_decode(file_get_contents($jsonFile), true);
            }

            // อ่านข้อมูลคำตอบอัตนัย
            $essayAnswer = '';
            if (file_exists($essayFile)) {
                $essayAnswer = file_get_contents($essayFile);
            }
            
            // แยกชื่อผู้เข้าสอบจากชื่อโฟลเดอร์
            $folderNameParts = explode('_', basename($userFolder));
            $fullName = $folderNameParts[0] ?? '';

            $sheet->setCellValue('A' . $row, $fullName);
            $sheet->setCellValue('B' . $row, $scoreData['multiple_choice_score'] ?? '');
            $sheet->setCellValue('C' . $row, json_encode($scoreData['multiple_choice_answers'] ?? []));
            $sheet->setCellValue('D' . $row, $essayAnswer);
            
            $row++;
        }
    }
}

// ตั้งค่าความกว้างคอลัมน์อัตโนมัติ
foreach(range('A','D') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// สร้างและส่งไฟล์ .xlsx
$writer = new Xlsx($spreadsheet);
$fileName = 'quiz_data_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
?>