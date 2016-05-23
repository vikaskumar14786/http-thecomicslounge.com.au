#############################################
##          Events Table - End Time        ##
#############################################;


DROP PROCEDURE IF EXISTS add_jcl_end ;
CREATE PROCEDURE add_jcl_end()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	ALTER TABLE #__jcalpro_events ADD
		`end_month` smallint(2) NOT NULL default '0'
		COMMENT 'End month as configured by the user'
		AFTER `end_days`
	/*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_events ADD
		`end_year` smallint(4) NOT NULL default '0'
		COMMENT 'End year as configured by the user'
		AFTER `end_month`
	/*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_events ADD
		`end_day` tinyint(2) NOT NULL default '0'
		COMMENT 'End day as configured by the user'
		AFTER `end_year`
	/*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_events ADD
		`end_hour` smallint(2) NOT NULL default '0'
		COMMENT 'End hour as configured by the user'
		AFTER `end_day`
	/*"*/;/*"*/
	
	ALTER TABLE #__jcalpro_events ADD
		`end_minute` smallint(2) NOT NULL default '0'
		COMMENT 'End minute as configured by the user'
		AFTER `end_hour`
	/*"*/;/*"*/
END
;
CALL add_jcl_end();
DROP PROCEDURE IF EXISTS add_jcl_end ;

