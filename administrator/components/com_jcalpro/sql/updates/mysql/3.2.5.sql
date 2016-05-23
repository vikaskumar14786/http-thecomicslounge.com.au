#############################################
##          Events Table - Metadata        ##
#############################################;

DROP PROCEDURE IF EXISTS add_jcl_meta ;
CREATE PROCEDURE add_jcl_meta()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	ALTER TABLE #__jcalpro_events ADD
		`metadata` TEXT NOT NULL default ''
		COMMENT 'JSON encoded metadata.'
		AFTER `params`
	/*"*/;/*"*/
END
;
CALL add_jcl_meta();
DROP PROCEDURE IF EXISTS add_jcl_meta ;

