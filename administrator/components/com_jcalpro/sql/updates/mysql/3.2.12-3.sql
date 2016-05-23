#############################################
##     Registrations Table - Asset ID      ##
#############################################;

/* using comments to bypass limitations in JDatabaseDriver::splitSql - do not remove! */

DROP PROCEDURE IF EXISTS add_jcl_asset_columns ;
CREATE PROCEDURE add_jcl_asset_columns()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_emails ADD
		`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
		COMMENT 'FK to the #__assets table.'
		AFTER `id`
	/*"*/;/*"*/

	ALTER TABLE #__jcalpro_forms ADD
		`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
		COMMENT 'FK to the #__assets table.'
		AFTER `id`
	/*"*/;/*"*/
END
;
CALL add_jcl_asset_columns();
DROP PROCEDURE IF EXISTS add_jcl_asset_columns ;
