-- XAMPP = MariaDB, seega IF NOT EXISTS töötab. Tee enne phpMyAdminis eksport (backup)!
CREATE TABLE IF NOT EXISTS schools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schools(name) SELECT DISTINCT school FROM reviews WHERE school <> '';

ALTER TABLE reviews
  ADD COLUMN IF NOT EXISTS school_id INT UNSIGNED NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS eaten_percent TINYINT UNSIGNED NULL COMMENT '0,25,50,75,100',
  ADD COLUMN IF NOT EXISTS tags VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'komaga eraldatud',
  ADD COLUMN IF NOT EXISTS meal_date DATE NULL,
  ADD COLUMN IF NOT EXISTS status ENUM('visible','hidden','flagged') NOT NULL DEFAULT 'visible';

UPDATE reviews r JOIN schools s ON s.name = r.school SET r.school_id = s.id WHERE r.school_id IS NULL;
UPDATE reviews SET meal_date = DATE(created_at) WHERE meal_date IS NULL;

-- üks hääl kasutaja kohta hinnangu kohta
ALTER TABLE votes ADD UNIQUE KEY IF NOT EXISTS uq_vote (review_id, user_id);
-- kiirus analüütika jaoks
ALTER TABLE reviews ADD INDEX IF NOT EXISTS idx_school_date (school_id, meal_date);
