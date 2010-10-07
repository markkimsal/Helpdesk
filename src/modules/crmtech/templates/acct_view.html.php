<?
echo $t['pageTitle'];
echo $t['pageNav'];
echo $t['toolbar']->toHtml();
echo $t['viewTable']->toHtml();
?>

<br style="clear:right;"/>
<h3>Owner</h3>

<?php
echo $t['ownerTable']->toHtml();
?>


<div>

<div class="crm-hdr">Ask a Question
<br/>

<div id="new_question_form" style="display:visible;">
<form method="POST" action="<?=cgn_sappurl('crmtech', 'issue', 'save');?>">
<textarea name="ctx" cols="60" rows="7"></textarea>
<br/>
<input type="submit" name="sbmt_button" id="new_question_btn" value="Post Question" style="display:block;"/>
<input type="hidden" name="redir" id="redir_id" value="issue"/>
<input type="hidden" name="crm_acct_id" id="crm_acct_id" value="<?=$t['acct_id'];?>"/>
</form >
</div>

</div>


<br style="clear:right;"/>
<?
echo $t['questHeader'];
?>

<?
echo $t['quest']->toHtml();
?>
