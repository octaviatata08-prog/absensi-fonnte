<?php

// ==========================================
// TIMEZONE INDONESIA
// ==========================================

date_default_timezone_set("Asia/Jakarta");


// ==========================================
// SESSION
// ==========================================

session_start();


// ==========================================
// CEK LOGIN
// ==========================================

if (
    !isset($_SESSION["login"]) ||
    $_SESSION["login"] !== true
) {

    header("Location: ../auth/login.php");

    exit;
}


// ==========================================
// CONFIG
// ==========================================

require_once "../config/database.php";
require_once "../config/fonnte.php";
require_once "../config/admin.php";


// ==========================================
// DATA SESSION
// ==========================================

$id_karyawan = $_SESSION["id_karyawan"];
$nama        = $_SESSION["nama"];
$no_hp       = $_SESSION["no_hp"];


// ==========================================
// WAKTU SEKARANG WIB
// ==========================================

$tanggal = date("Y-m-d");
$jam     = date("H:i:s");


// ==========================================
// CEK ABSEN HARI INI
// ==========================================

$cek = $conn->prepare("
    SELECT id
    FROM absensi
    WHERE id_karyawan = ?
    AND tanggal = ?
    LIMIT 1
");

$cek->bind_param(
    "is",
    $id_karyawan,
    $tanggal
);

$cek->execute();

$result = $cek->get_result();


// ==========================================
// SUDAH ABSEN
// ==========================================

if ($result->num_rows > 0) {

    header(
        "Location: index.php?pesan=sudah_masuk"
    );

    exit;
}


// ==========================================
// SIMPAN ABSEN
// ==========================================

$status = "HADIR";

$stmt = $conn->prepare("
    INSERT INTO absensi
    (
        id_karyawan,
        tanggal,
        jam_masuk,
        status
    )
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "isss",
    $id_karyawan,
    $tanggal,
    $jam,
    $status
);

$simpan = $stmt->execute();


// ==========================================
// BERHASIL
// ==========================================

if ($simpan) {

    // ======================================
    // FORMAT TANGGAL
    // ======================================

    $tanggal_format = date(
        "d-m-Y",
        strtotime($tanggal)
    );


    // ======================================
    // JAM UNTUK WHATSAPP
    // ======================================

    $jam_format = date(
        "H:i:s",
        strtotime($jam)
    );


    // ======================================
    // PESAN KARYAWAN
    // ======================================

    $pesan_karyawan =
        "🟢 ABSENSI MASUK\n\n" .
        "Halo {$nama}! 👋\n\n" .
        "Absensi masuk kamu berhasil dicatat.\n\n" .
        "📅 Tanggal : {$tanggal_format}\n" .
        "⏰ Jam Masuk : {$jam_format} WIB\n" .
        "📌 Status : HADIR\n\n" .
        "Terima kasih sudah melakukan absensi. 😊";


    // ======================================
    // PESAN ADMIN
    // ======================================

    $pesan_admin =
        "🟢 ABSENSI MASUK KARYAWAN\n\n" .
        "👤 Nama : {$nama}\n" .
        "🆔 ID Karyawan : {$id_karyawan}\n" .
        "📅 Tanggal : {$tanggal_format}\n" .
        "⏰ Jam Masuk : {$jam_format} WIB\n" .
        "📌 Status : HADIR";


    // ======================================
    // KIRIM WA KE KARYAWAN
    // ======================================

    $curl = curl_init();

    curl_setopt_array($curl, [

        CURLOPT_URL =>
            "https://api.fonnte.com/send",

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS => [
            "target" => $no_hp,
            "message" => $pesan_karyawan
        ],

        CURLOPT_HTTPHEADER => [
            "Authorization: " . $FONNTE_TOKEN
        ],

        CURLOPT_TIMEOUT => 30

    ]);

    $response_karyawan =
        curl_exec($curl);

    curl_close($curl);


    // ======================================
    // NOMOR ADMIN
    // ======================================

    $nomor_admin = "";


    if (isset($admin_no_hp)) {

        $nomor_admin = $admin_no_hp;

    } elseif (isset($no_hp_admin)) {

        $nomor_admin = $no_hp_admin;

    } elseif (isset($admin_phone)) {

        $nomor_admin = $admin_phone;

    }


    // ======================================
    // KIRIM WA KE ADMIN
    // ======================================

    if ($nomor_admin !== "") {

        $curl = curl_init();

        curl_setopt_array($curl, [

            CURLOPT_URL =>
                "https://api.fonnte.com/send",

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_POSTFIELDS => [
                "target" => $nomor_admin,
                "message" => $pesan_admin
            ],

            CURLOPT_HTTPHEADER => [
                "Authorization: " . $FONNTE_TOKEN
            ],

            CURLOPT_TIMEOUT => 30

        ]);

        $response_admin =
            curl_exec($curl);

        curl_close($curl);
    }


    // ======================================
    // KEMBALI KE DATA ABSENSI
    // ======================================

    header(
        "Location: index.php?pesan=masuk"
    );

    exit;
}


// ==========================================
// GAGAL
// ==========================================

header(
    "Location: index.php?pesan=gagal"
);

exit;