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
    ORDER BY a.tanggal DESC, a.jam_masuk DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query gagal: " . $conn->error);
}

$stmt->bind_param("ss", $mulai, $akhir);
$stmt->execute();

$result = $stmt->get_result();

$dataAbsensi = [];

while ($data = $result->fetch_assoc()) {
    $dataAbsensi[] = $data;
}

$totalData = count($dataAbsensi);
$totalHadir = 0;
$totalBelumPulang = 0;

foreach ($dataAbsensi as $data) {

    if (strtoupper($data["status"]) === "HADIR") {
        $totalHadir++;
    }

    if (
        !empty($data["jam_masuk"]) &&
        empty($data["jam_pulang"])
    ) {
        $totalBelumPulang++;
    }
}

$html = '
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
}

h1 {
    text-align: center;
    margin-bottom: 5px;
}

.periode {
    text-align: center;
    margin-bottom: 20px;
}

.ringkasan {
    width: 100%;
    margin-bottom: 15px;
}

.ringkasan td {
    border: 1px solid #999;
    padding: 7px;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background-color: #eeeeee;
    font-weight: bold;
}

th,
td {
    border: 1px solid #777;
    padding: 6px;
    text-align: center;
}

.footer {
    margin-top: 20px;
    text-align: right;
    font-size: 9px;
}

</style>

</head>

<body>

<h1>LAPORAN ABSENSI KARYAWAN</h1>

<div class="periode">
Periode:
' . date("d/m/Y", strtotime($mulai)) . '
-
' . date("d/m/Y", strtotime($akhir)) . '
</div>

<table class="ringkasan">

<tr>
<td>
<b>Total Data</b><br>
' . $totalData . '
</td>

<td>
<b>Total Hadir</b><br>
' . $totalHadir . '
</td>

<td>
<b>Belum Pulang</b><br>
' . $totalBelumPulang . '
</td>
</tr>

</table>

<table>

<thead>

<tr>
<th>No</th>
<th>Tanggal</th>
<th>NIK</th>
<th>Nama</th>
<th>Jabatan</th>
<th>Jam Masuk</th>
<th>Jam Pulang</th>
<th>Status</th>
</tr>

</thead>

<tbody>
';

$no = 1;

foreach ($dataAbsensi as $data) {

    $tanggal = date(
        "d/m/Y",
        strtotime($data["tanggal"])
    );

    $jamMasuk = !empty($data["jam_masuk"])
        ? $data["jam_masuk"]
        : "-";

    $jamPulang = !empty($data["jam_pulang"])
        ? $data["jam_pulang"]
        : "-";

    $html .= '
    <tr>

        <td>' . $no . '</td>

        <td>' . $tanggal . '</td>

        <td>' . htmlspecialchars($data["nik"]) . '</td>

        <td>' . htmlspecialchars($data["nama"]) . '</td>

        <td>' . htmlspecialchars($data["jabatan"]) . '</td>

        <td>' . $jamMasuk . '</td>

        <td>' . $jamPulang . '</td>

        <td>' . htmlspecialchars($data["status"]) . '</td>

    </tr>
    ';

    $no++;
}

if ($totalData === 0) {

    $html .= '
    <tr>
        <td colspan="8">
            Tidak ada data absensi pada periode ini.
        </td>
    </tr>
    ';
}

$html .= '

</tbody>

</table>

<div class="footer">
Dicetak pada:
' . date("d/m/Y H:i") . '
</div>

</body>
</html>
';

$dompdf = new \Dompdf\Dompdf();

$dompdf->setPaper("A4", "landscape");

$dompdf->loadHtml($html);

$dompdf->render();

$filename =
    "laporan_absensi_" .
    date("Ymd", strtotime($mulai)) .
    "_" .
    date("Ymd", strtotime($akhir)) .
    ".pdf";

$dompdf->stream(
    $filename,
    [
        "Attachment" => true
    ]
);

$stmt->close();

exit;