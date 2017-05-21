CREATE TABLE `%table_name%` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `error_code` CHAR(8) NOT NULL,
  `description` TEXT NOT NULL,
  `details` LONGTEXT NOT NULL,
  `occurrences` INT(10) UNSIGNED NOT NULL DEFAULT '1',
  `last_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `error_code` (`error_code`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;
