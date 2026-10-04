-- Käivita ÜKS KORD, enne kui uued kasutajad registreeruma hakkavad.
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS verified TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL;
-- olemasolevad kontod (sina, testkasutajad) loeme kinnitatuks:
UPDATE users SET verified = 1, verified_at = NOW() WHERE verified = 0;
