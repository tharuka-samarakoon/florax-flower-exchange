-- FloraX demo login repair
-- Run this if the flower_exchange database already exists and you do not want to re-import the full database.
USE flower_exchange;

UPDATE users
SET password_hash='$2y$12$Za4arToaXYS0OCgaQCIBZuyEVIXnNZNpigw7qk7ZpChSCjRqgT632', active=1, role='buyer'
WHERE email='buyer@florax.test';

UPDATE users
SET password_hash='$2y$12$KDy6dCGoZENZ9hDQpzZsyeKkDL00glPbWmf8RngfV2bg.nsS3hLPO', active=1, role='seller'
WHERE email='seller@florax.test';
