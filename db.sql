/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50516
Source Host           : localhost:3306
Source Database       : video

Target Server Type    : MYSQL
Target Server Version : 50516
File Encoding         : 65001

Date: 2012-01-10 14:50:18
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `debug`
-- ----------------------------
DROP TABLE IF EXISTS `debug`;
CREATE TABLE `debug` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `severity` int(2) DEFAULT NULL,
  `facility` varchar(255) DEFAULT NULL,
  `message` text,
  `debug_date` datetime DEFAULT NULL,
  `pid` bigint(31) DEFAULT '0',
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of debug
-- ----------------------------

-- ----------------------------
-- Table structure for `graphs`
-- ----------------------------
DROP TABLE IF EXISTS `graphs`;
CREATE TABLE `graphs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_inserted` datetime DEFAULT NULL,
  `last_minute_hits` int(31) DEFAULT NULL,
  `connect_time` int(31) DEFAULT NULL,
  `file_access_time` int(31) DEFAULT NULL,
  `transfers` int(31) DEFAULT NULL,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of graphs
-- ----------------------------
INSERT INTO `graphs` VALUES ('1', '2011-12-26 15:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('2', '2011-12-26 16:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('3', '2011-12-26 17:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('4', '2011-12-26 18:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('5', '2011-12-26 19:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('6', '2011-12-26 20:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('7', '2011-12-26 21:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('8', '2011-12-26 22:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('9', '2011-12-26 23:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('10', '2011-12-27 00:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('11', '2011-12-27 01:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('12', '2011-12-27 02:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('13', '2011-12-27 03:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('14', '2011-12-27 04:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('15', '2011-12-27 05:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('16', '2011-12-27 06:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('17', '2011-12-27 07:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('18', '2011-12-27 08:00:01', '0', '0', '0', '0');
INSERT INTO `graphs` VALUES ('19', '2011-12-27 09:00:01', '0', '0', '0', '0');

-- ----------------------------
-- Table structure for `settings`
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of settings
-- ----------------------------
INSERT INTO `settings` VALUES ('1', 'admin_email', 'admin@gromnet.net');
INSERT INTO `settings` VALUES ('2', 'debug', '0');
INSERT INTO `settings` VALUES ('3', 'storage1', '/var/www/videos/storage1');
INSERT INTO `settings` VALUES ('4', 'storage2', '/var/www/videos/storage2');
INSERT INTO `settings` VALUES ('5', 'storage3', '/var/www/videos/storage3');
INSERT INTO `settings` VALUES ('6', 'storage4', '/var/www/videos/storage4');

-- ----------------------------
-- Table structure for `stats`
-- ----------------------------
DROP TABLE IF EXISTS `stats`;
CREATE TABLE `stats` (
  `miss` bigint(20) unsigned DEFAULT '0',
  `hit` bigint(20) unsigned DEFAULT '0',
  `localtraffic` bigint(20) unsigned DEFAULT '0',
  `internettraffic` bigint(20) unsigned DEFAULT '0',
  `connect_time` int(32) DEFAULT '0',
  `file_access_time` int(32) DEFAULT '0',
  `troughput_internet` int(32) DEFAULT '0',
  `troughput_local` int(32) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of stats
-- ----------------------------
INSERT INTO `stats` VALUES ('0', '0', '0', '0', '0', '0', '0', '0');

-- ----------------------------
-- Table structure for `temporary`
-- ----------------------------
DROP TABLE IF EXISTS `temporary`;
CREATE TABLE `temporary` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `accessed` datetime DEFAULT NULL,
  `size` bigint(31) DEFAULT NULL,
  `progress` bigint(31) DEFAULT NULL,
  `packet_count` int(32) DEFAULT '0',
  `ip` varchar(16) DEFAULT '0.0.0.0',
  `pid` int(32) DEFAULT '0',
  UNIQUE KEY `id` (`id`) USING BTREE,
  UNIQUE KEY `request` (`request`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of temporary
-- ----------------------------

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `ulevel` int(31) DEFAULT NULL,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', 'admin', 'admin', '0');

-- ----------------------------
-- Table structure for `videos`
-- ----------------------------
DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `ip` varchar(16) DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  `size` int(32) DEFAULT NULL,
  `accessed` datetime DEFAULT NULL,
  `visits` int(31) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `reply_headers` varchar(512) DEFAULT NULL,
  UNIQUE KEY `id` (`id`) USING BTREE,
  UNIQUE KEY `request` (`request`) USING BTREE,
  KEY `idndx` (`id`) USING BTREE,
  FULLTEXT KEY `reqndx` (`request`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of videos
-- ----------------------------

-- ----------------------------
-- Table structure for `visits`
-- ----------------------------
DROP TABLE IF EXISTS `visits`;
CREATE TABLE `visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) DEFAULT NULL,
  `visit_date` datetime DEFAULT NULL,
  `request` varchar(255) DEFAULT NULL,
  `video_id` int(32) DEFAULT NULL,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of visits
-- ----------------------------
