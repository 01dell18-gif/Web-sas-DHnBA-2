@echo off
title SAS - SMK NEGERI 1 KLUNGKUNG Local Server
color 0b
echo =====================================================================
echo    SISTEM ADMINISTRASI UJIAN (SAS) - SMK NEGERI 1 KLUNGKUNG
echo    Generator Absensi dan Berita Acara Pelaksanaan Ujian (A4 PDF)
echo =====================================================================
echo.

set PHP_BIN=C:\xampp\php\php.exe
if not exist "%PHP_BIN%" (
    echo [ERROR] PHP tidak ditemukan di C:\xampp\php\php.exe.
    echo Pastikan XAMPP terpasang di drive C:, atau sesuaikan path PHP.
    pause
    exit /b 1
)

echo [1/2] Menyiapkan server lokal PHP 8.2...
echo [2/2] Server berjalan di: http://localhost:8080
echo.
echo Login Admin:
echo   URL      : http://localhost:8080/admin/login.php
echo   Username : admin
echo   Password : admin123
echo.
echo Tekan Ctrl + C untuk menghentikan server.
echo =====================================================================
echo.

start http://localhost:8080/

"%PHP_BIN%" -d extension_dir="C:\xampp\php\ext" -d browscap="" -d extension=gd -S 127.0.0.1:8080
pause
