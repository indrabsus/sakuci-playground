-- create_roles_table
-- Daftar role yang bisa dipilih saat menambah user. Admin bisa menambah
-- role baru lewat halaman /admin/roles.

CREATE TABLE IF NOT EXISTS `roles` (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Satu-satunya role bawaan. Role lain (staff, user, dst) dibuat sendiri
-- oleh admin lewat /admin/roles -- middleware & route-nya otomatis dibuat.
INSERT INTO `roles` (name, created_at, updated_at)
SELECT * FROM (SELECT 'admin' AS name, NOW() AS created_at, NOW() AS updated_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE name = 'admin');
