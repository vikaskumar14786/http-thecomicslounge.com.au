#############################################
##     Locations Table - Asset ID          ##
#############################################;

/* using comments to bypass limitations in JDatabaseDriver::splitSql - do not remove! */

DROP PROCEDURE IF EXISTS add_jcl_locations_asset_column ;
CREATE PROCEDURE add_jcl_locations_asset_column()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_locations ADD
		`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
		COMMENT 'FK to the #__assets table.'
		AFTER `id`
	/*"*/;/*"*/
END
;
CALL add_jcl_locations_asset_column();
DROP PROCEDURE IF EXISTS add_jcl_locations_asset_column ;
