<?php

class Crm_Util_Ui_Form_Layout_Frontend extends Cgn_Form_Layout {

	function renderForm($form) {
		$html = '<div style="padding:1px;background-color:#FFF;border:1px solid silver;width:'.$form->width.';">';
		$html .= '<div class="cgn_form" style="padding:5px;background-color:#EEE;">';
		if ($form->label != '' ) {
			$html .= '<h3 style="padding:0px 0px 13pt;">'.$form->label.'</h3>';
			$html .= "\n";
		}
		if ($form->formHeader != '' ) {
			$html .= '<P style="padding:0px 0px 3pt; text-align:justify;">'.$form->formHeader.'</P>';
			$html .= "\n";
		}
//		$attribs = array('method'=>$form->method, 'name'=>$form->name, 'id'=>$form->id);
		$action = '';
		if ($form->action) {
			$action = ' action="'.$form->action.'" ';
		}
		$html .= '<form method="'.$form->method.'" name="'.$form->name.'" id="'.$form->name.'"'.$action;
		if ($form->enctype) {
			$html .= ' enctype="'.$form->enctype.'"';
		}
		$html .= '>';
		$html .= "\n";
		$html .= '<table border="0" cellspacing="3" cellpadding="3">';
		foreach ($form->elements as $e) {
			$html .= '<tr><td valign="top" align="right" nowrap>';
			$html .= $e->label.'</td><td valign="top">';
			if ($e->type == 'textarea') {
				$html .= '<textarea class="forminput" name="'.$e->name.'" id="'.$e->name.'" rows="'.$e->rows.'" cols="'.$e->cols.'" >'.htmlentities($e->value,ENT_QUOTES).'</textarea>';
			} else if ($e->type == 'radio') {
				foreach ($e->choices as $cid => $c) {
					$selected = '';
					if ($c['selected'] == 1) { $selected = ' CHECKED="CHECKED" '; }
				$html .= '<input type="radio" name="'.$e->name.'" id="'.$e->name.sprintf('%02d',$cid+1).'" value="'.sprintf('%02d',$cid+1).'"'.$selected.'/><label for="'.$e->name.sprintf('%02d',$cid+1).'">'.$c['title'].'</label><br/> ';
				}
			} else if ($e->type == 'select') {
				$html .= $e->toHtml();
			} else if ($e->type == 'label') {
				$html .= $e->toHtml();
			} else if ($e->type == 'contentLine') {
				$html .= "<span style=\"text-align: justify;\">";
				$html .= $e->toHtml();
				$html .= "</span>";
			} else if ($e->type == 'check') {
				foreach ($e->choices as $cid => $c) {
					$selected = '';
					if ($c['selected'] == 1) { $selected = ' CHECKED="CHECKED" '; }
				$html .= '<input type="checkbox" name="'.$e->getName().'" id="'.$e->name.sprintf('%02d',$cid+1).'" value="'.$c['value'].'"'.$selected.'/>'.$c['title'].'<br/> ';
				}
			} else {
				$html .= '<input class="forminput" type="'.$e->type.'" name="'.$e->name.'" id="'.$e->name.'" value="'.htmlentities($e->value,ENT_QUOTES).'" size="'.$e->size.'"/>';
			}
			$html .= '</td></tr>';
		}
		$html .= '</table><br />';
		if ($form->formFooter != '' ) {
			$html .= '<P style="padding:0px 0px 3pt;text-align:justify;">'.$form->formFooter.'</P>';
			$html .= "\n";
		}
		$html .= '<div style="width:90%;text-align:right;">';
		$html .= "\n";
		$html .= '<input class="submitbutton" type="submit" name="'.$form->name.'_submit" value="Save"/>';
		$html .= '&nbsp;&nbsp;';
		$html .= '<input style="width:7em;" class="formbutton" type="button" name="'.$form->name.'_cancel" onclick="javascript:history.go(-1);" value="Cancel"/>';
		$html .= "\n";
		$html .= '</div>';
		$html .= "\n";

		foreach ($form->hidden as $e) {
			$html .= '<input type="hidden" name="'.$e->name.'" id="'.$e->name.'" value="'.htmlentities($e->value,ENT_QUOTES).'"/>';
		}

		$html .= '</form>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= "\n";

		return $html;
	}
}

class Crm_Issue_Admin_ListView extends Cgn_Mvc_ListView {

	function toHtml($id='') {
		$html  = '';
		$html .= $this->printOpen();
		$rows = $this->_model->getRowCount();

		$issueCount=0;
		for($x=0; $x < $rows; $x++) {
			$_issue = $this->_model->getValueAt($x, NULL);
			if ($issueCount == 0) {
				$issueCssClass = 'issue-post-first';
			} else {
				$issueCssClass = 'issue-post';
			}
			$issueCount++;
			$issueCssAdditional = strtolower($_issue->getThreadStatusStyle());
			$html .= '<div class="'.$issueCssClass .' '. $issueCssAdditional.'" >
			<div class="issue-post-metadata">
			<img src="'. cgn_sappurl('account', 'img').$_issue->get('user_id').'" alt="" class="avatar photo" height="50" width="50">

			<i><a href="'.cgn_sappurl('crmtech', 'acct', 'view', array('id'=>$_issue->get('crm_acct_id'))).'">'.$_issue->get('org_name').'</a></i>	<b>'.$_issue->get('user_name').'</b>	'.date('F d, Y', $_issue->get('post_datetime')).' &mdash; '.$_issue->getThreadStatusLabel().' <br/>
			</div>
			
			<div class="issue-post-content">
			'. $_issue->get('message').'
			</div>

			<div class="issue-post-controls">
			<a class="issue-read-all" id="issue_'.$_issue->getPrimaryKey().'" href="'. cgn_sappurl('crmtech', 'issue', 'view', array('id'=>$_issue->getThreadId())).'#'.$_issue->getThreadId().'">Read thread...</a> 
				<br/>
			</div>
			</div>
';



//			$datum = $this->_model->getValueAt($x, NULL);
//			$html .= '<li class="list_li_1">'.$datum.'</li>'."\n";
		}
		$html .= $this->printClose();
		return $html;
	}
}
