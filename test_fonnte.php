<?php

require_once "config/database.php";
require_once "config/fonnte.php";

$id_karyawan = 1;

$stmt = $conn->prepare("
    SELECT nama, no_hp
    FROM karyawan
    WHERE id = ?
");

$stmt->bind_param("i", $id_karyawan);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

$stmt->close();

if (!$data) {
    die("Data karyawan tidak ditemukan.");
}

$nama = $data["nama"];
$no_hp = $data["no_hp"];

$pesan = "Halo $nama 👋

Ini adalah tes notifikasi dari Sistem Absensi Karyawan.

Nomor WhatsApp berhasil diambil dari database. ✅";

$response = kirimFonnte($no_hp, $pesan);

echo "<h2>Hasil Pengiriman Fonnte</h2>";

echo "<p>Nama: " . htmlspecialchars($nama) . "</p>";
echo "<p>Nomor: " . htmlspecialchars($no_hp) . "</p>";

echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";