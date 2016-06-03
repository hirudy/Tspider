/*
 Navicat Premium Data Transfer

 Source Server         : rd-115.28.149.242
 Source Server Type    : MySQL
 Source Server Version : 50540
 Source Host           : 115.28.149.242
 Source Database       : snatch_lol

 Target Server Type    : MySQL
 Target Server Version : 50540
 File Encoding         : utf-8

 Date: 06/03/2016 19:58:37 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `control`
-- ----------------------------
DROP TABLE IF EXISTS `control`;
CREATE TABLE `control` (
  `key` varchar(125) NOT NULL DEFAULT '' COMMENT 'key值',
  `value` varchar(1000) NOT NULL DEFAULT '' COMMENT 'value值',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `great_god_rank`
-- ----------------------------
DROP TABLE IF EXISTS `great_god_rank`;
CREATE TABLE `great_god_rank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `version` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本号（爬取时间戳）',
  `area_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '大区id',
  `rank` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排名id',
  `username` varchar(100) NOT NULL DEFAULT '' COMMENT '用户名称',
  `qquin` varchar(100) NOT NULL DEFAULT '' COMMENT '用户的qqID',
  `icon_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所用头像id',
  `tier` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '段位-',
  `queue` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '段位-',
  `win_point` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '胜点',
  `champions` varchar(100) NOT NULL DEFAULT '' COMMENT '常用英雄ids,多个英雄id以逗号分割',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`version`,`area_id`,`rank`) COMMENT '唯一索引'
) ENGINE=InnoDB AUTO_INCREMENT=1072552 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `hero_external_statistics`
-- ----------------------------
DROP TABLE IF EXISTS `hero_external_statistics`;
CREATE TABLE `hero_external_statistics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `version` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新版本号',
  `champion_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'tgp英雄id',
  `item_frequent` varchar(1000) NOT NULL DEFAULT '' COMMENT '物品-经常使用推荐，json',
  `item_win` varchar(1000) NOT NULL DEFAULT '' COMMENT '物品-胜率最高推荐，json',
  `chart_patch_win` varchar(1000) NOT NULL DEFAULT '' COMMENT '图-版本胜率，json',
  `chart_patch_play` varchar(1000) NOT NULL DEFAULT '' COMMENT '图-版本使用，json',
  `chart_game_length_win` varchar(1000) NOT NULL DEFAULT '' COMMENT '图-游戏时长胜率，json',
  `chart_game_play_win` varchar(1000) NOT NULL DEFAULT '' COMMENT '图-游戏场数胜率，json',
  PRIMARY KEY (`id`),
  UNIQUE KEY `v_c` (`version`,`champion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1272 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `hero_god_rank`
-- ----------------------------
DROP TABLE IF EXISTS `hero_god_rank`;
CREATE TABLE `hero_god_rank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `version` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '爬取版本号',
  `hero_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '英雄tgpID',
  `rank` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排行榜值',
  `area_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '大区id',
  `area_name` varchar(50) NOT NULL DEFAULT '' COMMENT '大区显示名称',
  `icon_id` int(11) NOT NULL DEFAULT '0' COMMENT '头像id',
  `uin` varchar(50) NOT NULL DEFAULT '' COMMENT '用户uid',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名称',
  `proficiency` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '该人的当前英雄熟练度',
  PRIMARY KEY (`id`),
  UNIQUE KEY `v_h_r` (`version`,`hero_id`,`rank`)
) ENGINE=InnoDB AUTO_INCREMENT=947201 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `hero_rank`
-- ----------------------------
DROP TABLE IF EXISTS `hero_rank`;
CREATE TABLE `hero_rank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `version` int(10) unsigned NOT NULL COMMENT '爬取版本-时间戳',
  `champion_id` int(10) unsigned NOT NULL COMMENT 'tpg英雄id',
  `position` varchar(10) NOT NULL DEFAULT '' COMMENT '英雄位置',
  `win_ratio` float unsigned NOT NULL DEFAULT '0' COMMENT '胜率',
  `use_ratio` float unsigned NOT NULL DEFAULT '0' COMMENT '使用率',
  `ban_ratio` float unsigned NOT NULL DEFAULT '0' COMMENT '被禁率',
  `play_rank` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '英雄排行榜',
  `appear_rank` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登场排名',
  `kda` float unsigned NOT NULL DEFAULT '0' COMMENT 'kda',
  `k_num` float unsigned NOT NULL DEFAULT '0' COMMENT '平均击杀',
  `d_num` float unsigned NOT NULL DEFAULT '0' COMMENT '平均死亡',
  `a_num` float unsigned NOT NULL DEFAULT '0' COMMENT '平均助攻',
  `damage_taken` double unsigned NOT NULL DEFAULT '0' COMMENT '承受伤害',
  `damage_dealt` double unsigned NOT NULL DEFAULT '0' COMMENT '输出伤害',
  `minions_killed` float unsigned NOT NULL DEFAULT '0' COMMENT '击杀小兵数',
  `neutral_minions_killed` float unsigned NOT NULL DEFAULT '0' COMMENT '野怪击杀数',
  `gold_earned` double unsigned NOT NULL DEFAULT '0' COMMENT '金币获取',
  `killing_spree` float unsigned NOT NULL DEFAULT '0' COMMENT '连杀',
  PRIMARY KEY (`id`),
  KEY `v_c_p` (`version`,`champion_id`,`position`)
) ENGINE=InnoDB AUTO_INCREMENT=2231 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
