<?php
echo "<h2>".$t['acctName']."</h2>";
?>

<div id="crm_main">

<div id="crm_main_sub">


<div class="crm-hdr">Ask a Question
<br/>

<div id="new_question_form" style="display:block;">
<form method="POST" action="<?=cgn_appurl('crm', 'issue', 'save', '', 'https');?>">
<textarea name="ctx" cols="50" rows="1"></textarea>
<br/>
<input type="submit" name="sbmt_button" id="new_question_btn" value="Post" style="display:none;"/>
</form >
</div>

</div>



<h3 class="crm-hdr">Recent Questions</h3>

<?php
if (count($t['issueList']) < 1) {
?>
No questions.
<?php
} else {

	$issueCount=0;
	foreach ($t['issueList'] as $_issue) {
		if ($issueCount == 0) {
			$issueCssClass = 'issue-post-first';
		} else {
			$issueCssClass = 'issue-post';
		}
		$issueCount++;
		$issueCssAdditional = strtolower($_issue->getStatusStyle());
?>
		<div class="<?=$issueCssClass .' '. $issueCssAdditional;?>" >
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https').$_issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">

		<b><?=$_issue->get('user_name');?></b>	<span class="timestamp" style="display:none;"><?=date('c', $_issue->get('post_datetime'));?></span> <span class="fulldate"><?=date('F d, Y', $_issue->get('post_datetime'));?></span> &mdash; <?=$_issue->getStatusLabel();?> <br/>
		</div>
		
		<div class="issue-post-content">
		<?php echo $_issue->get('preview');?>
		</div>

		<div class="issue-post-controls">
		<a class="issue-read-all" id="issue_<?=$_issue->getPrimaryKey();?>" href="<?= cgn_appurl('crm', 'issue', '', array(), 'https').'#'.$_issue->getPrimaryKey();?>">Read all...</a> 
			<br/>
		</div>
		</div>

<?php
	}
}
?>

<h3 class="crm-hdr">Recent Files</h3>
<?php
if (count($t['fileList']) < 1) {
?>
No files.
<?php
} else {

	foreach ($t['fileList'] as $_issue) {
?>
		<div class="issue-post-metadata">
		</div>
		
		<div class="issue-post-content">
		<img src="<?=cgn_appurl(
			'webutil', 
			'identicon', 
			'', 
			array('s'=>'m', 'id'=>md5($_issue->get('cgn_guid'))),
			'https'
			).'icon.png'?>" 
				style="padding-right:1em;padding-bottom:.5em;" align="left"/>

		<a href="<?=cgn_appurl('crm', 'download', '', array('id'=>$_issue->get('crm_file_id')), 'https').$_issue->get('link_text');?>"><?php echo $_issue->get('title');?></a>
		<p>
			Uploaded on: <?php echo date('Y-m-d', $_issue->get('published_on'));?>
		</p>
		<p>
		<?= $_issue->get('description');?>
		</p>
		</div>
<?php
	}
}
?>


<!--
<h3 style="clear:left;color:#EEE; margin-top:1em;padding:.25em .5em;background-color:#777">Recent Members</h3>

<?php
if (count($t['memberList']) < 1) {
?>
No files.
<?php
} else {

	foreach ($t['memberList'] as $_issue) {
?>
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https');?>" alt="" class="avatar photo" height="50" width="50">
		On <?=date('F d, Y', $_issue->get('post_datetime'));?> <a href="#"><?=$_issue->get('user_name');?></a> said:
		</div>
		
		<div class="issue-post-content">
		<?php echo $_issue->get('message');?>
		</div>
<?php
	}
}
?>
-->

</div>
</div>

<div class="crm_menu">
<ul >
<li><a href="<?=cgn_appurl('crm', '', '', '', 'https');?>">Overview</a></li>
<li><a href="<?=cgn_appurl('crm', 'issue', '', '', 'https');?>">Questions</a></li>
<li><a href="<?=cgn_appurl('crm', 'file', '', '', 'https');?>">Files</a></li>
<?php
if ($t['isTech']):
?>
<li><a href="<?=cgn_sappurl('crmtech');?>">CRM Tech</a></li>
<?php
endif;
?>
<!--
<li><a href="#">Corkboard</a></li>
-->
<!--
<li><a href="#">Members</a></li>
-->
</ul>
</div>


<script type="text/javascript" language="JavaScript">
<!--
	$(document).ready(function() {
		$("TEXTAREA").bind('focus', function(e) {
			$(e.target).animate({height:'6em'});
			$("#new_question_btn").show();
		});
		$("TEXTAREA").bind('blur', function(e) {
			console.log ($(e.target).val());
			if ($(e.target).val() == '') {
				$(e.target).animate({height:'2em'});
			}
		});


		$(".timestamp").cuteTime();
		$(".timestamp").css('display', 'inline');
		$(".fulldate").css('display', 'none');
	});
-->
</script>
