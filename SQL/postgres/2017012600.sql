-- Update size of IP fields to support IPv6 addresses

ALTER TABLE userlogins 
  ALTER COLUMN "ip" TYPE character varying(41),
  ALTER COLUMN "real_ip" TYPE character varying(41);

