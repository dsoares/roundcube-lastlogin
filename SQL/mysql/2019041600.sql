-- Add field to save User-Agent information (dsoares/roundcube-lastlogin#27)

ALTER TABLE userlogins ADD `ua` varchar(255) NOT NULL DEFAULT '' AFTER `tor`;

