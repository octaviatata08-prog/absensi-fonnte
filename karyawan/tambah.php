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
| KHUSUS ADMIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../absensi/index.php");
    exit;
}

require_once "../config/database.php";
require_once "../config/fonnte.php";
require_once "../config/admin.php";

$error = "";


/*
|--------------------------------------------------------------------------
| PROSES TAMBAH KARYAWAN
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nik = trim($_POST["nik"] ?? "");
    $nama = trim($_POST["nama"] ?? "");
    $jabatan = trim($_POST["jabatan"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        $nik === "" ||
        $nama === "" ||
        $jabatan === "" ||
        $no_hp === "" ||
        $email === "" ||
        $password === "" ||
        $konfirmasi === ""
    ) {

        $error = "Semua data wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif ($password !== $konfirmasi) {

        $error = "Konfirmasi password tidak sama.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CEK NIK
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id
            FROM karyawan
            WHERE nik = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $nik);
        $stmt->execute();

        $result = $stmt->get_result();

        $nikAda = $result->num_rows > 0;

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | CEK EMAIL
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id
            FROM karyawan
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        $emailAda = $result->num_rows > 0;

        $stmt->close();


        if ($nikAda) {

            $error = "NIK sudah terdaftar.";

        } elseif ($emailAda) {

            $error = "Email sudah terdaftar.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | PASSWORD HASH
            |--------------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
            |--------------------------------------------------------------------------
            | TAMBAH KARYAWAN
            |--------------------------------------------------------------------------
            | Akun dari halaman Admin otomatis menjadi KARYAWAN.
            */

            $role = "karyawan";

            $stmt = $conn->prepare("
                INSERT INTO karyawan
                (
                    nik,
                    nama,
                    jabatan,
                    no_hp,
                    email,
                    password,
                    role
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssssss",
                $nik,
                $nama,
                $jabatan,
                $no_hp,
                $email,
                $passwordHash,
                $role
            );

            $berhasil = $stmt->execute();

            $stmt->close();


            if ($berhasil) {

                /*
                |--------------------------------------------------------------------------
                | NOTIFIKASI KARYAWAN
                |--------------------------------------------------------------------------
                */

                $pesanKaryawan = "Halo $nama 👋

Data karyawan kamu berhasil ditambahkan ke Sistem Absensi.

NIK : $nik
Nama : $nama
Jabatan : $jabatan
Email : $email

Silakan login menggunakan email atau NIK dan password yang telah diberikan.

Terima kasih.";

                kirimFonnte(
                    $no_hp,
                    $pesanKaryawan
                );


                /*
                |--------------------------------------------------------------------------
                | NOTIFIKASI ADMIN
                |--------------------------------------------------------------------------
                */

                $pesanAdmin = "📢 KARYAWAN BARU DITAMBAHKAN

NIK : $nik
Nama : $nama
Jabatan : $jabatan
WhatsApp : $no_hp
Email : $email

Data karyawan berhasil disimpan ke Sistem Absensi.";

                kirimFonnte(
                    $admin_wa,
                    $pesanAdmin
                );


                header("Location: index.php?pesan=tambah");
                exit;

            } else {

                $error = "Gagal menambahkan karyawan.";
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

    <title>Tambah Karyawan - Admin</title>


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

            max-width: 900px;

            margin: 35px auto;

        }


        /* =========================
           HEADER
        ========================= */

        .header {

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
           CARD
        ========================= */

        .card {

            background: rgba(255,255,255,.70);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            border:
                1px solid rgba(255,255,255,.9);

            border-radius: 28px;

            padding: 30px;

            box-shadow:
                0 18px 45px rgba(145, 86, 166, .13);

        }


        /* =========================
           ALERT
        ========================= */

        .alert {

            padding: 14px 17px;

            border-radius: 15px;

            margin-bottom: 20px;

            font-size: 13px;

            background: #ffe1eb;

            color: #b2456d;

            border: 1px solid #ffc6d8;

        }


        /* =========================
           FORM
        ========================= */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px 16px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .full {

            grid-column: 1 / -1;

        }


        label {

            color: #725b79;

            font-size: 13px;

            font-weight: bold;

            margin-bottom: 8px;

        }


        input {

            width: 100%;

            padding: 14px 15px;

            border:
                1px solid #dfcde6;

            border-radius: 15px;

            background: rgba(255,255,255,.80);

            outline: none;

            color: #5d4b64;

            font-size: 14px;

            transition: .2s;

        }


        input:focus {

            border-color: #c46fd1;

            box-shadow:
                0 0 0 4px
                rgba(196,111,209,.11);

        }


        input::placeholder {

            color: #b6a3ba;

        }


        /* =========================
           INFO ROLE
        ========================= */

        .role-info {

            padding: 14px 16px;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,224,241,.72),
                    rgba(228,216,255,.72)
                );

            color: #82598e;

            font-size: 13px;

            margin-bottom: 22px;

        }


        /* =========================
           BUTTONS
        ========================= */

        .actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

        }


        .btn {

            display: inline-block;

            border: none;

            padding: 13px 19px;

            border-radius: 14px;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

            cursor: pointer;

            transition: .2s;

        }


        .btn:hover {

            transform: translateY(-2px);

        }


        .btn-simpan {

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #ff65b5,
                    #a977ef
                );

            box-shadow:
                0 10px 23px
                rgba(207,80,175,.20);

        }


        .btn-kembali {

            color: #76577e;

            background:
                rgba(235,221,244,.85);

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 700px) {

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


            .card {

                padding: 20px;

                border-radius: 22px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .full {

                grid-column: auto;

            }


            .actions {

                flex-direction: column-reverse;

            }


            .btn {

                width: 100%;

                text-align: center;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
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

        <h1>
            ➕ Tambah Karyawan
        </h1>

        <p>
            Tambahkan data karyawan baru ke dalam sistem.
        </p>

    </div>


    <div class="card">


        <?php if ($error !== ""): ?>

            <div class="alert">

                <?= htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <div class="role-info">

            👤 Akun yang ditambahkan melalui halaman
            Admin otomatis memiliki role
            <strong>Karyawan</strong>.

        </div>


        <form method="POST">


            <div class="form-grid">


                <div class="form-group">

                    <label for="nik">
                        NIK
                    </label>

                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        placeholder="Masukkan NIK"
                        value="<?= htmlspecialchars($_POST["nik"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="nama">
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        placeholder="Masukkan nama lengkap"
                        value="<?= htmlspecialchars($_POST["nama"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="jabatan">
                        Jabatan
                    </label>

                    <input
                        type="text"
                        id="jabatan"
                        name="jabatan"
                        placeholder="Contoh: Programmer"
                        value="<?= htmlspecialchars($_POST["jabatan"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="no_hp">
                        Nomor WhatsApp
                    </label>

                    <input
                        type="text"
                        id="no_hp"
                        name="no_hp"
                        placeholder="Contoh: 08123456789"
                        value="<?= htmlspecialchars($_POST["no_hp"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Contoh: nama@gmail.com"
                        value="<?= htmlspecialchars($_POST["email"] ?? ""); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 6 karakter"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="konfirmasi">
                        Konfirmasi Password
                    </label>

                    <input
                        type="password"
                        id="konfirmasi"
                        name="konfirmasi"
                        placeholder="Ulangi password"
                        required
                    >

                </div>


            </div>


            <div class="actions">

                <a
                    href="index.php"
                    class="btn btn-kembali"
                >
                    ← Kembali
                </a>


                <button
                    type="submit"
                    class="btn btn-simpan"
                >
                    💾 Simpan Karyawan
                </button>

            </div>


        </form>


    </div>


</div>


</body>

</html>