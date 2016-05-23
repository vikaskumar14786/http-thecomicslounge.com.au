#############################################
##            Main Events Table            ##
#############################################;

CREATE TABLE IF NOT EXISTS #__jcalpro_events (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
	COMMENT 'FK to the #__assets table.', 
	 
	`title` varchar(255) NOT NULL default ''
	COMMENT 'Event Title',
	 
	`alias` varchar(255) NOT NULL default ''
	COMMENT 'Event Title Alias',
	 
	`description` text NOT NULL
	COMMENT 'Description of event (unfiltered)',
	
	`language` char(7) NOT NULL default '*'
	COMMENT 'Event language code',
	 
	`common_event_id` varchar(255) NOT NULL default ''
	COMMENT 'Identification string',
	 
	`location` int(11) NOT NULL default '0'
	COMMENT 'Primary key of location',
	 
	`rec_id` int(11) NOT NULL default '0'
	COMMENT 'Primary key of parent recurrence',
	
	`detached_from_rec` tinyint(1) NOT NULL default	'0'
	COMMENT 'Boolean flag to denote if this event is detached from the other recurrences',
	
	`day` tinyint(2) NOT NULL default '0'
	COMMENT 'Day as configured by the user',
	
	`month` smallint(2) NOT NULL default '0'
	COMMENT 'Month as configured by the user',
	
	`year` smallint(4) NOT NULL default '0'
	COMMENT 'Year as configured by the user',
	
	`hour` smallint(2) NOT NULL default '0'
	COMMENT 'Hour as configured by the user',
	
	`minute` smallint(2) NOT NULL default '0'
	COMMENT 'Minute as configured by the user',
	
	`timezone` varchar(255) NOT NULL default ''
	COMMENT 'Timezone as configured by the user',
	
	`start_date` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Start of event in UTC DateTime',
	
	`end_date` datetime default '0000-00-00	00:00:00'
	COMMENT 'End of event in UTC DateTime',
	
	`registration` tinyint(1) NOT NULL default 0
	COMMENT 'Flag to denote if registration is allowed',
	
	`registration_capacity` int(11) NOT NULL default 0
	COMMENT 'Maximum amount of registrations to allow - 0 means "no limit"',
	
	`registration_start_day` tinyint(2) NOT NULL default '0'
	COMMENT 'Day registration starts as configured by the user',
	
	`registration_start_month` smallint(2) NOT NULL default '0'
	COMMENT 'Month registration starts as configured by the user',
	
	`registration_start_year` smallint(4) NOT NULL default '0'
	COMMENT 'Year registration starts as configured by the user',
	
	`registration_start_hour` smallint(2) NOT NULL default '0'
	COMMENT 'Hour registration starts as configured by the user',
	
	`registration_start_minute` smallint(2) NOT NULL default '0'
	COMMENT 'Minute registration starts as configured by the user',
	
	`registration_start_date` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Start of event registration in UTC DateTime',
	
	`registration_until_event` tinyint(1) NOT NULL default	'0'
	COMMENT 'Boolean flag to denote if this event can have registrations up to the start time',
	
	`registration_end_day` tinyint(2) NOT NULL default '0'
	COMMENT 'Day registration ends as configured by the user',
	
	`registration_end_month` smallint(2) NOT NULL default '0'
	COMMENT 'Month registration ends as configured by the user',
	
	`registration_end_year` smallint(4) NOT NULL default '0'
	COMMENT 'Year registration ends as configured by the user',
	
	`registration_end_hour` smallint(2) NOT NULL default '0'
	COMMENT 'Hour registration ends as configured by the user',
	
	`registration_end_minute` smallint(2) NOT NULL default '0'
	COMMENT 'Minute registration ends as configured by the user',
	
	`registration_end_date` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'End of event registration in UTC DateTime',
	
	`recur_type` tinyint(1) NOT NULL default 0
	COMMENT 'Recur type for event (merged with rec_type_select in v2)',
	
	`recur_end_type` tinyint(1) unsigned NOT NULL	default '0'
	COMMENT 'Recur end type - 1 for X occurrences (recur_end_count), 2 for given end date (recur_end_until)',
	
	`recur_end_count` tinyint unsigned NOT NULL default '0'
	COMMENT 'Recur end count, only used when recur_end_type is 1',
	
	`recur_end_until` varchar(10) NOT NULL default ''
	COMMENT 'Recur end date, raw string from user, only used when recur_end_type is 2',
	
	`recur_end_datetime` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Calculated end date for recurrences in UTC',
	
	
	`recur_val`	tinyint(4) default '0',
	`rec_daily_period` smallint NOT NULL default '0',
	`rec_weekly_period` smallint NOT NULL default '0',
	`rec_weekly_on_monday` tinyint(1) NOT NULL default '0',
	`rec_weekly_on_tuesday` tinyint(1) NOT NULL default '0',
	`rec_weekly_on_wednesday` tinyint(1) NOT NULL default '0',
	`rec_weekly_on_thursday` tinyint(1) NOT NULL default '0',
	`rec_weekly_on_friday` tinyint(1) NOT NULL default '0',
	`rec_weekly_on_saturday` tinyint(1) NOT NULL default '0',
	`rec_weekly_on_sunday` tinyint(1) NOT NULL default '0',
	`rec_monthly_period` smallint NOT NULL default '0',
	`rec_monthly_type` tinyint(1) NOT NULL default '0',
	`rec_monthly_day_number` smallint NOT NULL default '0',
	`rec_monthly_day_list` varchar(100) default '',
	`rec_monthly_day_order` tinyint(1) NOT NULL default '0',
	`rec_monthly_day_type` tinyint(1) NOT NULL default '0',
	`rec_yearly_period` smallint NOT NULL default '0',
	`rec_yearly_on_month` tinyint(1) NOT NULL default '0',
	`rec_yearly_on_month_list` varchar(50) default '',
	`rec_yearly_type`	tinyint(1) NOT NULL default '0',
	`rec_yearly_day_number` smallint NOT NULL default '0',
	`rec_yearly_day_order` tinyint(1) NOT NULL default '0',
	`rec_yearly_day_type` tinyint(1) NOT NULL default '0',
	
	`approved` tinyint(1) NOT NULL default '0',
	`private` tinyint(1) NOT NULL default '0',
	
	`published` tinyint(1) default '0'
	COMMENT 'publication status of event - 0 is Unpublished, 1 is Published, -2 is Trashed',
	
	`featured` tinyint(1) default '0'
	COMMENT 'featured status of event',
	
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when event was created, in UTC',	
	 
	`created_by` int(11) NOT NULL default '0'
	COMMENT 'User id of form creator',
	
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when event was last modified in UTC, deprecates last_updated',	
	 
	`modified_by` int(11) NOT NULL default '0'
	COMMENT 'User id of last modifier',
	
	`checked_out` int(11) unsigned NOT NULL default '0'
	COMMENT 'Locking column to prevent simultaneous updates',
	
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Date and Time event was checked out',
	
	`duration_type` tinyint(1) NOT NULL default 0
	COMMENT 'Type used when calculating duration',
	
	`end_minutes` smallint(2) NOT NULL default 0
	COMMENT 'end minutes used when calculating duration',
	
	`end_hours` smallint(2) NOT NULL default 0
	COMMENT 'end hours used when calculating duration',
	
	`end_days` smallint(2) NOT NULL default 0
	COMMENT 'end days used when calculating duration',
	
	`end_month` smallint(2) NOT NULL default '0'
	COMMENT 'End month as configured by the user',
	
	`end_year` smallint(4) NOT NULL default '0'
	COMMENT 'End year as configured by the user',
	
	`end_day` tinyint(2) NOT NULL default '0'
	COMMENT 'End day as configured by the user',
	
	`end_hour` smallint(2) NOT NULL default '0'
	COMMENT 'End hour as configured by the user',
	
	`end_minute` smallint(2) NOT NULL default '0'
	COMMENT 'End minute as configured by the user',
	
	`params` text NOT NULL default ''
	COMMENT 'Extra parameters for this event',
	 
	`metadata` TEXT NOT NULL default ''
	COMMENT 'JSON encoded metadata.',
	
	PRIMARY KEY (id),
	KEY start_date (start_date),
	KEY end_date (end_date),
	KEY published (published),
	KEY approved (approved),
	KEY rec_id (rec_id),
	KEY created_by (created_by),
	KEY private (private),
	KEY common_event_id (common_event_id),
	KEY modified (modified),
	KEY idx_language (language),
	KEY idx_start_end_date (start_date, end_date),
	KEY idx_published_approved (published, approved),
	KEY idx_private_created_by (private, created_by)
) ENGINE=MyISAM;


#############################################
##               Forms Table               ##
#############################################;

CREATE TABLE IF NOT EXISTS `#__jcalpro_forms` (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
	COMMENT 'FK to the #__assets table.', 
	 
	`title` varchar(255) NOT NULL default ''
	COMMENT 'Form Title',
	 
	`type` tinyint(1) NOT NULL default '0'
	COMMENT 'Type of form: 0=Event form, 1=Registration form',
	
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when form was created',	
	 
	`created_by` int(11) NOT NULL default '0'
	COMMENT 'User id of form creator',
	
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when form was last modified',	
	 
	`modified_by` int(11) NOT NULL default '0'
	COMMENT 'User id of last modifier',
	 
	`published` tinyint(1) default '0'
	COMMENT 'Publication status',
	
	`checked_out` int(11) unsigned NOT NULL default '0'
	COMMENT 'Locking column to prevent simultaneous updates',
	
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Date and Time form was checked out',
	 
	`default` tinyint(1) NOT NULL default '0'
	COMMENT 'Determines if this is the default custom form',
	 
	PRIMARY KEY (id)
) ENGINE=MyISAM;



#############################################
##               Fields Table              ##
#############################################;

CREATE TABLE IF NOT EXISTS `#__jcalpro_fields` (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
	COMMENT 'FK to the #__assets table.',
	 
	`name` varchar(255) NOT NULL default ''
	COMMENT 'Field name attribute',
	 
	`title` varchar(255) NOT NULL default ''
	COMMENT 'Field Title (and label)',
	 
	`type` varchar(255) NOT NULL default 'text'
	COMMENT 'Type of field, which must match a corresponding class/trigger',
	 
	`description` text NOT NULL default ''
	COMMENT 'Description of field displayed to the end user',
	
	`default` varchar(255) NOT NULL default ''
	COMMENT 'Default value of the field',
	 
	`formtype` tinyint(1) default '-1'
	COMMENT 'Type of form this field appears in: -1 for any, 0 for events, 1 for registration',
	
	`event_display` tinyint(1) default '1'
	COMMENT 'Display status - 0=hidden, 1=header, 2=top list, 3=bottom list, 4=side list',
	
	`params` text NOT NULL default ''
	COMMENT 'Various parameters for field - options, html attributes, etc',
	
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when field was created',	
	 
	`created_by` int(11) NOT NULL default '0'
	COMMENT 'User id of field creator',
	
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when field was last modified',	
	 
	`modified_by` int(11) NOT NULL default '0'
	COMMENT 'User id of last modifier',
	 
	`published` tinyint(1) default '0'
	COMMENT 'Publication status',
	
	`checked_out` int(11) unsigned NOT NULL default '0'
	COMMENT 'Locking column to prevent simultaneous updates',
	
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when field was checked out',
	 
	PRIMARY KEY (id)
) ENGINE=MyISAM;



#############################################
##            Registration Table           ##
#############################################;

CREATE TABLE IF NOT EXISTS `#__jcalpro_registration` (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
	COMMENT 'FK to the #__assets table.',

	`event_id` int(11)	NOT NULL
	COMMENT 'Primary Key of Event',
	
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when user registered',
	
	`created_by` int(11) NOT NULL default '0'
	COMMENT 'User id of registered user',
	
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when field was last modified',	
	 
	`modified_by` int(11) NOT NULL default '0'
	COMMENT 'User id of last modifier',
	
	`published` tinyint(1) default '0'
	COMMENT 'Confirmation status',
	
	`user_id` int(11) unsigned NOT NULL default '0'
	COMMENT 'Primary key of user',
	 
	`user_name` varchar(255) NOT NULL default ''
	COMMENT 'Alternate name for attending user',
	 
	`user_email` varchar(255) NOT NULL default ''
	COMMENT 'Alternate email for attending user',
	 
	`confirmation` varchar(128) NOT NULL default ''
	COMMENT 'Confirmation code',
	
	`checked_out` int(11) unsigned NOT NULL default '0'
	COMMENT 'Locking column to prevent simultaneous updates',
	
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Date and Time event was checked out',
	
	`params` text NOT NULL default ''
	COMMENT 'Extra parameters for this registration, like payment info, etc',
	 
	PRIMARY KEY (id)
) ENGINE=MyISAM;


#############################################
##         Form Fields Xref Table          ##
#############################################;

CREATE TABLE IF NOT EXISTS `#__jcalpro_form_fields` (

	`form_id` int(11) NOT NULL
	COMMENT 'Primary key of form',
	
	`field_id` int(11) NOT NULL
	COMMENT 'Primary key of field',
	
	`ordering` int(11) NOT NULL DEFAULT 0
	COMMENT 'ordering of fields'
	
) ENGINE=MyISAM;


#############################################
##     Events to Categories Xref Table     ##
#############################################;

CREATE TABLE IF NOT EXISTS `#__jcalpro_event_categories` (

	`event_id` int(11) NOT NULL
	COMMENT 'Primary key of event',
	
	`category_id` int(11) NOT NULL
	COMMENT 'Primary key of category',
	
	`canonical` tinyint(1) NOT NULL DEFAULT 0
	COMMENT 'is this the canonical category',
	
	UNIQUE KEY `key_event_category` (`event_id`, `category_id`)
) ENGINE=MyISAM;


#############################################
##             Locations Table             ##
#############################################;

CREATE TABLE IF NOT EXISTS `#__jcalpro_locations` (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
	COMMENT 'FK to the #__assets table.', 
	 
	`title` varchar(255) NOT NULL default ''
	COMMENT 'Location Title',
	 
	`alias` varchar(255) NOT NULL default ''
	COMMENT 'Location Alias (for URLs)',
	 
	`address` varchar(255) NOT NULL default ''
	COMMENT 'Location Address',
	 
	`city` varchar(100) NOT NULL default ''
	COMMENT 'Location City',
	 
	`state` varchar(100) NOT NULL default ''
	COMMENT 'Location State',
	 
	`country` varchar(100) NOT NULL default ''
	COMMENT 'Location Country',
	 
	`postal_code` varchar(20) NOT NULL default ''
	COMMENT 'Location Postal Code',
	 
	`latitude` float(16,12) NOT NULL default 0.0
	COMMENT 'Location Latitude',
	 
	`longitude` float(16,12) NOT NULL default 0.0
	COMMENT 'Location Longitude',
	 
	`latlng` Point NOT NULL
	COMMENT 'Location point',
	
	`created` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when Location was created',	
	 
	`created_by` int(11) NOT NULL default '0'
	COMMENT 'User id of Location creator',
	
	`modified` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'when Location was last modified',	
	 
	`modified_by` int(11) NOT NULL default '0'
	COMMENT 'User id of last modifier',
	 
	`published` tinyint(1) default '0'
	COMMENT 'Publication status',
	
	`checked_out` int(11) unsigned NOT NULL default '0'
	COMMENT 'Locking column to prevent simultaneous updates',
	
	`checked_out_time` datetime NOT NULL default '0000-00-00 00:00:00'
	COMMENT 'Date and Time Location was checked out',
	 
	PRIMARY KEY (id)
) ENGINE=MyISAM;

#############################################
##          Email Templates Table          ##
#############################################;

CREATE TABLE IF NOT EXISTS #__jcalpro_emails (

	`id` int(11)	NOT NULL auto_increment
	COMMENT 'Primary Key',
	
	`asset_id` int(10) unsigned NOT NULL DEFAULT '0'
	COMMENT 'FK to the #__assets table.', 
	
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
