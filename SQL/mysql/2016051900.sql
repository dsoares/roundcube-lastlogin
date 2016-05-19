-- Update field name
ALTER TABLE userlogins CHANGE session_id sess_id VARCHAR(128) NOT NULL DEFAULT '';
