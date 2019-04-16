-- Add field to save User-Agent information (dsoares/roundcube-lastlogin#27)

ALTER TABLE userlogins ADD COLUMN "ua" TYPE character varying(255);
