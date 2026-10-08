<?php

session_start();

require_once "config/fonnte.php";

$success = false;
$error = "";
$responseData = null;

$nomorInput = "";
$pesanInput = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nomorInput = trim($_POST["nomor"] ?? "");
    $pesanInput = trim($_POST["pesan"] ?? "");

    if ($nomorInput === "") {
        $error = "Nomor WhatsApp wajib diisi!";

    } elseif ($pesanInput === "") {
        $error = "Pesan WhatsApp wajib diisi!";

    } elseif (!function_exists("kirimWA")) {
        $error = "Fungsi kirimWA() tidak ditemukan. Cek config/fonnte.php!";

    } else {

        $hasil = kirimWA($nomorInput, $pesanInput);

        $responseData = $hasil;

        if (($hasil["status"] ?? false) === true) {
            $success = true;
        } else {

            if (!empty($hasil["reason"])) {
                $error = $hasil["reason"];

            } elseif (!empty($hasil["response"]["reason"])) {
                $error = $hasil["response"]["reason"];

            } elseif (!empty($hasil["response"]["detail"])) {
                $error = $hasil["response"]["detail"];

            } else {
                $error = "WhatsApp gagal dikirim. Cek token dan koneksi Fonnte.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Test WhatsApp Fonnte</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;

    background:
        radial-gradient(circle at 10% 20%, rgba(255,255,255,.9), transparent 30%),
        radial-gradient(circle at 90% 80%, rgba(210,190,255,.45), transparent 30%),
        linear-gradient(135deg, #dfe9ff, #f6eaff);

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 25px;

    overflow-x: hidden;
}

/* BUBBLES */

.bubble {
    position: fixed;
    border-radius: 50%;
    background: rgba(255,255,255,.35);
    backdrop-filter: blur(5px);
    pointer-events: none;
}

.bubble.one {
    width: 180px;
    height: 180px;
    top: -50px;
    left: -50px;
}

.bubble.two {
    width: 120px;
    height: 120px;
    right: 5%;
    top: 10%;
}

.bubble.three {
    width: 220px;
    height: 220px;
    bottom: -80px;
    left: 10%;
}

.bubble.four {
    width: 90px;
    height: 90px;
    right: 15%;
    bottom: 10%;
}

/* CARD */

.container {
    width: 100%;
    max-width: 550px;

    background: rgba(255,255,255,.72);

    border: 1px solid rgba(255,255,255,.8);

    backdrop-filter: blur(18px);

    border-radius: 28px;

    padding: 35px;

    box-shadow:
        0 25px 70px rgba(76, 64, 130, .18);

    position: relative;

    z-index: 5;
}

.header {
    text-align: center;
    margin-bottom: 28px;
}

.icon {
    width: 75px;
    height: 75px;

    margin: auto;
    margin-bottom: 15px;

    border-radius: 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 38px;

    background: linear-gradient(
        135deg,
        #d8c8ff,
        #bcd8ff
    );

    box-shadow:
        0 12px 25px rgba(100,80,180,.18);
}

h1 {
    color: #4c4770;
    font-size: 25px;
    margin-bottom: 8px;
}

.subtitle {
    color: #77728d;
    font-size: 14px;
}

/* FORM */

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;

    margin-bottom: 8px;

    color: #514d6d;

    font-size: 14px;

    font-weight: bold;
}

input,
textarea {
    width: 100%;

    border: 1px solid #ddd8ee;

    background: rgba(255,255,255,.8);

    border-radius: 15px;

    padding: 14px 16px;

    outline: none;

    font-size: 14px;

    color: #444;

    transition: .25s;
}

input:focus,
textarea:focus {
    border-color: #a995e8;

    box-shadow:
        0 0 0 4px rgba(169,149,232,.13);
}

textarea {
    min-height: 130px;
    resize: vertical;
}

/* BUTTON */

button.send {
    width: 100%;

    border: none;

    padding: 15px;

    border-radius: 16px;

    background: linear-gradient(
        135deg,
        #9f8de0,
        #7ca9e8
    );

    color: white;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    box-shadow:
        0 10px 25px rgba(120,100,190,.25);

    transition: .25s;
}

button.send:hover {
    transform: translateY(-2px);

    box-shadow:
        0 14px 30px rgba(120,100,190,.32);
}

button.send:active {
    transform: scale(.98);
}

/* INFO */

.info {
    margin-top: 20px;

    padding: 14px 16px;

    border-radius: 15px;

    background: rgba(239,235,255,.7);

    color: #68627d;

    font-size: 12px;

    line-height: 1.6;
}

/* RESPONSE */

.response {
    margin-top: 20px;

    padding: 15px;

    border-radius: 15px;

    background: #f5f3fa;

    color: #555;

    font-size: 12px;

    overflow-x: auto;
}

.response pre {
    white-space: pre-wrap;
    word-break: break-word;
}

/* TOAST */

.toast {
    position: fixed;

    top: 25px;
    right: 25px;

    min-width: 330px;
    max-width: 430px;

    padding: 16px 18px;

    border-radius: 18px;

    display: flex;

    align-items: center;

    gap: 12px;

    z-index: 99999;

    box-shadow:
        0 18px 45px rgba(60,50,100,.22);

    backdrop-filter: blur(18px);

    animation: toastMasuk .45s ease;

    transition:
        opacity .4s ease,
        transform .4s ease;
}

.toast.success {
    background: rgba(225,255,237,.96);

    border: 1px solid #a4e7bb;

    color: #176b38;
}

.toast.error {
    background: rgba(255,232,232,.96);

    border: 1px solid #f1aaaa;

    color: #9c2929;
}

.toast-icon {
    width: 42px;
    height: 42px;

    flex-shrink: 0;

    border-radius: 50%;

    background: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 21px;

    font-weight: bold;
}

.toast-content {
    flex: 1;
}

.toast-content strong {
    display: block;

    font-size: 14px;

    margin-bottom: 4px;
}

.toast-content span {
    display: block;

    font-size: 12px;

    line-height: 1.5;
}

.toast-close {
    border: none;

    background: transparent;

    color: inherit;

    font-size: 22px;

    cursor: pointer;

    line-height: 1;

    padding: 3px;
}

@keyframes toastMasuk {

    from {
        opacity: 0;
        transform: translateX(50px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }

}

/* MOBILE */

@media (max-width: 600px) {

    body {
        padding: 15px;
    }

    .container {
        padding: 25px 20px;
        border-radius: 22px;
    }

    h1 {
        font-size: 22px;
    }

    .toast {
        top: 15px;
        left: 15px;
        right: 15px;

        min-width: unset;
        max-width: unset;
    }

}

</style>

</head>

<body>

<div class="bubble one"></div>
<div class="bubble two"></div>
<div class="bubble three"></div>
<div class="bubble four"></div>

<div class="container">

    <div class="header">

        <div class="icon">
            📱
        </div>

        <h1>Test WhatsApp Fonnte</h1>

        <div class="subtitle">
            Kirim pesan WhatsApp untuk mengecek koneksi Fonnte
        </div>

    </div>


    <form method="POST">

        <div class="form-group">

            <label>
                Nomor WhatsApp
            </label>

            <input
                type="text"
                name="nomor"
                placeholder="Contoh: 081234567890"
                value="<?= htmlspecialchars($nomorInput) ?>"
                autocomplete="off"
            >

        </div>


        <div class="form-group">

            <label>
                Pesan
            </label>

            <textarea
                name="pesan"
                placeholder="Tulis pesan WhatsApp..."
            ><?= htmlspecialchars($pesanInput) ?></textarea>

        </div>


        <button
            type="submit"
            class="send"
        >
            📲 Kirim WhatsApp
        </button>

    </form>


    <div class="info">

        💡 <b>Tips:</b><br>

        Nomor boleh menggunakan format
        <b>08xxxxxxxxxx</b> atau
        <b>628xxxxxxxxxx</b>.

        Sistem akan otomatis mengubah format
        08 menjadi 62.

    </div>


    <?php if ($responseData): ?>

        <div class="response">

            <b>Response Fonnte:</b>

            <pre><?= htmlspecialchars(
                json_encode(
                    $responseData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            ) ?></pre>

        </div>

    <?php endif; ?>

</div>


<?php if ($success): ?>

    <div
        class="toast success"
        id="toast"
    >

        <div class="toast-icon">
            ✓
        </div>

        <div class="toast-content">

            <strong>
                WhatsApp Berhasil Dikirim!
            </strong>

            <span>
                Pesan berhasil dikirim melalui Fonnte.
            </span>

        </div>

        <button
            class="toast-close"
            onclick="tutupToast()"
            type="button"
        >
            ×
        </button>

    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div
        class="toast error"
        id="toast"
    >

        <div class="toast-icon">
            !
        </div>

        <div class="toast-content">

            <strong>
                WhatsApp Gagal!
            </strong>

            <span>
                <?= htmlspecialchars($error) ?>
            </span>

        </div>

        <button
            class="toast-close"
            onclick="tutupToast()"
            type="button"
        >
            ×
        </button>

    </div>

<?php endif; ?>


<script>

function tutupToast() {

    const toast = document.getElementById("toast");

    if (!toast) {
        return;
    }

    toast.style.opacity = "0";
    toast.style.transform = "translateX(50px)";

    setTimeout(() => {

        toast.remove();

    }, 400);

}


/* Otomatis hilang setelah 5 detik */

setTimeout(() => {

    tutupToast();

}, 5000);

</script>

</body>

</html>