<?php

class Crm_Content_Publisher_CrmFile extends Cgn_Content_Publisher_Plugin {

	public $codeName    = 'crm_file_download';
	public $tableName   = 'crm_file';
	public $displayName = 'CRM File Download';

	public function getFormValue() {
		return $this->codeName;
	}

	public function getDisplayName() {
		return $this->displayName;
	}


	public function publishAsCustom($content) {
		Cgn::loadModLibrary('Crm::Crm_File');
		if ($content->dataItem->cgn_content_id < 1) {
			trigger_error("Can't publish an unsaved content item");
			return FALSE;
		}
		if ($content->dataItem->_isNew == TRUE) {
			trigger_error("Can't publish an unsaved content item");
			return FALSE;
		}
		//only up the published date once
		$content->setPublishedOn();

		$content->dataItem->save();


		//__ FIXME __ use the data item for this search functionality
		$db = Cgn_Db_Connector::getHandle();
		$db->query("SELECT * FROM crm_file WHERE
			cgn_content_id = ".$content->dataItem->cgn_content_id);
		if ($db->nextRecord()) {
			$asset = new Crm_File_Model();
			$asset->dataItem->row2Obj($db->record);
			$asset->dataItem->_isNew = FALSE;
		} else {
			$asset = new Crm_File_Model();
		}

		$session = Cgn_Session::getSessionObj();

		$asset->dataItem->cgn_content_id = $content->dataItem->cgn_content_id;
		$asset->dataItem->cgn_guid = $content->dataItem->cgn_guid;
		$asset->dataItem->title = $content->dataItem->title;
		$asset->dataItem->mime = $content->dataItem->mime;
		$asset->dataItem->caption = $content->dataItem->caption;
		$asset->dataItem->file_binary = $content->dataItem->binary;
		$asset->dataItem->description = $content->dataItem->caption;
		$asset->dataItem->link_text = $content->dataItem->link_text;
		$asset->dataItem->cgn_content_version = $content->dataItem->version;
		$asset->dataItem->edited_on = $content->dataItem->edited_on;
		$asset->dataItem->created_on = $content->dataItem->created_on;
		$asset->dataItem->set('crm_acct_id', $crmAcctId);
		$asset->setPublishedOn( $content->dataItem->published_on );

		$asset->save();
		return $asset;
	}

	/**
	 * Called from Cgn_Content_Publisher
	 */
	public function loadPublished($id) {
		Cgn::loadModLibrary('Crm::Crm_File');

		$asset = new Crm_File_Model();
		$asset->dataItem->andWhere('cgn_content_id', $id);
		$asset->dataItem->load();
		return $asset;
	}

	public function getReturnUrl($publishedContent) {
		return cgn_adminurl('content', 'main');
	}
}
