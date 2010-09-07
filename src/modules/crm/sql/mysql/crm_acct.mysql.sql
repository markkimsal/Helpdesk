DROP TABLE IF EXISTS `crm_acct`;
CREATE TABLE `crm_acct` (
		
	`crm_acct_id` integer (11) NOT NULL auto_increment,  -- 

	`org_name` varchar (255),  -- 

	`org_account_id` int (11),  -- 

	`is_active` tinyint (1),  -- 

	`agreement_date` int (11),  -- 

	`agreement_ip_addr` varchar (90),  -- 

	`created_on` int (11),  -- 

	`approved_on` int (11),  -- 

	`group_code` int (11),  -- 

	`owner_id` int (11),  -- 

	`valid_thru` int (11),  -- 

	PRIMARY KEY (crm_acct_id)
);

CREATE INDEX `is_active_idx`   ON `crm_acct` (`is_active`); 
CREATE INDEX `valid_thru_idx`  ON `crm_acct` (`valid_thru`); 
CREATE INDEX `org_account_idx` ON `crm_acct` (`org_account_id`); 
CREATE INDEX `owner_idx`       ON `crm_acct` (`owner_id`); 
CREATE INDEX `approved_on_idx` ON `crm_acct` (`approved_on`); 
ALTER TABLE  `crm_acct` COLLATE utf8_general_ci;
