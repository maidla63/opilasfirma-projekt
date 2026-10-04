ALTER TABLE users ADD COLUMN IF NOT EXISTS nickname VARCHAR(20) NULL, ADD UNIQUE KEY IF NOT EXISTS uq_nick (nickname);

CREATE TABLE IF NOT EXISTS menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NOT NULL,
  menu_date DATE NOT NULL,
  meal_name VARCHAR(120) NOT NULL,
  UNIQUE KEY uq_menu (school_id, menu_date, meal_name),
  KEY idx_menu (school_id, menu_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reports (
  review_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
