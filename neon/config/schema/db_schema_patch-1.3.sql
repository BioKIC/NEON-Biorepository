CREATE TABLE `NeonSessioning` (
  `sessionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sessionUsername` varchar(45) NOT NULL,
  `sessionNum` int(10) unsigned NOT NULL,
  `sessionName` varchar(101) GENERATED ALWAYS AS (concat(`sessionUsername`,'-',`sessionNum`)) STORED,
  `startTime` datetime NOT NULL,
  `endTime` datetime DEFAULT NULL,
  PRIMARY KEY (`sessionID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `NeonSample` 
ADD COLUMN `sessionID` INT(10) unsigned NULL AFTER `igsn_check`;