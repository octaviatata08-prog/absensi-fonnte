<?php
session_start();

if (isset($_SESSION["login"]) && $_SESSION["login"] === true) {

    if ($_SESSION["role"] === "admin") {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../karyawan/dashboard.php");
    }

    exit;
}

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Email dan password wajib diisi!";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                nik,
                nama,
                jabatan,
                no_hp,
                email,
                password,
                role
            FROM karyawan
            WHERE email = ?
            AND role = 'admin'
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["login"] = true;
                $_SESSION["id_karyawan"] = $user["id"];
                $_SESSION["nik"] = $user["nik"];
                $_SESSION["nama"] = $user["nama"];
                $_SESSION["jabatan"] = $user["jabatan"];
                $_SESSION["no_hp"] = $user["no_hp"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = "admin";

                header("Location: ../admin/dashboard.php");
                exit;

            } else {

                $error = "Password admin salah!";
            }

        } else {

            $error = "Akun admin tidak ditemukan!";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login Admin - Sistem Absensi</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    min-height: 100vh;
    font-family: Arial, sans-serif;

    background:
        linear-gradient(
            135deg,
            #eee4ff,
            #e4f3ff,
            #f9e7f2
        );

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;
}

.bubble {
    position: fixed;
    border-radius: 50%;
    opacity: .55;
}

.bubble.one {
    width: 230px;
    height: 230px;
    background: #cbb0ff;
    top: -70px;
    left: -60px;
}

.bubble.two {
    width: 280px;
    height: 280px;
    background: #a9d9ff;
    right: -100px;
    bottom: -100px;
}

.bubble.three {
    width: 130px;
    height: 130px;
    background: #ffc5df;
    right: 20%;
    top: 8%;
}

.container {
    width: 100%;
    max-width: 430px;
    padding: 20px;

    position: relative;
    z-index: 2;
}

.card {
    background: rgba(255,255,255,.70);
    backdrop-filter: blur(20px);

    border: 1px solid rgba(255,255,255,.85);

    border-radius: 30px;

    padding: 35px 30px;

    box-shadow:
        0 25px 60px rgba(90,70,130,.16);
}

.logo {
    width: 78px;
    height: 78px;

    margin: 0 auto 15px;

    border-radius: 25px;

    background:
        linear-gradient(
            135deg,
            #a989e6,
            #73b9e8
        );

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 35px;
}

h1 {
    text-align: center;
    color: #57466f;
    margin-bottom: 7px;
}

.subtitle {
    text-align: center;
    color: #888;
    font-size: 14px;
    margin-bottom: 25px;
}

.admin-label {
    text-align: center;
    margin-bottom: 20px;

    color: #8064a5;
    font-size: 13px;
    font-weight: bold;
}

label {
    display: block;

    color: #62556f;
    font-size: 14px;
    font-weight: bold;

    margin: 15px 0 7px;
}

input {
    width: 100%;

    padding: 14px 16px;

    border: 1px solid #e1d8ee;

    border-radius: 15px;

    outline: none;

    background: rgba(255,255,255,.85);

    font-size: 14px;
}

input:focus {
    border-color: #a98bdf;

    box-shadow:
        0 0 0 3px rgba(169,139,223,.12);
}

button {
    width: 100%;

    border: none;

    padding: 14px;

    margin-top: 22px;

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            #a98be5,
            #79bde9
        );

    color: white;

    font-size: 15px;
    font-weight: bold;

    cursor: pointer;
}

.error {
    background: #ffe5eb;

    color: #b64062;

    padding: 12px;

    border-radius: 13px;

    text-align: center;

    font-size: 13px;

    margin-bottom: 15px;
}

.back {
    text-align: center;

    margin-top: 20px;

    color: #777;

    font-size: 14px;
}

.back a {
    color: #8163bd;

    font-weight: bold;

    text-decoration: none;
}

</style>

</head>

<body>

<div class="bubble one"></div>
<div class="bubble two"></div>
<div class="bubble three"></div>

<div class="container">

    <div class="card">

        <div class="logo">
            🔐
        </div>

        <h1>
            Login Admin
        </h1>

        <div class="subtitle">
            Sistem Absensi Karyawan
        </div>

        <div class="admin-label">
            🛡️ HALAMAN KHUSUS ADMIN
        </div>

        <?php if ($error !== ""): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <label>
                Email Admin
            </label>

            <input
                type="email"
                name="email"
                placeholder="Masukkan email admin"
                required
            >


            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Masukkan password admin"
                required
            >


            <button type="submit">
                🔐 Masuk sebagai Admin
            </button>

        </form>


        <div class="back">

            Login sebagai karyawan?

            <a href="karyawan_login.php">
                Login Karyawan
            </a>

        </div>

    </div>

</div>

</body>
</html>