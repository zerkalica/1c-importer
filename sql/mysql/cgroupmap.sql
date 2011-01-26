CREATE TABLE IF NOT EXISTS `cgroupmap` (
  `id` int(10) unsigned NOT NULL,
  `cid` varchar(128) NOT NULL,
  PRIMARY KEY (`cid`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

