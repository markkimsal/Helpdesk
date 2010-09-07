<?php

class Crm_Signal {

	/**
	 * create or load a Cgn_Asset object out of this content
	 */
	function publishAsCrmFile($signal) {
		Cgn::loadModLibrary('Crm::Crm_File');
		$content = $signal->getSource()->eventContentObj;
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
		$crmAcctId = $session->get('crm_acct_id');

		$asset->dataItem->cgn_content_id = $content->dataItem->cgn_content_id;
		$asset->dataItem->cgn_guid = $content->dataItem->cgn_guid;
		$asset->dataItem->title = $content->dataItem->title;
		$asset->dataItem->mime = $content->dataItem->mime;
		$asset->dataItem->caption = $content->dataItem->caption;
		$asset->dataItem->file_binary = $content->dataItem->binary;
		$asset->dataItem->description = $content->dataItem->description;
		$asset->dataItem->link_text = $content->dataItem->link_text;
		$asset->dataItem->cgn_content_version = $content->dataItem->version;
		$asset->dataItem->edited_on = $content->dataItem->edited_on;
		$asset->dataItem->created_on = $content->dataItem->created_on;
		$asset->dataItem->set('crm_acct_id', $crmAcctId);
		$asset->setPublishedOn( $content->dataItem->published_on );

		$asset->save();
		return cgn_adminurl('content', 'main');
	}

	/**
	 * Called from the signal manager
	 */
	public function loadPublished($signal) {
		Cgn::loadModLibrary('Crm::Crm_File');

		$src = $signal->getSource();
		if (!is_object($src)) {
			return NULL;
		}

		if (isset($src->id)) {
			$id = $src->id;
		}
		
		$entry = new Crm_File_Model();
		$entry->dataItem->andWhere('cgn_content_id', $id);
		$entry->dataItem->load();
		return $entry;
		var_dump($entry);

		$db->query('select * from crm_file 
			WHERE cgn_content_id = '.$id);
		$db->nextRecord();
		$result = $db->record;
		if ($result) {
			$db->freeResult();
			Cgn::loadModLibrary('Crm::Crm_File');
			$published = new Crm_File_Model($result['cgn_file_id']);
		}
	}

}
