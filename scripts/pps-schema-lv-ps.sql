--
-- NAME
--
--  pps-schema-lv-ps.sql
--
-- CONCEPT
--
--  Create pattern features and pattern feature value tables for Liberating
--  Voices patterns in the context of Pattern Sphere.
--
-- NOTES
--
--  Here is the existing definition of a pattern in PS:
--
--   CREATE TABLE pattern (
--    id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
--    plid int(10) unsigned NOT NULL COMMENT 'id of parent planguage',
--    url varchar(255) DEFAULT NULL COMMENT 'optional URL',
--    prank int(10) unsigned NOT NULL COMMENT 'order of pattern in language',
--    title varchar(255) DEFAULT NULL COMMENT 'pattern title',
--    problem longtext DEFAULT NULL COMMENT 'problem statement',
--    discussion longtext DEFAULT NULL COMMENT 'pattern discussion',
--    context longtext DEFAULT NULL COMMENT 'pattern context',
--    solution longtext DEFAULT NULL COMMENT 'pattern solution',
--    card longtext DEFAULT NULL COMMENT 'card content',
--    image varchar(255) DEFAULT NULL COMMENT 'graphic image path',
--    synopsis longtext DEFAULT NULL COMMENT 'brief description',
--    creator int(11) DEFAULT NULL,
--    PRIMARY KEY (id),
--    UNIQUE KEY plid (plid,prank),
--    KEY creator (creator),
--    CONSTRAINT pattern_ibfk_1 FOREIGN KEY (plid) REFERENCES planguage (id),
--    CONSTRAINT pattern_ibfk_2 FOREIGN KEY (creator) REFERENCES phpauth_users (id)
--  );
--
-- $Id: pps-schema-lv-ps.sql,v 1.3 2025/12/19 15:59:12 rose Exp rose $

use pps;
--
-- create pattern template
--
INSERT INTO pattern_template (name) VALUES ('Liberating Voices');
--
-- create pattern language
--
INSERT INTO pattern_language (name) VALUES ('Liberating Voices');
--
-- pattern_view
--
INSERT INTO pattern_view(name) VALUES ('Pattern Sphere');
--
-- 'pf_url' is a pattern feature value table for 'url', type 'tinytext'
--
INSERT INTO pattern_feature (name, type) VALUES ('url', 'tinytext');
CREATE TABLE IF NOT EXISTS pf_url (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value TINYTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_title' is a pattern feature value table for 'title', type 'tinytext'
--
INSERT INTO pattern_feature (name, type, required) VALUES ('title', 'tinytext', 1);
CREATE TABLE IF NOT EXISTS pf_title (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value TINYTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_problem' is a pattern feature value table for 'problem', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('problem', 'longtext');
CREATE TABLE IF NOT EXISTS pf_problem (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_discussion' is a pattern feature value table for 'discussion', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('discussion', 'longtext');
CREATE TABLE IF NOT EXISTS pf_discussion (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_context' is a pattern feature value table for 'context', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('context', 'longtext');
CREATE TABLE IF NOT EXISTS pf_context (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_solution' is a pattern feature value table for 'solution', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('solution', 'longtext');
CREATE TABLE IF NOT EXISTS pf_solution (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_card' is a pattern feature value table for 'card', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('card', 'longtext');
CREATE TABLE IF NOT EXISTS pf_card (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_image' is a pattern feature value table for 'image', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('image', 'longtext');
CREATE TABLE IF NOT EXISTS pf_image (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
--
-- 'pf_synopsis' is a pattern feature value table for 'synopsis', type 'longtext'
--
INSERT INTO pattern_feature (name, type) VALUES ('synopsis', 'longtext');
CREATE TABLE IF NOT EXISTS pf_synopsis (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value LONGTEXT NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
);
