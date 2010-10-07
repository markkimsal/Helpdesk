<?php
Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
Cgn::loadLibrary("lib_cgn_mvc");
Cgn::loadLibrary("lib_cgn_mvc_table");



/**
 * CRM Issue service
 *
 * Show browsable issues, and a form for asking a new question
 */
class Cgn_Service_Crm_File extends Cgn_Service {

	public $requireLogin = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('Support Files');
		Cgn_Template::addSiteCss('crm_screen.css');
	}

	function mainEvent($req, &$t) {
		Cgn::loadModLibrary('Crm::Crm_Acct');
		$u = $req->getUser();
		//find cached account ID
		$accountId = $req->getSessionVar('crm_acct_id');
		if (!$accountId) {
			$account = Crm_Acct::findSupportAccount($u);
			if ($account) {
				$accountId = $account->getPrimaryKey();
			} else {
				$u->addMessage('Permission denied', 'msg_warn');
				return false;
			}
		} else {
			$account = new Cgn_DataItem('crm_acct');
			$account->load($accountId);
		}
		$t['acctName'] = $account->get('org_name');
		$t['acctId'] = $accountId;

		//pagination
		$p = $req->cleanInt('p');
		if ($p < 1) {
			$p = 1;
		}
		$t['baseUrl']  = cgn_appurl('crm', 'file', '', '', 'https');
		$t['prevUrl']  = cgn_appurl('crm', 'file', '', array('p'=>$p-1), 'https');
		$t['nextUrl']  = cgn_appurl('crm', 'file', '', array('p'=>$p+1), 'https');
		$t['curPage']  = $p;


		//load up files
		$t['fileList'] = $this->_getFiles($accountId, $t, $p);
		//pagination
		$t['rowCount'] = count($t['fileList']);
	}

	/**
	 * Load up recent files based on an account id.
	 */
	function _getFiles($accountId, &$t, $page=1) {
		Cgn::loadModLibrary('Crm::Crm_File');
		$finder = new Crm_File_Model_List();
		$finder->dataItem->_cols = array('crm_file_id', 'link_text', 'title', 'published_on', 'cgn_guid');
		$finder->dataItem->andWhere('crm_acct_id', $accountId);
		$finder->dataItem->limit(10, $page-1);

		$t['unlimitedRowCount'] = $finder->dataItem->getUnlimitedCount();
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
	}

}
