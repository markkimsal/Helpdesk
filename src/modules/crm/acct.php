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

	public $requireLogin = FALSE;
	public $usesConfig   = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('Support Account');
		Cgn_Template::addSiteCss('crm_screen.css');
	}

	/**
	 * Show some account info and a form to invite new users
	 */
	public function mainEvent($req, &$t) {
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


		$t['inviteForm'] = $this->_loadInviteForm($u);

		$finder = new Cgn_DataItem('crm_invite');
		$finder->_cols = array('crm_invite_id', 'email');
		$finder->andWhere('crm_acct_id', $accountId);
		$finder->_rsltByPkey = false;
		$invites = $finder->findAsArray();

		$t['inviteTable'] = $this->_loadInviteTable($invites);
	}

	/**
	 * Accept invitations
	 */
	public function acceptinviteEvent($req, &$t) {
		$u = $req->getUser();
		$tkcode = $req->cleanString('tk');
		$finder = new Cgn_DataItem('crm_invite');
		$finder->andWhere('ticket_code', $tkcode);
//		$finder->andWhere('accepted_on', null, 'IS');
		$ticketList = $finder->find();
		$ticket = null;
		foreach ($ticketList as $_tk) {
			$ticket = $_tk;
		}
		if ($ticket == null) {
		var_dump($ticket);exit();
			$u->addSessionMessage('Your invitation may have expired.', 'msg_err');
			$this->presenter = 'redirect';
			$t['url'] = cgn_appurl('crm');
			return;
		}


		$t['passwordForm'] = $this->_loadPasswordForm($tkcode);

		/*
		$u->addSessionMessage('Your invitation has been accepted.');
		$this->presenter = 'redirect';
		$t['url'] = cgn_appurl('crm');
		 */

	}


	public function activateEvent($req, &$t) {
		$u = $req->getUser();
		$this->acceptinviteEvent($req, $t);
		if ($this->presenter == 'redirect') {
			return;
		}
		unset($t['passwordForm']);
		//ticket has been validated from acceptinviteEvent
		$tkcode = $req->cleanString('tk');
		$invite  = new Cgn_DataItem('crm_invite');
		$invite->load( array('ticket_code= "'.$tkcode.'"') );
		if ($invite->_isNew) {
			$u->addSessionMessage('Your invitation may have expired.', 'msg_err');
			$this->presenter = 'redirect';
			$t['url'] = cgn_appurl('crm');
			return;
		}
		$crmAcctId = $invite->get('crm_acct_id');
		$crmAcctObj = new Crm_Acct($crmAcctId);
		$orgAcctId = $crmAcctObj->get('org_account_id');

		$newUser = new Cgn_User();
		$newUser->username =  $invite->get('email');
		$newUser->mail     =  $invite->get('email');
		$newUser->setPassword( $req->cleanString('pwd1') );
		$result = Cgn_User::registerUser($newUser);
		$newUser->bindSession();
		$newUserId = $newUser->userId;
		$orgMembership = new Cgn_DataItem('cgn_user_org_link');
		$orgMembership->set('cgn_org_id', $orgAcctId);
		$orgMembership->set('cgn_user_id', $newUserId);
		$orgMembership->set('joined_on', time());
		$orgMembership->set('is_active', 1);
		$orgMembership->set('role_code', 'member');
		$orgMembership->set('inviter_id', $invite->get('inviter_id'));
		$orgMembership->save();

		$invite->set('accepted_on', time());
		$invite->set('accepted_ip', @$_SERVER['REMOTE_ADDR']);
		$invite->save();

		$u->addSessionMessage('Your invitation has been accepted.');
		$this->presenter = 'redirect';
		$t['url'] = cgn_sappurl('crm');
	}


	/**
	 */
	public function applyEvent($req, &$t) {
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


	/**
	 * Load and display a form to allow invitations by email
	 */
	public function _loadInviteForm($u) {
		$f = new Cgn_Form('form_invite_member');
		$f->layout = new Cgn_Form_Layout_Dl();
		$f->width = '400px';
		$f->action = cgn_sappurl('crm', 'invite', 'invite');
		//$f->label = 'Invite organization members';
		$f->appendElement(new Cgn_Form_ElementInput('member_email', 'E-mail Address'), $values['member_email']);
		return $f;

	}

	/**
	 * Load and display a form to take a password for registration
	 */
	public function _loadPasswordForm($tk) {
		$f = new Cgn_Form('form_collect_pwd');
		$f->width = '660px';
		$f->action = cgn_appurl('crm', 'acct', 'activate');
		$f->label = 'Pick a password for your brand new account.';
		$f->appendElement(new Cgn_Form_ElementPassword('pwd1', 'Password'));
		$f->appendElement(new Cgn_Form_ElementPassword('pwd2', 'Repeat Password'));
		$f->appendElement(new Cgn_Form_ElementHidden('tk'), $tk);
		return $f;

	}


	/**
	 * Display a table full of all pending invites.
	 */
	public function _loadInviteTable($invites) {
		Cgn::loadLibrary('lib_cgn_mvc');
		Cgn::loadLibrary('lib_cgn_mvc_table');
		$dm = new Cgn_Mvc_TableModel($invites);
//		$dm->data = $invites;
		$dm->columns = array('email', 'crm_invite_id', 'crm_invite_id');

		$f = new Cgn_Mvc_TableView($dm);

		$url =  new Cgn_Mvc_Table_ColRenderer_Url(cgn_appurl('crm', 'invite', 'resend'), array('id'=>2));
		$url->setLinkText('resend');
		$f->setColRenderer(1, $url);

		$url2 =  new Cgn_Mvc_Table_ColRenderer_Url(cgn_appurl('crm', 'invite', 'delete'), array('id'=>2));
		$url2->setLinkText('remove');
		$f->setColRenderer(2, $url2);

		return $f;
	}
}

