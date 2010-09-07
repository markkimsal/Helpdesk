<?php

Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
Cgn::loadLibrary("lib_cgn_mvc");
Cgn::loadLibrary("lib_cgn_mvc_table");



/**
 * CRM Issue service
 *
 * Show browsable issues, and a form for asking a new question
 */
class Cgn_Service_Crm_Issue extends Cgn_Service {

	public $requireLogin = TRUE;

	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('Support Issues');
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
		$c = $req->cleanInt('c');
		if ($c < 1) {
			$c = 1;
		}
		$t['c'] = $c;


		$t['baseUrl']  = cgn_appurl('crm', 'issue', '', '', 'https');
		$t['prevUrl']  = cgn_appurl('crm', 'issue', '', array('p'=>$p-1), 'https');
		$t['nextUrl']  = cgn_appurl('crm', 'issue', '', array('p'=>$p+1), 'https');
		$t['curPage']  = $p;

		//load up question
		$t['issueList'] = $this->_getIssues($accountId, $t, $p);
		$this->_textToHtml($t['issueList']);

		//pagination
		$t['rowCount'] = count($t['issueList']);
	}

	function quickRepliesEvent($req, &$t) {
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

		//pagination
		$p = $req->cleanInt('p');
		if ($p < 1) {
			$p = 1;
		}
		$c = $req->cleanInt('c');
		if ($c < 1) {
			$c = 1;
		}


		$id = $req->cleanInt('id');
		$t['issueList'] = $this->_getReplies($accountId, $id, $c);

		//take each Cgn_DataItem and translate the text into 
		// <p> wrapped tags, like php's nl2br but with <p> tags
		$this->_textToHtml($t['issueList']);
		$this->presenter = 'self';

		$t['baseUrl']  = cgn_appurl('crm', 'issue', '', array('id'=>$id,'p'=>$p,'c'=>$c, 'xhr'=>1), 'https').'#'.$id;
		$t['prevUrl']  = cgn_appurl('crm', 'issue', '', array('id'=>$id,'p'=>$p,'c'=>$c-1, 'xhr'=>1), 'https').'#'.$id;
		$t['nextUrl']  = cgn_appurl('crm', 'issue', '', array('id'=>$id,'p'=>$p,'c'=>$c+1, 'xhr'=>1), 'https').'#'.$id;
		$t['curPage']  = $p;

		$t['c'] = $c;

//		$this->templateStyle = 'bare';
//		$currentTemplate = Cgn_ObjectStore::getConfig("config://template/default/name");
//		Cgn_Template::addSiteCss('../'.$currentTemplate.'/crm_screen.css');
//		$templateName = 'bare';
//		Cgn_ObjectStore::storeConfig("config://template/default/name", $templateName);
	}

	/**
	 * Load up recent issues based on an account id.
	 */
	function _getReplies($accountId, $id, $page=1) {
		Cgn::loadModLibrary('Crm::Crm_Issue');
		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->andWhere('crm_acct_id', $accountId);
		$finder->dataItem->andWhere('thread_id', $id);
		$finder->dataItem->sort('post_datetime', 'DESC');
		$finder->dataItem->limit(1, $page-1);
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
	}

	/**
	 * Load up recent issues based on an account id.
	 */
	function _getIssues($accountId, &$t, $page=1) {
		Cgn::loadModLibrary('Crm::Crm_Issue');
		$finder = new Crm_Issue_Model_List();
		$finder->dataItem->andWhere('crm_acct_id', $accountId);
		$finder->dataItem->andWhere('thread_id', 0);
		$finder->dataItem->orWhereSub('thread_id', NULL, 'IS');
		$finder->dataItem->sort('post_datetime', 'DESC');
		$finder->dataItem->limit(5, $page-1);

		$t['unlimitedRowCount'] = $finder->dataItem->getUnlimitedCount();
		$finder->_rsltByPkey = FALSE;
		return $finder->loadVisibleList();
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
	 * Save a new or old question
	 */
	function saveEvent($req, &$t) {
		Cgn::loadModLibrary('CRM::Crm_Issue');
		$id = $req->cleanInt('id');
		$u = $req->getUser();
		$issue = Crm_Issue_Model::createNewIssue();


		if ($id) {
			//TODO: check owner
			//$issue->andWhere('cgn_user_id', $u->userId);
			$issue->load($id);
		}
		$accountId = $req->getSessionVar('crm_acct_id');

		$comment = $req->cleanMultiLine('ctx');
		$name = $u->getDisplayName();
		$issue->set('message', $comment);
		$issue->set('post_datetime', time());
		$issue->set('crm_acct_id', $accountId);
		$issue->set('user_name', $name);
		$issue->set('user_id', $u->userId);
		$issue->save();
		$id = $issue->getPrimaryKey();

		//alert technicians
		$this->_sendCrmtechNotice($id);

		//redir home
		$t['url'] = cgn_appurl('crm', '', '', '', 'https');
		$this->presenter = 'redirect';

		//unless we need to go back to the questions page
		if ($req->cleanString('redir') == 'issue') {
			$t['url'] = cgn_appurl('crm', 'issue', '', '', 'https');
		}
	}


	/**
	 * Save a new or old reply to a question
	 */
	function saveReplyEvent($req, &$t) {
		Cgn::loadModLibrary('CRM::Crm_Issue');
		$id     = $req->cleanInt('id');
		$thread = $req->cleanInt('thread_id');
		$force  = $req->cleanInt('force');
		$open   = $req->cleanInt('open');
		$issue  = Crm_Issue_Model::createNewIssue();
		$accountId = $req->getSessionVar('crm_acct_id');


		$closed = $this->_checkClosedStatus($thread);
		if (!$force) {
			if ($closed) {
				//stack a new event
				$tick = new Cgn_SystemTicket('crm', 'issue', 'confirmReply');
				Cgn_SystemRunner::stackTicket($tick);
				return TRUE;
			}
		}

		if ($id) {
			//TODO: check owner
			//$issue->andWhere('cgn_user_id', $u->userId);
			$issue->load($id);
		}

		$comment = $req->cleanMultiLine('ctx');
		$u       = $req->getUser();
		$name    = $u->getDisplayName();

		$issue->set('message', $comment);
		$issue->set('post_datetime', time());
		$issue->set('crm_acct_id', $accountId);
		$issue->set('user_name', $name);
		$issue->set('user_id', $u->userId);
		$issue->set('thread_id', $thread);
		$issue->save();


		//alert technicians
		$this->_sendCrmtechNotice($thread);

		//check for re-opening the PARENT issue
		if ($force && $closed && $open) {
			$dataItem = new Cgn_DataItem('crm_issue');
			$dataItem->set('crm_issue_id', $thread);
			$dataItem->set('status_id', Crm_Issue_Model::STATUS_ROP);
			//force update
			$dataItem->_isNew = FALSE;
			$dataItem->save();
		}

		$t['url'] = cgn_appurl('crm');
		$this->presenter = 'redirect';
	}

	/**
	 * Warn the user that replying will reopen the ticket
	 */
	public function confirmReplyEvent($req, &$t) {
		$t['header'] = '<h3>Reopen this issue?</h3>';

		$comment = $req->cleanMultiLine('ctx');
		$id     = $req->cleanInt('id');
		$thread = $req->cleanInt('thread_id');
		$t['confirmForm'] = $this->_loadConfirmForm($comment, $id, $thread);
	}


	public function output($req, &$t) {
		include( dirname(__FILE__).'/templates/'.$this->serviceName.'_'.$this->eventName.'.html.php');
	}

	public function _checkClosedStatus($issueId) {
		$issue  = Crm_Issue_Model::createNewIssue();
		$issue->dataItem->andWhere('status_id', Crm_Issue_Model::STATUS_DON);
		$res = $issue->load($issueId);
		return $res;
	}

	public function _loadConfirmForm($comment, $id, $thread) {
		Cgn::loadLibrary("Html_Widgets::lib_cgn_widget");
		Cgn::loadLibrary("Form::lib_cgn_form");

		$f = new Cgn_Form('content_01');
		$f->label = 'This issue was marked as being closed.  When you reply to this issue you can change the status and reopen the issue.  You can also add a comment without changing the status if you want.';
		$f->width = '660px';
		$f->action = cgn_appurl('crm','issue','saveReply', '', 'https');
		$radio = new Cgn_Form_ElementRadio('open', 'Reopen Issue?');
		$radio->addChoice('No, leave it closed.', 0);
		$radio->addChoice('Yes, this issue needs more attention.', 1);
		$radio->setValue(0);

		$f->appendElement(new Cgn_Form_ElementText('ctx', 'Comments'), $comment);
		$f->appendElement($radio, 0);
		$f->appendElement(new Cgn_Form_ElementHidden('force'),1);
		$f->appendElement(new Cgn_Form_ElementHidden('id'),$id);
		$f->appendElement(new Cgn_Form_ElementHidden('thread_id'),$thread);
		return $f;
	}

	/**
	 * Send a notification email to all "crmtech" users
	 */
	protected function _sendCrmtechNotice($issueId) {
		Cgn::loadLibrary("Mail::lib_cgn_message_mail");

		$finder = new Cgn_DataItem('cgn_user');
		$finder->_cols = array('email');
		$finder->hasOne('cgn_user_group_link', 'cgn_user_id', 'Tgrouplink');
		$finder->_relatedSingle[] = array(
			'ftable'=>'cgn_group', 
			'falias'=>'Tgroup', 
			'lk' => 'cgn_group_id', 
			'ltable' => 'Tgrouplink', 
			'fk' => 'cgn_group_id');
		$finder->andWhere('Tgroup.code', 'crmtech');
		$finder->andWhere('email', '', '!=');

		$list = $finder->findAsArray();
		$toList = array();
		foreach ($list as $row) {
			$toList[] = $row['email'];
		}

		if (Cgn_ObjectStore::hasConfig('config://default/email/replyto')) {
			$defaultReply = Cgn_ObjectStore::getConfig('config://default/email/replyto');
		} else {
			$defaultReply = Cgn_ObjectStore::getConfig('config://default/email/contactus');
		}

		//TODO: add uid user's email to the message.
		$m = new Cgn_Message_Mail();
		$m->subject = '[CRMTECH] New Support Issue';
		$m->toList = $toList;
		$m->from   = $defaultReply;
		$m->reply  = $defaultReply;
		$m->body  .= "Click to read: \n".
		cgn_appurl(
			'crmtech', 'issue', 'view',
			array('id'=>$issueId),
			'https'
		);

		$m->sendMail();
	}

}
?>
