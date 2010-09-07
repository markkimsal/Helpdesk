<?php

Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
Cgn::loadLibrary("Html_Widgets::lib_cgn_toolbar");
Cgn::loadLibrary("lib_cgn_mvc");
Cgn::loadLibrary("lib_cgn_mvc_table");



/**
 * CRM Main service
 *
 * Show an overview of account activity
 */
class Cgn_Service_Crmtech_Main extends Cgn_Service {

	public $requireLogin = TRUE;

	/**
	 * Signal whether or not the user can access
	 * this service given event $e
	 */
	function authorize($e, $u) {
		if ($u->isAnonymous())
			return FALSE;
		if (!$u->belongsToGroup('crmtech'))
			return FALSE;

		return true;
	}

	public function eventBefore($req, &$t) {

		Cgn_Template::setPageTitle('CRM Tech');
		Cgn_Template::addSiteCss('crm_screen.css');
	}

	public function _makeToolbar() {
	   	$tb = new Cgn_HtmlWidget_Toolbar();

		$btn1 = new Cgn_HtmlWidget_Button(cgn_appurl($this->moduleName, 'issue'), 'Browse All Questions');
		$tb->addButton($btn1);

		$btn2 = new Cgn_HtmlWidget_Button(cgn_appurl($this->moduleName, 'file'), "Browse All Files");
		$tb->addButton($btn2);

		$btn3 = new Cgn_HtmlWidget_Button(cgn_appurl($this->moduleName, 'acct'), "Browse All Organizations");
		$tb->addButton($btn3);
		return $tb;
	}

	/**
	 * Show a list of pending organizations 
	 *
	 * Show a list of files which don't belong to anybody.
	 */
	function mainEvent($req, &$t) {
		$t['toolbar'] = $this->_makeToolbar();
//		$t['acctName'] = $account->get('org_name');
//		$t['acctId'] = $accountId;

		$orgs = $this->_findPendingOrganizations();
		$t['orgsHeader'] = '<h2>Pending Organizaitons</h2>';
		$t['orgs'] = $this->_makeOrgTable($orgs);

		$quest = $this->_findPendingQuestions();
		$t['questHeader'] = '<h2>Latest Issue Activity</h2>';
		$t['quest'] = $this->_makeQuestionTable($quest);
		$t['quest']->attribs = array('cellpadding'=>'7');


		$files = $this->_findPendingFiles();
		$t['filesHeader'] = '<h2>Pending Files</h2>';
		$t['files'] = $this->_makeFileTable($files);

		$t['questStatus'] = $this->_findQuestionsByStatus();

/*
		$t['fileList'] = $this->_getFiles($accountId);
		$this->_textToHtml($t['issueList']);
 */
	}

	/**
	 * htmlentities and nl2p
	 */
	function _textToHtml(&$issues) {
		foreach ($issues as $_idx => $_i) {
			$m = htmlentities($_i->get('message'));
			$m = str_replace("\r", "\n", $m);
			$m = str_replace("\n\n", "\n", $m);
			$m = ereg_replace("[a-zA-Z]+://([-]*[.]?[a-zA-Z0-9_/-?&%])*", "<a href=\"\\0\">\\0</a>", $m);
			$m = '<p>'. str_replace("\n\n", "</p><p>", $m).'</p>';
			$_i->set('message', $m);
			$issues[$_idx] = $_i;
		}
	}


	/**
	 * Find counts of all questions grouped by status code
	 */
	function _findQuestionsByStatus() {
		Cgn::loadModLibrary('Crm::Crm_Issue');
		$finder = new Cgn_DataItem('crm_issue');
		$finder->_cols = array('count(*) as total_count', 'status_id');
		$finder->andWhere('thread_id', 0);
		$finder->orWhereSub('thread_id', NULL, 'IS');
		$finder->groupBy('status_id');
//		$finder->dataItem->limit(2);
		$finder->_rsltByPkey = FALSE;
		$quest = $finder->findAsArray();
		$allStatus = Crm_Issue_Model::_getStatusIds();
		$ret = array();
		foreach ($allStatus as $_id) {
			$ret[$_id] = array('total_count'=>0, 'status_id'=>$_id, 'display_name'=>Crm_Issue_Model::_getStatusLabelStatic($_id));
			foreach ($quest as $_rec) {
				if ($_rec['status_id'] == $_id) {
					$ret[$_id]['total_count'] = $_rec['total_count'];
					break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Load up recent files based on an account id.
	 */
	function _getFiles($accountId) {
		Cgn::loadModLibrary('Crm::Crm_File');
		$finder = new Crm_File_Model_List();
		$finder->dataItem->andWhere('crm_acct_id', $accountId);
		$finder->dataItem->limit(2);
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
	}

	/**
	 * Wrap list of cgn_account information into a table
	 */
	function _makeOrgTable($orgList) {
		Cgn::loadLibrary('Form::Lib_Cgn_Form');
		$list = new Cgn_Mvc_TableModel();
		$table = new Cgn_Mvc_TableView($list);
		$table->attribs['width']='600';
		$table->attribs['border']='0';

		$table->_model->headers = array('Company Name', 'Terms Agreement');

		foreach ($orgList as $_org) {
			$table->_model->data[] = array(
				cgn_applink(
				   $_org->get('org_name'),
				   'crmtech', 'acct', 'approve', 
				   array('id'=>$_org->get('crm_acct_id')),
				   'https'
			   ),
			   $_org->get('agreement_date')
			);
		}
		$table->setColRenderer(1, new Cgn_Mvc_Table_DateRenderer('Y-M-d'));
		return $table;
	}


	/**
	 * Wrap list of crm_file information into a table
	 */
	function _makeFileTable($orgList) {
		Cgn::loadLibrary('Form::Lib_Cgn_Form');
		$list = new Cgn_Mvc_TableModel();
		$table = new Cgn_Mvc_TableView($list);
		$table->attribs['width']='600';
		$table->attribs['border']='0';

		$table->_model->headers = array('File Name');

		foreach ($orgList as $_org) {
			$table->_model->data[] = array(
				cgn_applink(
				   $_org->get('link_text'),
				   'crmtech', 'file', 'edit', 
				   array('id'=>$_org->get('crm_file_id')),
				   'https'
				)
			);
		}
		return $table;
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

//		$table->_model->headers = array('Question Name', 'Org', 'Status');
		$table->_model->data = $questList;
/*
		foreach ($questList as $_item) {
			$table->_model->data[] = $_item;
			/*
			$table->_model->data[] = array(
				cgn_applink(
				   substr($_item->get('message'), 0, 200),
				   'crmtech', 'issue', 'view', 
				   array('id'=>$_item->get('crm_issue_id')),
				   'https'
			   ),
			   $_item->get('org_name'),
			   Crm_Issue_Model::_getStatusLabelStatic($_item->get('status_id')),
			);
			// * /
		}
 */
		return $table;
	}



	/**
	 * Show a list of files which do not have a CRM account
	 */
	function _findPendingFiles() {
		//find any user_org links
		$finder = new Cgn_DataItem('crm_file');
		$finder->_cols[] = 'crm_file.crm_file_id';
		$finder->_cols[] = 'crm_file.link_text';
		$finder->andWhere('crm_file.crm_acct_id', NULL, 'IS');
		$finder->orWhereSub('crm_file.crm_acct_id', 0);

		$finder->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$supportAccounts = $finder->find();
		return $supportAccounts;
	}

	/**
	 * Show a list of questions which have status "new"
	 */
	function _findPendingQuestions() {
		Cgn::loadModLibrary('Crm::Crm_Issue');
/*
		$startingValues = array();
		$startingValues[] = 0; //bad status values
		$startingValues[] = Crm_Issue_Model::STATUS_NEW;
		$startingValues[] = Crm_Issue_Model::STATUS_ACC;
		$startingValues[] = Crm_Issue_Model::STATUS_STR;
		$startingValues[] = Crm_Issue_Model::STATUS_ROP;
 */

		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->_cols = array('crm_issue.*', 'Tacct.org_name');
//		$finder->andWhere('crm_issue.status_id', $startingValues, 'IN');
//		$finder->andWhere('crm_issue.thread_id', NULL, 'IS');
		$finder->dataItem->hasOne('crm_acct', 'crm_acct_id', 'Tacct', 'crm_acct_id');
		$finder->dataItem->limit(50);
		$finder->dataItem->sort('post_datetime', 'DESC');


		$finder->dataItem->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$itemList = $finder->loadVisibleList();
		return $itemList;
	}

	/**
	 * Show a list of organization which do not have CRM support
	 */
	function _findPendingOrganizations() {
		//find any user_org links
		$finder = new Cgn_DataItem('crm_acct');
		$finder->andWhere('is_active', 0);
		/*
		$finder = new Cgn_DataItem('cgn_account');
		$finder->_cols[] = 'cgn_account.*';
		$finder->hasOne('crm_acct', 'org_account_id', 'Tacct', 'cgn_account_id');
		$finder->andWhere('Tacct.crm_acct_id', NULL, 'IS');

		//organizational accounts have user_id=0
		$finder->andWhere('cgn_user_id', 0);
//		$finder->andWhere('Tacct.cgn_account_id', $oids, 'IN');
		$finder->_rsltByPkey = FALSE;
		//TODO: add date checking with valid_thru
		 */
		$supportAccounts = $finder->find();
		return $supportAccounts;
	}
}
?>
