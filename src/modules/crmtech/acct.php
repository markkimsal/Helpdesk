<?php

Cgn::loadLibrary('Html_Widgets::Lib_Cgn_Widget');
Cgn::loadLibrary('Html_Widgets::Lib_Cgn_Toolbar');
Cgn::loadLibrary('Form::Lib_Cgn_Form');
Cgn::loadLibrary('Lib_Cgn_Mvc');
Cgn::loadLibrary('Lib_Cgn_Mvc_Table');
Cgn::loadModLibrary('Crm::Crm_Acct');

class Cgn_Service_Crmtech_Acct extends Cgn_Service_Crud {

	public $representing = "Account";
	public $pageTitle    = 'CRM Accounts';
	public $dataModelName = 'Crm_Acct';

	public $tableHeaderList = array('ID', 'Company Name', 'Start Date');
	public $tableColList    = array('crm_acct_id', 'org_name', 'approved_on');
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
	 * Add navigation
	 */
	public function eventBefore($req, &$t) {
		Cgn_Template::addSiteCss('crm_screen.css');
		Cgn_Template::addSiteCss('crmtech_screen.css');
		$t['pageNav'] = '<div><a href="'.cgn_appurl('crmtech').'">Back to tech dashboard</a></div>';
	}

	/**
	 * Show a list of accounts
	 */
	public function mainEvent($req, &$t) {
		$ret = parent::mainEvent($req, $t);
		$viewUrl = cgn_sappurl('crmtech', 'acct', 'view');
		$viewUrlParams = array('id'=>0);
		$t['dataGrid']->setColRenderer(2, new Cgn_Mvc_Table_DateRenderer('m-d-Y'));
		//make the company name clickable by using the first col's value as a parameter to 'id'
		$t['dataGrid']->setColRenderer(1, new Cgn_Mvc_Table_ColRenderer_Url($viewUrl, $viewUrlParams));
		return $ret;
	}

	protected function _loadListData() {
		$finder = new Cgn_DataItem('crm_acct');
		return $finder->findAsArray();
	}

	protected function _makeTableRow($d) {
		if ($d['org_name'] == '')
			$d['org_name'] = '# No Name #';
		return $d;
	}


	public function saveEvent($req, &$t) {
		parent::saveEvent($req, $t);
		if ($this->item->get('approved_on') == 0) {
			$this->item->set('approved_on', time());
			$this->item->save();
		}
	}

	/**
	 * Show a list of accounts
	 */
	public function createEvent($req, &$t) {
		$this->accountObj = new Cgn_DataItem('cgn_account');
		$this->accountObj->load($req->cleanInt('acct_id'));
		parent::createEvent($req, $t);
	}

	/**
	 * Show account info, owner info, and a list of issues
	 */
	public function viewEvent($req, &$t) {
		parent::viewEvent($req, $t);

		//load the user who started this group
		$ownerId = $this->dataModel->get('owner_id');
		$u = Cgn_User::load($ownerId);
		$u->fetchAccount();
		$t['owner'] = $u;
		$dataModel = array();
		$dataModel[] = array('Username', $u->username);
		$dataModel[] = array('E-mail',   $u->email);
		$dataModel[] = array('Name',     $u->account->get('lastname').', '.$u->account->get('firstname'));


		$dm = new Cgn_Mvc_TableModel($dataModel);
		$dm->data = $dataModel;
		$t['ownerTable'] = new Cgn_Mvc_TableView($dm);


		$t['acct_id']    = $this->dataModel->get('crm_acct_id');

		$quest = $this->_findAcctIssues($req->cleanInt('id'));
		$t['questHeader'] = '<h3>Latest Issues</h3>';
		$t['quest'] = $this->_makeQuestionTable($quest);
		$t['quest']->attribs = array('cellpadding'=>'7');


		//member table
		$finder = new Cgn_DataItem('cgn_user_org_link');
		$finder->_cols= array('TB.username', 'role_code');
		$finder->hasOne('cgn_user', 'cgn_user_id', 'TB', 'cgn_user_id');

		$finder->andWhere('cgn_org_id', $this->dataModel->get('org_account_id'));
		$finder->_rsltByPkey = false;
		$members = $finder->findAsArray();

		$t['memberTable'] = $this->_loadMemberTable($members);
	}


	/**
	 * Show a form to approve a pending support account
	 */
	function approveEvent($req, &$t) {
		//make page title 
		$this->_makePageTitle($t);

		//make toolbar
		$this->_makeToolbar($t);

		//load a default data model if one is set
		$c = $this->dataModelName;
		$this->dataModel = new $c();
		$this->dataModel->load($req->cleanInt('id'));

		//make the form
		$f = $this->_makeApproveForm($t, $this->dataModel);
		$this->_makeApproveFields($f, $this->dataModel, TRUE);
	}

	/**
	 * Approve the application by creating a new organization
	 */
	function saveApproveEvent($req, &$t) {
		$u = $req->getUser();

		//load a default data model if one is set
		$c = $this->dataModelName;
		$this->dataModel = new $c();
		$this->dataModel->load($req->cleanInt('id'));

		if ($req->cleanString('doapprove') == 'd') {
			//delete application
			$this->dataModel->deleteAccount();
			$u->addMessage("Application deleted");
			$t['url'] = cgn_sappurl('crmtech', 'acct');
			$this->presenter = 'redirect';
			return;
		}

		//if not 'a', don't do anything
		if ($req->cleanString('doapprove') != 'a') {
			$t['url'] = cgn_sappurl('crmtech', 'acct');
			$this->presenter = 'redirect';
			return;
		}

		$this->dataModel->turnOnAccount($u->userId);

		//create a new organization and add this user as the leader
		$orgAcct = $this->dataModel->_makeOrg($this->dataModel->get('owner_id'));
		$orgAcctId = $orgAcct->getPrimaryKey();

		$this->dataModel->set('org_account_id', $orgAcctId);
		$this->dataModel->save();

		$this->_sendApprovalEmail($this->dataModel->get('owner_id'));

		$u->addMessage("Created new support account.");
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
		if (is_object($this->accountObj)) {
			$acctValues = $this->accountObj->valuesAsArray();
		}

		foreach ($values as $k=>$v) {
			if ($v == '' && isset($acctValues[$k])) {
				$v = $acctValues[$k];
			}

			//don't add the primary key if we're in edit mode
			if ($editMode == TRUE) {
				if ($k == 'id' || $k == $dataModel->get('_table').'_id') continue;
			}
			$v = $this->formatValue($k, $v, $dataModel);
			$widget = new Cgn_Form_ElementInput($k);
			$widget->size = 55;
			$f->appendElement($widget, $v);
			unset($widget);
		}

//		$f->appendElement(new Cgn_Form_ElementHidden('org_account_id'), $acctValues['cgn_account_id']);
		if ($editMode == TRUE) {
			$f->appendElement(new Cgn_Form_ElementHidden('id'), $dataModel->getPrimaryKey());
		}
	}


	/**
	 * Make fields for the approve form
	 */
	protected function _makeApproveFields($f, $dataModel, $editMode=FALSE) {
		$values = $dataModel->valuesAsArray();

		unset($values['group_code']);
		unset($values['owner_id']);
		unset($values['valid_thru']);
		unset($values['approved_on']);
		unset($values['org_account_id']);
		unset($values['is_active']);
		unset($values['created_on']);

		$widget = new Cgn_Form_ElementLabel('org_name', 'Organization');
		$widget->size = 55;
		$f->appendElement($widget, $values['org_name']);
		unset($widget);

		$widget = new Cgn_Form_ElementLabel('ip_addr', 'IP Address');
		$widget->size = 55;
		$f->appendElement($widget, $values['agreement_ip_addr']);
		unset($widget);

		$widget = new Cgn_Form_ElementLabel('date', 'Agreement Date');
		$widget->size = 55;
		if ($values['agreement_date'] > 0) {
			$f->appendElement($widget, date('m-d-Y G:i:s',$values['agreement_date']));
		} else {
			$f->appendElement($widget, 'No Agreement');
		}
		unset($widget);

		$widget = new Cgn_Form_ElementRadio('doapprove', 'Approve/Deny?');
		$widget->size = 55;
		$widget->addChoice('Approve this organization', 'a');
		$widget->addChoice('Deny and remove this application', 'd');
		$f->appendElement($widget, $values['agreement_ip_addr']);
		unset($widget);

		$f->appendElement(new Cgn_Form_ElementHidden('id'), $values['crm_acct_id']);
		if ($editMode == TRUE) {
			$f->appendElement(new Cgn_Form_ElementHidden('id'), $dataModel->getPrimaryKey());
		}
	}


	/**
	 * Function to create a form for approving accounts.
	 */
	protected function _makeApproveForm(&$t, $dataModel) {
		$f = new Cgn_Form('datacrud_01');
		$f->width="auto";
		$f->action = cgn_appurl($this->moduleName, $this->serviceName, 'saveApprove', array(), 'https');
		$t['form'] = $f;
		return $f;
	}

	/**
	 * Send a confirmation email
	 */
	protected function _sendApprovalEmail($uid) {
		Cgn::loadLibrary("Mail::lib_cgn_message_mail");

		$defaultEmail = Cgn_ObjectStore::getConfig('config://default/email/contactus');
		if (Cgn_ObjectStore::hasConfig('config://default/email/replyto')) {
			$defaultReply = Cgn_ObjectStore::getConfig('config://default/email/replyto');
		} else {
			$defaultReply = $defaultEmail;
		}

		//TODO: add uid user's email to the message.
		$m = new Cgn_Message_Mail();
		$m->subject = 'New Support Application';
		$m->toList = array($defaultEmail);
		$m->from   = $defaultReply;
		$m->reply  = $defaultReply;
		$m->body   = "Support application approved.\n\n";
	}

	/**
	 * Fetch a list of issues realted to an account id
	 */
	function _findAcctIssues($acct_id) {
		Cgn::loadModLibrary('Crm::Crm_Issue');

		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->_cols = array('crm_issue.*', 'Tacct.org_name');
		$finder->dataItem->hasOne('crm_acct', 'crm_acct_id', 'Tacct', 'crm_acct_id');
		$finder->dataItem->andWhere('crm_issue.crm_acct_id', $acct_id);
		$finder->dataItem->limit(50);
		$finder->dataItem->sort('post_datetime', 'DESC');

		$finder->dataItem->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$itemList = $finder->loadVisibleList();
		return $itemList;
	}

	/**
	 * Wrap list of crm_issue information into a table
	 */
	function _makeQuestionTable($questList) {
		Cgn::loadModLibrary('Crm::Crm_Issue');
		Cgn::loadModLibrary('Crm::Crm_Util_Ui');
		Cgn::loadLibrary('Form::Lib_Cgn_Form');
		$list = new Cgn_Mvc_ListModel();
		$table = new Crm_Issue_Admin_ListView($list);
		$table->attribs['width']='600';
		$table->attribs['border']='0';
		$table->_model->data = $questList;
		return $table;
	}



	/**
	 * Create a table to show users and their roles
	 */
	public function _loadMemberTable($invites) {
		Cgn::loadLibrary('lib_cgn_mvc');
		Cgn::loadLibrary('lib_cgn_mvc_table');
		$dm = new Cgn_Mvc_TableModel($invites);
		$dm->columns = array('username', 'role_code');
		$dm->headers = array('Username', 'Role');

		$f = new Cgn_Mvc_TableView($dm);
		return $f;
	}
}
