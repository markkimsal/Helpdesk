<?php

Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
Cgn::loadLibrary("Form::lib_cgn_form");
Cgn::loadLibrary("lib_cgn_mvc");
Cgn::loadLibrary("lib_cgn_mvc_table");
Cgn::loadLibrary("Mail::lib_cgn_message_mail");

Cgn::loadModLibrary("Crm::Crm_Util_Ui");
Cgn::loadModLibrary("Crm::Crm_Acct");

/**
 * CRM Account service
 *
 * Provide application functionality
 */
class Cgn_Service_Crm_Acct extends Cgn_Service {

	public $requireLogin = TRUE;
	public $usesConfig   = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('Support Account');
		Cgn_Template::addSiteCss('crm_screen.css');
	}

	function mainEvent($req, &$t) {

		$u = $req->getUser();
		$account = $this->_findPendingSupport($u);
		if (is_object($account)) {
			$this->templateName = 'acct_pending';
			$t['email'] = $u->email;
		} else {
			$this->templateName = 'acct_noaccess';
			$t['form'] = $this->_loadApplyForm($values);
		}
	}

	function applyEvent($req, &$t) {
		$u = $req->getUser();

		$newAcct = new Crm_Acct();
		$newAcct->dataItem->andWhere('owner_id', $u->getUserId());
		$newAcct->dataItem->load();
		if ($newAcct->dataItem->_isNew) {
			$newAcct->dataItem->created_on = time();
			$newAcct->dataItem->owner_id = $u->getUserId();
		} else {
			//application saved
			$t['message'] = 'Your application is being processed.';
			return false;
		}
		$newAcct->dataItem->org_name = $req->cleanString('company_name');
		$newAcct->dataItem->save();

		$defaultEmail = Cgn_ObjectStore::getConfig('config://default/email/contactus');
		if (Cgn_ObjectStore::hasConfig('config://default/email/replyto')) {
			$defaultReply = Cgn_ObjectStore::getConfig('config://default/email/replyto');
		} else {
			$defaultReply = $defaultEmail;
		}

		$m = new Cgn_Message_Mail();
		$m->subject = 'New Support Application';
		$m->toList = array($defaultEmail);
		$m->from   = $defaultReply;
		$m->reply  = $defaultReply;
		$m->body   = "New support application requested.\n\n";
		$m->body  .= "Organization name: ". $newAcct->dataItem->get('org_name'). "\n";
		$m->body  .= "Click to approve: \n".
				cgn_appurl(
				   'crmtech', 'acct', 'approve', 
				   array('id'=>$newAcct->dataItem->get('crm_acct_id')),
				   'https'
			   );

		foreach ($req->postvars as $k => $p) {
			$m->body .= $k .': '.$p."\n";
		}
		$m->sendMail();

		$this->presenter = 'redirect';
		$t['url'] = cgn_appurl('crm', 'acct', 'tos');
	}



	function _findPendingSupport($user) {
		//find accounts with the owner id of $user, but approved = 0
		$finder = new Cgn_DataItem('crm_acct');
		$finder->andWhere('crm_acct_id', NULL, 'IS NOT');
		$finder->andWhere('crm_acct.is_active', 0);
		$finder->andWhere('crm_acct.owner_id', $user->userId);
		$finder->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$supportAccounts = $finder->find();
		if ( isset($supportAccounts[0]) && is_object($supportAccounts[0])) {
			return $supportAccounts[0];
		}

		//find any user_org links
		$finder = new Cgn_DataItem('cgn_user_org_link');
		$finder->andWhere('cgn_user_id', $user->userId);
		$orgs = $finder->find();
		if (count($orgs) < 1) {
			return FALSE;
		}
		$oids = array();
		foreach ($orgs as $_o) {
			$oids[] = $_o->get('cgn_org_id');
		}
		$finder = new Cgn_DataItem('crm_acct');
		$finder->andWhere('crm_acct_id', NULL, 'IS NOT');
		$finder->hasOne('cgn_account', 'cgn_account_id', 'Tacct', 'org_account_id');
		//organizational accounts have user_id=0
		$finder->andWhere('crm_acct.is_active', 0);
		$finder->andWhere('Tacct.cgn_user_id', 0);
		$finder->andWhere('Tacct.cgn_account_id', $oids, 'IN');
		$finder->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$supportAccounts = $finder->find();
		return $supportAccounts[0];
	}
}

?>
