-- Update size of IP fields to support IPv6 addresses

ALTER TABLE userlogins MODIFY ip varchar(40) NOT NULL DEFAULT '0';
ALTER TABLE userlogins MODIFY real_ip varchar(40) NOT NULL DEFAULT '0';

