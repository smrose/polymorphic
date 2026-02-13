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
INSERT INTO pattern_view(name, ptid) VALUES ('Liberating Voices Test', 2);
--
-- 'url', type string
--
INSERT INTO pattern_feature (name, type) VALUES ('url', 'string');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 1), (2, 1);
--
-- 'title', type 'string'
--
INSERT INTO pattern_feature (name, type, required) VALUES ('title', 'string', 1);
INSERT INTO pt_feature (ptid, fid) VALUES (1, 2), (2, 2);
--
-- 'problem', type 'text'
--
INSERT INTO pattern_feature (name, type) VALUES ('problem', 'text');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 3), (2, 3);
--
-- 'discussion', type 'text'
--
INSERT INTO pattern_feature (name, type) VALUES ('discussion', 'text');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 4), (2, 4);
--
-- 'context', type 'text'
--
INSERT INTO pattern_feature (name, type) VALUES ('context', 'text');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 5), (2, 5);
--
-- 'pf_solution' , type 'text'
--
INSERT INTO pattern_feature (name, type) VALUES ('solution', 'text');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 6), (2, 6);
--
-- 'pf_card', type 'text'
--
INSERT INTO pattern_feature (name, type) VALUES ('card', 'text');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 7), (2, 7);
--
-- 'pf_synopsis', type 'text'
--
INSERT INTO pattern_feature (name, type) VALUES ('synopsis', 'text');
INSERT INTO pt_feature (ptid, fid) VALUES (1, 8), (2, 8);
