#############################################
##    Main Events Table - Language Code    ##
#############################################;

DROP PROCEDURE IF EXISTS add_jcl_lang ;
CREATE PROCEDURE add_jcl_lang()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1060 BEGIN END /*"*/;/*"*/
	ALTER TABLE #__jcalpro_events ADD
		`language` char(7) NOT NULL default '*'
		COMMENT 'Event language code'
		AFTER `description`
	/*"*/;/*"*/
END
;
CALL add_jcl_lang();
DROP PROCEDURE IF EXISTS add_jcl_lang ;


#############################################
##    Main Events Table - Extra Indexes    ##
#############################################;


DROP PROCEDURE IF EXISTS add_jcl_idx ;
CREATE PROCEDURE add_jcl_idx()
BEGIN
	DECLARE CONTINUE HANDLER FOR 1061 BEGIN END /*"*/;/*"*/
	
	CREATE INDEX end_date
		ON #__jcalpro_events (end_date)
	/*"*/;/*"*/
	
	CREATE INDEX published
		ON #__jcalpro_events (published)
	/*"*/;/*"*/
	
	CREATE INDEX approved
		ON #__jcalpro_events (approved)
	/*"*/;/*"*/
	
	CREATE INDEX idx_language
		ON #__jcalpro_events (language)
	/*"*/;/*"*/
	
	CREATE INDEX idx_start_end_date
		ON #__jcalpro_events (start_date, end_date)
	/*"*/;/*"*/
	
	CREATE INDEX idx_published_approved
		ON #__jcalpro_events (published, approved)
	/*"*/;/*"*/
	
	CREATE INDEX idx_private_created_by
		ON #__jcalpro_events (private, created_by)
	/*"*/;/*"*/
END
;
CALL add_jcl_idx();
DROP PROCEDURE IF EXISTS add_jcl_idx ;
