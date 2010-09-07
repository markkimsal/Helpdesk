<?php

Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
Cgn::loadLibrary("lib_cgn_mvc");
Cgn::loadLibrary("lib_cgn_mvc_table");


Cgn::loadModLibrary('Crm::Crm_Acct');

/**
 * CRM Main service
 *
 * Show an overview of account activity
 */
class Cgn_Service_Crm_Download extends Cgn_Service {

	public $requireLogin = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::addSiteCss('crm_screen.css');
	}

	function mainEvent($req, &$t) {
		$u = $req->getUser();

		//find cached account ID, or do a look up
		$accountId = $req->getSessionVar('crm_acct_id');
		if (!$accountId) {
			//echo "searching for id";
			$account = Crm_Acct::findSupportAccount($u);
			if ($account) {
				$accountId = $account->getPrimaryKey();
			}

		} else {
			$account = new Cgn_DataItem('crm_acct');
			$account->load($accountId);
		}
		if (!$account) {
			$this->templateName = 'main_noaccess';
			return;
		}

		$link = $req->cleanString(1);
		$fileId = $req->cleanInt('id');
		$crmFile = new Cgn_DataItem('crm_file');
		$crmFile->andWhere('link_text', $link);
		$crmFile->andWhere('crm_file_id', $fileId);
		$crmFile->andWhere('crm_acct_id', $accountId);
		$crmFile->_cols = array('title', 'mime', 'crm_file_id');
		$crmFile->load();
		if ($crmFile->_isNew) {
			//no article found
			//Cgn_ErrorStack::throwWarning('Cannot find that crmFile.', 121);
			$this->templateName = 'main_noaccess';
			return false;
		}

		//ob_start('gz_handler') breaks firefox when downloading gzips
		// so we will clear out the buffer no matter what type of file is being 
		// downloaded.
		ob_end_clean();
		ob_end_clean();

		/**
		 * These two headers are only needed by IE (6?)
		 */
		header('Cache-Control: public, must-revalidate');
		header('Pragma: Public');

		$offset = 60 * 60 * 24 * 1;
		$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
		header($ExpStr); 

		header('Content-Type: '. $crmFile->mime);
		header('Content-Disposition: attachment;filename='.$crmFile->title.';');
		$db = Cgn_Db_Connector::getHandle();
		$streamTicket = $db->prepareBlobStream('crm_file', 'file_binary', $crmFile->crm_file_id, 5, 'crm_file_id');

		header('Content-Length: '. sprintf('%d', ($streamTicket['bytelen'])));
		while (! $streamTicket['finished'] ) {
			echo $db->nextStreamChunk($streamTicket);
			ob_flush();
		}
		exit();
	}
}
?>
