-- PostgreSQL initial database structure for lastlogin plugin.

--
-- Sequence "userlogins_seq"
-- Name: userlogins_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE userlogins_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

--
-- Table "userlogins"
-- Name: userlogins; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE userlogins (
    "id" integer DEFAULT nextval('userlogins_seq'::text) PRIMARY KEY,
    "timestamp" timestamp with time zone DEFAULT now() NOT NULL,
    "user_id" integer NOT NULL,
    "username" varchar(128) DEFAULT '' NOT NULL,
    "sess_id" varchar(128) DEFAULT '' NOT NULL,
    "ip" varchar(41) DEFAULT '' NOT NULL,
    "real_ip" varchar(41) DEFAULT '' NOT NULL,
    "hostname" varchar(255) DEFAULT '' NOT NULL,
    "geoloc" varchar(255) DEFAULT '' NOT NULL,
    "tor" boolean NOT NULL DEFAULT FALSE
);

CREATE INDEX userlogins_user_id_idx ON userlogins (user_id);
