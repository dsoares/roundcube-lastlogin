-- Update field name
ALTER TABLE userlogins CHANGE sess_id  session_id VARCHAR(128) NOT NULL DEFAULT '';
