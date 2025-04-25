ALTER TABLE `users` 
ADD COLUMN `accesstokensesar` VARCHAR(250) NULL DEFAULT NULL AFTER `password`,
ADD COLUMN `refreshtokensesar` VARCHAR(250) NULL DEFAULT NULL AFTER `accesstokensesar`,
ADD UNIQUE INDEX `accesstokensesar_UNIQUE` (`accesstokensesar` ASC) VISIBLE,
ADD UNIQUE INDEX `refreshtokensesar_UNIQUE` (`refreshtokensesar` ASC) VISIBLE;
;