DROP TABLE IF EXISTS `articles`;

DROP TABLE IF EXISTS `album_pictures`;

DROP TABLE IF EXISTS `albums`;
DROP TABLE IF EXISTS `pictures`;

DROP TABLE IF EXISTS `storage`;

DROP TABLE IF EXISTS `object_likes`;
DROP TABLE IF EXISTS `object_comments`;

DROP TABLE IF EXISTS `user_connections`;
DROP TABLE IF EXISTS `users`;

DROP TABLE IF EXISTS `sections`;

DROP TABLE IF EXISTS `object_metadata`;
DROP TABLE IF EXISTS `object_queue`;

DROP TABLE IF EXISTS `objects`;

DROP TABLE IF EXISTS `runtime`;

CREATE TABLE `runtime` (
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `key` VARCHAR(255) NOT NULL DEFAULT '',
  `key2` VARCHAR(255) NOT NULL DEFAULT '',
  UNIQUE KEY(`key`, `key2`),
  `value` VARCHAR(255) NOT NULL DEFAULT ''
) default charset='utf8mb4';

CREATE TABLE `objects` (
  `object_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`object_id`),
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `object_name` VARCHAR(255) NOT NULL DEFAULT '',
  INDEX(`object_name`),
  `user_id` BIGINT UNSIGNED DEFAULT 0,
  `type` VARCHAR(64) not null
) default charset='utf8mb4';

CREATE TABLE IF NOT EXISTS `object_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY( `id` ),
  `object_id` BIGINT UNSIGNED NOT NULL REFERENCES `objects`(`object_id`),
  INDEX( `object_id` ),
  `action` VARCHAR(255) NOT NULL DEFAULT 'default',
  UNIQUE KEY( `object_id`, `action` ),
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lock_id` VARCHAR(255) DEFAULT NULL,
  INDEX( `lock_id` ),
  `ltime` DATETIME DEFAULT NULL,
  `qtime` DATETIME DEFAULT NULL,
  `priority` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `tries` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `processed` BOOLEAN NOT NULL DEFAULT false,
  INDEX(`lock_id`, `processed`, `priority`, `ctime`, `tries`)
) default charset='utf8mb4';

CREATE TABLE `object_metadata` (
  `object_id` BIGINT UNSIGNED default null REFERENCES objects(`object_id`),
  `key` VARCHAR(64) NOT NULL DEFAULT '',
  UNIQUE KEY(`object_id`, `key`),
  INDEX(`object_id`),
  `value` TEXT NOT NULL DEFAULT ''
) default charset='utf8mb4';

CREATE TABLE `sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `base_uri` VARCHAR(255) NOT NULL,
  UNIQUE KEY(`base_uri`),
  `title` VARCHAR(255) NOT NULL,
  `type` varchar(32) NOT NULL,
  KEY `type_title`(`type`, `title`)
) default charset='utf8mb4';

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `object_id` BIGINT UNSIGNED default null REFERENCES objects(`object_id`),
  `email` VARCHAR(255) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `auth_token` VARCHAR(255) NOT NULL DEFAULT '',
  `verified` BOOLEAN NOT NULL DEFAULT false,
  `admin` BOOLEAN NOT NULL DEFAULT false,
  `disabled` BOOLEAN NOT NULL DEFAULT false,
  UNIQUE KEY(`email`)
) default charset='utf8mb4';

CREATE TABLE `user_connections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `user_id` BIGINT UNSIGNED NOT NULL REFERENCES `users`(`id`),
  `external_id` VARCHAR(64) NOT NULL DEFAULT '',
  `service` VARCHAR(32) NOT NULL,
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `token` TEXT DEFAULT NULL,
  `secret` TEXT DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `screen_name` VARCHAR(255) DEFAULT NULL,
  `picture` VARCHAR(255) DEFAULT NULL,
  KEY(`external_id`),
  KEY(`user_id`)
) default charset='utf8mb4';

CREATE TABLE IF NOT EXISTS `object_likes` (
  `object_id` BIGINT UNSIGNED NOT NULL REFERENCES `objects`(`object_id`),
  `user_id` BIGINT UNSIGNED NOT NULL REFERENCES `users`(`id`),
  UNIQUE KEY(`object_id`, `user_id`),
  INDEX(`object_id`),
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `object_comments` (
  `object_id` BIGINT UNSIGNED NOT NULL REFERENCES `objects`(`object_id`),
  `user_id` BIGINT UNSIGNED NOT NULL REFERENCES `users`(`id`),
  INDEX(`object_id`),
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(`object_id`, `ctime`),
  `comment` TEXT NOT NULL DEFAULT ''
);

CREATE TABLE `storage` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `hash` VARCHAR(40) NOT NULL,
  INDEX(`hash`),
  `user_id` BIGINT UNSIGNED DEFAULT NULL REFERENCES `users`(`id`),
  `original_name` VARCHAR(255) NOT NULL,
  `extension` VARCHAR(255) NOT NULL,
  `size`  INT UNSIGNED NOT NULL DEFAULT 0,
  `ctime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) default charset='utf8mb4';

CREATE TABLE `pictures` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `object_id` BIGINT UNSIGNED NOT NULL REFERENCES `objects`(`object_id`),
  `width` INT UNSIGNED NOT NULL default 0,
  `height` INT UNSIGNED NOT NULL default 0,
  `title` varchar(255) NOT NULL,
  `description` TEXT NOT NULL,
  `storage_id` BIGINT UNSIGNED NOT NULL REFERENCES `storage`(`id`)
) default charset='utf8mb4';

CREATE TABLE `albums` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `object_id` BIGINT UNSIGNED NOT NULL REFERENCES `objects`(`object_id`),
  `title` VARCHAR(255) NOT NULL,
  `linked_object_id` BIGINT UNSIGNED DEFAULT NULL REFERENCES `objects`(`object_id`),
  INDEX(`linked_object_id`),
  `system` BOOLEAN DEFAULT false,
  `highlight_id` INT UNSIGNED DEFAULT NULL REFERENCES `pictures`(`id`)
) default charset='utf8mb4';

CREATE TABLE `album_pictures` (
  `album_id` INT UNSIGNED NOT NULL REFERENCES `albums`(`id`),
  `picture_id` INT UNSIGNED NOT NULL REFERENCES `pictures`(`id`),
  `sortorder` TINYINT UNSIGNED NOT NULL,
  INDEX(`album_id`),
  INDEX(`picture_id`),
  INDEX(`album_id`, `picture_id`)
);

CREATE TABLE `articles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(`id`),
  `object_id` BIGINT UNSIGNED NOT NULL REFERENCES `objects`(`object_id`),
  `ptime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `section_id` BIGINT UNSIGNED NOT NULL REFERENCES `sections`(`id`),
  `cname` VARCHAR(255) NOT NULL,
  `summary` TEXT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL
) default charset='utf8mb4';
