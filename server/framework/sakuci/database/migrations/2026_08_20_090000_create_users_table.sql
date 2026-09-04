-- create_users_table
-- Tabel user dengan kolom role untuk login multi-role (admin/staff/user).

CREATE TABLE IF NOT EXISTS `users` (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(255) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(50)  NOT NULL DEFAULT 'user',
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Satu-satunya akun bawaan: admin, password rahasia123
-- (hash dibuat dengan password_hash(), lihat /docs untuk penjelasan)
-- Role lain (staff, user, dst) dibuat sendiri oleh admin lewat /admin/roles
-- dan /admin/users setelah instalasi pertama.
INSERT INTO `users` (username, password, role, created_at, updated_at)
SELECT * FROM (SELECT 'admin' AS username, '$2y$10$2qINBU7JUITh89Z4fS3aR.F0nBgX5TD7hrg7lnGdDm3OW4jQU1t4q' AS password, 'admin' AS role, NOW() AS created_at, NOW() AS updated_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE username = 'admin');
