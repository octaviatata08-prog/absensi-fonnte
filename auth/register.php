<?php

session_start();

require_once "../config/database.php";
require_once "../config/fonnte.php";
require_once "../config/admin.php";

$error = "";
$success = false;
$wa_error = "";


// ==========================================
// PROSES REGISTER
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nik        = trim($_POST["nik"] ?? "");
    $nama       = trim($_POST["nama"] ?? "");
    $jabatan    = trim($_POST["jabatan"] ?? "");
    $no_hp      = trim($_POST["no_hp"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $password   = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";


    // ==========================================
    // VALIDASI
    // ==========================================

    if (
        $nik === "" ||
        $nama === "" ||
        $jabatan === "" ||
        $no_hp === "" ||
        $email === "" ||
        $password === "" ||
        $konfirmasi === ""
    ) {

        $error = "Semua data wajib diisi!";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid!";

    } elseif ($password !== $konfirmasi) {

        $error = "Konfirmasi password tidak sama!";

    } else {


        // ==========================================
        // CEK NIK
        // ==========================================

        $cekNik = $conn->prepare(
            "SELECT id FROM karyawan WHERE nik = ? LIMIT 1"
        );

        $cekNik->bind_param("s", $nik);
        $cekNik->execute();

        $hasilNik = $cekNik->get_result();

        if ($hasilNik->num_rows > 0) {

            $error = "NIK sudah terdaftar!";

        }

        $cekNik->close();


        // ==========================================
        // CEK EMAIL
        // ==========================================

        if ($error === "") {

            $cekEmail = $conn->prepare(
                "SELECT id FROM karyawan WHERE email = ? LIMIT 1"
            );

            $cekEmail->bind_param("s", $email);
            $cekEmail->execute();

            $hasilEmail = $cekEmail->get_result();

            if ($hasilEmail->num_rows > 0) {

                $error = "Email sudah terdaftar!";

            }

            $cekEmail->close();
        }


        // ==========================================
        // SIMPAN DATA
        // ==========================================

        if ($error === "") {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


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
                VALUES (?, ?, ?, ?, ?, ?, 'karyawan')
            ");


            $stmt->bind_param(
                "ssssss",
                $nik,
                $nama,
                $jabatan,
                $no_hp,
                $email,
                $passwordHash
            );


            if ($stmt->execute()) {


                // ==========================================
                // PESAN WHATSAPP KARYAWAN
                // ==========================================

                $pesanWA = "✨ *AKUN BERHASIL DIBUAT*

Halo *$nama* 👋

Akun Sistem Absensi kamu berhasil dibuat.

NIK      : $nik
Jabatan  : $jabatan
Email    : $email

Silakan login menggunakan email dan password yang sudah didaftarkan.

Terima kasih 🌸";


                // ==========================================
                // KIRIM WHATSAPP KE KARYAWAN
                // ==========================================

                $hasilWA = kirimWA(
                    $no_hp,
                    $pesanWA
                );


                // ==========================================
                // CEK HASIL WHATSAPP
                // ==========================================

                if (
                    isset($hasilWA["status"]) &&
                    $hasilWA["status"] === true
                ) {

                    $wa_error = "";

                } else {

                    if (isset($hasilWA["reason"])) {

                        $wa_error = $hasilWA["reason"];

                    } elseif (isset($hasilWA["response"])) {

                        $wa_error = json_encode(
                            $hasilWA["response"],
                            JSON_PRETTY_PRINT
                        );

                    } else {

                        $wa_error = "Pesan WhatsApp gagal dikirim.";

                    }
                }


                // ==========================================
                // REGISTER BERHASIL
                // ==========================================

                $success = true;


            } else {

                $error = "Gagal membuat akun: " . $stmt->error;

            }


            $stmt->close();
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

<title>Sistem Absensi - Register</title>


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

    padding: 30px 15px;
}


/* ==========================================
   BUBBLE
========================================== */

.bubble {

    position: fixed;

    border-radius: 50%;

    opacity: .5;
}


.b1 {

    width: 230px;

    height: 230px;

    background: #d9bcff;

    top: -80px;

    left: -70px;
}


.b2 {

    width: 280px;

    height: 280px;

    background: #b7ddff;

    right: -100px;

    bottom: -100px;
}


/* ==========================================
   CARD
========================================== */

.card {

    width: 100%;

    max-width: 600px;

    background:
        rgba(255,255,255,.7);

    backdrop-filter: blur(18px);

    border:
        1px solid
        rgba(255,255,255,.8);

    border-radius: 30px;

    padding: 32px;

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

    width: 70px;

    height: 70px;

    margin: auto;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            #b995ff,
            #83c9ff
        );

    display: flex;

    justify-content: center;

    align-items: center;

    font-size: 32px;
}


h1 {

    text-align: center;

    color: #57466f;

    margin-top: 13px;
}


.subtitle {

    text-align: center;

    color: #888;

    font-size: 14px;

    margin:
        7px 0 25px;
}


/* ==========================================
   FORM
========================================== */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        0 18px;
}


label {

    display: block;

    color: #62556f;

    font-weight: bold;

    font-size: 14px;

    margin:
        13px 0 7px;
}


input {

    width: 100%;

    padding: 13px 15px;

    border:
        1px solid
        #e0d6ec;

    border-radius: 14px;

    background:
        rgba(255,255,255,.85);

    outline: none;
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
   BACK
========================================== */

.back {

    text-align: center;

    margin-top: 18px;

    font-size: 14px;

    color: #777;
}


.back a {

    color: #8163bd;

    font-weight: bold;

    text-decoration: none;
}


/* ==========================================
   SUCCESS POPUP
========================================== */

.success-overlay {

    position: fixed;

    inset: 0;

    background:
        rgba(70,50,90,.35);

    backdrop-filter: blur(7px);

    display: flex;

    justify-content: center;

    align-items: center;

    z-index: 99;

    padding: 20px;
}


.success-box {

    width: 100%;

    max-width: 430px;

    background:
        rgba(255,255,255,.95);

    border-radius: 28px;

    padding: 35px 28px;

    text-align: center;

    box-shadow:
        0 25px 60px
        rgba(70,50,100,.25);
}


.success-icon {

    font-size: 55px;

    margin-bottom: 12px;
}


.success-box h2 {

    color: #604c7b;

    margin-bottom: 10px;
}


.success-box p {

    color: #777;

    line-height: 1.6;
}


.success-box a {

    display: block;

    margin-top: 22px;

    background:
        linear-gradient(
            135deg,
            #a98be5,
            #79bde9
        );

    color: white;

    padding: 13px;

    border-radius: 14px;

    text-decoration: none;

    font-weight: bold;
}


/* ==========================================
   WA WARNING
========================================== */

.wa-warning {

    margin-top: 15px;

    padding: 12px;

    border-radius: 13px;

    background: #fff3cd;

    color: #856404;

    font-size: 13px;

    line-height: 1.5;
}


/* ==========================================
   RESPONSIVE
========================================== */

@media(max-width:600px) {

    .form-grid {

        grid-template-columns: 1fr;
    }


    .card {

        padding:
            25px 20px;
    }

}

</style>

</head>


<body>


<div class="bubble b1"></div>

<div class="bubble b2"></div>


<div class="card">


    <div class="icon">
        👩‍💼
    </div>


    <h1>
        Daftar Karyawan
    </h1>


    <div class="subtitle">

        Buat akun untuk menggunakan
        Sistem Absensi

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <div class="form-grid">


            <div>

                <label>
                    NIK
                </label>

                <input
                    type="text"
                    name="nik"
                    placeholder="Masukkan NIK"
                    value="<?= htmlspecialchars($nik ?? '') ?>"
                    required
                >

            </div>


            <div>

                <label>
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="nama"
                    placeholder="Nama lengkap"
                    value="<?= htmlspecialchars($nama ?? '') ?>"
                    required
                >

            </div>


            <div>

                <label>
                    Jabatan
                </label>

                <input
                    type="text"
                    name="jabatan"
                    placeholder="Contoh: Karyawan"
                    value="<?= htmlspecialchars($jabatan ?? '') ?>"
                    required
                >

            </div>


            <div>

                <label>
                    No. HP
                </label>

                <input
                    type="text"
                    name="no_hp"
                    placeholder="08xxxxxxxxxx"
                    value="<?= htmlspecialchars($no_hp ?? '') ?>"
                    required
                >

            </div>


            <div>

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    required
                >

            </div>


            <div>

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Buat password"
                    required
                >

            </div>


            <div>

                <label>
                    Konfirmasi Password
                </label>

                <input
                    type="password"
                    name="konfirmasi"
                    placeholder="Ulangi password"
                    required
                >

            </div>


        </div>


        <button type="submit">

            ✨ Buat Akun

        </button>


    </form>


    <div class="back">

        Sudah punya akun?

        <a href="index.php">
            Login sekarang
        </a>

    </div>


</div>


<?php if ($success): ?>

<div class="success-overlay">

    <div class="success-box">


        <div class="success-icon">
            🎉
        </div>


        <h2>
            ✨ Akun Berhasil Dibuat!
        </h2>


        <p>

            Akun karyawan kamu berhasil dibuat.

            <br>

            Silakan login menggunakan
            email dan password yang sudah
            didaftarkan.

        </p>


        <?php if ($wa_error !== ""): ?>

            <div class="wa-warning">

                ⚠️ Akun berhasil dibuat,
                tetapi WhatsApp belum terkirim.

                <br><br>

                <?= htmlspecialchars($wa_error) ?>

            </div>

        <?php else: ?>

            <p style="margin-top:12px; color:#4f9b72;">

                📱 Notifikasi WhatsApp berhasil dikirim.

            </p>

        <?php endif; ?>


        <a href="index.php">

            🔐 Login Sekarang

        </a>


    </div>

</div>

<?php endif; ?>


</body>

</html>