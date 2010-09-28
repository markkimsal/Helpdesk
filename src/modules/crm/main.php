<?php

Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
Cgn::loadLibrary("Form::lib_cgn_form");
Cgn::loadLibrary("lib_cgn_mvc");
Cgn::loadLibrary("lib_cgn_mvc_table");

Cgn::loadModLibrary("Crm::Crm_Util_Ui");
Cgn::loadModLibrary("Crm::Crm_Acct");

/**
 * CRM Main service
 *
 * Show an overview of account activity
 */
class Cgn_Service_Crm_Main extends Cgn_Service {

	public $requireLogin = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('Support Home');
		Cgn_Template::addSiteCss('crm_screen.css');
		Cgn_Template::addSiteJs('crm_cutetime.js');
	}

	function mainEvent($req, &$t) {
		$u = $req->getUser();

		//find cached account ID, or do a look up
		$accountId = $req->getSessionVar('crm_acct_id');
		if (!$accountId) {
			//echo "searching for id";
			$account = $this->_findSupportAccount($u);
			if ($account) {
				$accountId = $account->getPrimaryKey();
			}

		} else {
			$account = new Cgn_DataItem('crm_acct');
			$account->load($accountId);
		}
		if (!$account) {
			$newTicket = new Cgn_SystemTicket('crm', 'apply', 'main');
			Cgn_SystemRunner::stackTicket($newTicket);
			return TRUE;
		}
		$t['acctName'] = $account->get('org_name');
		$t['acctId'] = $accountId;

		$t['issueList'] = $this->_getIssues($accountId);
		$this->_textToHtml($t['issueList']);

		//TODO add in user icons
//		Cgn_User_Account::getAccountImageUrl($uid, $aid);

		$t['fileList'] = $this->_getFiles($accountId);
		//$this->_textToHtml($t['fileList']);
	}

	/**
	 * htmlentities and nl2p
	 */
	function _textToHtml(&$issues) {
		foreach ($issues as $_idx => $_i) {
			$m = htmlentities($_i->get('message'));
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

			$issues[$_idx] = $_i;
		}
	}

	/**
	 * Load up recent issues based on an account id.
	 */
	function _getIssues($accountId) {
		Cgn::loadModLibrary('Crm::Crm_Issue');
		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->andWhere('crm_acct_id', $accountId);
		$finder->dataItem->andWhere('thread_id', 0);
		$finder->dataItem->orWhereSub('thread_id', NULL, 'IS');
		$finder->dataItem->sort('post_datetime', 'DESC');
		$finder->dataItem->limit(4);
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
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
	 * Try organizational accounts first, then individual accounts
	 */
	function _findSupportAccount($u) {
		//find organization support account
		$account = Crm_Acct::findSupportAccount($u);

		if (!$account) {
			//find direct support account
			$account = $this->_findIndividualSupport($u);
		}
		if ($account) {
			$accountId = $account->getPrimaryKey();
		}
		if ($accountId > 0) {
			$session = Cgn_Session::getSessionObj();
			$session->set('crm_acct_id', $accountId);
		}
		return $account;
	}

	function _findIndividualSupport($user) {
	}


	public function processAuthFailure($e, $req, &$t) {
		$newTicket = new Cgn_SystemTicket('crm', 'apply', 'main');
		Cgn_SystemRunner::stackTicket($newTicket);
		return TRUE;
	}
}
