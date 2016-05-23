#############################################
##          Events Table - Featured        ##
#############################################;

/* using comments to bypass limitations in JDatabaseDriver::splitSql - do not remove! */

DROP PROCEDURE IF EXISTS add_jcl_featured ;
CREATE PROCEDURE add_jcl_featured()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	ALTER TABLE #__jcalpro_events ADD
		`featured` tinyint(1) NOT NULL default '0'
		COMMENT 'featured status of event'
		AFTER `published`
	/*"*/;/*"*/
END
;
CALL add_jcl_featured();
DROP PROCEDURE IF EXISTS add_jcl_featured ;