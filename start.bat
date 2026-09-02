@echo off
title Sakuci PHP & MySQL Ground
echo ========================================================
echo        🐘 Sakuci PHP & MySQL Playground 🚀
echo ========================================================
echo.

where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] PHP tidak ditemukan di PATH sistem Anda!
    echo Pastikan PHP 8.x sudah terinstal dan ditambahkan ke PATH.
    pause
    exit /b 1
)

echo [*] Menyiapkan server lokal PHP...
echo [*] Membuka browser ke http://localhost:8000
start "" "http://localhost:8000"

echo [*] Server berjalan di http://127.0.0.1:8000
echo [*] Tekan Ctrl + C untuk menghentikan server.
echo.

php -d extension=pdo_sqlite -d extension=sqlite3 -S 127.0.0.1:8000 router.php
