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

$error = "";


/* =========================
   DATA KARYAWAN
========================= */

$karyawan = $conn->query("
    SELECT id, nik, nama, jabatan, no_hp
    FROM karyawan
    WHERE role = 'karyawan'
    ORDER BY nama ASC
");


/* =========================
   SIMPAN
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_karyawan = intval($_POST["id_karyawan"] ?? 0);
    $tanggal = $_POST["tanggal"] ?? "";
    $jam_masuk = $_POST["jam_masuk"] ?? "";
    $jam_pulang = $_POST["jam_pulang"] ?? "";
    $status = $_POST["status"] ?? "Hadir";


    if (
        $id_karyawan <= 0 ||
        empty($tanggal)
    ) {

        $error = "Karyawan dan tanggal wajib diisi.";

    } else {

        /* =========================
           CEK DUPLIKAT
        ========================= */

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

        $hasil = $cek->get_result();


        if ($hasil->num_rows > 0) {

            $error =
                "Karyawan tersebut sudah memiliki absensi pada tanggal itu.";

        } else {

            /* =========================
               AMBIL DATA KARYAWAN
            ========================= */

            $dataKaryawan = $conn->prepare("
                SELECT nama, no_hp
                FROM karyawan
                WHERE id = ?
                LIMIT 1
            ");

            $dataKaryawan->bind_param(
                "i",
                $id_karyawan
            );

            $dataKaryawan->execute();

            $hasilKaryawan =
                $dataKaryawan->get_result();

            $rowKaryawan =
                $hasilKaryawan->fetch_assoc();


            if (!$rowKaryawan) {

                $error = "Data karyawan tidak ditemukan.";

            } else {

                $namaKaryawan =
                    $rowKaryawan["nama"];

                $noHp =
                    $rowKaryawan["no_hp"];


                /* =========================
                   INSERT ABSENSI
                ========================= */

                $stmt = $conn->prepare("
                    INSERT INTO absensi
                    (
                        id_karyawan,
                        tanggal,
                        jam_masuk,
                        jam_pulang,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "issss",
                    $id_karyawan,
                    $tanggal,
                    $jam_masuk,
                    $jam_pulang,
                    $status
                );


                if ($stmt->execute()) {

                    /* =========================
                       FORMAT JAM
                    ========================= */

                    $tampilJamMasuk =
                        !empty($jam_masuk)
                        ? date("H:i", strtotime($jam_masuk))
                        : "-";

                    $tampilJamPulang =
                        !empty($jam_pulang)
                        ? date("H:i", strtotime($jam_pulang))
                        : "-";


                    /* =========================
                       FORMAT TANGGAL
                    ========================= */

                    $tampilTanggal =
                        date(
                            "d-m-Y",
                            strtotime($tanggal)
                        );


                    /* =========================
                       PESAN WHATSAPP
                    ========================= */

                    $pesanWA =
"📢 *NOTIFIKASI ABSENSI*

Halo *{$namaKaryawan}*,

Data absensi kamu telah ditambahkan oleh Admin.

📅 Tanggal: {$tampilTanggal}
🕐 Jam Masuk: {$tampilJamMasuk}
🕐 Jam Pulang: {$tampilJamPulang}
📌 Status: {$status}

Data absensi kamu telah berhasil dicatat ke dalam sistem.

Jika terdapat kesalahan pada data absensi, silakan hubungi Admin.

Terima kasih.
*Sistem Absensi Karyawan*";


                    /* =========================
                       KIRIM WHATSAPP
                    ========================= */

                    $hasilWA =
                        kirimWA(
                            $noHp,
                            $pesanWA
                        );


                    /* =========================
                       REDIRECT
                    ========================= */

                    if (
                        isset($hasilWA["status"]) &&
                        $hasilWA["status"] === true
                    ) {

                        header(
                            "Location: index.php?pesan=tambah&wa=berhasil"
                        );

                    } else {

                        header(
                            "Location: index.php?pesan=tambah&wa=gagal"
                        );

                    }

                    exit;

                } else {

                    $error =
                        "Gagal menyimpan data absensi.";

                }

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Tambah Absensi - Sistem Absensi
</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(255,182,193,.35),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 20%,
            rgba(180,190,255,.35),
            transparent 30%
        ),
        #f8f7ff;

    color: #514b68;

    padding: 20px;

}

.container {

    max-width: 650px;

    margin: 40px auto;

}

.card {

    background:
        rgba(255,255,255,.78);

    border:
        1px solid rgba(255,255,255,.90);

    border-radius: 30px;

    padding: 30px;

    backdrop-filter:
        blur(20px);

    box-shadow:
        0 20px 50px
        rgba(80,70,110,.10);

}

h1 {

    font-size: 25px;

    margin-bottom: 8px;

}

.subtitle {

    color: #888195;

    font-size: 13px;

    margin-bottom: 25px;

}

.form-group {

    margin-bottom: 17px;

}

label {

    display: block;

    margin-bottom: 7px;

    color: #635d72;

    font-size: 13px;

    font-weight: 700;

}

input,
select {

    width: 100%;

    padding: 13px 14px;

    border:
        1px solid #ddd8eb;

    border-radius: 14px;

    background:
        rgba(255,255,255,.80);

    color: #514b68;

    outline: none;

}

input:focus,
select:focus {

    border-color: #a69adf;

    box-shadow:
        0 0 0 4px
        rgba(166,154,223,.12);

}

.error {

    padding: 13px;

    margin-bottom: 18px;

    border-radius: 14px;

    background:
        rgba(255,215,225,.65);

    color: #a6536b;

    font-size: 13px;

}

.buttons {

    display: flex;

    gap: 10px;

    margin-top: 25px;

}

.btn {

    flex: 1;

    padding: 13px;

    border: none;

    border-radius: 14px;

    text-align: center;

    text-decoration: none;

    cursor: pointer;

    font-weight: 700;

    font-size: 13px;

}

.btn-save {

    background:
        linear-gradient(
            135deg,
            #9186d8,
            #b59be8
        );

    color: white;

}

.btn-back {

    background:
        rgba(225,221,238,.75);

    color: #635d72;

}

@media (max-width: 600px) {

    .container {
        margin: 15px auto;
    }

    .card {
        padding: 22px;
        border-radius: 24px;
    }

    .buttons {
        flex-direction: column;
    }

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h1>
    ➕ Tambah Absensi
</h1>

<p class="subtitle">
    Tambahkan data absensi karyawan secara manual.
</p>


<?php if ($error !== ""): ?>

<div class="error">
    ⚠️ <?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<form method="POST">


<div class="form-group">

<label>
    Karyawan
</label>

<select name="id_karyawan" required>

<option value="">
    -- Pilih Karyawan --
</option>

<?php if ($karyawan): ?>

<?php while ($row = $karyawan->fetch_assoc()): ?>

<option
    value="<?= $row["id"] ?>"
    <?= (
        ($_POST["id_karyawan"] ?? "") == $row["id"]
    ) ? "selected" : "" ?>
>

    <?= htmlspecialchars($row["nik"]) ?>
    -
    <?= htmlspecialchars($row["nama"]) ?>

</option>

<?php endwhile; ?>

<?php endif; ?>

</select>

</div>


<div class="form-group">

<label>
    Tanggal
</label>

<input
    type="date"
    name="tanggal"
    value="<?= htmlspecialchars(
        $_POST["tanggal"] ?? date("Y-m-d")
    ) ?>"
    required
>

</div>


<div class="form-group">

<label>
    Jam Masuk
</label>

<input
    type="time"
    name="jam_masuk"
    value="<?= htmlspecialchars(
        $_POST["jam_masuk"] ?? ""
    ) ?>"
>

</div>


<div class="form-group">

<label>
    Jam Pulang
</label>

<input
    type="time"
    name="jam_pulang"
    value="<?= htmlspecialchars(
        $_POST["jam_pulang"] ?? ""
    ) ?>"
>

</div>


<div class="form-group">

<label>
    Status
</label>

<select name="status">

<option
    value="Hadir"
    <?= ($_POST["status"] ?? "") === "Hadir" ? "selected" : "" ?>
>
    Hadir
</option>

<option
    value="Izin"
    <?= ($_POST["status"] ?? "") === "Izin" ? "selected" : "" ?>
>
    Izin
</option>

<option
    value="Sakit"
    <?= ($_POST["status"] ?? "") === "Sakit" ? "selected" : "" ?>
>
    Sakit
</option>

<option
    value="Alpha"
    <?= ($_POST["status"] ?? "") === "Alpha" ? "selected" : "" ?>
>
    Alpha
</option>

</select>

</div>


<div class="buttons">

<a
    href="index.php"
    class="btn btn-back"
>
    ← Kembali
</a>

<button
    type="submit"
    class="btn btn-save"
>
    💾 Simpan Absensi
</button>

</div>


</form>

</div>

</div>

</body>

</html>