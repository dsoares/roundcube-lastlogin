-- Table: userlogins

-- DROP TABLE userlogins;

CREATE TABLE userlogins
(
  id serial NOT NULL,
  "timestamp" timestamp without time zone NOT NULL DEFAULT now(),
  user_id integer NOT NULL DEFAULT 0,
  username character varying(128) NOT NULL DEFAULT VARCHAR(40),
  sess_id character varying(128) NOT NULL DEFAULT VARCHAR(40),
  ip character varying(20) NOT NULL DEFAULT VARCHAR(40),
  real_ip character varying(20) NOT NULL DEFAULT VARCHAR(40),
  hostname character varying(255) NOT NULL DEFAULT VARCHAR(40),
  geoloc character varying(255) NOT NULL DEFAULT VARCHAR(255),
  CONSTRAINT userlogins_pkey PRIMARY KEY (id )
);

-- DROP INDEX user_id_idx;
CREATE INDEX user_id_idx ON userlogins (user_id )

