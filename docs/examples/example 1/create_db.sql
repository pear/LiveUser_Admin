# phpMyAdmin MySQL-Dump
# version 2.5.0-rc2
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Generation Time: Nov 22, 2003 at 09:38 AM
# Server version: 4.0.14
# PHP Version: 4.3.3
# Database : `liveuser`
# --------------------------------------------------------

#
# Dumping data for table `liveuser_applications`
#

INSERT INTO `liveuser_applications` VALUES (1, 'LIVEUSER');
# --------------------------------------------------------


#
# Table structure for table `liveuser_applications_seq`
#
# Creation: Aug 17, 2003 at 12:10 PM
# Last update: Aug 17, 2003 at 12:10 PM
#

CREATE TABLE `liveuser_applications_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Dumping data for table `liveuser_applications_seq`
#

INSERT INTO `liveuser_applications_seq` VALUES (1);
# --------------------------------------------------------

#
# Dumping data for table `liveuser_areas`
#

INSERT INTO `liveuser_areas` VALUES (1, 1, 'ONLY_AREA');
# --------------------------------------------------------

#
# Table structure for table `liveuser_areas_seq`
#
# Creation: Aug 17, 2003 at 12:10 PM
# Last update: Aug 17, 2003 at 12:10 PM
#

CREATE TABLE `liveuser_areas_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Dumping data for table `liveuser_areas_seq`
#

INSERT INTO `liveuser_areas_seq` VALUES (1);
# --------------------------------------------------------

#
# Table structure for table `liveuser_groups_seq`
#
# Creation: Aug 17, 2003 at 12:10 PM
# Last update: Aug 17, 2003 at 12:10 PM
#

CREATE TABLE `liveuser_groups_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;


# --------------------------------------------------------
#


# Dumping data for table `liveuser_rights`
#

INSERT INTO `liveuser_rights` VALUES (1, 1, 'MODIFYNEWS', 'N', 'N', 'N');
INSERT INTO `liveuser_rights` VALUES (2, 1, 'EDITNEWS', 'N', 'N', 'N');
# --------------------------------------------------------

#
# Table structure for table `liveuser_rights_seq`
#
# Creation: Aug 17, 2003 at 12:10 PM
# Last update: Aug 17, 2003 at 12:10 PM
#

CREATE TABLE `liveuser_rights_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Dumping data for table `liveuser_rights_seq`
#

INSERT INTO `liveuser_rights_seq` VALUES (2);
# --------------------------------------------------------

#
# Dumping data for table `liveuser_userrights`
#

INSERT INTO `liveuser_userrights` VALUES (1, 1, 1);
INSERT INTO `liveuser_userrights` VALUES (1, 2, 1);
INSERT INTO `liveuser_userrights` VALUES (2, 2, 1);
# --------------------------------------------------------

#
# Table structure for table `news`
#
# Creation: Nov 13, 2003 at 08:48 PM
# Last update: Nov 22, 2003 at 09:31 AM
#

CREATE TABLE `news` (
  `news_id` int(11) NOT NULL default '0',
  `news_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `news_title` varchar(64) NOT NULL default '',
  `news_content` text,
  `news_category` varchar(32) default 'general',
  PRIMARY KEY  (`news_id`)
) TYPE=MyISAM;

#
# Dumping data for table `news`
#

INSERT INTO `news` VALUES (0, '2003-11-13 20:42:28', '', NULL, NULL);
# --------------------------------------------------------

#
# Table structure for table `news_seq`
#
# Creation: Nov 13, 2003 at 08:46 PM
# Last update: Nov 13, 2003 at 08:55 PM
#

CREATE TABLE `news_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Dumping data for table `news_seq`
#

INSERT INTO `news_seq` VALUES (3);

INSERT INTO liveuser_languages (language_id, two_letter_name) VALUES (1, 'fr');
INSERT INTO liveuser_languages (language_id, two_letter_name) VALUES (2, 'en');

ALTER TABLE `liveuser_users` 
ADD `name` VARCHAR( 50 ) NOT NULL ,
ADD `email` VARCHAR( 100 ) NOT NULL ;