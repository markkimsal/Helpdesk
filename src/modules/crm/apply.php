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
class Cgn_Service_Crm_Apply extends Cgn_Service {

	public $requireLogin = FALSE;
	public $usesConfig   = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('Support Account');
		Cgn_Template::addSiteCss('crm_screen.css');
	}

	function mainEvent($req, &$t) {
		$u = $req->getUser();
		$token = null;
		if ($u->isAnonymous()) {
			$token = $req->getSessionVar('crm_token');
		}
		$account = $this->_findPendingSupport($u, $token);
		if (is_object($account)) {
			//there was a bug where you could sign up but 
			//not agree to the TOS, so this check will
			//validate agreement_date and agreement_ip_addr
			if ($account->agreeTos()) {
				$this->templateName = 'apply_pending';
				$t['email'] = $u->email;
			} else {
				//show the TOS again
				$this->presenter = 'redirect';
				$t['url'] = cgn_appurl('crm', 'apply', 'tos');
				return true;
			}
		} else {
			$this->templateName = 'apply_create';
			$t['form'] = $this->_loadApplyForm($values);
		}
	}

	function applyEvent($req, &$t) {
		$u = $req->getUser();

		$newAcct = new Crm_Acct();

		//find the existing account by owner_id or crm_token
		if ($u->isAnonymous()) {
			if($req->getSessionVar('crm_token')) {
				$id = $this->_unMarshallToken($req->getSessionVar('crm_token'));
				//make sure the account is unclaimed by adding owner_id=0
				$newAcct->dataItem->andWhere('owner_id', 0);
				$newAcct->dataItem->load($id);
			}
		} else {
			$newAcct->dataItem->andWhere('owner_id', $u->getUserId());
			$newAcct->dataItem->load();
		}

		if ($newAcct->dataItem->_isNew) {
			$newAcct->dataItem->created_on = time();
			$newAcct->dataItem->owner_id = $u->getUserId();
		} else {
			//application saved
			if ($newAcct->dataItem->agreement_date == '0') {
				$this->presenter = 'redirect';
				$t['url'] = cgn_appurl('crm', 'apply', 'tos');
				return TRUE;
			}

			if ($u->isAnonymous()) {
				$this->presenter = 'redirect';
				$t['url'] = cgn_appurl('crm', 'apply', 'noreg');
			} else {
				$t['message'] = 'Your application is being processed.';
				return FALSE;
			}
		}

		$newAcct->dataItem->org_name = $req->cleanString('company_name');
		$newAcct->dataItem->save();


		//if anonymous application, set the crm_token as their auto-increment id
		if ($u->isAnonymous()) {
			$req->setSessionvar('crm_token', $this->_makeIncrToken($newAcct->dataItem->getPrimaryKey()));
		}

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
		$t['url'] = cgn_appurl('crm', 'apply', 'tos');
	}


	/**
	 * Display a TOS
	 */
	function tosEvent($req, &$t) {
		//$u = $req->getUser();
		$tosFile = $this->getConfig('tos_file');
		$t['tos'] = @file_get_contents( dirname(__FILE__).'/'.$tosFile);
	}

	/**
	 * Save click-thru aggreement to TOS
	 */
	function savetosEvent($req, &$t) {
		$agree = $req->cleanString('agree');
		if ($agree !== 'on') {
			$this->templateName = 'apply_tos';
			return FALSE;
		}

		//save IP and agreement date
		$dataItem = new Cgn_DataItem('crm_acct');
		$u = $req->getUser();

		if ($u->isAnonymous()) {
			if($req->getSessionVar('crm_token')) {
				$id = $this->_unMarshallToken($req->getSessionVar('crm_token'));
				//make sure the account is unclaimed by adding owner_id=0
				$dataItem->andWhere('owner_id', 0);
				$dataItem->load($id);
			} else {
				$u->addMessage("We can't find your support account anymore, please login and try again.", 'msg_warn');
				return false;
			}
		} else {
			$dataItem->andWhere('owner_id', $u->getUserId());
			$dataItem->load();
		}
		$dataItem->agreement_date = time();
		$dataItem->agreement_ip_addr = $_SERVER['REMOTE_ADDR'];
		$dataItem->save();

		//if the configuration calls for auto-reg, do it now
		$autoApprove = $this->getConfig('auto_approve');
		if ( !$u->isAnonymous() && ($autoApprove === '1' || $autoApprove === 1)) {
			$newAcct = new Crm_Acct();
			$newAcct->dataItem = $dataItem;
			$this->_autoApproveAccount($u, $newAcct);
		}

		//if user is anonymous, show them the register form
		if ($u->isAnonymous()) {
			$this->presenter = 'redirect';
			$t['url'] = cgn_sappurl('crm', 'apply', 'noreg');
		} else {
			$this->presenter = 'redirect';
			$u->addMessage("We can't find your support account anymore, please login and try again.", 'msg_warn');
			$t['url'] = cgn_sappurl('crm');
		}
	}

	/**
	 * This event gets called if all the support account is created, but 
	 * the user is anonymous
	 */
	function noregEvent($req, &$t) {
		$values = array();
		$values['email'] = $req->cleanString('email');
		$t['regtest'] = '<p>In order to access your support account, you need a valid 
			user account.</p><p>If you already have an account, login with your regular 
			credentials using the Log-in form.</p>';
		$t['form'] = $this->_loadRegForm($values);

		$t['loginform'] = $this->_loadLoginForm($values);
	}

	/**
	 * PASSTHRU
	 */
	public function regsaveEvent($req, &$t) {

		$u = $req->getUser();
		if ($u->isAnonymous()) {
			$newTicket = new Cgn_SystemTicket('login', 'register', 'save');
			Cgn_SystemRunner::stackTicket($newTicket);
			/*
			Cgn_Template::assignArray('redir', base64_encode(
				cgn_appurl($tk->module, $tk->service, $tk->event, $req->getvars)
			));
			 */
		}
		$newTicket = new Cgn_SystemTicket('crm', 'apply', 'regpostsave');
		Cgn_SystemRunner::stackTicket($newTicket);
		/*
		Cgn_Template::assignArray('redir', base64_encode(
			cgn_appurl($tk->module, $tk->service, $tk->event, $req->getvars)
		));
		 */
	}

	/**
	 * PASSTHRU
	 */
	public function loginEvent($req, &$t) {

		$u = $req->getUser();
		if ($u->isAnonymous()) {
			$newTicket = new Cgn_SystemTicket('login', 'main', 'login');
			Cgn_SystemRunner::stackTicket($newTicket);
			Cgn_Template::assignArray('redir', base64_encode(
				cgn_appurl($tk->module, $tk->service, $tk->event, $req->getvars)
			));

		}

		$newTicket = new Cgn_SystemTicket('crm', 'apply', 'regpostsave');
		Cgn_SystemRunner::stackTicket($newTicket);
		Cgn_Template::assignArray('redir', base64_encode(
			cgn_appurl($tk->module, $tk->service, $tk->event, $req->getvars)
		));
	}


	/**
	 * If the registration was successfull, erase session crm_token and 
	 * update the owner_id of the account in question
	 */
	public function regpostsaveEvent($req, &$t) {
		$u = $req->getUser();
		if ($u->isAnonymous()) {
			//retreive fatal errors which stop template
			$e = Cgn_ErrorStack::pullError('error');
			if ($e) {
				$u->addMessage('Unable to save your account: '.$e->message, 'msg_warn');
			} else {
				$u->addMessage('Unable to save your account', 'msg_warn');
			}
			$newTicket = new Cgn_SystemTicket('crm', 'apply', 'noreg');
			Cgn_SystemRunner::stackTicket($newTicket);
			return TRUE;
		}
		//got a good registration, user is logged in
		$newAcct = new Crm_Acct();

		//if the crm_token is involved, clear it
		if($req->getSessionVar('crm_token')) {
			$id = $this->_unMarshallToken($req->getSessionVar('crm_token'));
			//make sure the account is unclaimed by adding owner_id=0
			$newAcct->dataItem->andWhere('owner_id', 0);
			$newAcct->dataItem->load($id);
			$newAcct->dataItem->set('owner_id', $u->userId);
			$newAcct->dataItem->save();

			$req->clearSessionVar('crm_token');
		}

		//handle auto-approve if enabled
		$autoApprove = $this->getConfig('auto_approve');
		if ( ($autoApprove === '1' || $autoApprove === 1)) {
			$this->_autoApproveAccount($u, $newAcct);
		}

		$this->presenter = 'redirect';
		$t['url'] = cgn_sappurl('crm');
		return TRUE;
	}

	function _loadApplyForm($values = array()) {
		$f = new Cgn_Form('content_01');
		$f->width = '660px';
		$f->action = cgn_appurl('crm','apply','apply');
		$f->label = 'Support account information';
		$f->appendElement(new Cgn_Form_ElementInput('company_name', 'Company Name'), $values['org_name']);
		$f->appendElement(new Cgn_Form_ElementInput('emp_num','Number of Employees'), $values['emp_num']);
		$f->appendElement(new Cgn_Form_ElementText('comments', 'Comments', 15, 40));
		return $f;
	}

	function _findPendingSupport($user, $token=NULL) {
		$finder = new Crm_Acct_List();
		if ($user->isAnonymous()) {
			if($token != '') {
				$id = $this->_unMarshallToken($token);
				//make sure the account is unclaimed by adding owner_id=0
				$finder->dataItem->andWhere('owner_id', 0);
				if($finder->dataItem->load($id)) {
					return $finder;
				}
			}
		} else {
			//find accounts with the owner id of $user, but approved = 0
			$finder->dataItem->andWhere('crm_acct_id', NULL, 'IS NOT');
			$finder->dataItem->andWhere('crm_acct.is_active', 0);
			$finder->dataItem->andWhere('crm_acct.owner_id', $user->userId);
			$finder->dataItem->_rsltByPkey = FALSE;
			//TODO: add dat checking with valid_thru
			$supportAccounts = $finder->loadVisibleList();
			if ( isset($supportAccounts[0]) && is_object($supportAccounts[0])) {
				return $supportAccounts[0];
			}

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

		$finder = new Crm_Acct_List();
		$finder->dataItem->andWhere('crm_acct_id', NULL, 'IS NOT');
		$finder->dataItem->hasOne('cgn_account', 'cgn_account_id', 'Tacct', 'org_account_id');
		//organizational accounts have user_id=0
		$finder->dataItem->andWhere('crm_acct.is_active', 0);
		$finder->dataItem->andWhere('Tacct.cgn_user_id', 0);
		$finder->dataItem->andWhere('Tacct.cgn_account_id', $oids, 'IN');
		$finder->dataItem->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$supportAccounts = $finder->loadVisibleList();
		return $supportAccounts[0];
	}


	function _loadRegForm($values) {
		include_once(CGN_LIB_PATH.'/form/lib_cgn_form.php');
		include_once(CGN_LIB_PATH.'/html_widgets/lib_cgn_widget.php');
		$f = new Cgn_Form('reg');
		$f->showCancel = FALSE;
		$f->labelSubmit = 'Register';
		$f->action = cgn_appurl('crm','apply','regsave', array(), 'https');
		$f->label = 'Site Registration';
		$f->appendElement(new Cgn_Form_ElementInput('email'),$values['email']);
		$f->appendElement(new Cgn_Form_ElementPassword('password'));
		$f->appendElement(new Cgn_Form_ElementPassword('password2','Confirm Password'));
		$f->appendElement(new Cgn_Form_ElementHidden('event'),'save');
		return $f;
	}

	function _loadLoginForm($values) {
		include_once(CGN_LIB_PATH.'/form/lib_cgn_form.php');
		include_once(CGN_LIB_PATH.'/html_widgets/lib_cgn_widget.php');
		$f = new Cgn_Form('login');
		$f->showCancel = FALSE;
		$f->labelSubmit = 'Sign-in';
		$f->action = cgn_appurl('crm','apply','login', array(), 'https');
		$f->label = 'Sign-in';
		$f->appendElement(new Cgn_Form_ElementInput('email'),$values['email']);
		$f->appendElement(new Cgn_Form_ElementPassword('password'));
		$f->appendElement(new Cgn_Form_ElementHidden('event'),'login');
		return $f;
	}


	/**
	 * Return a token representing an incremental number, or any number
	 *
	 * @param int $num  any positive integer
	 */
	private function _makeIncrToken($num) {
    	$crc =  substr(sprintf('%u',crc32($num)), 0, 3);
    	$tok =  base_convert( $num.'a'.$crc, 11,26);
		return $tok;
	}

	/**
	 * Return a positive integer from a token.
	 *
	 * @return int  positive integer from token
	 */
	private function _unMarshallToken($tok) {
		$newtok = base_convert($tok,26,11);
		list($num, $junk) =  explode('a',$newtok);
		return $num;
	}

	protected function _autoApproveAccount($user, $newAcct) {
		$newAcct->turnOnAccount($user->userId);

		//create a new organization and add this user as the leader
		$orgAcct = $newAcct->_makeOrg($newAcct->get('owner_id'));
		$orgAcctId = $orgAcct->getPrimaryKey();
		$newAcct->set('org_account_id', $orgAcctId);
		$newAcct->save();

		$this->_sendApprovalEmail($newAcct->get('owner_id'));
		$user->addSessionMessage("Your support account is now active!");
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

		$m->sendMail();
	}
}
