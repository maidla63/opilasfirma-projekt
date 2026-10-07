-- Kas haldur saab igakuist raportit e-mailile (1 = jah)
ALTER TABLE users ADD COLUMN IF NOT EXISTS report_monthly TINYINT(1) NOT NULL DEFAULT 1;
