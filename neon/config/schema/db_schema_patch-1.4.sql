ALTER TABLE `users` 
ADD COLUMN `accesstokensesar` VARCHAR(250) NULL DEFAULT NULL AFTER `password`,
ADD COLUMN `refreshtokensesar` VARCHAR(250) NULL DEFAULT NULL AFTER `accesstokensesar`,
ADD COLUMN `developmentaccesstokensesar` VARCHAR(250) NULL DEFAULT NULL AFTER `refreshtokensesar`,
ADD COLUMN `developmentrefreshtokensesar` VARCHAR(250) NULL DEFAULT NULL AFTER `developmentaccesstokensesar`,
ADD UNIQUE INDEX `accesstokensesar_UNIQUE` (`accesstokensesar` ASC) VISIBLE,
ADD UNIQUE INDEX `refreshtokensesar_UNIQUE` (`refreshtokensesar` ASC) VISIBLE;
ADD UNIQUE INDEX `developmentaccesstokensesar_UNIQUE` (`developmentaccesstokensesar` ASC) VISIBLE,
ADD UNIQUE INDEX `developmentrefreshtokensesar_UNIQUE` (`developmentrefreshtokensesar` ASC) VISIBLE;

ALTER TABLE `neonsample` 
ADD COLUMN `sampleLastUpdatedSESAR` TIMESTAMP NULL DEFAULT NULL AFTER `igsnPushedToNEON`;
;