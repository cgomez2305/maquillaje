-- Cerrar las demás sesiones al cambiar o restablecer la contraseña (ASVS 3.3.3).
-- Las sesiones iniciadas antes de sessions_valid_after dejan de ser válidas.
ALTER TABLE users ADD COLUMN sessions_valid_after DATETIME NULL AFTER last_login_at;
