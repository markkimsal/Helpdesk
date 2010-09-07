<?php

Cgn::loadModLibrary('Content::Cgn_Content');
class Crm_File_Model  
 extends Cgn_PublishedContent {

	const STATUS_NEW = 1;
	const STATUS_ACC = 2;
	const STATUS_STR = 3;
	const STATUS_RSL = 4;
	const STATUS_FIX = 5;
	const STATUS_DON = 6;
	const STATUS_NOF = 7;
	const STATUS_DUP = 8;
	const STATUS_INF = 9;

	var $ownerIdField = 'user_id';
	var $groupIdField = 'crm_acct_id';

	var $sharingModeRead   = '';
	var $sharingModeCreate = '';
	var $sharingModeUpdate = '';
	var $sharingModeDelete = '';


	public $tableName = 'crm_file';

	public function initDataItem() {
		parent::initDataItem();

		$this->dataItem->title   = '';
		$this->dataItem->caption = '';

		$this->dataItem->_nuls[] = 'crm_acct_id';
	}

	public static function createNewIssue() {
		$issue = new Crm_Issue_Model();
		$issue->set('status_id', 1);
		$issue->set('is_hidden', 0);
		$issue->dataItem->_nuls[] = 'thread_id';
		$issue->dataItem->_nuls[] = 'reply_id';
		return $issue;
	}

	public function get($k) {
		return $this->dataItem->get($k);
	}

}


class Crm_File_Model_List 
 extends Cgn_Data_Model_List {

	public $tableName = 'crm_file';
	public $modelName = 'Crm_File_Model';

	var $ownerIdField = 'user_id';
	var $groupIdField = 'crm_acct_id';

	var $sharingModeRead   = '';
	var $sharingModeCreate = '';
	var $sharingModeUpdate = '';
	var $sharingModeDelete = '';


	public static function createNewIssue() {
		$issue = new Crm_Issue_Model();
		$issue->set('status_id', 1);
		$issue->set('is_hidden', 0);
		$issue->dataItem->_nuls[] = 'thread_id';
		$issue->dataItem->_nuls[] = 'reply_id';
		return $issue;
	}


}
