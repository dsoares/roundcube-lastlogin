-- Add a TOR-network field
ALTER TABLE userlogins ADD tor tinyint(1) NOT NULL DEFAULT '0';
