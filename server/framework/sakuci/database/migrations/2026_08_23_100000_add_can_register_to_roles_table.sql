-- add_can_register_to_roles_table
-- Role dengan can_register = 1 muncul sebagai pilihan di halaman /register.

ALTER TABLE `roles` ADD COLUMN can_register TINYINT(1) NOT NULL DEFAULT 0;
