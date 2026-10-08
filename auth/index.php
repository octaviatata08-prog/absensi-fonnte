<?php

session_start();

date_default_timezone_set("Asia/Jakarta");

require_once "../config/database.php";
require_once "../config/fonnte.php";


// ==========================================
// VARIABEL
// ==========================================

$error = "";


// ==========================================
// PROSES LOGIN
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    // ==========================================
    // VALIDASI
    // ==========================================

    if ($email === "" || $password === "") {

        $error = "Email dan password wajib diisi!";

    } else {


        // ==========================================
        // CARI DATA KARYAWAN
        // ==========================================

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
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();


        // ==========================================
        // DATA DITEMUKAN
        // ==========================================

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();


            // ==========================================
            // CEK PASSWORD
            // ==========================================

            if (password_verify($password, $user["password"])) {


                // ==========================================
                // SIMPAN SESSION
                // ==========================================

                $_SESSION["login"] = true;

                $_SESSION["id_karyawan"] = $user["id"];

                $_SESSION["nik"] = $user["nik"];

                $_SESSION["nama"] = $user["nama"];

                $_SESSION["jabatan"] = $user["jabatan"];

                $_SESSION["no_hp"] = $user["no_hp"];

                $_SESSION["email"] = $user["email"];

                $_SESSION["role"] = $user["role"];


                // ==========================================
                // WAKTU LOGIN WIB
                // ==========================================

                $waktuLogin = date(
                    "d-m-Y H:i:s"
                );


                // ==========================================
                // PESAN WHATSAPP
                // ==========================================

                $pesanWA = "🔐 *LOGIN BERHASIL*

Halo *{$user["nama"]}* 👋

Kamu berhasil login ke *Sistem Absensi*.

📧 Email : {$user["email"]}
🕐 Waktu : {$waktuLogin} WIB

Selamat menggunakan Sistem Absensi. 🌸";


                // ==========================================
                // KIRIM WHATSAPP
                // ==========================================

                if (function_exists("kirimWA")) {

                    kirimWA(
                        $user["no_hp"],
                        $pesanWA
                    );

                }


                // ==========================================
                // REDIRECT SESUAI ROLE
                // ==========================================

                if ($user["role"] === "admin") {

                    header(
                        "Location: ../admin/dashboard.php"
                    );

                    exit;

                } else {

                    header(
                        "Location: ../karyawan/dashboard.php"
                    );

                    exit;
                }


            } else {

                $error = "Email atau password salah!";

            }


        } else {

            $error = "Email atau password salah!";

        }


        $stmt->close();
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

<title>Sistem Absensi - Login</title>


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
            #f7eaff,
            #e8f2ff,
            #ffe8f4
        );

    display: flex;

    justify-content: center;

    align-items: center;

    padding: 20px;

    overflow: hidden;
}


/* ==========================================
   BUBBLES
========================================== */

.bubble {

    position: fixed;

    border-radius: 50%;

    opacity: .5;
}


.b1 {

    width: 250px;

    height: 250px;

    background: #d9bcff;

    top: -90px;

    left: -80px;
}


.b2 {

    width: 300px;

    height: 300px;

    background: #b7ddff;

    right: -110px;

    bottom: -110px;
}


/* ==========================================
   CARD
========================================== */

.card {

    width: 100%;

    max-width: 430px;

    background:
        rgba(255,255,255,.72);

    backdrop-filter: blur(18px);

    border:
        1px solid
        rgba(255,255,255,.8);

    border-radius: 30px;

    padding: 35px;

    box-shadow:
        0 20px 50px
        rgba(100,80,150,.15);

    position: relative;

    z-index: 2;
}


/* ==========================================
   ICON
========================================== */

.icon {

    width: 75px;

    height: 75px;

    margin: auto;

    border-radius: 23px;

    background:
        linear-gradient(
            135deg,
            #b995ff,
            #83c9ff
        );

    display: flex;

    justify-content: center;

    align-items: center;

    font-size: 34px;
}


h1 {

    text-align: center;

    color: #57466f;

    margin-top: 15px;
}


.subtitle {

    text-align: center;

    color: #888;

    font-size: 14px;

    margin:
        7px 0 25px;
}


/* ==========================================
   ERROR
========================================== */

.error {

    background: #ffe5eb;

    color: #b64062;

    padding: 12px;

    border-radius: 13px;

    text-align: center;

    font-size: 13px;

    margin-bottom: 15px;
}


/* ==========================================
   FORM
========================================== */

label {

    display: block;

    color: #62556f;

    font-weight: bold;

    font-size: 14px;

    margin:
        15px 0 7px;
}


input {

    width: 100%;

    padding: 14px 15px;

    border:
        1px solid
        #e0d6ec;

    border-radius: 14px;

    background:
        rgba(255,255,255,.85);

    outline: none;

    font-size: 14px;
}


input:focus {

    border-color: #a98be5;

    box-shadow:
        0 0 0 3px
        rgba(169,139,229,.12);
}


/* ==========================================
   BUTTON
========================================== */

button {

    width: 100%;

    margin-top: 24px;

    border: none;

    border-radius: 15px;

    padding: 14px;

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


button:hover {

    opacity: .9;
}


/* ==========================================
   REGISTER
========================================== */

.register {

    text-align: center;

    margin-top: 20px;

    color: #777;

    font-size: 14px;
}


.register a {

    color: #8163bd;

    font-weight: bold;

    text-decoration: none;
}


/* ==========================================
   RESPONSIVE
========================================== */

@media(max-width:500px) {

    .card {

        padding:
            28px 22px;
    }

}

</style>

</head>


<body>


<div class="bubble b1"></div>

<div class="bubble b2"></div>


<div class="card">


    <div class="icon">
        🔐
    </div>


    <h1>
        Sistem Absensi
    </h1>


    <div class="subtitle">

        Silakan login untuk melanjutkan

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            placeholder="Masukkan email"
            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
            required
        >


        <label>
            Password
        </label>

        <input
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
        >


        <button type="submit">

            🔐 Login

        </button>


    </form>


    <div class="register">

        Belum punya akun?

        <a href="register.php">
            Daftar sekarang
        </a>

    </div>


</div>


</body>

</html>