<?php

session_start();

require_once "../config/database.php";

/* =========================
   CEK LOGIN
========================= */

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

$role = $_SESSION["role"] ?? "";
$nama = $_SESSION["nama"] ?? "User";

if ($role !== "admin" && $role !== "karyawan") {
    header("Location: ../auth/index.php");
    exit;
}

$id_karyawan = $_SESSION["id_karyawan"] ?? 0;

/* =========================
   TANGGAL
========================= */

$tanggalHariIni = date("Y-m-d");

/* =========================
   PESAN
========================= */

$pesan = $_GET["pesan"] ?? "";

/* =========================
   DATA
========================= */

$dataAbsensi = null;

/* =========================
   STATISTIK ADMIN
========================= */

$totalAbsensi = 0;
$totalMasuk = 0;
$totalPulang = 0;

if ($role === "admin") {

    $query = $conn->query("
        SELECT COUNT(*) AS total
        FROM absensi
    ");

    if ($query) {
        $totalAbsensi = $query->fetch_assoc()["total"];
    }

    $query = $conn->query("
        SELECT COUNT(*) AS total
        FROM absensi
        WHERE jam_masuk IS NOT NULL
        AND jam_masuk != ''
    ");

    if ($query) {
        $totalMasuk = $query->fetch_assoc()["total"];
    }

    $query = $conn->query("
        SELECT COUNT(*) AS total
        FROM absensi
        WHERE jam_pulang IS NOT NULL
        AND jam_pulang != ''
    ");

    if ($query) {
        $totalPulang = $query->fetch_assoc()["total"];
    }

    /* =========================
       SEMUA DATA ABSENSI
    ========================= */

    $dataAbsensi = $conn->query("
        SELECT
            absensi.id,
            absensi.id_karyawan,
            absensi.tanggal,
            absensi.jam_masuk,
            absensi.jam_pulang,
            absensi.status,
            karyawan.nik,
            karyawan.nama,
            karyawan.jabatan
        FROM absensi
        INNER JOIN karyawan
            ON absensi.id_karyawan = karyawan.id
        ORDER BY
            absensi.tanggal DESC,
            absensi.jam_masuk DESC
    ");

}

/* =========================
   DATA KARYAWAN
========================= */

if ($role === "karyawan") {

    $stmt = $conn->prepare("
        SELECT
            id,
            tanggal,
            jam_masuk,
            jam_pulang,
            status
        FROM absensi
        WHERE id_karyawan = ?
        ORDER BY tanggal DESC, jam_masuk DESC
    ");

    $stmt->bind_param("i", $id_karyawan);
    $stmt->execute();

    $dataAbsensi = $stmt->get_result();
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
    Data Absensi - Sistem Absensi
</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color: #4f4a63;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(255,182,193,.35),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 15%,
            rgba(180,190,255,.35),
            transparent 30%
        ),
        radial-gradient(
            circle at 50% 100%,
            rgba(220,190,255,.30),
            transparent 35%
        ),
        #f8f7ff;

    padding: 20px;
}

/* =========================
   BUBBLES
========================= */

.bubble {
    position: fixed;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}

.bubble.one {
    width: 180px;
    height: 180px;
    top: -60px;
    left: -60px;
    background: rgba(255,180,200,.25);
}

.bubble.two {
    width: 230px;
    height: 230px;
    right: -70px;
    top: 120px;
    background: rgba(180,190,255,.25);
}

.bubble.three {
    width: 170px;
    height: 170px;
    bottom: -60px;
    left: 15%;
    background: rgba(215,190,255,.25);
}

/* =========================
   NAVBAR
========================= */

.navbar {

    position: relative;
    z-index: 10;

    max-width: 1400px;
    margin: auto;

    padding: 15px 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;

    background: rgba(255,255,255,.70);

    border:
        1px solid rgba(255,255,255,.85);

    border-radius: 25px;

    backdrop-filter: blur(20px);

    box-shadow:
        0 15px 40px rgba(90,80,130,.10);
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {

    width: 48px;
    height: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            #d9d1ff,
            #ffcfdc
        );

    font-size: 23px;
}

.brand-text h2 {
    font-size: 19px;
    color: #504a68;
}

.brand-text span {

    display: block;

    margin-top: 2px;

    font-size: 12px;

    color: #8a849d;
}

.nav-links {

    display: flex;

    align-items: center;

    gap: 7px;

    flex-wrap: wrap;

    justify-content: center;
}

.nav-links a {

    text-decoration: none;

    color: #6c667d;

    padding: 10px 13px;

    border-radius: 13px;

    font-size: 13px;

    font-weight: 600;

    transition: .25s;
}

.nav-links a:hover {

    background: rgba(220,214,255,.45);

    color: #65599b;
}

.nav-links a.active {

    background: rgba(205,197,255,.55);

    color: #66599b;
}

.nav-links a.logout {

    color: #ad6478;

    background: rgba(255,215,225,.45);
}

/* =========================
   CONTAINER
========================= */

.container {

    position: relative;

    z-index: 2;

    max-width: 1400px;

    margin: 25px auto;
}

/* =========================
   HEADER
========================= */

.page-header {

    padding: 28px;

    margin-bottom: 20px;

    border-radius: 28px;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.78),
            rgba(239,235,255,.72)
        );

    border:
        1px solid rgba(255,255,255,.85);

    backdrop-filter: blur(20px);

    box-shadow:
        0 15px 40px rgba(90,80,130,.08);
}

.page-header h1 {

    color: #48425e;

    font-size: 28px;

    margin-bottom: 7px;
}

.page-header p {

    color: #7d778b;

    font-size: 14px;
}

/* =========================
   STATISTIK
========================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 20px;
}

.stat {

    padding: 22px;

    border-radius: 24px;

    background:
        rgba(255,255,255,.68);

    border:
        1px solid rgba(255,255,255,.85);

    backdrop-filter: blur(18px);

    box-shadow:
        0 12px 30px rgba(90,80,130,.07);
}

.stat-title {

    color: #858093;

    font-size: 13px;

    margin-bottom: 7px;
}

.stat-number {

    color: #514b68;

    font-size: 30px;

    font-weight: 800;
}

/* =========================
   CARD
========================= */

.card {

    background:
        rgba(255,255,255,.70);

    border:
        1px solid rgba(255,255,255,.85);

    border-radius: 28px;

    padding: 24px;

    backdrop-filter: blur(18px);

    box-shadow:
        0 12px 35px rgba(90,80,130,.07);
}

.card-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;
}

.card-header h2 {

    color: #514b68;

    font-size: 18px;
}

/* =========================
   BUTTON
========================= */

.btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    padding: 11px 17px;

    border-radius: 14px;

    border: none;

    text-decoration: none;

    cursor: pointer;

    font-size: 13px;

    font-weight: 700;

    transition: .25s;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-tambah {

    color: white;

    background:
        linear-gradient(
            135deg,
            #9186d8,
            #b59be8
        );

    box-shadow:
        0 8px 20px rgba(145,134,216,.20);
}

.btn-edit {

    color: #65599b;

    background:
        rgba(220,214,255,.65);
}

.btn-hapus {

    color: #a6536b;

    background:
        rgba(255,215,225,.70);
}

/* =========================
   TABLE
========================= */

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

    text-align: left;

    padding: 13px;

    color: #858093;

    font-size: 12px;

    border-bottom:
        1px solid rgba(130,120,160,.12);
}

td {

    padding: 14px 13px;

    color: #625d70;

    font-size: 13px;

    border-bottom:
        1px solid rgba(130,120,160,.08);
}

tr:last-child td {
    border-bottom: none;
}

/* =========================
   STATUS
========================= */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;
}

.status-hadir {

    background: rgba(204,238,220,.65);

    color: #4c8567;
}

.status-izin {

    background: rgba(255,226,190,.65);

    color: #9a7445;
}

.status-sakit {

    background: rgba(205,225,255,.65);

    color: #55759b;
}

.status-alpha {

    background: rgba(255,211,220,.65);

    color: #a85d70;
}

.status-default {

    background: rgba(220,214,255,.60);

    color: #6d6195;
}

/* =========================
   ACTION
========================= */

.actions {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}

/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 40px 20px;

    color: #8d879c;

    font-size: 13px;
}

/* =========================
   NOTIFICATION
========================= */

.notif-overlay {

    position: fixed;

    inset: 0;

    z-index: 9999;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(55,45,80,.22);

    backdrop-filter: blur(7px);
}

.notif-box {

    width: 100%;

    max-width: 390px;

    padding: 35px 30px;

    text-align: center;

    border-radius: 30px;

    background:
        rgba(255,255,255,.92);

    box-shadow:
        0 25px 70px rgba(70,60,100,.20);

    animation: popup .35s ease;
}

@keyframes popup {

    from {

        opacity: 0;

        transform: scale(.85);

    }

    to {

        opacity: 1;

        transform: scale(1);

    }
}

.notif-icon {

    width: 65px;

    height: 65px;

    margin: 0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #ddd4ff,
            #ffd8e2
        );

    font-size: 30px;
}

.notif-box h2 {

    color: #514b68;

    margin-bottom: 8px;
}

.notif-box p {

    color: #817b8e;

    font-size: 13px;

    line-height: 1.6;

    margin-bottom: 20px;
}

.notif-button {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 15px;

    background:
        linear-gradient(
            135deg,
            #9186d8,
            #b59be8
        );

    color: white;

    font-weight: 700;

    cursor: pointer;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 900px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .navbar {
        flex-direction: column;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }

}

@media (max-width: 500px) {

    body {
        padding: 12px;
    }

    .page-header {
        padding: 22px;
    }

    .page-header h1 {
        font-size: 24px;
    }

    .card {
        padding: 18px;
    }

}

</style>

</head>

<body>

<div class="bubble one"></div>
<div class="bubble two"></div>
<div class="bubble three"></div>


<!-- NAVBAR -->

<nav class="navbar">

    <div class="brand">

        <div class="brand-icon">

            <?= $role === "admin" ? "👑" : "👤" ?>

        </div>

        <div class="brand-text">

            <h2>
                Sistem Absensi
            </h2>

            <span>

                <?= $role === "admin"
                    ? "Admin Panel"
                    : "Karyawan Panel"
                ?>

            </span>

        </div>

    </div>


    <div class="nav-links">

        <?php if ($role === "admin"): ?>

            <a href="../admin/dashboard.php">
                🏠 Dashboard
            </a>

            <a href="../karyawan/index.php">
                👥 Karyawan
            </a>

            <a href="index.php" class="active">
                📋 Absensi
            </a>

            <a href="../test_api.php">
                🔌 Test API
            </a>

        <?php else: ?>

            <a href="../karyawan/dashboard.php">
                🏠 Dashboard
            </a>

            <a href="index.php" class="active">
                📋 Absensi
            </a>

        <?php endif; ?>

        <a
            href="../auth/logout.php"
            class="logout"
        >
            🚪 Keluar
        </a>

    </div>

</nav>


<!-- MAIN -->

<main class="container">


    <section class="page-header">

        <h1>
            📋 Data Absensi
        </h1>

        <p>

            <?php if ($role === "admin"): ?>

                Kelola seluruh data absensi karyawan.

            <?php else: ?>

                Lihat riwayat absensi kamu.

            <?php endif; ?>

        </p>

    </section>


    <!-- =========================
         ADMIN
    ========================== -->

    <?php if ($role === "admin"): ?>


        <!-- STATISTIK -->

        <section class="stats">

            <div class="stat">

                <div class="stat-title">
                    Total Absensi
                </div>

                <div class="stat-number">
                    <?= $totalAbsensi ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-title">
                    Total Absen Masuk
                </div>

                <div class="stat-number">
                    <?= $totalMasuk ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-title">
                    Total Absen Pulang
                </div>

                <div class="stat-number">
                    <?= $totalPulang ?>
                </div>

            </div>

        </section>


        <!-- DATA ABSENSI -->

        <section class="card">

            <div class="card-header">

                <h2>
                    👥 Semua Data Absensi
                </h2>

                <a
                    href="tambah.php"
                    class="btn btn-tambah"
                >
                    ➕ Tambah Absensi
                </a>

            </div>


            <div class="table-wrapper">

                <?php if ($dataAbsensi && $dataAbsensi->num_rows > 0): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>NIK</th>

                                <th>Nama</th>

                                <th>Jabatan</th>

                                <th>Tanggal</th>

                                <th>Jam Masuk</th>

                                <th>Jam Pulang</th>

                                <th>Status</th>

                                <th>Aksi</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php while ($row = $dataAbsensi->fetch_assoc()): ?>

                            <tr>

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
                                    <?= htmlspecialchars($row["tanggal"]) ?>
                                </td>

                                <td>

                                    <?= !empty($row["jam_masuk"])
                                        ? htmlspecialchars($row["jam_masuk"])
                                        : "-"
                                    ?>

                                </td>

                                <td>

                                    <?= !empty($row["jam_pulang"])
                                        ? htmlspecialchars($row["jam_pulang"])
                                        : "-"
                                    ?>

                                </td>

                                <td>

                                    <?php

                                    $status = strtolower(
                                        trim(
                                            $row["status"] ?? ""
                                        )
                                    );

                                    if ($status === "hadir"):

                                    ?>

                                        <span class="status status-hadir">
                                            HADIR
                                        </span>

                                    <?php elseif ($status === "izin"): ?>

                                        <span class="status status-izin">
                                            IZIN
                                        </span>

                                    <?php elseif ($status === "sakit"): ?>

                                        <span class="status status-sakit">
                                            SAKIT
                                        </span>

                                    <?php elseif (
                                        $status === "alpha" ||
                                        $status === "alpa"
                                    ): ?>

                                        <span class="status status-alpha">
                                            ALPHA
                                        </span>

                                    <?php else: ?>

                                        <span class="status status-default">
                                            <?= htmlspecialchars(
                                                $row["status"] ?? "-"
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CRUD -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="edit.php?id=<?= $row["id"] ?>"
                                            class="btn btn-edit"
                                        >
                                            ✏️ Edit
                                        </a>


                                        <a
                                            href="hapus.php?id=<?= $row["id"] ?>"
                                            class="btn btn-hapus"
                                            onclick="return confirm('Yakin ingin menghapus data absensi ini?')"
                                        >
                                            🗑️ Hapus
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty">

                        📋 Belum ada data absensi.

                    </div>

                <?php endif; ?>

            </div>

        </section>


    <?php else: ?>


        <!-- =========================
             KARYAWAN
        ========================== -->


        <section class="card">

            <div class="card-header">

                <h2>
                    📋 Riwayat Absensi Saya
                </h2>

                <a
                    href="masuk.php"
                    class="btn btn-tambah"
                >
                    🟢 Absen Masuk
                </a>

            </div>


            <div class="table-wrapper">

                <?php if ($dataAbsensi && $dataAbsensi->num_rows > 0): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>Tanggal</th>

                                <th>Jam Masuk</th>

                                <th>Jam Pulang</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php while ($row = $dataAbsensi->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $row["tanggal"]
                                    ) ?>
                                </td>

                                <td>

                                    <?= !empty($row["jam_masuk"])
                                        ? htmlspecialchars($row["jam_masuk"])
                                        : "-"
                                    ?>

                                </td>

                                <td>

                                    <?= !empty($row["jam_pulang"])
                                        ? htmlspecialchars($row["jam_pulang"])
                                        : "-"
                                    ?>

                                </td>

                                <td>

                                    <span class="status status-hadir">

                                        <?= htmlspecialchars(
                                            $row["status"] ?? "-"
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty">

                        📋 Belum ada riwayat absensi.

                    </div>

                <?php endif; ?>

            </div>

        </section>


    <?php endif; ?>


</main>


<!-- NOTIFIKASI -->

<?php if ($pesan !== ""): ?>

<div class="notif-overlay" id="notifOverlay">

    <div class="notif-box">

        <div class="notif-icon">

            <?php

            if ($pesan === "tambah") {
                echo "➕";
            } elseif ($pesan === "edit") {
                echo "✏️";
            } elseif ($pesan === "hapus") {
                echo "🗑️";
            } elseif ($pesan === "masuk") {
                echo "💗";
            } elseif ($pesan === "pulang") {
                echo "💜";
            } else {
                echo "✨";
            }

            ?>

        </div>


        <h2>

            <?php

            if ($pesan === "tambah") {

                echo "Absensi Berhasil Ditambahkan!";

            } elseif ($pesan === "edit") {

                echo "Absensi Berhasil Diperbarui!";

            } elseif ($pesan === "hapus") {

                echo "Absensi Berhasil Dihapus!";

            } elseif ($pesan === "masuk") {

                echo "Absen Masuk Berhasil!";

            } elseif ($pesan === "pulang") {

                echo "Absen Pulang Berhasil!";

            } else {

                echo "Berhasil!";

            }

            ?>

        </h2>


        <p>

            <?php

            if ($pesan === "tambah") {

                echo "Data absensi berhasil ditambahkan ke sistem.";

            } elseif ($pesan === "edit") {

                echo "Perubahan data absensi berhasil disimpan.";

            } elseif ($pesan === "hapus") {

                echo "Data absensi berhasil dihapus dari sistem.";

            } elseif ($pesan === "masuk") {

                echo "Data absen masuk berhasil disimpan.";

            } elseif ($pesan === "pulang") {

                echo "Data absen pulang berhasil disimpan.";

            } else {

                echo "Proses berhasil dilakukan.";

            }

            ?>

        </p>


        <button
            type="button"
            class="notif-button"
            onclick="tutupNotif()"
        >
            Oke, Mengerti
        </button>

    </div>

</div>

<?php endif; ?>


<script>

function tutupNotif() {

    const notif =
        document.getElementById("notifOverlay");

    if (notif) {

        notif.remove();

    }

    const url =
        new URL(window.location.href);

    url.searchParams.delete("pesan");

    window.history.replaceState(
        {},
        document.title,
        url.pathname
    );

}

</script>


</body>

</html>