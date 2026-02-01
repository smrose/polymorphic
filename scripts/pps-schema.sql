--
-- NAME
--
--  pps-schema.sql
--
-- CONCEPT
--
--  Create a pps MySQL database and user to implement a "polymorphic" Pattern Sphere
--  schema.
--
-- $Id: pps-schema.sql,v 1.1 2025/12/31 13:02:56 rose Exp rose $

--
-- create the database and user
--
CREATE DATABASE IF NOT EXISTS pps
 CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;
use pps;
GRANT ALL PRIVILEGES ON pps.* TO 'pps'@'localhost'
 IDENTIFIED BY 'Pa++3rn 5ph3r3';
FLUSH PRIVILEGES;
--
-- create pattern_template
--
CREATE TABLE IF NOT EXISTS pattern_template (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL UNIQUE,
  notes varchar(16383) DEFAULT NULL,
  created timestamp NOT NULL DEFAULT current_timestamp(),
  modified timestamp NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp()
);
INSERT INTO pattern_template(name, notes)
  VALUES ('All features',
          'All features are automatically available in this template');
--
-- 'pattern'
--
CREATE TABLE IF NOT EXISTS pattern (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ptid int unsigned NOT NULL,
  notes varchar(16383),
  created timestamp NOT NULL DEFAULT current_timestamp(),
  modified timestamp NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT FOREIGN KEY (ptid) REFERENCES pattern_template(id)
   ON DELETE CASCADE
);
--
-- supported languages keyed on 2-letter code
--
CREATE TABLE IF NOT EXISTS language (
  code CHAR(2) NOT NULL,
  description varchar(255) NOT NULL,
  PRIMARY KEY (code)
);
INSERT INTO language(code, description) VALUES('en', 'US English');
--
-- 'pattern_language' defines a named collection of patterns
--
CREATE TABLE IF NOT EXISTS pattern_language (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ptid int unsigned NOT NULL,
  name varchar(255) NOT NULL UNIQUE,
  notes varchar(16383),
  created timestamp NOT NULL DEFAULT current_timestamp(),
  modified timestamp NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT FOREIGN KEY(ptid) REFERENCES pattern_template(id)
);
--
-- 'pattern_feature' defines a feature, which has an id, name, data
-- type, and 'required' field. Each pattern has a collection of
-- features with values. The values are in per-feature tables that are
-- created when a 'pattern_feature' is added.
--
CREATE TABLE IF NOT EXISTS pattern_feature (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL UNIQUE,
  type enum('string', 'text', 'image', 'integer'),
  required tinyint(1) DEFAULT 0,
  notes varchar(16383) DEFAULT NULL,
  created timestamp NOT NULL DEFAULT current_timestamp(),
  modified timestamp NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp()
);
--
-- pt_feature associates features with a template
--
 CREATE TABLE IF NOT EXISTS pt_feature (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ptid int unsigned NOT NULL,
  fid int unsigned NOT NULL,
  created timestamp NOT NULL DEFAULT current_timestamp(),
  modified timestamp NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT UNIQUE (ptid, fid),
  CONSTRAINT FOREIGN KEY(ptid) REFERENCES pattern_template(id)
   ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY(fid) REFERENCES pattern_feature(id)
   ON DELETE CASCADE
 );
--
-- 'plmember' links patterns to a language
--
CREATE TABLE IF NOT EXISTS plmember (
  pid int unsigned NOT NULL,
  plid int unsigned NOT NULL,
  CONSTRAINT UNIQUE(plid, pid),
  FOREIGN KEY (pid) REFERENCES pattern(id),
  FOREIGN KEY (plid) REFERENCES pattern_language(id)
);
--
-- 'pattern_view' describes how pattern features are displayed in a context.
--
CREATE TABLE IF NOT EXISTS pattern_view (
  id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ptid int unsigned NOT NULL,
  name varchar(255) NOT NULL,
  layout mediumtext,
  created timestamp NOT NULL DEFAULT current_timestamp(),
  modified timestamp NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  notes varchar(16383),
  CONSTRAINT FOREIGN KEY (ptid) REFERENCES pattern_template(id)
);
