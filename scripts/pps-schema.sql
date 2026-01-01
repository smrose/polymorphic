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
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  notes VARCHAR(16383) DEFAULT NULL
);
--
-- 'pattern'
--
CREATE TABLE IF NOT EXISTS pattern (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ptid INT UNSIGNED NOT NULL,
  notes VARCHAR(16383),
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT FOREIGN KEY (ptid) REFERENCES pattern_template(id)
   ON DELETE CASCADE
);
--
-- supported languages keyed on 2-letter code
--
CREATE TABLE IF NOT EXISTS language (
  code CHAR(2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  PRIMARY KEY (code)
);
--
-- pattern note with language
--
CREATE TABLE IF NOT EXISTS pattern_note (
  note TINYTEXT NOT NULL,
  pid INT UNSIGNED NOT NULL,
  language CHAR(2) NOT NULL DEFAULT 'en',
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT FOREIGN KEY(language) REFERENCES language(code),
  CONSTRAINT FOREIGN KEY(pid) REFERENCES pattern(id)
);
--
-- 'pattern_language' defines a named collection of patterns
--
CREATE TABLE IF NOT EXISTS pattern_language (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  notes VARCHAR(16383),
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp()
);
--
-- 'pattern_feature' defines a feature, which has an id, name, data
-- type, and 'required' field. Each pattern has a collection of
-- features with values. The values are in per-feature tables that are
-- created when a 'pattern_feature' is added.
--
CREATE TABLE IF NOT EXISTS pattern_feature (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  type ENUM('string', 'text', 'image', 'integer'),
  required tinyint(1) DEFAULT 0,
  notes VARCHAR(16383) DEFAULT NULL,
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp()
);
--
-- pt_feature associates features with a template
--
 CREATE TABLE IF NOT EXISTS pt_feature (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ptid INT UNSIGNED NOT NULL,
  fid INT UNSIGNED NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT UNIQUE (ptid, fid),
  CONSTRAINT FOREIGN KEY(ptid) REFERENCES pattern_template(id)
   ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY(fid) REFERENCES pattern_feature(id)
   ON DELETE CASCADE
 );
--
-- 'pattern_view' describes how pattern features are displayed in a context.
--
CREATE TABLE IF NOT EXISTS pattern_view (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  notes VARCHAR(16383)
);
--
-- 'pattern_view_feature_position' defines the ordered pattern features used
-- in a 'pattern_view'
--
CREATE TABLE IF NOT EXISTS pattern_view_feature_position (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pfid INT UNSIGNED NOT NULL,
  pvfp INT UNSIGNED NOT NULL,
  `order` INT UNSIGNED NOT NULL UNIQUE,
  CONSTRAINT FOREIGN KEY(pfid) REFERENCES pattern_feature(id),
  CONSTRAINT FOREIGN KEY(pvfp) REFERENCES pattern_view(id)
);
