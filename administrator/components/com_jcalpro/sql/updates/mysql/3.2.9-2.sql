#############################################
##    Orphaned Event Children Xref Table   ##
#############################################;

CREATE TABLE IF NOT EXISTS #__jcalpro_event_xref (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',

	`parent_id` int(11) NOT NULL
	COMMENT 'Primary key of event',
	
	`child_id` int(11) NOT NULL
	COMMENT 'Primary key of child event',
	
	PRIMARY KEY (id)
	
) ENGINE=MyISAM;
