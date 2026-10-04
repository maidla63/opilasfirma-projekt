-- Kooli halduri ligipääs dashboardile
ALTER TABLE users ADD COLUMN IF NOT EXISTS manages_school_id INT UNSIGNED NULL;

-- Näide: anna direktorile ligipääs oma kooli andmetele (asenda email ja kooli nimi):
-- UPDATE users SET manages_school_id = (SELECT id FROM schools WHERE name = 'Minu Kool')
--   WHERE email = 'direktor@kool.ee';
