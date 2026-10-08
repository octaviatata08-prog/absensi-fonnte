<?php

session_start();

if (isset($_SESSION["login"]) && $_SESSION["login"] === true) {

    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: ../admin/index.php");
        exit;
    }

    if (($_SESSION["role"] ?? "") === "karyawan") {
        header("Location: ../absensi/index.php");
        exit;
    }
}

header("Location: index.php");
exit;