<?php

class Crm_Issue_Model extends Cgn_Data_Model {

	var $groupIdField = 'crm_acct_id';
	public $sharingModeRead = '';
	public $replyCount      = NULL;

	static $_globalCache    = array();

	const STATUS_NEW = 1;
	const STATUS_ACC = 2;
	const STATUS_STR = 3;
	const STATUS_RSL = 4;
	const STATUS_FIX = 5;
	const STATUS_DON = 6;
	const STATUS_NOF = 7;
	const STATUS_DUP = 8;
	const STATUS_INF = 9;
	const STATUS_ROP = 10;

	public $tableName = 'crm_issue';


	public static function createNewIssue() {
		$issue = new Crm_Issue_Model();
		$issue->initBlank();
		return $issue;
	}
	
	public function initBlank() {
		$this->set('status_id', 1);
		$this->set('is_hidden', 0);
		$this->set('post_datetime', time());
		$this->set('thread_id', NULL);
		$this->set('reply_id',  NULL);
		$this->set('subject',   NULL);
		$this->set('message',   NULL);
		$this->set('crm_acct_id',   NULL);
		$this->set('user_id',   NULL);
		$this->set('user_name',   NULL);
		$this->set('last_edit_username',   NULL);
		$this->set('last_edit_datetime',   NULL);
		$this->dataItem->_nuls[] = 'thread_id';
		$this->dataItem->_nuls[] = 'reply_id';
	}

	public function save() {
		if ($this->get('crm_acct_id') < 1) {
			return false;
		}

		$s = Crm_Issue_Model::_generateSubject($this->get('message'));
		$this->set('subject', $s);

		return parent::save();
	}

	public static function _generateSubject($m) {
		$p = substr(htmlentities($m), 0, 200);
		$p = str_replace("\r", "\n", $p);
		$p = str_replace("\n\n", "\n", $p);
		return $p;
	}

	public function getReplyCount() {
		if ($this->replyCount !== NULL) {
			return $this->replyCount;
		}
		$finder = new Cgn_DataItem('crm_issue');
		$finder->_cols = array('COUNT(crm_issue_id) AS reply_count');
		$finder->groupBy('thread_id');
		$finder->andWhere('thread_id', NULL, 'IS NOT');
		$finder->andWhere('thread_id', NULL, 'IS NOT');
		$finder->andWhere('thread_id', $this->getPrimaryKey());
		$finder->_rsltByPkey = FALSE;
		$results = $finder->findAsArray();
		$this->replyCount = intval(@$results[0]['reply_count']);
		return $this->replyCount;
	}

	/**
	 * Return the id of this thread
	 */
	public function getThreadId() {
		//if this is a thread starter, act as normal
		if ($this->get('thread_id') == 0) {
			return $this->get('crm_issue_id');
		}
		return $this->get('thread_id');
	}

	/**
	 * Return the status label of this thread
	 */
	public function getThreadStatusLabel() {
		//if this is a thread starter, act as normal
		if ($this->get('thread_id') == 0) {
			return Crm_Issue_Model::_getStatusLabelStatic($this->get('status_id'));
		}
		//else, we have a reply, get the parent by thread_id
		if (!isset(Crm_Issue_Model::$_globalCache[$this->get('thread_id')])) {
			//load up the parent thread
			Crm_Issue_Model::$_globalCache[$this->get('thread_id')] = new Crm_Issue_Model();
			Crm_Issue_Model::$_globalCache[$this->get('thread_id')]->load($this->get('thread_id'));
		}

		$parent = Crm_Issue_Model::$_globalCache[$this->get('thread_id')];
		return Crm_Issue_Model::_getStatusLabelStatic($parent->get('status_id'));
	}

	public function getStatusLabel() {
		return Crm_Issue_Model::_getStatusLabelStatic($this->get('status_id'));
	}

	public static function _getStatusLabelStatic($id) {
		switch($id) {
			case Crm_Issue_Model::STATUS_NEW:
				return 'New';

			case Crm_Issue_Model::STATUS_ACC:
				return 'Accepted';

			case Crm_Issue_Model::STATUS_STR:
				return 'Started';

			case Crm_Issue_Model::STATUS_DON:
				return 'Done';

			case Crm_Issue_Model::STATUS_FIX:
				return 'Fixed';

			case Crm_Issue_Model::STATUS_NOF:
				return 'Won\'t Fix';

			case Crm_Issue_Model::STATUS_DUP:
				return 'Duplicate';

			case Crm_Issue_Model::STATUS_INF:
				return 'Need Info';

			case Crm_Issue_Model::STATUS_ROP:
				return 'Reopened';
		}
		return 'Unknown';
	}

	public static function _getStatusIds() {
		return array(
			Crm_Issue_Model::STATUS_NEW,
			Crm_Issue_Model::STATUS_ACC,
			Crm_Issue_Model::STATUS_STR,
			Crm_Issue_Model::STATUS_DON,
			Crm_Issue_Model::STATUS_FIX,
			Crm_Issue_Model::STATUS_NOF,
			Crm_Issue_Model::STATUS_DUP,
			Crm_Issue_Model::STATUS_INF,
			Crm_Issue_Model::STATUS_ROP
		);
	}


	/**
	 * Return the status style of this thread
	 */
	public function getThreadStatusStyle() {
		//if this is a thread starter, act as normal
		if ($this->get('thread_id') == 0) {
			return Crm_Issue_Model::_getStatusStyleStatic($this->get('status_id'));
		}
		//else, we have a reply, get the parent by thread_id
		if (!isset(Crm_Issue_Model::$_globalCache[$this->get('thread_id')])) {
			//load up the parent thread
			Crm_Issue_Model::$_globalCache[$this->get('thread_id')] = new Crm_Issue_Model();
			Crm_Issue_Model::$_globalCache[$this->get('thread_id')]->load($this->get('thread_id'));
		}

		$parent = Crm_Issue_Model::$_globalCache[$this->get('thread_id')];
		return Crm_Issue_Model::_getStatusStyleStatic($parent->get('status_id'));
	}

	public function getStatusStyle() {
		return Crm_Issue_Model::_getStatusStyleStatic($this->get('status_id'));
	}

	public function getReplies() {
		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->andWhere('thread_id', $this->getPrimaryKey());
		$finder->dataItem->sort('post_datetime', 'ASC');
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
	}

	public static function _getStatusStyleStatic($id) {
		switch($id) {
			case Crm_Issue_Model::STATUS_NEW:
				return 'issue-new';

			case Crm_Issue_Model::STATUS_ACC:
				return 'issue-acc';

			case Crm_Issue_Model::STATUS_STR:
				return 'issue-str';

			case Crm_Issue_Model::STATUS_DON:
				return 'issue-don';

			case Crm_Issue_Model::STATUS_FIX:
				return 'issue-fix';

			case Crm_Issue_Model::STATUS_NOF:
				return 'issue-nof';

			case Crm_Issue_Model::STATUS_DUP:
				return 'issue-dup';

			case Crm_Issue_Model::STATUS_INF:
				return 'issue-inf';

			case Crm_Issue_Model::STATUS_ROP:
				return 'issue-rop';
		}
		return 'issue-unk';
	}

}

class Crm_Issue_Model_List extends Cgn_Data_Model_List {
	public $tableName = 'crm_issue';
	public $modelName = 'Crm_Issue_Model';

	var $ownerIdField = 'user_id';
	var $groupIdField = 'crm_acct_id';

	var $sharingModeRead   = '';
	var $sharingModeCreate = '';
	var $sharingModeUpdate = '';
	var $sharingModeDelete = '';

}
