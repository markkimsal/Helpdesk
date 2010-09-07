<?php

/**
 * Utility function to wrap account functions
 */
class Crm_Acct extends Cgn_Data_Model {

	public $tableName =  'crm_acct';
	public $sharingModeRead = '';


	public function initDataItem() {
		parent::initDataItem();
		$this->dataItem->org_name = '';
		$this->dataItem->is_active = 0;
		$this->dataItem->org_account_id = NULL;
		$this->dataItem->agreement_date = '';
		$this->dataItem->agreement_ip_addr = '';

		$this->dataItem->_nuls[] = 'org_account_id';
	}

	/**
	 * Try organizational accounts first, then individual accounts
	 */
	static function findSupportAccount($u) {
		//find organization support account
		$account = Crm_Acct::findOrganizationalSupport($u);

		if (!$account) {
			//find direct support account
			$account = Crm_Acct::findIndividualSupport($u);
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

	static function findIndividualSupport($user) {
	}


	static function findOrganizationalSupport($user) {
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
		$finder->andWhere('crm_acct.is_active', 1);
		$finder->andWhere('Tacct.cgn_user_id', 0);
		$finder->andWhere('Tacct.cgn_account_id', $oids, 'IN');
		$finder->_rsltByPkey = FALSE;
		//TODO: add dat checking with valid_thru
		$supportAccounts = $finder->find();
		return $supportAccounts[0];
	}


	/**
	 * Create a new organization account, 
	 * make this UID the owner (cgn_user_org_link),
	 * return the new org object
	 *
	 * @return Cgn_DataItem  the created org cgn_account.
	 */
	public function _makeOrg($uid) {
		$org = new Cgn_DataItem('cgn_account');
		$org->set('cgn_user_id', 0); //this is an org
		$org->set('org_name', $this->get('org_name'));
		$org->save();
		$link = new Cgn_DataItem('cgn_user_org_link');
		$link->_nuls[] = 'inviter_id';
		$link->set('cgn_org_id', $org->getPrimaryKey());
		$link->set('cgn_user_id', $uid);
		$link->set('joined_on', time());
		$link->set('is_active', 1);
		$link->set('role_code', 'leader');
		$link->set('inviter_id', NULL);
		$link->save();
		return $org;
	}

	/**
	 * Set approved_on = now and is_actve = 1
	 *
	 * Don't do anything with the $uid right now, later track
	 * who approved what account
	 */
	public function turnOnAccount($uid) {
		$this->set('approved_on', time());
		$this->set('is_active', 1);
	}

	/**
	 * Return true if the agreement_date and ip_addr are set
	 */
	public function agreeTos() {
		return ($this->get('agreement_date') >0 && $this->get('agreement_ip_addr') != '');
	}
}


class Crm_Acct_List extends Cgn_Data_Model_List {

	var $dataItemList     = array();
	var $tableName        = 'crm_acct';
	var $modelName        = 'Crm_Acct';
	var $searchIndexName  = 'global';
	var $useSearch        = FALSE;

	var $ownerIdField = '';
	var $groupIdField = '';

	var $sharingModeRead   = '';
	var $sharingModeCreate = '';
	var $sharingModeUpdate = '';
	var $sharingModeDelete = '';


}
