# $Id$

#
# Table structure for table 'liveuser_translations'
#
DROP TABLE IF EXISTS `liveuser_translations`;
CREATE TABLE `liveuser_translations` (
    `section_id` int(11) unsigned NOT NULL default '0',
    `section_type` tinyint(3) unsigned NOT NULL default '0',
    `language_id` varchar(2) NOT NULL default '',
    `name` varchar(50) NOT NULL default '',
    `description` varchar(255) default NULL,
    PRIMARY KEY (`section_id`,`section_type`, `language_id`)
);

# Dumping data for table `liveuser_users`
#

INSERT INTO liveuser_users (auth_user_id, handle, passwd, lastlogin, owner_user_id, owner_group_id, is_active) VALUES ('c4ca4238a0b923820dcc509a6f75849b', 'boss', 'test', NULL, NULL, 1, 'Y');
INSERT INTO liveuser_users (auth_user_id, handle, passwd, lastlogin, owner_user_id, owner_group_id, is_active) VALUES ('c81e728d9d4c2f636f067f89cc14862c', 'hoss', 'bonanza', NULL, 1, NULL, 'Y');

#
# Dumping data for table `liveuser_areas`
#

INSERT INTO liveuser_areas (area_id, application_id, area_define_name) VALUES (1, 0, 'TestArea');
INSERT INTO liveuser_areas (area_id, application_id, area_define_name) VALUES (2, 0, 'Area51');
INSERT INTO liveuser_areas (area_id, application_id, area_define_name) VALUES (3, 0, 'Coffeemaker');

#
# Dumping data for table `liveuser_grouprights`
#

INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (1, 1, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (1, 2, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (1, 7, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (2, 3, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (2, 4, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (2, 5, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (2, 6, 1);
INSERT INTO liveuser_grouprights (group_id, right_id, right_level) VALUES (2, 7, 1);

#
# Dumping data for table `liveuser_groups`
#

INSERT INTO liveuser_groups (group_id, owner_user_id, owner_group_id, is_active, group_define_name) VALUES (1, 1, 1, 'Y', 'Group1');
INSERT INTO liveuser_groups (group_id, owner_user_id, owner_group_id, is_active, group_define_name) VALUES (2, 1, 1, 'Y', 'Group2');

#
# Dumping data for table `liveuser_groupusers`
#

INSERT INTO liveuser_groupusers (perm_user_id, group_id) VALUES (1, 1);
INSERT INTO liveuser_groupusers (perm_user_id, group_id) VALUES (1, 2);
INSERT INTO liveuser_groupusers (perm_user_id, group_id) VALUES (2, 1);

#
# Dumping data for table `liveuser_perm_users`
#

INSERT INTO liveuser_perm_users (perm_user_id, auth_user_id, perm_type, auth_container_name) VALUES (1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 'DB');
INSERT INTO liveuser_perm_users (perm_user_id, auth_user_id, perm_type, auth_container_name) VALUES (2, 'c81e728d9d4c2f636f067f89cc14862c', 1, 'DB');

#
# Dumping data for table `liveuser_rights`
#

INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (1, 1, 'READ_TESTS', 'N', 'N');
INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (2, 1, 'WRITE_TESTS', 'N', 'N');
INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (3, 2, 'ACCESS', 'N', 'Y');
INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (4, 2, 'LAUNCH_ATOMIC_BOMB', 'N', 'N');
INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (5, 2, 'FLY_ALIEN_SPACE_CRAFT', 'N', 'N');
INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (6, 3, 'MAKE_COFFEE', 'N', 'N');
INSERT INTO liveuser_rights (right_id, area_id, right_define_name, has_implied, has_level) VALUES (7, 3, 'DRINK_COFFEE', 'N', 'Y');

#
# Dumping data for table `liveuser_userrights`
#

INSERT INTO liveuser_userrights (perm_user_id, right_id, right_level) VALUES (1, 3, 3);
INSERT INTO liveuser_userrights (perm_user_id, right_id, right_level) VALUES (1, 7, 3);

#
# Dumping data for table `liveuser_translations`
#

INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (1, 2, 'de', 'TestGebiet', 'Ein Gebiet zum testen.');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (1, 2, 'en', 'TestArea', 'An Area for testing.');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (2, 2, 'de', 'Area51', 'Jeder kennt dieses Gebiet.');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (2, 2, 'en', 'Area51', 'Everybody knows this area.');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (3, 2, 'de', 'Kaffeemaschine', 'Kaffeemaschine Typ 165-X');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (3, 2, 'en', 'Coffeemaker', 'Coffeemaker type 165-X.');

INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (1, 3, 'de', 'Dummies', 'Die Dummy-Gruppe');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (1, 3, 'en', 'Dummies', 'The dummy group');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (2, 3, 'de', 'Genies', 'Die wahren Genies (verkannt, aber brilliant)');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (2, 3, 'en', 'Genies', 'The true genii (unrecognized but brilliant)');

INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (1, 5, 'de', 'Lesen', 'Der Benutzer darf etwas lesen.');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (2, 5, 'de', 'Schreiben', 'Der Benutzer darf etwas schreiben.');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (3, 5, 'de', 'Zugangang', '"Sesam ?ffne dich"-Recht');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (4, 5, 'de', 'Feuer', 'Atombombe z?nden');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (5, 5, 'de', 'Lift up', 'Alien Raumschiff fliegen');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (6, 5, 'de', 'Kaffee machen', 'Kaffeepulver, Wasser, einschalten, warten ...');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (7, 5, 'de', 'Kaffee trinken', 'Ahh, Koffein');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (1, 5, 'en', 'Read', 'Read authority');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (2, 5, 'en', 'Write', 'Write something new');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (3, 5, 'en', 'Access', 'Open the doors');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (4, 5, 'en', 'Fire', 'Launch the atomic bombs');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (5, 5, 'en', 'Lift up', 'Let\'s fly an alian space craft');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (6, 5, 'en', 'Make coffee', 'coffee, water, switch it on, wait ...');
INSERT INTO liveuser_translations (section_id, section_type, language_id, name, description) VALUES (7, 5, 'en', 'Drink coffee', 'Ahh, caffeine');