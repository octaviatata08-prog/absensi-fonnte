```php
<?php

session_start();

if (
    !isset($_SESSION["login"]) ||
    $_SESSION["login"] !== true ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../auth/index.php");
    exit;
}

require_once "../config/database.php";

date_default_timezone_set("Asia/Jakarta");

$nama = $_SESSION["nama"] ?? "Admin";


// =====================================================
// DATA STATISTIK
// =====================================================

$totalKaryawan = 0;
$totalAdmin = 0;
$totalHadirHariIni = 0;


// Total karyawan

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM karyawan
    WHERE role = 'karyawan'
");

if ($result) {

    $data = $result->fetch_assoc();

    $totalKaryawan = $data["total"] ?? 0;
}


// Total admin

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM karyawan
    WHERE role = 'admin'
");

if ($result) {

    $data = $result->fetch_assoc();

    $totalAdmin = $data["total"] ?? 0;
}


// Total absensi hari ini

$hariIni = date("Y-m-d");

$stmtHariIni = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM absensi
    WHERE tanggal = ?
");

$stmtHariIni->bind_param(
    "s",
    $hariIni
);

$stmtHariIni->execute();

$resultHariIni = $stmtHariIni->get_result();

if ($resultHariIni) {

    $data = $resultHariIni->fetch_assoc();

    $totalHadirHariIni = $data["total"] ?? 0;
}

$stmtHariIni->close();


// =====================================================
// DATA KARYAWAN TERBARU
// =====================================================

$karyawanTerbaru = $conn->query("
    SELECT
        id,
        nik,
        nama,
        jabatan,
        no_hp,
        email,
        role
    FROM karyawan
    ORDER BY id DESC
    LIMIT 5
");

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Admin - Sistem Absensi</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =====================================================
   BODY
===================================================== */

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    min-height: 100vh;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(255,255,255,.9),
            transparent 28%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(210,195,255,.45),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #e5edff,
            #f7eaff
        );

    color: #4d4968;

    padding: 25px;

}


/* =====================================================
   BUBBLES
===================================================== */

.bubble {

    position: fixed;

    border-radius: 50%;

    background:
        rgba(255,255,255,.30);

    backdrop-filter: blur(5px);

    pointer-events: none;

    z-index: 0;

}

.bubble.one {

    width: 180px;

    height: 180px;

    left: -70px;

    top: -50px;

}

.bubble.two {

    width: 130px;

    height: 130px;

    right: 5%;

    top: 8%;

}

.bubble.three {

    width: 220px;

    height: 220px;

    left: 8%;

    bottom: -90px;

}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    width: 100%;

    max-width: 1200px;

    margin: auto;

    position: relative;

    z-index: 2;

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    padding: 18px 22px;

    background:
        rgba(255,255,255,.70);

    backdrop-filter: blur(18px);

    border:
        1px solid
        rgba(255,255,255,.85);

    border-radius: 22px;

    box-shadow:
        0 15px 45px
        rgba(70,60,120,.12);

    margin-bottom: 25px;

}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

}


.brand-icon {

    width: 48px;

    height: 48px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 24px;

    background:
        linear-gradient(
            135deg,
            #bca9ef,
            #9fc8ef
        );

}


.brand-text h2 {

    font-size: 18px;

    color: #4e496d;

}


.brand-text span {

    font-size: 12px;

    color: #88839b;

}


/* =====================================================
   LOGOUT
===================================================== */

.logout {

    text-decoration: none;

    padding: 10px 16px;

    border-radius: 12px;

    background: #fff;

    color: #756d8e;

    font-size: 13px;

    font-weight: bold;

    border:
        1px solid
        #ebe7f4;

    transition: .25s;

}


.logout:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 18px
        rgba(70,60,120,.12);

}


/* =====================================================
   WELCOME
===================================================== */

.welcome {

    padding: 28px;

    border-radius: 25px;

    background:
        linear-gradient(
            135deg,
            rgba(180,165,235,.80),
            rgba(158,197,237,.80)
        );

    color: white;

    box-shadow:
        0 18px 40px
        rgba(100,90,160,.18);

    margin-bottom: 25px;

}


.welcome h1 {

    font-size: 27px;

    margin-bottom: 7px;

}


.welcome p {

    font-size: 14px;

    opacity: .92;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 25px;

}


.stat-card {

    background:
        rgba(255,255,255,.72);

    backdrop-filter: blur(15px);

    border:
        1px solid
        rgba(255,255,255,.8);

    border-radius: 21px;

    padding: 22px;

    box-shadow:
        0 12px 30px
        rgba(70,60,120,.10);

}


.stat-icon {

    font-size: 28px;

    margin-bottom: 12px;

}


.stat-card h3 {

    font-size: 28px;

    color: #514b70;

    margin-bottom: 4px;

}


.stat-card p {

    font-size: 13px;

    color: #88839b;

}


/* =====================================================
   SECTION
===================================================== */

.section {

    background:
        rgba(255,255,255,.72);

    backdrop-filter: blur(15px);

    border:
        1px solid
        rgba(255,255,255,.8);

    border-radius: 22px;

    padding: 24px;

    margin-bottom: 25px;

    box-shadow:
        0 12px 30px
        rgba(70,60,120,.10);

}


.section-title {

    font-size: 18px;

    color: #504a6e;

    margin-bottom: 18px;

}


/* =====================================================
   MENU GRID
===================================================== */

.menu-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

}


.menu-card {

    text-decoration: none;

    color: inherit;

    padding: 20px;

    border-radius: 17px;

    background:
        rgba(245,243,255,.75);

    border:
        1px solid
        #ebe7f5;

    transition: .25s;

}


.menu-card:hover {

    transform: translateY(-4px);

    background: white;

    box-shadow:
        0 10px 25px
        rgba(80,70,130,.12);

}


.menu-icon {

    font-size: 28px;

    margin-bottom: 10px;

}


.menu-card h3 {

    font-size: 14px;

    margin-bottom: 5px;

    color: #554f70;

}


.menu-card p {

    font-size: 12px;

    color: #89849a;

    line-height: 1.5;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 700px;

}


th {

    text-align: left;

    padding: 13px;

    background: #f3f0fc;

    color: #625c7b;

    font-size: 12px;

}


td {

    padding: 13px;

    border-bottom:
        1px solid
        #eeeaf5;

    font-size: 12px;

    color: #6e6980;

}


/* =====================================================
   BADGE
===================================================== */

.badge {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;

}


.badge.admin {

    background: #e7ddff;

    color: #7355b5;

}


.badge.karyawan {

    background: #dff2ff;

    color: #3979a4;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 950px) {

    .menu-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 800px) {

    .stats {

        grid-template-columns:
            1fr;

    }

    .menu-grid {

        grid-template-columns:
            1fr 1fr;

    }

    .navbar {

        align-items: flex-start;

    }

}


@media (max-width: 500px) {

    body {

        padding: 15px;

    }

    .navbar {

        flex-direction: column;

    }

    .logout {

        width: 100%;

        text-align: center;

    }

    .welcome h1 {

        font-size: 22px;

    }

    .menu-grid {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<div class="bubble one"></div>

<div class="bubble two"></div>

<div class="bubble three"></div>


<div class="container">


    <!-- =================================================
         NAVBAR
    ================================================== -->

    <div class="navbar">


        <div class="brand">


            <div class="brand-icon">
                🛡️
            </div>


            <div class="brand-text">

                <h2>
                    Sistem Absensi
                </h2>

                <span>
                    Panel Administrator
                </span>

            </div>


        </div>


        <a
            href="../auth/logout.php"
            class="logout"
        >
            Keluar
        </a>


    </div>


    <!-- =================================================
         WELCOME
    ================================================== -->

    <div class="welcome">


        <h1>

            Halo,
            <?= htmlspecialchars($nama) ?>
            👋

        </h1>


        <p>

            Selamat datang di Dashboard Administrator.
            Kelola data karyawan, absensi, laporan,
            dan WhatsApp dari sini.

        </p>


    </div>


    <!-- =================================================
         STATISTIK
    ================================================== -->

    <div class="stats">


        <!-- TOTAL KARYAWAN -->

        <div class="stat-card">


            <div class="stat-icon">
                👥
            </div>


            <h3>
                <?= $totalKaryawan ?>
            </h3>


            <p>
                Total Karyawan
            </p>


        </div>


        <!-- TOTAL ADMIN -->

        <div class="stat-card">


            <div class="stat-icon">
                🛡️
            </div>


            <h3>
                <?= $totalAdmin ?>
            </h3>


            <p>
                Total Admin
            </p>


        </div>


        <!-- ABSENSI HARI INI -->

        <div class="stat-card">


            <div class="stat-icon">
                📅
            </div>


            <h3>
                <?= $totalHadirHariIni ?>
            </h3>


            <p>
                Absensi Hari Ini
            </p>


        </div>


    </div>


    <!-- =================================================
         MENU UTAMA
    ================================================== -->

    <div class="section">


        <div class="section-title">
            Menu Utama
        </div>


        <div class="menu-grid">


            <!-- DATA ABSENSI -->

            <a
                href="../absensi/index.php"
                class="menu-card"
            >


                <div class="menu-icon">
                    📋
                </div>


                <h3>
                    Data Absensi
                </h3>


                <p>
                    Lihat dan kelola data absensi
                    karyawan.
                </p>


            </a>


            <!-- LAPORAN ABSENSI -->

            <a
                href="../absensi/laporan.php"
                class="menu-card"
            >


                <div class="menu-icon">
                    📊
                </div>


                <h3>
                    Laporan Absensi
                </h3>


                <p>
                    Lihat rekap dan laporan
                    kehadiran karyawan.
                </p>


            </a>


            <!-- TAMBAH KARYAWAN -->

            <a
                href="../auth/register.php"
                class="menu-card"
            >


                <div class="menu-icon">
                    👤
                </div>


                <h3>
                    Tambah Karyawan
                </h3>


                <p>
                    Daftarkan akun karyawan
                    baru.
                </p>


            </a>


            <!-- TEST WHATSAPP -->

            <a
                href="../test_api.php"
                class="menu-card"
            >


                <div class="menu-icon">
                    📱
                </div>
```
