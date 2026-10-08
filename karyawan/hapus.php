<?php

session_start();

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../config/fonnte.php";
require_once "../config/admin.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


// =============================
// AMBIL DATA KARYAWAN
// =============================

$stmt = $conn->prepare("
    SELECT nama, no_hp, nik, jabatan, email
    FROM karyawan
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$karyawan = $result->fetch_assoc();

$stmt->close();


// Jika data tidak ditemukan
if (!$karyawan) {
    header("Location: index.php");
    exit;
}


// =============================
// SIMPAN DATA SEBELUM DIHAPUS
// =============================

$nama = $karyawan["nama"];
$no_hp = $karyawan["no_hp"];
$nik = $karyawan["nik"];
$jabatan = $karyawan["jabatan"];
$email = $karyawan["email"];


// =============================
// HAPUS DATA
// =============================

$stmt = $conn->prepare("
    DELETE FROM karyawan
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    $stmt->close();


    // =============================
    // NOTIFIKASI KE KARYAWAN
    // =============================

    $pesanKaryawan = "Halo $nama 👋

Data karyawan kamu telah dihapus dari Sistem Absensi Karyawan.

NIK : $nik
Nama : $nama
Jabatan : $jabatan

Jika penghapusan ini tidak sesuai, silakan hubungi admin.

Terima kasih.";

    kirimFonnte($no_hp, $pesanKaryawan);


    // =============================
    // NOTIFIKASI KE ADMIN
    // =============================

    $pesanAdmin = "📢 DATA KARYAWAN DIHAPUS

Data karyawan telah dihapus dari sistem.

NIK : $nik
Nama : $nama
Jabatan : $jabatan
WhatsApp : $no_hp
Email : $email

Data karyawan berhasil dihapus dari Sistem Absensi Karyawan.";

    kirimFonnte($admin_wa, $pesanAdmin);


    // =============================
    // KEMBALI
    // =============================

    header("Location: index.php?pesan=hapus");
    exit;

} else {

    $stmt->close();

    header("Location: index.php?pesan=gagal_hapus");
    exit;
}