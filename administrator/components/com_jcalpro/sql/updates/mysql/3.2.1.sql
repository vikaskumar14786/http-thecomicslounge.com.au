#############################################
##          Email Templates Table          ##
#############################################;

CREATE TABLE IF NOT EXISTS #__jcalpro_emails (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`context` varchar(255)
	COMMENT 'Email context, used to determine what emails to send',
	
	`subject` varchar(255) NOT NULL default ''
	COMMENT 'Email Subject',
	 
	`body` text NOT NULL
	COMMENT 'Email body',
	 
	`fromname` varchar(255) NOT NULL default ''
	COMMENT 'Name the email comes from',
	 
	`mailfrom` varchar(255) NOT NULL default ''
	COMMENT 'Address the email comes from',
	
	`language` char(7) NOT NULL default '*'
	COMMENT 'Language code',
	
	`default` tinyint(1) default '0'
	COMMENT 'Default for this context',
	
	`published` tinyint(1) default '0'
	COMMENT 'publication status of email - 0 is Unpublished, 1 is Published, -2 is Trashed',
	
	`ordering` int(11) NOT NULL DEFAULT 0
	COMMENT 'ordering (for default)',
	
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when email was created',	
	 
	`created_by` int(11) NOT NULL default '0'
	COMMENT 'User id of email creator',
	
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when email was last modified',	
	 
	`modified_by` int(11) NOT NULL default '0'
	COMMENT 'User id of last modifier',
	
	`checked_out` int(11) unsigned NOT NULL default '0'
	COMMENT 'Locking column to prevent simultaneous updates',
	
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Date and Time email was checked out',
	
	PRIMARY KEY (id)
	
) ENGINE=MyISAM;
