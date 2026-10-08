```php
<?php

session_start();

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../karyawan/dashboard.php");
    exit;
}

require_once "../config/database.php";

$mulai = !empty($_GET["mulai"])
    ? $_GET["mulai"]
    : date("Y-m-01");

$akhir = !empty($_GET["akhir"])
    ? $_GET["akhir"]
    : date("Y-m-d");

$stmt = $conn->prepare("
    SELECT
        absensi.id,
        absensi.tanggal,
        karyawan.nik,
        karyawan.nama,
        karyawan.jabatan,
        absensi.jam_masuk,
        absensi.jam_pulang,
        absensi.status
    FROM absensi
    INNER JOIN karyawan
        ON absensi.id_karyawan = karyawan.id
    WHERE absensi.tanggal BETWEEN ? AND ?
    ORDER BY absensi.tanggal DESC, absensi.id DESC
");

$stmt->bind_param("ss", $mulai, $akhir);
$stmt->execute();

$result = $stmt->get_result();

$dataLaporan = [];

$totalData = 0;
$totalHadir = 0;
$totalBelumPulang = 0;

while ($row = $result->fetch_assoc()) {

    $dataLaporan[] = $row;
    $totalData++;

    if (strtoupper($row["status"]) === "HADIR") {
        $totalHadir++;
    }

    if (
        !empty($row["jam_masuk"]) &&
        $row["jam_masuk"] !== "00:00:00" &&
        (
            empty($row["jam_pulang"]) ||
            $row["jam_pulang"] === "00:00:00"
        )
    ) {
        $totalBelumPulang++;
    }
}

$stmt->close();

function formatTanggal($tanggal)
{
    return date("d/m/Y", strtotime($tanggal));
}

function formatJam($jam)
{
    if (
        empty($jam) ||
        $jam === "00:00:00" ||
        $jam === "00:00"
    ) {
        return "-";
    }

    return date("H:i", strtotime($jam));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laporan Absensi</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 30px;
            color: #334155;

            background:
                radial-gradient(
                    circle at top left,
                    #e5ddff,
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    #d9ecff,
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #f5f3ff,
                    #eef7ff
                );
        }

        .container {
            max-width: 1250px;
            margin: auto;
        }

        .card {
            background: rgba(255,255,255,.78);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,.8);
            border-radius: 22px;
            box-shadow: 0 12px 35px rgba(91,78,130,.10);
        }

        .header {
            padding: 25px;
            margin-bottom: 20px;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .title-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .icon {
            width: 58px;
            height: 58px;
            border-radius: 17px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                #ddd6fe,
                #bfdbfe
            );

            font-size: 28px;
        }

        h1 {
            color: #4338ca;
            font-size: 27px;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .back-btn {
            text-decoration: none;
            color: #475569;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 12px 18px;
            border-radius: 13px;
            font-weight: bold;
        }

        .filter {
            padding: 22px;
            margin-bottom: 20px;
        }

        .filter-title {
            color: #4338ca;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        label {
            display: block;
            color: #64748b;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input[type="date"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dbe3ef;
            border-radius: 12px;
            outline: none;
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
        }

        .btn-filter {
            background: linear-gradient(
                135deg,
                #6366f1,
                #8b5cf6
            );
            color: white;
        }

        .export-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .btn-excel {
            background: #dcfce7;
            color: #15803d;
        }

        .btn-pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-print {
            background: #e0e7ff;
            color: #4338ca;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        .stat {
            padding: 22px;
        }

        .stat-icon {
            font-size: 25px;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
            color: #4338ca;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            margin-top: 5px;
        }

        .table-card {
            padding: 24px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 15px;
        }

        .table-title {
            font-size: 19px;
            font-weight: bold;
            color: #4338ca;
        }

        .periode {
            background: #f8fafc;
            color: #64748b;
            padding: 9px 13px;
            border-radius: 10px;
            font-size: 13px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #eef2ff;
            color: #4338ca;
            padding: 14px 12px;
            text-align: left;
            font-size: 13px;
        }

        td {
            padding: 13px 12px;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
        }

        tr:hover td {
            background: #f8faff;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: bold;
        }

        .hadir {
            background: #dcfce7;
            color: #15803d;
        }

        .lain {
            background: #fef3c7;
            color: #92400e;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        @media (max-width: 800px) {

            body {
                padding: 15px;
            }

            .header-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .back-btn {
                width: 100%;
                text-align: center;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .filter,
            .back-btn,
            .export-buttons {
                display: none !important;
            }

            .card,
            .stat,
            .table-card {
                box-shadow: none;
                border: none;
                background: white;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="card header">

        <div class="header-top">

            <div class="title-box">

                <div class="icon">
                    📊
                </div>

                <div>

                    <h1>
                        Laporan Absensi
                    </h1>

                    <div class="subtitle">
                        Rekap data kehadiran karyawan
                    </div>

                </div>

            </div>

            <a
                href="../admin/dashboard.php"
                class="back-btn"
            >
                ← Kembali
            </a>

        </div>

    </div>


    <!-- FILTER -->

    <div class="card filter">

        <div class="filter-title">
            🔎 Filter Laporan
        </div>

        <form
            method="GET"
            class="filter-form"
        >

            <div>

                <label>
                    Tanggal Mulai
                </label>

                <input
                    type="date"
                    name="mulai"
                    value="<?= htmlspecialchars($mulai) ?>"
                    required
                >

            </div>

            <div>

                <label>
                    Tanggal Akhir
                </label>

                <input
                    type="date"
                    name="akhir"
                    value="<?= htmlspecialchars($akhir) ?>"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn btn-filter"
            >
                🔍 Tampilkan
            </button>

        </form>


        <div class="export-buttons">

            <a
                href="export_excel.php?mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>"
                class="btn btn-excel"
            >
                📊 Export Excel
            </a>

            <a
                href="export_pdf.php?mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>"
                class="btn btn-pdf"
                target="_blank"
            >
                📄 Export PDF
            </a>

            <button
                type="button"
                onclick="window.print()"
                class="btn btn-print"
            >
                🖨️ Cetak
            </button>

        </div>

    </div>


    <!-- STATISTIK -->

    <div class="stats">

        <div class="card stat">

            <div class="stat-icon">
                📋
            </div>

            <div class="stat-number">
                <?= $totalData ?>
            </div>

            <div class="stat-label">
                Total Data Absensi
            </div>

        </div>


        <div class="card stat">

            <div class="stat-icon">
                ✅
            </div>

            <div class="stat-number">
                <?= $totalHadir ?>
            </div>

            <div class="stat-label">
                Total Kehadiran
            </div>

        </div>


        <div class="card stat">

            <div class="stat-icon">
                🕐
            </div>

            <div class="stat-number">
                <?= $totalBelumPulang ?>
            </div>

            <div class="stat-label">
                Belum Absen Pulang
            </div>

        </div>

    </div>


    <!-- TABEL -->

    <div class="card table-card">

        <div class="table-header">

            <div class="table-title">
                Detail Laporan
            </div>

            <div class="periode">

                <?= formatTanggal($mulai) ?>

                -

                <?= formatTanggal($akhir) ?>

            </div>

        </div>


        <div class="table-wrapper">

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

                <?php if (!empty($dataLaporan)): ?>

                    <?php foreach ($dataLaporan as $index => $row): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>
                                <?= formatTanggal($row["tanggal"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["nik"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["nama"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["jabatan"]) ?>
                            </td>

                            <td>
                                <?= formatJam($row["jam_masuk"]) ?>
                            </td>

                            <td>
                                <?= formatJam($row["jam_pulang"]) ?>
                            </td>

                            <td>

                                <?php if (
                                    strtoupper($row["status"]) === "HADIR"
                                ): ?>

                                    <span class="status hadir">
                                        HADIR
                                    </span>

                                <?php else: ?>

                                    <span class="status lain">
                                        <?= htmlspecialchars($row["status"]) ?>
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >
                            📭 Tidak ada data absensi
                            pada periode tersebut.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>
```
