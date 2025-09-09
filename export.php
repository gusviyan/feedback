<?php
require 'vendor/autoload.php';
include 'config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$spesialis_id = $_GET['spesialis_id'] ?? '';

$where = "WHERE 1=1";
if ($start_date && $end_date) {
    $where .= " AND f.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}
if ($spesialis_id) {
    $where .= " AND d.spesialis_id = $spesialis_id";
}

$sql = "
    SELECT d.nama_dokter, s.nama_spesialis,
           SUM(CASE WHEN f.jenis='like' THEN 1 ELSE 0 END) AS total_like,
           SUM(CASE WHEN f.jenis='dislike' THEN 1 ELSE 0 END) AS total_dislike
    FROM doctors d
    LEFT JOIN spesialis s ON d.spesialis_id=s.id
    LEFT JOIN feedback f ON d.id=f.doctor_id
    $where
    GROUP BY d.id, d.nama_dokter, s.nama_spesialis
    ORDER BY d.nama_dokter
";
$result = $conn->query($sql);

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Feedback Dokter");

// Header
$sheet->setCellValue("A1", "Nama Dokter");
$sheet->setCellValue("B1", "Spesialis");
$sheet->setCellValue("C1", "Total Like");
$sheet->setCellValue("D1", "Total Dislike");

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A$rowNum", $row['nama_dokter']);
    $sheet->setCellValue("B$rowNum", $row['nama_spesialis']);
    $sheet->setCellValue("C$rowNum", $row['total_like']);
    $sheet->setCellValue("D$rowNum", $row['total_dislike']);
    $rowNum++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="feedback_dokter.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
