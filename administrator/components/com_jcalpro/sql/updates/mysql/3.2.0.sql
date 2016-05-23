#############################################
##          Fields Table - Assets          ##
#############################################;

DROP PROCEDURE IF EXISTS add_jcl_assets ;
CREATE PROCEDURE add_jcl_assets()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	ALTER TABLE #__jcalpro_fields ADD
		`asset_id` int(10) unsigned NOT NULL default '0'
		COMMENT 'FK to the #__assets table.'
		AFTER `id`
	/*"*/;/*"*/
END
;
CALL add_jcl_assets();
DROP PROCEDURE IF EXISTS add_jcl_assets ;

