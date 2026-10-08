<?php

session_start();

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    die("Akses ditolak.");
}

require_once "../config/database.php";
require_once "../vendor/autoload.php";

$mulai = $_GET["mulai"] ?? date("Y-m-01");
$akhir = $_GET["akhir"] ?? date("Y-m-d");

$sql = "
    SELECT
        a.tanggal,
        k.nik,
        k.nama,
        k.jabatan,
        a.jam_masuk,
        a.jam_pulang,
        a.status
    FROM absensi a
    INNER JOIN karyawan k
        ON a.id_karyawan = k.id
    WHERE a.tanggal BETWEEN ? AND ?
    ORDER BY a.tanggal DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query gagal: " . $conn->error);
}

$stmt->bind_param("ss", $mulai, $akhir);
$stmt->execute();

$result = $stmt->get_result();

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Laporan Absensi");

$sheet->setCellValue("A1", "LAPORAN ABSENSI KARYAWAN");

$sheet->setCellValue(
    "A2",
    "Periode: " .
    date("d/m/Y", strtotime($mulai)) .
    " - " .
    date("d/m/Y", strtotime($akhir))
);

$sheet->setCellValue("A4", "No");
$sheet->setCellValue("B4", "Tanggal");
$sheet->setCellValue("C4", "NIK");
$sheet->setCellValue("D4", "Nama");
$sheet->setCellValue("E4", "Jabatan");
$sheet->setCellValue("F4", "Jam Masuk");
$sheet->setCellValue("G4", "Jam Pulang");
$sheet->setCellValue("H4", "Status");

$sheet->getStyle("A1")->getFont()->setBold(true);
$sheet->getStyle("A4:H4")->getFont()->setBold(true);

$row = 5;
$no = 1;

while ($data = $result->fetch_assoc()) {

    $sheet->setCellValue("A" . $row, $no);

    $sheet->setCellValue(
        "B" . $row,
        date("d/m/Y", strtotime($data["tanggal"]))
    );

    $sheet->setCellValue("C" . $row, $data["nik"]);
    $sheet->setCellValue("D" . $row, $data["nama"]);
    $sheet->setCellValue("E" . $row, $data["jabatan"]);

    $sheet->setCellValue(
        "F" . $row,
        $data["jam_masuk"] ?? "-"
    );

    $sheet->setCellValue(
        "G" . $row,
        $data["jam_pulang"] ?? "-"
    );

    $sheet->setCellValue(
        "H" . $row,
        $data["status"]
    );

    $row++;
    $no++;
}

$sheet->getColumnDimension("A")->setWidth(8);
$sheet->getColumnDimension("B")->setWidth(15);
$sheet->getColumnDimension("C")->setWidth(15);
$sheet->getColumnDimension("D")->setWidth(25);
$sheet->getColumnDimension("E")->setWidth(20);
$sheet->getColumnDimension("F")->setWidth(15);
$sheet->getColumnDimension("G")->setWidth(15);
$sheet->getColumnDimension("H")->setWidth(15);

$filename =
    "laporan_absensi_" .
    date("Ymd", strtotime($mulai)) .
    "_" .
    date("Ymd", strtotime($akhir)) .
    ".xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=" . $filename
);

header("Cache-Control: max-age=0");

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

$writer->save("php://output");

$stmt->close();

exit;