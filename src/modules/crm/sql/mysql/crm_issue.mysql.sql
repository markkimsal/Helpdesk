CREATE TABLE IF NOT EXISTS `crm_issue` (
		
	`crm_issue_id` integer (11) NOT NULL auto_increment,  -- 

	`crm_acct_id` integer (11),  -- 

	`is_hidden` tinyint (1),  -- 

	`status_id` tinyint (1),  -- 

	`reply_id` integer (11),  -- 

	`thread_id` integer (11),  -- 

	`subject` varchar (200),  -- 

	`user_id` int(10) unsigned default '0', --

	`user_name` varchar (32),  -- 

	`post_datetime` integer (11),  -- 

	`last_edit_username` varchar (32),  -- 

	`last_edit_datetime` integer (11),  -- 

	`message` text,  -- 

	PRIMARY KEY (crm_issue_id)
)TYPE=InnoDB;

CREATE INDEX user_idx ON crm_issue (user_id);
CREATE INDEX thread_id ON crm_issue (thread_id);
CREATE INDEX reply_id ON crm_issue (reply_id);
CREATE INDEX crm_acct_idx ON crm_issue (crm_acct_id);
CREATE INDEX post_datetime ON crm_issue (post_datetime);


