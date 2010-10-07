<?php

Cgn::loadLibrary('Html_Widgets::Lib_Cgn_Widget');
Cgn::loadLibrary('Html_Widgets::Lib_Cgn_Toolbar');
Cgn::loadLibrary('Form::Lib_Cgn_Form');
Cgn::loadLibrary('Lib_Cgn_Mvc');
Cgn::loadLibrary('Lib_Cgn_Mvc_Table');
Cgn::loadModLibrary('Crm::Crm_Issue');

class Cgn_Service_Crmtech_Issue extends Cgn_Service_Crud {

	public $representing = "Issue";
	public $pageTitle    = 'CRM Issues';
	public $dataItemName = 'crm_file';
	public $dataModelName = 'Crm_Issue_Model';
	//
	public $tableHeaderList = array('ID', 'Account', 'Subject');
	public $tableColList    = array('crm_issue_id', 'org_name', 'subject');
	public $tablePaged      = TRUE;

	private $fltrStudyId = 0;

	private $accountObj = NULL;

	public $requireLogin = TRUE;

	function authorize($e, $u) {
		if ($u->isAnonymous())
			return FALSE;
		if (!$u->belongsToGroup('crmtech'))
			return FALSE;

		return true;
	}
	

	/**
	 * Add navigation and css
	 */
	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('CRM Tech Issues');
		Cgn_Template::addSiteCss('crm_screen.css');
		$t['pageNav'] = '<div><a href="'.cgn_appurl('crmtech').'">Back to CRM home</a></div>';
	}

	/**
	 * Show a list of accounts
	 */
	public function mainEvent($req, &$t) {
		$ret = parent::mainEvent($req, $t);
		$viewUrl = cgn_appurl('crmtech', 'issue', 'view');
		$editUrl = cgn_appurl('crmtech', 'issue', 'edit');
		$t['dataGrid']->setColRenderer(0, new Cgn_Mvc_Table_ColRenderer_Url($editUrl, array('id'=>0) ));
		$t['dataGrid']->setColRenderer(2, new Cgn_Mvc_Table_ColRenderer_Url($viewUrl, array('id'=>0) ));
		return $ret;
	}

	/**
	 * Don't load the "file_binary" field for editing
	 */
	protected function _loadListData() {
		$finder = new Cgn_DataItem('crm_issue');
		$finder->_cols = array(
			'crm_issue_id',
			'IF(LENGTH(subject), subject, "no subject") as subject',
			'Tacct.org_name');
		$finder->hasOne('crm_acct', 'crm_acct_id', 'Tacct', 'crm_acct_id');
		$finder->andWhere('thread_id', 0);
		$finder->orWhereSub('thread_id', NULL, 'IS');
		$req = Cgn_SystemRequest::getCurrentRequest();

		if ($req->cleanInt('status_id')) {
			$finder->andWhere('status_id', $req->cleanInt('status_id'));
		}
		return $finder->findAsArray();
	}


	/**
	 * Show a form to make a new data item
	 * Don't load the "file_binary" field for editing
	 */
	/*
	 */
	function editEvent($req, &$t) {
		//make page title 
		$this->_makePageTitle($t);

		//make toolbar
		$this->_makeToolbar($t);
		$c = $this->dataModelName;
		$this->dataModel = new $c();
		$this->dataModel->dataItem->_cols = array(
			'crm_issue_id',
			'crm_acct_id',
			'status_id',
			'is_hidden',
			'subject',
			'message',
			);
		$this->dataModel->load($req->cleanInt('id'));

		//make the form
		$f = $this->_makeEditForm($t, $this->dataModel);
		$this->_makeFormFields($f, $this->dataModel, TRUE);
	}

	protected function _makeTableRow($d) {
		return $d;
	}

	/**
	 * Delete one reply
	 */
	public function delreplyEvent($req, &$t) {

		$id = $req->cleanInt('id');
		$isAjax = $req->cleanString('xhr');
		$eraser = new Cgn_DataItem('crm_issue');
		$eraser->andWhere('crm_issue_id', $id);
		$eraser->delete();

	}

	/**
	 * Delete entire thread reply
	 */
	public function delEvent($req, &$t) {

		$id = $req->cleanInt('id');
		$isAjax = $req->cleanString('xhr');
		$eraser = new Cgn_DataItem('crm_issue');
		$eraser->andWhere('crm_issue_id', $id);
		$eraser->delete();

		$eraser = new Cgn_DataItem('crm_issue');
		$eraser->andWhere('thread_id', $id);
		$eraser->delete();

	}



	/**
	 * Load 1 data item and place it in the template array.
	 */
	function viewEvent($req, &$t) {
		//make page title 
		$this->_makePageTitle($t);

		//make toolbar
		$this->_makeToolbar($t);

		//load a default data model if one is set
		if ($this->dataModelName != '') {
			$c = $this->dataModelName;
			$this->dataModel = new $c();
		} else {
			$this->dataModel = new Cgn_DataItem($this->dataItemName);
		}
		if(!$this->dataModel->load($req->cleanInt('id'))) {
			$this->templateName = 'issue_nosuchissue';
		}

		$this->_textToHtml($this->dataModel);
		$t['issue'] = $this->dataModel;

		if ($this->eventName == 'view') {

			//Edit button
			$editParams = array('id'=>$req->cleanInt('id'));
			$btn4 = new Cgn_HtmlWidget_Button(
				cgn_appurl($this->moduleName, $this->serviceName, 'edit', $editParams),
				"Edit This ".ucfirst(strtolower($this->representing)));
				
			$t['toolbar']->addButton($btn4);

			//Delete button
			$delParams = array('id'=>$req->cleanInt('id'), 
				'table'=>$this->dataModel->get('_table'));
			$btn3 = new Cgn_HtmlWidget_Button(
				cgn_appurl($this->moduleName, $this->serviceName, 'del', $delParams),
				"Delete This ".ucfirst(strtolower($this->representing)));
				
			$t['toolbar']->addButton($btn3);
		}

		$t['replyList'] = $this->_getReplies( $req->cleanInt('id') );
		//take each Cgn_DataItem and translate the text into 
		// <p> wrapped tags, like php's nl2br but with <p> tags
		foreach ($t['replyList'] as $_k => $_v) {
			$this->_textToHtml($_v);
			$t['replyList'][$_k] = $_v;
		}

		$allStatus = Crm_Issue_Model::_getStatusIds();
		$statusNames = array();
		foreach ($allStatus as $_id) {
			$statusNames[$_id] = Crm_Issue_Model::_getStatusLabelStatic($_id);
		}
		$t['statusList'] = $statusNames;

		$t['account'] = new Cgn_DataItem('crm_acct');
		$t['account']->load($this->dataModel->get('crm_acct_id'));
	}

	/**
	 * Not used
	 */
	function saveEvent($req, &$t) {
		Cgn::loadModLibrary('CRM::Crm_Issue');

		$id = $req->cleanInt('id');
		$item = Crm_Issue_Model::createNewIssue();
		//load a default data model if one is set
		if ($id > 0 ) {
			$item->load($id);
		}
		$vals = $item->valuesAsArray();

		foreach ($vals as $_key => $_val) {
			if ($_key == $item->get('_pkey')) {continue;}
			//for some reason, i called the text area "ctx" in the form.
			//maybe to stop spam?
			if ($_key == 'message') { $_key = 'ctx'; }
			if ($req->hasParam($_key)) {
				if ($_key == 'ctx') 
				$item->set('message', $req->cleanMultiline($_key));
				else
				$item->set($_key, $req->cleanString($_key));
			}
		}
		$this->item = $item;

		if ($this->item->get('subject') == '') {
			$s = Crm_Issue_Model::_generateSubject($this->item->get('message'));
			$this->item->set('subject', $s);
			$this->item->save();
		}
		$item->save();
		//send notices to account holders.
		$this->_alertAccount($item->getPrimaryKey());

		$this->redirectHome($t);
	}


	/**
	 * Save a new or old reply to a question
	 */
	function saveReplyEvent($req, &$t) {
		Cgn::loadModLibrary('CRM::Crm_Issue');
		$id     = $req->cleanInt('id');
		$thread = $req->cleanInt('thread_id');
		$sid    = $req->cleanInt('status_id');
		$u      = $req->getUser();
		$issue  = Crm_Issue_Model::createNewIssue();

		if ($id) {
			//TODO: check owner
			//$issue->andWhere('cgn_user_id', $u->userId);
			$issue->load($id);
		}

		$parent = Crm_Issue_Model::createNewIssue();
		$parent->load($thread);
		//$parent->set('status_id', Crm_Issue_Model::STATUS_DON);
		$parent->set('status_id', $sid);
		$parent->save();

		$accountId = $parent->get('crm_acct_id');


		$comment = $req->cleanMultiLine('ctx');
		if (trim($comment) == '') {
			//we just saved the status change, don't save a new reply.
			$t['url'] = cgn_appurl('crmtech', '', '', '', 'https');
			$this->presenter = 'redirect';
			return;
		}
		$name = $u->getDisplayName();
		$issue->set('message', $comment);
		$issue->set('post_datetime', time());
		$issue->set('crm_acct_id', $accountId);
		$issue->set('user_name', $name);
		$issue->set('user_id', $u->userId);
		$issue->set('thread_id', $thread);
		$issue->save();

		$this->_alertAccount($thread);
		$t['url'] = cgn_appurl('crmtech', '', '', '', 'https');
		$this->presenter = 'redirect';
	}

	/**
	 * Not used
	 */
	public function createEvent($req, &$t) {
		parent::createEvent($req, $t);
	}

	/**
	 * Function to create a default form
	 */
	protected function _makeCreateForm(&$t, $dataModel) {
		$f = parent::_makeCreateForm($t, $dataModel);
		$f->action = cgn_appurl($this->moduleName, $this->serviceName, 'save', '', 'https');
		$t['form'] = $f;
		return $f;
	}

	function getHomeUrl($params = array()) {
		list($module,$service,$event) = explode('.', Cgn_ObjectStore::getObject('request://mse'));
		return cgn_appurl($module,$service, '', $params, 'https');
	}


	protected function _makeFormFields($f, $dataModel, $editMode=FALSE) {
		$values = $dataModel->valuesAsArray();
//		$acctValues = $this->accountObj->valuesAsArray();

		//load a list of account IDs and names, use them for crm_acct_id field
		$accountList = $this->_loadAccounts();

		foreach ($values as $k=>$v) {
			if ($k == 'crm_acct_id') {
				$widget = new Cgn_Form_ElementSelect('crm_acct_id', 'Organization');
				$widget->size = 1;
				$widget->addChoice('No Org Set', '_0');
				foreach ($accountList as $_acct) {
					$widget->addChoice($_acct['org_name'], $_acct['crm_acct_id']);
				}
				if (isset($values['crm_acct_id'])) {
					$widget->setValue($values['crm_acct_id']);
				}
				$f->appendElement($widget, $v);
				unset($widget);
				continue;
			}

			if ($k == 'status_id') {
				$widget = new Cgn_Form_ElementSelect('status_id', 'Status');
				$widget->size = 1;
				$statusList = Crm_Issue_Model::_getStatusIds();
//				$statusList = Crm_Issue_Model::_getStatusLabelStatic($id);
				foreach ($statusList as $_status) {
					$widget->addChoice(
						Crm_Issue_Model::_getStatusLabelStatic($_status),
						 $_status);
				}
				if (isset($values['status_id'])) {
					$widget->setValue((int)$values['status_id']);
				}
				$f->appendElement($widget, $v);
				unset($widget);
				continue;
			}

			if ($k == 'is_hidden') {
				$widget = new Cgn_Form_ElementSelect('is_hidden', 'Hidden?');
				$widget->size = 1;
				$widget->addChoice('No', '0');
				$widget->addChoice('Yes', '1');
				$widget->setValue($values['is_hidden']);
				$f->appendElement($widget, $v);
				unset($widget);
				continue;
			}
			if ($k == 'message') {
				$widget = new Cgn_Form_ElementText('message', 'Message');
				$widget->size = 1;
				$widget->setValue($values['message']);
				$f->appendElement($widget, $v);
				unset($widget);
				continue;
			}

			//don't add the primary key if we're in edit mode
			if ($editMode == TRUE) {
				if ($k == 'id' || $k == $dataModel->get('_table').'_id') continue;
			}
			$widget = new Cgn_Form_ElementInput($k);
			$widget->size = 55;
			$f->appendElement($widget, $v);
			unset($widget);
		}

		//not used
//		$f->appendElement(new Cgn_Form_ElementHidden('org_account_id'), $acctValues['cgn_account_id']);
		if ($editMode == TRUE) {
			$f->appendElement(new Cgn_Form_ElementHidden('id'), $dataModel->getPrimaryKey());
		}
	}

	protected function _loadAccounts() {
		$finder = new Cgn_DataItem('crm_acct');
		$finder->_cols = array(
			'org_account_id', 
			'crm_acct_id',
			'org_name',
		);
		return $finder->findAsArray();

	}

	/**
	 * htmlentities and nl2p
	 */
	function _textToHtml($_i) {
		$m = htmlentities($_i->get('message'));
		$m = trim($m);
		if ($m == '') {
			$_i->set('preview', '<p>&nbsp</p>');
			$_i->set('message', '<p>&nbsp</p>');
			return;
		}
		$p = substr(htmlentities($_i->get('message')), 0, 200);
		$m = str_replace("\r", "\n", $m);
		$m = str_replace("\n\n", "\n", $m);
		$m = ereg_replace("[a-zA-Z]+://([-]*[.]?[a-zA-Z0-9_/-?&%])*", "<a href=\"\\0\">\\0</a>", $m);
		$m = '<p>'. str_replace("\n\n", "</p><p>", $m).'</p>';
		$_i->set('message', $m);

		$p = str_replace("\r", "\n", $p);
		$p = str_replace("\n\n", "\n", $p);
		$p = '<p>'. str_replace("\n\n", "</p><p>", $p).'</p>';
		$_i->set('preview', $p);
	}

	/**
	 * Load up recent issues based on an account id.
	 */
	function _getReplies( $id ) {
		Cgn::loadModLibrary('Crm::Crm_Issue');
		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->andWhere('thread_id', $id);
		$finder->dataItem->sort('post_datetime', 'ASC');
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
	}

	public function _alertAccount($issueId) {
		Cgn::loadLibrary('Mail::Lib_Cgn_Message_Mail');
		$defaultEmail = Cgn_ObjectStore::getConfig('config://default/email/contactus');
		if (Cgn_ObjectStore::hasConfig('config://default/email/replyto')) {
			$defaultReply = Cgn_ObjectStore::getConfig('config://default/email/replyto');
		} else {
			$defaultReply = $defaultEmail;
		}

		//find the account emails based on the crm_acct_id of the issue_id
		$emailList = $this->_getAccountEmailsForIssue($issueId);

		$m = new Cgn_Message_Mail();
		$m->sendCombinedTo = FALSE;
		$m->subject = 'New Reply to Support Issue';
		$m->toList = $emailList;
		$m->from   = $defaultReply;
		$m->reply  = $defaultReply;
		$m->body  .= "Click to read: \n".
		cgn_appurl(
			'crm', 'issue', '',
			'',
			'https'
		).'ra'.$issueId;

		$m->sendMail();
	}

	/**
	 * Load all account emails for users in the org what 
	 * owns this issueId
	 *
	 * @returm Array  list of emails
	 */
	public function _getAccountEmailsForIssue($issueId) {
		//find the account emails based on the crm_acct_id of the issue_id
		$finder = new Cgn_DataItem('crm_issue');
		$finder->_cols = array('Tacct.crm_acct_id', 'crm_issue.crm_issue_id');
		$finder->andWhere('crm_issue_id', $issueId);
		$finder->hasOne('crm_acct', 'crm_acct_id', 'Tacct', 'crm_acct_id');
		$rows = $finder->find();
		$accountId = 0;
		foreach ($rows as $row) {
			$accountId = (int)$row->get('crm_acct_id');
		}
		if ($accountId < 1 ) {
			return array();
		}

		//get the organization ID from the crm account ID
		$finder = new Cgn_DataItem('crm_acct');
		$finder->_cols = array('Torg.cgn_account_id');
		$finder->andWhere('crm_acct_id', $accountId);
		$finder->hasOne('cgn_account', 'cgn_account_id', 'Torg', 'org_account_id');
		$rows = $finder->find();
		$orgId = 0;
		foreach ($rows as $row) {
			$orgId = (int)$row->get('cgn_account_id');
		}
		if ($orgId < 1 ) {
			return array();
		}

		//get the emails from the cgn_user table and the cgn_user_org_link table
		$finder = new Cgn_DataItem('cgn_user_org_link');
		$finder->_cols = array('Tuser.email', 'Tuser.cgn_user_id');
		$finder->andWhere('cgn_org_id', $orgId);
		$finder->hasOne('cgn_user', 'cgn_user_id', 'Tuser', 'cgn_user_id');
		$finder->echoSelect();
		$rows = $finder->find();

		$emails = array();
		foreach ($rows as $row) {
			$emails[ $row->get('cgn_user_id') ] = $row->get('email');
		}
		return $emails;
	}

}
