<?php

session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Email dan password wajib diisi.";

    } else {

        $stmt = $conn->prepare("
            SELECT id, nik, nama, jabatan, no_hp, email, password, role
            FROM karyawan
            WHERE email = ? AND role = 'admin'
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin["password"])) {

                $_SESSION["login"] = true;
                $_SESSION["role"] = "admin";
                $_SESSION["id_karyawan"] = $admin["id"];
                $_SESSION["nik"] = $admin["nik"];
                $_SESSION["nama"] = $admin["nama"];
                $_SESSION["jabatan"] = $admin["jabatan"];
                $_SESSION["no_hp"] = $admin["no_hp"];
                $_SESSION["email"] = $admin["email"];

                header("Location: dashboard.php");
                exit;

            } else {

                $error = "Password yang kamu masukkan salah.";
            }

        } else {

            $error = "Akun Admin tidak ditemukan.";
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
    font-family: "Segoe UI", Arial, sans-serif;

    display: flex;
    justify-content: center;
    align-items: center;

    padding: 25px;

    background:
        radial-gradient(circle at 10% 20%, rgba(255, 182, 193, .35), transparent 30%),
        radial-gradient(circle at 90% 20%, rgba(180, 190, 255, .35), transparent 30%),
        radial-gradient(circle at 50% 100%, rgba(210, 190, 255, .35), transparent 35%),
        #f8f7ff;

    overflow: hidden;
}

/* BUBBLES */

.bubble {
    position: fixed;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(1px);
}

.bubble.one {
    width: 180px;
    height: 180px;
    background: rgba(255, 182, 193, .25);
    top: -60px;
    left: -50px;
}

.bubble.two {
    width: 230px;
    height: 230px;
    background: rgba(177, 190, 255, .25);
    right: -80px;
    top: 100px;
}

.bubble.three {
    width: 150px;
    height: 150px;
    background: rgba(221, 190, 255, .25);
    bottom: -50px;
    left: 15%;
}

/* CONTAINER */

.login-container {
    width: 100%;
    max-width: 950px;

    display: grid;
    grid-template-columns: 1fr 1fr;

    background: rgba(255,255,255,.65);
    border: 1px solid rgba(255,255,255,.8);

    border-radius: 35px;

    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);

    box-shadow:
        0 25px 60px rgba(100, 90, 150, .15);

    overflow: hidden;
}

/* LEFT */

.left-panel {
    padding: 55px 45px;

    display: flex;
    flex-direction: column;
    justify-content: center;

    background:
        linear-gradient(
            145deg,
            rgba(170, 185, 255, .75),
            rgba(226, 195, 255, .65),
            rgba(255, 205, 215, .65)
        );
}

.icon {
    width: 75px;
    height: 75px;

    display: flex;
    justify-content: center;
    align-items: center;

    border-radius: 24px;

    background: rgba(255,255,255,.65);

    font-size: 38px;

    margin-bottom: 25px;

    box-shadow: 0 10px 25px rgba(80,70,120,.12);
}

.left-panel h1 {
    font-size: 38px;
    color: #45405c;
    margin-bottom: 12px;
}

.left-panel h1 span {
    color: #7767b5;
}

.left-panel p {
    color: #66627a;
    line-height: 1.7;
    font-size: 15px;
    max-width: 350px;
}

.badge {
    margin-top: 30px;

    display: inline-flex;
    width: fit-content;

    padding: 9px 17px;

    border-radius: 30px;

    background: rgba(255,255,255,.55);

    color: #62568d;

    font-size: 13px;
    font-weight: 600;
}

/* RIGHT */

.form-panel {
    padding: 55px 45px;

    background: rgba(255,255,255,.45);
}

.form-panel h2 {
    color: #45405c;
    font-size: 29px;
    margin-bottom: 7px;
}

.subtitle {
    color: #858094;
    font-size: 14px;
    margin-bottom: 30px;
}

/* ERROR */

.error {
    background: rgba(255, 220, 225, .75);
    border: 1px solid rgba(240, 150, 165, .45);

    color: #a54f63;

    padding: 13px 15px;

    border-radius: 15px;

    font-size: 14px;

    margin-bottom: 20px;
}

/* INPUT */

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;

    margin-bottom: 8px;

    color: #5c576d;

    font-size: 14px;
    font-weight: 600;
}

.input-box {
    position: relative;
}

.input-box span {
    position: absolute;

    left: 15px;
    top: 50%;

    transform: translateY(-50%);

    font-size: 17px;
}

.input-box input {
    width: 100%;

    padding: 14px 15px 14px 45px;

    border-radius: 15px;

    border: 1px solid rgba(150,140,180,.25);

    outline: none;

    background: rgba(255,255,255,.75);

    color: #464158;

    font-size: 14px;

    transition: .25s;
}

.input-box input:focus {
    border-color: #a99ae8;

    box-shadow:
        0 0 0 4px rgba(169,154,232,.12);
}

/* BUTTON */

.btn-login {
    width: 100%;

    border: none;

    padding: 15px;

    border-radius: 17px;

    background:
        linear-gradient(
            135deg,
            #8e84d8,
            #b59be8
        );

    color: white;

    font-size: 15px;
    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 10px 22px rgba(142,132,216,.25);

    transition: .25s;
}

.btn-login:hover {
    transform: translateY(-2px);

    box-shadow:
        0 14px 28px rgba(142,132,216,.32);
}

.register-link {
    text-align: center;

    margin-top: 22px;

    font-size: 14px;

    color: #777286;
}

.register-link a {
    color: #7466b5;

    text-decoration: none;

    font-weight: 700;
}

.register-link a:hover {
    text-decoration: underline;
}

/* RESPONSIVE */

@media (max-width: 750px) {

    .login-container {
        grid-template-columns: 1fr;
        max-width: 480px;
    }

    .left-panel {
        padding: 35px 30px;
    }

    .left-panel h1 {
        font-size: 30px;
    }

    .form-panel {
        padding: 35px 30px;
    }

}

@media (max-width: 450px) {

    body {
        padding: 15px;
    }

    .login-container {
        border-radius: 25px;
    }

    .left-panel,
    .form-panel {
        padding: 30px 22px;
    }

}

</style>

</head>

<body>

<div class="bubble one"></div>
<div class="bubble two"></div>
<div class="bubble three"></div>

<div class="login-container">

    <!-- LEFT -->

    <div class="left-panel">

        <div class="icon">👑</div>

        <h1>
            Sistem <span>Absensi</span>
        </h1>

        <p>
            Selamat datang di halaman khusus Admin.
            Silakan login untuk mengakses dashboard
            dan mengelola Sistem Absensi Karyawan.
        </p>

        <div class="badge">
            🔐 Admin Access
        </div>

    </div>


    <!-- RIGHT -->

    <div class="form-panel">

        <h2>Login Admin 👋</h2>

        <p class="subtitle">
            Masuk menggunakan akun Admin kamu.
        </p>


        <?php if ($error !== ""): ?>

            <div class="error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label>Email Admin</label>

                <div class="input-box">

                    <span>📧</span>

                    <input
                        type="email"
                        name="email"
                        placeholder="Masukkan email admin"
                        required
                        autocomplete="email"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>Password</label>

                <div class="input-box">

                    <span>🔒</span>

                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >

                </div>

            </div>


            <button type="submit" class="btn-login">
                Masuk sebagai Admin →
            </button>

        </form>


        <div class="register-link">

            Belum punya akun Admin?
            <a href="register.php">Daftar Admin</a>

        </div>

    </div>

</div>

</body>
</html>