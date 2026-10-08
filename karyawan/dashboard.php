<?php
session_start();

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

if ($_SESSION["role"] !== "karyawan") {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once "../config/database.php";

$id_karyawan = $_SESSION["id_karyawan"];
$nama        = $_SESSION["nama"];
$nik         = $_SESSION["nik"];
$jabatan     = $_SESSION["jabatan"];
$no_hp       = $_SESSION["no_hp"];
$email       = $_SESSION["email"];

// =========================
// STATISTIK ABSENSI
// =========================

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk != '' THEN 1 ELSE 0 END) AS masuk,
        SUM(CASE WHEN jam_pulang IS NOT NULL AND jam_pulang != '' THEN 1 ELSE 0 END) AS pulang
    FROM absensi
    WHERE id_karyawan = ?
");

$stmt->bind_param("i", $id_karyawan);
$stmt->execute();

$stats = $stmt->get_result()->fetch_assoc();

$total_absensi = $stats["total"] ?? 0;
$total_masuk   = $stats["masuk"] ?? 0;
$total_pulang  = $stats["pulang"] ?? 0;

$stmt->close();

// =========================
// ABSENSI TERBARU
// =========================

$stmt = $conn->prepare("
    SELECT tanggal, jam_masuk, jam_pulang, status
    FROM absensi
    WHERE id_karyawan = ?
    ORDER BY tanggal DESC, id DESC
    LIMIT 5
");

$stmt->bind_param("i", $id_karyawan);
$stmt->execute();

$riwayat = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Karyawan</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    min-height: 100vh;
    background: linear-gradient(
        135deg,
        #f7eaff,
        #e8f2ff,
        #ffe8f4
    );
    color: #554866;
}

/* =========================
   NAVBAR
========================= */

.navbar {
    height: 72px;
    padding: 0 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;

    background: rgba(255,255,255,.65);
    backdrop-filter: blur(18px);

    border-bottom: 1px solid rgba(255,255,255,.8);

    position: sticky;
    top: 0;
    z-index: 10;
}

.brand {
    font-size: 20px;
    font-weight: bold;
    color: #624d7d;
}

.brand span {
    color: #9b7bd0;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-right a {
    text-decoration: none;
    color: #655875;
    font-size: 14px;
    padding: 9px 13px;
    border-radius: 12px;
}

.nav-right a:hover {
    background: rgba(180,150,230,.15);
}

.logout {
    background: #f6dce8 !important;
    color: #a34e70 !important;
}

/* =========================
   CONTAINER
========================= */

.container {
    width: 90%;
    max-width: 1200px;
    margin: 35px auto;
}

/* =========================
   WELCOME
========================= */

.welcome {
    background: rgba(255,255,255,.68);
    backdrop-filter: blur(18px);
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 15px 40px rgba(90,70,130,.10);
    margin-bottom: 25px;
}

.welcome h1 {
    font-size: 28px;
    color: #59476f;
    margin-bottom: 8px;
}

.welcome p {
    color: #888;
}

/* =========================
   STAT CARDS
========================= */

.stats {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat {
    background: rgba(255,255,255,.68);
    backdrop-filter: blur(15px);
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 12px 30px rgba(90,70,130,.09);
}

.stat-icon {
    font-size: 28px;
    margin-bottom: 10px;
}

.stat h3 {
    font-size: 28px;
    color: #5d4a75;
}

.stat p {
    color: #888;
    margin-top: 5px;
    font-size: 14px;
}

/* =========================
   MAIN GRID
========================= */

.main-grid {
    display: grid;
    grid-template-columns: 1fr 1.6fr;
    gap: 20px;
}

/* =========================
   CARD
========================= */

.card {
    background: rgba(255,255,255,.68);
    backdrop-filter: blur(18px);
    border-radius: 25px;
    padding: 25px;
    box-shadow: 0 12px 30px rgba(90,70,130,.09);
}

.card h2 {
    color: #5d4a75;
    margin-bottom: 18px;
}

/* =========================
   PROFILE
========================= */

.profile-item {
    margin-bottom: 15px;
}

.profile-item small {
    display: block;
    color: #999;
    margin-bottom: 4px;
}

.profile-item strong {
    color: #5c4b6d;
}

/* =========================
   BUTTON
========================= */

.buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 20px;
}

.btn {
    text-align: center;
    text-decoration: none;
    padding: 13px;
    border-radius: 14px;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.btn-masuk {
    background: linear-gradient(135deg,#9fdfb7,#6fc995);
}

.btn-pulang {
    background: linear-gradient(135deg,#ffb5ca,#e990ac);
}

.btn-riwayat {
    grid-column: 1 / -1;
    background: linear-gradient(135deg,#a98be5,#79bde9);
}

/* =========================
   TABLE
========================= */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    color: #766782;
    font-size: 13px;
    padding: 12px 8px;
    border-bottom: 1px solid #eadff2;
}

td {
    padding: 13px 8px;
    font-size: 13px;
    color: #777;
    border-bottom: 1px solid rgba(230,220,240,.7);
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 10px;
    background: #e2f7ea;
    color: #4c9b68;
    font-size: 11px;
    font-weight: bold;
}

/* =========================
   RESPONSIVE
========================= */

@media(max-width:850px) {

    .main-grid {
        grid-template-columns: 1fr;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .nav-right a:not(.logout) {
        display: none;
    }
}

@media(max-width:500px) {

    .container {
        width: 94%;
    }

    .welcome h1 {
        font-size: 22px;
    }

    .buttons {
        grid-template-columns: 1fr;
    }

    .btn-riwayat {
        grid-column: auto;
    }

    .navbar {
        padding: 0 4%;
    }

    .brand {
        font-size: 17px;
    }
}

</style>

</head>

<body>

<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <div class="brand">
        🌸 Sistem <span>Absensi</span>
    </div>

    <div class="nav-right">

        <a href="dashboard.php">
            🏠 Dashboard
        </a>

        <a href="../absensi/index.php">
            📋 Riwayat Absensi
        </a>

        <a href="../auth/logout.php" class="logout">
            🚪 Keluar
        </a>

    </div>

</nav>


<div class="container">

    <!-- WELCOME -->

    <div class="welcome">

        <h1>
            Halo, <?= htmlspecialchars($nama) ?>! 👋
        </h1>

        <p>
            Selamat datang di Dashboard Karyawan.
            Jangan lupa melakukan absensi hari ini.
        </p>

    </div>


    <!-- STATISTIK -->

    <div class="stats">

        <div class="stat">

            <div class="stat-icon">📋</div>

            <h3>
                <?= $total_absensi ?>
            </h3>

            <p>Total Absensi</p>

        </div>


        <div class="stat">

            <div class="stat-icon">🟢</div>

            <h3>
                <?= $total_masuk ?>
            </h3>

            <p>Total Absen Masuk</p>

        </div>


        <div class="stat">

            <div class="stat-icon">🔵</div>

            <h3>
                <?= $total_pulang ?>
            </h3>

            <p>Total Absen Pulang</p>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="main-grid">

        <!-- PROFILE -->

        <div class="card">

            <h2>👤 Profil Saya</h2>

            <div class="profile-item">
                <small>NIK</small>
                <strong>
                    <?= htmlspecialchars($nik) ?>
                </strong>
            </div>

            <div class="profile-item">
                <small>Nama</small>
                <strong>
                    <?= htmlspecialchars($nama) ?>
                </strong>
            </div>

            <div class="profile-item">
                <small>Jabatan</small>
                <strong>
                    <?= htmlspecialchars($jabatan) ?>
                </strong>
            </div>

            <div class="profile-item">
                <small>No. HP</small>
                <strong>
                    <?= htmlspecialchars($no_hp) ?>
                </strong>
            </div>

            <div class="profile-item">
                <small>Email</small>
                <strong>
                    <?= htmlspecialchars($email) ?>
                </strong>
            </div>


            <div class="buttons">

                <a
                    href="../absensi/masuk.php"
                    class="btn btn-masuk"
                >
                    🟢 Absen Masuk
                </a>

                <a
                    href="../absensi/pulang.php"
                    class="btn btn-pulang"
                >
                    🔵 Absen Pulang
                </a>

                <a
                    href="../absensi/index.php"
                    class="btn btn-riwayat"
                >
                    📋 Lihat Riwayat Absensi
                </a>

            </div>

        </div>


        <!-- RIWAYAT -->

        <div class="card">

            <h2>📊 Absensi Terbaru</h2>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($riwayat->num_rows > 0): ?>

                        <?php while ($row = $riwayat->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($row["tanggal"]) ?>
                                </td>

                                <td>
                                    <?= $row["jam_masuk"]
                                        ? htmlspecialchars($row["jam_masuk"])
                                        : "-"
                                    ?>
                                </td>

                                <td>
                                    <?= $row["jam_pulang"]
                                        ? htmlspecialchars($row["jam_pulang"])
                                        : "-"
                                    ?>
                                </td>

                                <td>

                                    <span class="status">
                                        <?= htmlspecialchars($row["status"]) ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="4" style="text-align:center;">
                                Belum ada data absensi.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>