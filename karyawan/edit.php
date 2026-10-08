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


/*
|--------------------------------------------------------------------------
| AMBIL ID
|--------------------------------------------------------------------------
*/
$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA KARYAWAN
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT id, nik, nama, jabatan, no_hp, email, role
    FROM karyawan
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$karyawan = $result->fetch_assoc();

$stmt->close();


if (!$karyawan) {
    header("Location: index.php");
    exit;
}


$error = "";


/*
|--------------------------------------------------------------------------
| PROSES UPDATE
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nik = trim($_POST["nik"] ?? "");
    $nama = trim($_POST["nama"] ?? "");
    $jabatan = trim($_POST["jabatan"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role = $_POST["role"] ?? "karyawan";

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
        $email === ""
    ) {

        $error = "Semua data wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif (!in_array($role, ["admin", "karyawan"])) {

        $error = "Role tidak valid.";

    } elseif ($password !== "" && strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif ($password !== "" && $password !== $konfirmasi) {

        $error = "Konfirmasi password tidak sama.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | CEK NIK DUPLIKAT
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id
            FROM karyawan
            WHERE nik = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->bind_param("si", $nik, $id);
        $stmt->execute();

        $result = $stmt->get_result();

        $nikAda = $result->num_rows > 0;

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | CEK EMAIL DUPLIKAT
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id
            FROM karyawan
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->bind_param("si", $email, $id);
        $stmt->execute();

        $result = $stmt->get_result();

        $emailAda = $result->num_rows > 0;

        $stmt->close();


        if ($nikAda) {

            $error = "NIK sudah digunakan karyawan lain.";

        } elseif ($emailAda) {

            $error = "Email sudah digunakan karyawan lain.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | UPDATE DENGAN / TANPA PASSWORD
            |--------------------------------------------------------------------------
            */

            if ($password !== "") {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare("
                    UPDATE karyawan
                    SET
                        nik = ?,
                        nama = ?,
                        jabatan = ?,
                        no_hp = ?,
                        email = ?,
                        password = ?,
                        role = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "sssssssi",
                    $nik,
                    $nama,
                    $jabatan,
                    $no_hp,
                    $email,
                    $passwordHash,
                    $role,
                    $id
                );

            } else {

                $stmt = $conn->prepare("
                    UPDATE karyawan
                    SET
                        nik = ?,
                        nama = ?,
                        jabatan = ?,
                        no_hp = ?,
                        email = ?,
                        role = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ssssssi",
                    $nik,
                    $nama,
                    $jabatan,
                    $no_hp,
                    $email,
                    $role,
                    $id
                );
            }


            $berhasil = $stmt->execute();

            $stmt->close();


            if ($berhasil) {


                /*
                |--------------------------------------------------------------------------
                | NOTIFIKASI KARYAWAN
                |--------------------------------------------------------------------------
                */

                $pesanKaryawan = "Halo $nama 👋

Data akun kamu telah diperbarui oleh Admin.

NIK : $nik
Nama : $nama
Jabatan : $jabatan
Email : $email
Role : " . ucfirst($role) . "

Silakan gunakan data terbaru untuk login.

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

                $pesanAdmin = "📢 DATA KARYAWAN DIPERBARUI

Data karyawan berhasil diperbarui.

NIK : $nik
Nama : $nama
Jabatan : $jabatan
WhatsApp : $no_hp
Email : $email
Role : " . ucfirst($role) . "

Data berhasil diperbarui di Sistem Absensi Karyawan.";


                kirimFonnte(
                    $admin_wa,
                    $pesanAdmin
                );


                header("Location: index.php?pesan=edit");
                exit;

            } else {

                $error = "Gagal memperbarui data karyawan.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN DATA TERBARU DI FORM
    |--------------------------------------------------------------------------
    */

    $karyawan["nik"] = $nik;
    $karyawan["nama"] = $nama;
    $karyawan["jabatan"] = $jabatan;
    $karyawan["no_hp"] = $no_hp;
    $karyawan["email"] = $email;
    $karyawan["role"] = $role;
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

    <title>Edit Karyawan - Admin</title>


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


        /* NAVBAR */

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


        /* CONTAINER */

        .container {

            width: 92%;

            max-width: 900px;

            margin: 35px auto;
        }


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


        /* CARD */

        .card {

            background: rgba(255,255,255,.70);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            border:
                1px solid rgba(255,255,255,.9);

            border-radius: 27px;

            padding: 30px;

            box-shadow:
                0 18px 45px rgba(145, 86, 166, .13);
        }


        /* FORM */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 19px 15px;
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


        input,
        select {

            width: 100%;

            padding: 14px;

            border:
                1px solid #dfcde6;

            border-radius: 15px;

            background: rgba(255,255,255,.80);

            outline: none;

            color: #5d4b64;

            font-size: 14px;

            transition: .2s;
        }


        input:focus,
        select:focus {

            border-color: #c46fd1;

            box-shadow:
                0 0 0 4px rgba(196,111,209,.11);
        }


        input::placeholder {

            color: #b6a3ba;
        }


        .password-info {

            margin-top: 8px;

            color: #a28ca8;

            font-size: 11px;
        }


        /* ALERT */

        .alert {

            padding: 14px 17px;

            border-radius: 15px;

            margin-bottom: 20px;

            font-size: 13px;

            background: #ffe1eb;

            color: #b2456d;

            border: 1px solid #ffc6d8;
        }


        /* BUTTON */

        .actions {

            display: flex;

            gap: 10px;

            margin-top: 25px;

            flex-wrap: wrap;
        }


        .btn {

            display: inline-block;

            border: none;

            text-decoration: none;

            padding: 13px 20px;

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


        .btn-save {

            background:
                linear-gradient(
                    135deg,
                    #ff65b5,
                    #a977ef
                );

            box-shadow:
                0 10px 23px rgba(207, 80, 175, .20);
        }


        .btn-back {

            background: rgba(232,220,242,.90);

            color: #76577e;
        }


        @media (max-width: 700px) {

            .navbar {

                flex-direction: column;

                align-items: flex-start;

                padding: 16px 20px;
            }


            .nav-links {

                width: 100%;
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


            .header h1 {

                font-size: 25px;
            }

        }

    </style>

</head>


<body>


<!-- NAVBAR -->

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


<!-- CONTENT -->

<div class="container">


    <div class="header">

        <h1>
            ✏️ Edit Karyawan
        </h1>

        <p>
            Perbarui data karyawan yang terdaftar
            dalam sistem.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert">

            <?= htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="card">


        <form method="POST">


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        NIK
                    </label>

                    <input
                        type="text"
                        name="nik"
                        value="<?= htmlspecialchars($karyawan["nik"]); ?>"
                        placeholder="Masukkan NIK"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        name="nama"
                        value="<?= htmlspecialchars($karyawan["nama"]); ?>"
                        placeholder="Masukkan nama lengkap"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Jabatan
                    </label>

                    <input
                        type="text"
                        name="jabatan"
                        value="<?= htmlspecialchars($karyawan["jabatan"]); ?>"
                        placeholder="Contoh: Programmer"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Nomor WhatsApp
                    </label>

                    <input
                        type="text"
                        name="no_hp"
                        value="<?= htmlspecialchars($karyawan["no_hp"]); ?>"
                        placeholder="Contoh: 08123456789"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($karyawan["email"]); ?>"
                        placeholder="contoh@gmail.com"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Role
                    </label>

                    <select name="role" required>

                        <option
                            value="karyawan"
                            <?= $karyawan["role"] === "karyawan" ? "selected" : ""; ?>
                        >
                            👤 Karyawan
                        </option>

                        <option
                            value="admin"
                            <?= $karyawan["role"] === "admin" ? "selected" : ""; ?>
                        >
                            👑 Admin
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Password Baru
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Kosongkan jika tidak diubah"
                    >

                    <div class="password-info">
                        Kosongkan jika password lama tetap digunakan.
                    </div>

                </div>


                <div class="form-group full">

                    <label>
                        Konfirmasi Password Baru
                    </label>

                    <input
                        type="password"
                        name="konfirmasi"
                        placeholder="Ulangi password baru"
                    >

                </div>


            </div>


            <div class="actions">

                <button
                    type="submit"
                    class="btn btn-save"
                >
                    💾 Simpan Perubahan
                </button>


                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    ← Kembali
                </a>

            </div>


        </form>


    </div>


</div>


</body>

</html>