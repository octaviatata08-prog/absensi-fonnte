<?php

session_start();

require_once "../config/database.php";
require_once "../config/fonnte.php";

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: index.php");
    exit;
}

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* =========================
   AMBIL DATA ABSENSI
========================= */

$stmt = $conn->prepare("
    SELECT
        a.id,
        a.tanggal,
        a.jam_masuk,
        a.jam_pulang,
        a.status,
        k.nama,
        k.no_hp
    FROM absensi a
    INNER JOIN karyawan k
        ON a.id_karyawan = k.id
    WHERE a.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?pesan=gagal_hapus");
    exit;
}

$data = $result->fetch_assoc();

$stmt->close();


/* =========================
   DATA UNTUK WHATSAPP
========================= */

$nama = $data["nama"];
$no_hp = $data["no_hp"];
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

$status = $data["status"];


/* =========================
   HAPUS DATA
========================= */

$hapus = $conn->prepare("
    DELETE FROM absensi
    WHERE id = ?
");

$hapus->bind_param("i", $id);

if (!$hapus->execute()) {
    $hapus->close();

    header("Location: index.php?pesan=gagal_hapus");
    exit;
}

$hapus->close();


/* =========================
   KIRIM WHATSAPP
========================= */

$pesanWA =
"📢 NOTIFIKASI ABSENSI

Halo " . $nama . ",

Data absensi kamu telah dihapus oleh Admin.

📅 Tanggal: " . $tanggal . "
🕐 Jam Masuk: " . $jamMasuk . "
🕐 Jam Pulang: " . $jamPulang . "
📌 Status: " . $status . "

Jika penghapusan data ini tidak sesuai, silakan hubungi Admin.

Terima kasih.
Sistem Absensi Karyawan";


$hasilWA = kirimWA(
    $no_hp,
    $pesanWA
);


/* =========================
   KEMBALI KE DATA ABSENSI
========================= */

if (
    is_array($hasilWA) &&
    ($hasilWA["status"] ?? false) === true
) {
    header(
        "Location: index.php?pesan=hapus&wa=berhasil"
    );
} else {
    header(
        "Location: index.php?pesan=hapus&wa=gagal"
    );
}

exit;