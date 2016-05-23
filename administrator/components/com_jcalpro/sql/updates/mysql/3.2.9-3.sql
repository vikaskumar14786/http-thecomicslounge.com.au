#############################################
##     Locations Table - Address Parts     ##
#############################################;

/* using comments to bypass limitations in JDatabaseDriver::splitSql - do not remove! */

DROP PROCEDURE IF EXISTS add_jcl_locations_columns ;
CREATE PROCEDURE add_jcl_locations_columns()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_locations ADD
		`state` varchar(100) NOT NULL default ''
		COMMENT 'Location State'
		AFTER `city`
	/*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_locations ADD
		`country` varchar(100) NOT NULL default ''
		COMMENT 'Location Country'
		AFTER `state`
	/*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_locations ADD
		`postal_code` varchar(20) NOT NULL default ''
		COMMENT 'Location Postal Code'
	AFTER `country`
	/*"*/;/*"*/
END
;
CALL add_jcl_locations_columns();
DROP PROCEDURE IF EXISTS add_jcl_locations_columns ;

