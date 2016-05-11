-- MySQL/MariaDB initial database structure for lastlogin plugin.

/*!40014  SET FOREIGN_KEY_CHECKS=0 */;

-- Table structure for table `userlogins`

CREATE TABLE IF NOT EXISTS `userlogins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `username` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `sess_id` varchar(128) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `real_ip` varchar(20) NOT NULL DEFAULT '',
  `hostname` varchar(255) NOT NULL DEFAULT '',
  `geoloc` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  INDEX `user_id` (`user_id`)
) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;


/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
