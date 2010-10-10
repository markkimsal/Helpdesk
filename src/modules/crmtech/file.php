<?php

Cgn::loadLibrary('Html_Widgets::Lib_Cgn_Widget');
Cgn::loadLibrary('Html_Widgets::Lib_Cgn_Toolbar');
Cgn::loadLibrary('Form::Lib_Cgn_Form');
Cgn::loadLibrary('Lib_Cgn_Mvc');
Cgn::loadLibrary('Lib_Cgn_Mvc_Table');
Cgn::loadModLibrary('Crm::Crm_File');

class Cgn_Service_Crmtech_File extends Cgn_Service_Crud {

	public $representing = "File";
	public $pageTitle    = 'CRM Files';
	public $dataModelName = 'Crm_File_Model';
	//
	public $tableHeaderList = array('ID', 'Account', 'Filename', 'Mime');
	public $tableColList    = array('crm_file_id', 'org_name', 'title', 'mime');
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
	 * Add navigation and css
	 */
	public function eventBefore($req, &$t) {
		Cgn_Template::setPageTitle('CRM Tech Files');
		Cgn_Template::addSiteCss('crm_screen.css');
		Cgn_Template::addSiteCss('crmtech_screen.css');
		$t['pageNav'] = '<div><a href="'.cgn_appurl('crmtech').'">Back to CRM home</a></div>';
	}

	/**
	 * Show a list of accounts
	 */
	public function mainEvent($req, &$t) {
		$ret = parent::mainEvent($req, $t);
		$url = cgn_appurl('crmtech', 'file', 'edit');
		$t['dataGrid']->setColRenderer(2, new Cgn_Mvc_Table_ColRenderer_Url($url, array('id'=>0) ));
		return $ret;
	}

	/**
	 * Don't load the "file_binary" field for editing
	 */
	protected function _loadListData() {
		$finder = new Cgn_DataItem('crm_file');
		$finder->_cols = array(
			'cgn_content_id', 
			'cgn_content_version',
			'cgn_guid',
			'crm_file_id',
			'title',
			'mime',
			'caption',
			'Tacct.org_name');
		$finder->hasOne('crm_acct', 'crm_acct_id', 'Tacct', 'crm_acct_id');
		return $finder->findAsArray();
	}


	/**
	 * Show a form to make a new data item
	 * Don't load the "file_binary" field for editing
	 */
	function editEvent($req, &$t) {
		//make page title 
		$this->_makePageTitle($t);

		//make toolbar
		$this->_makeToolbar($t);
		$c = $this->dataModelName;
		$this->dataModel = new $c();
		$this->dataModel->dataItem->_cols = array(
			'crm_file_id', 
			'cgn_content_id', 
			'cgn_content_version',
			'cgn_guid',
			'crm_acct_id',
			'title',
			'mime',
			'caption');
		$this->dataModel->load($req->cleanInt('id'));

		//make the form
		$f = $this->_makeEditForm($t, $this->dataModel);
		$this->_makeFormFields($f, $this->dataModel, TRUE);
	}

	protected function _makeTableRow($d) {
		return $d;
	}


	/**
	 * Not used
	 */
	function saveEvent($req, &$t) {
		parent::saveEvent($req, $t);
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
	 * Function to create a default form
	 */
	protected function _makeCreateForm(&$t, $dataModel) {
		$f = new Cgn_FormAdmin('up01','','POST','multipart/form-data');
		$f->width="650px";
		//$f = parent::_makeCreateForm($t, $dataModel);
		$f->action = cgn_appurl($this->moduleName, $this->serviceName, 'save', '', 'https');
		$t['form'] = $f;
		return $f;
	}

	function getHomeUrl($params = array()) {
		list($module,$service,$event) = explode('.', Cgn_ObjectStore::getObject('request://mse'));
		return cgn_appurl($module,$service, '', $params, 'https');
	}


	protected function _makeFormFields($f, $dataModel, $editMode=FALSE) {
		if ($editMode == FALSE) {
			$this->_makeCreateFields($f, $dataModel);
			return;
		}
		$values = $dataModel->valuesAsArray();
//		$acctValues = $this->accountObj->valuesAsArray();

		//load a list of account IDs and names, use them for crm_acct_id field
		$accountList = $this->_loadAccounts();

		foreach ($values as $k=>$v) {
			if ($k == 'crm_acct_id') {
				$widget = new Cgn_Form_ElementSelect('crm_acct_id', 'Organization');
				$widget->size = 1;
				$widget->addChoice('No Org Set', '_0');
				foreach ($accountList as $_acct) {
					$widget->addChoice($_acct['org_name'], $_acct['crm_acct_id']);
				}
				if (isset($values['crm_acct_id'])) {
					$widget->setValue($values['crm_acct_id']);
				}
				$f->appendElement($widget, $v);
				unset($widget);
				continue;
			}
			/*
			 * not used
			if ($v == '' && isset($acctValues[$k])) {
				$v = $acctValues[$k];
			}
			 */

			//don't add the primary key if we're in edit mode
			if ($editMode == TRUE) {
				if ($k == 'id' || $k == $dataModel->get('_table').'_id') continue;
			}
			$widget = new Cgn_Form_ElementInput($k);
			$widget->size = 55;
			$f->appendElement($widget, $v);
			unset($widget);
		}

		//not used
//		$f->appendElement(new Cgn_Form_ElementHidden('org_account_id'), $acctValues['cgn_account_id']);
		if ($editMode == TRUE) {
			$f->appendElement(new Cgn_Form_ElementHidden('id'), $dataModel->getPrimaryKey());
		}
	}

	protected function _makeCreateFields($f, $dataModel) {
		$widget = new Cgn_Form_ElementInput('title');
		$widget->size = 55;
		$f->appendElement($widget, 'New File');
		unset($widget);

		/*
		$widget = new Cgn_Form_ElementInput('title');
		$widget->size = 55;
		$f->appendElement($widget, 'New File');
		unset($widget);
		 */

//		$f->action = cgn_adminurl('content','upload','saveUpload');
		$f->label = 'Choose a file from your computer to upload.';
		$f->appendElement(new Cgn_Form_ElementHidden('MAX_FILE_SIZE'),2000000);
		$f->appendElement(new Cgn_Form_ElementFile('filename','Upload',55));
		$titleInput = new Cgn_Form_ElementInput('title','Save As',55);

	}

	protected function _loadAccounts() {
		$finder = new Cgn_DataItem('crm_acct');
		$finder->_cols = array(
			'org_account_id', 
			'crm_acct_id',
			'org_name',
		);
		return $finder->findAsArray();

	}
}

