<?php
echo $t['pageNav'];
$issue = $t['issue'];
?>
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https').$issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">
		Status: <?=$issue->getStatusLabel();?> <br/>
		On <?=date('F d, Y', $issue->get('post_datetime'));?> <a href="#"><?=$issue->get('user_name');?></a> said:
		</div>
		
		<div id="content_<?=$issue->getPrimaryKey();?>" class="issue-post-content">
		<?php echo $issue->get('message');?>
		</div>

		<div class="issue-post-controls">
			<a class="issue-reply" id="issue_<?=$issue->getPrimaryKey();?>" href="#">Reply</a>
			<br/>
			<form method="POST" action="<?=cgn_appurl('crmtech', 'issue', 'saveReply', array(), 'https');?>" id="reply_<?=$issue->getPrimaryKey();?>">
			<textarea name="ctx" cols="60" rows="6"></textarea>
			<br/>
			Change Status: <select name="status_id">
			<?php
				//default to Done, 6
				foreach ($t['statusList'] as $_sid => $_sname) {
					if ($_sid == 6) 
					echo '<option value="'.$_sid.'" SELECTED="selected">'.htmlentities($_sname).'</option>';
					else
					echo '<option value="'.$_sid.'">'.htmlentities($_sname).'</option>';
				}
			?>
			</select>
			<br/>

			<input type="submit" name="sbmt_button" value="Post Reply"/>
			<input type="hidden" name="thread_id" value="<?=$issue->getPrimaryKey();?>"/>
			</form >

		</div>


<?php
if (count($t['replyList']) < 1) {
?>
No replies.
<?php
} else {
?>
<h5 style="color:#EEE; padding:.25em .5em;background-color:#777">Comments: 0</h5>
<?php
	foreach ($t['replyList'] as $_issue) {
?>
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https').$_issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">
		On <?=date('F d, Y', $_issue->get('post_datetime'));?> <a href="#"><?=$_issue->get('user_name');?></a> said:
		</div>
		
		<div id="loaded_<?=$_issue->getPrimaryKey();?>" class="issue-post-content">
		<?php echo $_issue->get('message');?>
		</div>

<?php
	}
}
?>

<script type="text/javascript" language="JavaScript">
<!--
	$(document).ready(function() {
		$("#new_question_href").bind('click', function(e) {
			$("#new_question_form").toggle();
		});
		$(".issue-reply").bind('click', function(e) {
			var t = e.target.id;
			var temp = new Array();
			temp = t.split('_');
			var id = temp[1];
			$("#reply_"+id).show();
			e.preventDefault();
			e.stopPropagation();
		});


	});
-->
</script>
