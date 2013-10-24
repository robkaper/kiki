ALTER TABLE users ADD verified BOOLEAN NOT NULL DEFAULT false;
UPDATE users SET verified=1;
