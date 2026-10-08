<?php

session_start();

/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HALAMAN INI KHUSUS ADMIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../absensi/index.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| AMBIL DATA KARYAWAN
|--------------------------------------------------------------------------
*/
$query = $conn->query("
    SELECT id, nik, nama, jabatan, no_hp, email, role, created_at
    FROM karyawan
    ORDER BY id DESC
");

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Data Karyawan - Admin</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }


        body {

            min-height: 100vh;

            color: #604b68;

            background:
                radial-gradient(
                    circle at 5% 5%,
                    rgba(255, 126, 199, .42),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 95% 95%,
                    rgba(171, 126, 255, .40),
                    transparent 32%
                ),

                linear-gradient(
                    135deg,
                    #fff1fa,
                    #f0e8ff
                );

            padding-bottom: 40px;

        }


        /* =========================
           BUBBLES
        ========================= */

        body::before {

            content: "";

            position: fixed;

            width: 250px;
            height: 250px;

            top: -90px;
            left: -80px;

            border-radius: 50%;

            background: rgba(255, 107, 188, .22);

            filter: blur(3px);

            z-index: -1;

        }


        body::after {

            content: "";

            position: fixed;

            width: 330px;
            height: 330px;

            right: -120px;
            bottom: -130px;

            border-radius: 50%;

            background: rgba(168, 125, 255, .22);

            filter: blur(3px);

            z-index: -1;

        }


        /* =========================
           NAVBAR
        ========================= */

        .navbar {

            position: sticky;

            top: 0;

            z-index: 10;

            padding: 17px 5%;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            background: rgba(255,255,255,.68);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            border-bottom:
                1px solid rgba(255,255,255,.9);

            box-shadow:
                0 10px 30px rgba(145, 82, 168, .10);

        }


        .logo {

            color: #8d579d;

            font-size: 20px;

            font-weight: bold;

        }


        .admin-label {

            display: inline-block;

            margin-left: 7px;

            padding: 5px 9px;

            border-radius: 20px;

            background:
                linear-gradient(
                    135deg,
                    #ffe0f1,
                    #e4d8ff
                );

            color: #a14c8f;

            font-size: 10px;

            vertical-align: middle;

        }


        .nav-links {

            display: flex;

            gap: 7px;

            flex-wrap: wrap;

        }


        .nav-links a {

            color: #755b7d;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

            padding: 9px 13px;

            border-radius: 12px;

            transition: .2s;

        }


        .nav-links a:hover {

            background: rgba(255, 218, 241, .75);

            color: #b43d87;

        }


        .nav-links .logout {

            background:
                linear-gradient(
                    135deg,
                    #ff73b9,
                    #a77aed
                );

            color: white;

        }


        /* =========================
           CONTAINER
        ========================= */

        .container {

            width: 92%;

            max-width: 1200px;

            margin: 35px auto;

        }


        /* =========================
           HEADER
        ========================= */

        .header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

        }


        .header h1 {

            color: #8b579d;

            font-size: 30px;

            margin-bottom: 8px;

        }


        .header p {

            color: #987da1;

            font-size: 14px;

        }


        /* =========================
           BUTTON
        ========================= */

        .btn {

            display: inline-block;

            border: none;

            text-decoration: none;

            padding: 12px 17px;

            border-radius: 14px;

            color: white;

            font-size: 13px;

            font-weight: bold;

            cursor: pointer;

            transition: .2s;

        }


        .btn:hover {

            transform: translateY(-2px);

        }


        .btn-tambah {

            background:
                linear-gradient(
                    135deg,
                    #ff65b5,
                    #a977ef
                );

            box-shadow:
                0 10px 23px rgba(207, 80, 175, .20);

        }


        .btn-edit {

            background:
                linear-gradient(
                    135deg,
                    #ffb36b,
                    #ee83b4
                );

        }


        .btn-hapus {

            background:
                linear-gradient(
                    135deg,
                    #ff7b9d,
                    #d75a91
                );

        }


        /* =========================
           CARD
        ========================= */

        .card {

            background: rgba(255,255,255,.70);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            border:
                1px solid rgba(255,255,255,.9);

            border-radius: 25px;

            padding: 22px;

            box-shadow:
                0 18px 45px rgba(145, 86, 166, .13);

            overflow: hidden;

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

            min-width: 950px;

            border-collapse: collapse;

        }


        thead {

            background:
                linear-gradient(
                    135deg,
                    rgba(255,222,241,.80),
                    rgba(229,217,255,.80)
                );

        }


        th {

            padding: 15px;

            text-align: left;

            color: #895b99;

            font-size: 13px;

        }


        td {

            padding: 15px;

            font-size: 13px;

            color: #66536d;

            border-bottom:
                1px solid rgba(215,191,224,.45);

        }


        tr:hover td {

            background:
                rgba(255,225,244,.32);

        }


        /* =========================
           BADGE
        ========================= */

        .badge {

            display: inline-block;

            padding: 7px 11px;

            border-radius: 30px;

            background:
                linear-gradient(
                    135deg,
                    #ffe0f1,
                    #e4d8ff
                );

            color: #984b90;

            font-size: 11px;

            font-weight: bold;

        }


        .badge-admin {

            background:
                linear-gradient(
                    135deg,
                    #eadcff,
                    #ffd9ef
                );

            color: #7649a2;

        }


        /* =========================
           AKSI
        ========================= */

        .aksi {

            display: flex;

            gap: 7px;

            flex-wrap: wrap;

        }


        /* =========================
           EMPTY
        ========================= */

        .kosong {

            text-align: center;

            padding: 50px 20px;

            color: #a087a6;

        }


        .kosong-icon {

            font-size: 45px;

            margin-bottom: 12px;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 750px) {

            .navbar {

                flex-direction: column;

                align-items: flex-start;

                padding: 16px 20px;

            }


            .nav-links {

                width: 100%;

            }


            .nav-links a {

                padding: 8px 10px;

            }


            .container {

                width: 94%;

                margin-top: 25px;

            }


            .header {

                flex-direction: column;

                align-items: flex-start;

            }


            .header h1 {

                font-size: 25px;

            }


            .btn-tambah {

                width: 100%;

                text-align: center;

            }


            .card {

                padding: 15px;

                border-radius: 20px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR ADMIN
====================================================== -->

<nav class="navbar">

    <div class="logo">

        🌸 Sistem Absensi

        <span class="admin-label">
            ADMIN
        </span>

    </div>


    <div class="nav-links">

        <a href="../admin/index.php">
            🏠 Dashboard
        </a>

        <a href="index.php">
            👥 Karyawan
        </a>

        <a href="../absensi/index.php">
            📋 Absensi
        </a>

        <a href="../test_api.php">
            📱 Test API
        </a>

        <a
            href="../auth/logout.php"
            class="logout"
        >
            Keluar
        </a>

    </div>

</nav>


<!-- =====================================================
     CONTENT
====================================================== -->

<div class="container">


    <div class="header">

        <div>

            <h1>
                👥 Data Karyawan
            </h1>

            <p>
                Kelola data karyawan yang terdaftar
                dalam sistem.
            </p>

        </div>


        <a
            href="tambah.php"
            class="btn btn-tambah"
        >
            ＋ Tambah Karyawan
        </a>

    </div>


    <div class="card">


        <?php if ($query->num_rows > 0): ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>NIK</th>

                            <th>Nama</th>

                            <th>Jabatan</th>

                            <th>WhatsApp</th>

                            <th>Email</th>

                            <th>Role</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php $no = 1; ?>


                        <?php while ($row = $query->fetch_assoc()): ?>


                            <tr>

                                <td>
                                    <?= $no++; ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $row["nik"]
                                    ); ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $row["nama"]
                                        ); ?>
                                    </strong>

                                </td>


                                <td>

                                    <span class="badge">

                                        <?= htmlspecialchars(
                                            $row["jabatan"]
                                        ); ?>

                                    </span>

                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $row["no_hp"]
                                    ); ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $row["email"]
                                    ); ?>
                                </td>


                                <td>

                                    <span
                                        class="
                                            badge
                                            <?= $row["role"] === "admin"
                                                ? "badge-admin"
                                                : ""
                                            ?>
                                        "
                                    >

                                        <?= $row["role"] === "admin"
                                            ? "👑 Admin"
                                            : "👤 Karyawan"
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="aksi">


                                        <a
                                            href="edit.php?id=<?= $row["id"]; ?>"
                                            class="btn btn-edit"
                                        >
                                            ✏️ Edit
                                        </a>


                                        <a
                                            href="hapus.php?id=<?= $row["id"]; ?>"
                                            class="btn btn-hapus"
                                            onclick="return confirm('Yakin ingin menghapus karyawan ini?');"
                                        >
                                            🗑️ Hapus
                                        </a>


                                    </div>

                                </td>

                            </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="kosong">

                <div class="kosong-icon">
                    👥
                </div>

                <p>
                    Belum ada data karyawan.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>