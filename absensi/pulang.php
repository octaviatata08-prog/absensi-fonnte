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
// CARI ABSENSI HARI INI
// ==========================================

$cek = $conn->prepare("
    SELECT
        id,
        jam_masuk,
        jam_pulang
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
// BELUM ABSEN MASUK
// ==========================================

if ($result->num_rows === 0) {

    header(
        "Location: index.php?pesan=belum_masuk"
    );

    exit;
}


$data = $result->fetch_assoc();


// ==========================================
// SUDAH ABSEN PULANG
// ==========================================

if (
    !empty($data["jam_pulang"]) &&
    $data["jam_pulang"] !== "00:00:00"
) {

    header(
        "Location: index.php?pesan=sudah_pulang"
    );

    exit;
}


// ==========================================
// UPDATE JAM PULANG
// ==========================================

$stmt = $conn->prepare("
    UPDATE absensi
    SET jam_pulang = ?
    WHERE id = ?
");

$stmt->bind_param(
    "si",
    $jam,
    $data["id"]
);

$update = $stmt->execute();


// ==========================================
// BERHASIL
// ==========================================

if ($update) {

    // ======================================
    // FORMAT TANGGAL
    // ======================================

    $tanggal_format = date(
        "d-m-Y",
        strtotime($tanggal)
    );


    // ======================================
    // JAM PULANG WIB
    // ======================================

    $jam_format = date(
        "H:i:s",
        strtotime($jam)
    );


    // ======================================
    // PESAN KARYAWAN
    // ======================================

    $pesan_karyawan =
        "🔵 ABSENSI PULANG\n\n" .
        "Halo {$nama}! 👋\n\n" .
        "Absensi pulang kamu berhasil dicatat.\n\n" .
        "📅 Tanggal : {$tanggal_format}\n" .
        "⏰ Jam Pulang : {$jam_format} WIB\n" .
        "📌 Status : HADIR\n\n" .
        "Terima kasih, hati-hati di perjalanan. 😊";


    // ======================================
    // PESAN ADMIN
    // ======================================

    $pesan_admin =
        "🔵 ABSENSI PULANG KARYAWAN\n\n" .
        "👤 Nama : {$nama}\n" .
        "🆔 ID Karyawan : {$id_karyawan}\n" .
        "📅 Tanggal : {$tanggal_format}\n" .
        "⏰ Jam Pulang : {$jam_format} WIB\n" .
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
    // KEMBALI
    // ======================================

    header(
        "Location: index.php?pesan=pulang"
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