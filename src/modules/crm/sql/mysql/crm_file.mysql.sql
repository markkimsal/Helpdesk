CREATE TABLE IF NOT EXISTS `crm_file` (
  `crm_file_id` int(11) NOT NULL auto_increment,
  `cgn_content_id` int(11) NOT NULL default 0,
  `cgn_content_version` int(11) NOT NULL default 0,
  `cgn_guid` varchar(255) NOT NULL default '',
  `crm_acct_id` integer (11) NOT NULL default 0,  -- 
  `title` varchar(255) NOT NULL default '',
  `mime` varchar(255) NOT NULL default '',
  `caption` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `file_binary` longblob NOT NULL,
  `link_text` varchar(255) NOT NULL,
  `published_on` integer (11) NOT NULL default 0,
  `edited_on` integer (11) NOT NULL default 0,
  `created_on` integer (11) NOT NULL default 0,
  PRIMARY KEY  (`crm_file_id`)
);

CREATE INDEX edited_on_idx ON crm_file (`edited_on`);
CREATE INDEX published_on_idx ON crm_file (`published_on`);
CREATE INDEX created_on_idx ON crm_file (`created_on`);
CREATE INDEX link_text_idx ON crm_file (`link_text`);
ALTER TABLE `crm_file` COLLATE utf8_general_ci;
