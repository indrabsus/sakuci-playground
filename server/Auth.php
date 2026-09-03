<?php

namespace Sakuci;

use PDO;
use Exception;

class Auth
{
    private const SESSION_COOKIE = 'sakuci_session';
    private const SESSION_LIFETIME = 60 * 60 * 24 * 30; // 30 hari

    public static function register(string $name, string $username, string $email, string $password): array
    {
        $name = trim($name);
        $username = strtolower(trim($username));
        $email = strtolower(trim($email));

        if (empty($name)) {
            throw new Exception('Nama lengkap wajib diisi.');
        }

        if (strlen($username) < 3 || !preg_match('/^[a-z0-9_.-]+$/', $username)) {
            throw new Exception('Username minimal 3 karakter (huruf, angka, titik, strip, underscore).');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter.');
        }

        $pdo = Database::getConnection();

        // Cek duplikasi username / email
        $checkStmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $checkStmt->execute([$username, $email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if ($existing['username'] === $username) {
                throw new Exception('Username "' . $username . '" sudah digunakan. Silakan pilih username lain.');
            }
            if ($existing['email'] === $email) {
                throw new Exception('Email "' . $email . '" sudah terdaftar. Silakan gunakan menu Masuk.');
            }
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))");
        
        // Sesuaikan fungsi datetime untuk MySQL vs SQLite
        if (Database::getActiveDriver() === 'mysql') {
            $insertStmt = $pdo->prepare("INSERT INTO `users` (`name`, `username`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, NOW(), NOW())");
        }
        
        $insertStmt->execute([$name, $username, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        // Inisialisasi berkas default workspace untuk user baru
        WorkspaceManager::initializeDefaultFiles($userId);

        // Siapkan tabel latihan unik user (u{userId}_mahasiswa, etc.)
        Database::ensureUserPracticeTables($userId);

        // Buat sesi login otomatis
        $token = self::createSession($userId);

        return [
            'success' => true,
            'message' => 'Pendaftaran berhasil! Selamat datang di Sakuci Ground.',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'username' => $username,
                'email' => $email
            ]
        ];
    }

    public static function login(string $login, string $password): array
    {
        $login = trim($login);
        if (empty($login) || empty($password)) {
            throw new Exception('Username/email dan password wajib diisi.');
        }

        $pdo = Database::getConnection();
        $isMySql = (Database::getActiveDriver() === 'mysql');
        $quote = $isMySql ? '`' : '"';

        $stmt = $pdo->prepare("SELECT * FROM {$quote}users{$quote} WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Username/email atau password salah.');
        }

        $userId = (int)$user['id'];

        // Pastikan berkas user siap
        WorkspaceManager::initializeDefaultFiles($userId);

        // Pastikan tabel latihan user siap
        Database::ensureUserPracticeTables($userId);

        // Buat sesi
        $token = self::createSession($userId);

        return [
            'success' => true,
            'message' => 'Login berhasil! Selamat datang kembali, ' . htmlspecialchars($user['name']) . '.',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ];
    }

    public static function createSession(int $userId): string
    {
        $pdo = Database::getConnection();
        $token = bin2hex(random_bytes(32));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME);

        $isMySql = (Database::getActiveDriver() === 'mysql');
        $quote = $isMySql ? '`' : '"';

        $stmt = $pdo->prepare("INSERT INTO {$quote}user_sessions{$quote} (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $token, $ip, $ua, $expiresAt]);

        // Simpan di cookie HTTP
        setcookie(self::SESSION_COOKIE, $token, [
            'expires' => time() + self::SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return $token;
    }

    public static function getTokenFromRequest(): ?string
    {
        // 1. Dari Cookie
        if (!empty($_COOKIE[self::SESSION_COOKIE])) {
            return trim($_COOKIE[self::SESSION_COOKIE]);
        }

        // 2. Dari Header Authorization: Bearer <token>
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // 3. Dari query parameter ?token=
        if (!empty($_GET['token'])) {
            return trim($_GET['token']);
        }

        return null;
    }

    public static function user(): ?array
    {
        $token = self::getTokenFromRequest();
        if (empty($token)) {
            return null;
        }

        try {
            $pdo = Database::getConnection();
            $isMySql = (Database::getActiveDriver() === 'mysql');
            $quote = $isMySql ? '`' : '"';
            $nowFunc = $isMySql ? 'NOW()' : "datetime('now')";

            $sql = "SELECT u.id, u.name, u.username, u.email, u.created_at, s.expires_at 
                    FROM {$quote}users{$quote} u
                    JOIN {$quote}user_sessions{$quote} s ON u.id = s.user_id
                    WHERE s.session_token = ? AND s.expires_at > {$nowFunc}
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            return [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'created_at' => $user['created_at']
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function logout(): bool
    {
        $token = self::getTokenFromRequest();
        if (!empty($token)) {
            try {
                $pdo = Database::getConnection();
                $isMySql = (Database::getActiveDriver() === 'mysql');
                $quote = $isMySql ? '`' : '"';
                $stmt = $pdo->prepare("DELETE FROM {$quote}user_sessions{$quote} WHERE session_token = ?");
                $stmt->execute([$token]);
            } catch (\Throwable $e) {}
        }

        setcookie(self::SESSION_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return true;
    }
}
