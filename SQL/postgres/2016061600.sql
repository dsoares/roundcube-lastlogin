-- Add a TOR-network field (split in steps for performance)
ALTER TABLE userlogins ADD COLUMN "tor" BOOLEAN;
UPDATE userlogins SET "tor" = 'f';
ALTER TABLE userlogins ALTER COLUMN "tor" SET NOT NULL;
