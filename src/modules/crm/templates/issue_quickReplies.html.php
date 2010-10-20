
<?php
if (count($t['issueList']) < 1) {
?>
No replies.
<?php
} else {

	foreach ($t['issueList'] as $_issue) {
?>
	<div class="issue-post <?=$issueCssClass. ' '.$issueCssAdditional;?>">
		<div class="issue-post-metadata">
		<img src="<?= cgn_sappurl('account', 'img').$_issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">
		<b><?=$_issue->get('user_name');?></b>: <span class="timestamp" style="display:none;"><?=date('c', $_issue->get('post_datetime'));?></span>  <span class="fulldate"><?=date('F d, Y - h:i', $_issue->get('post_datetime'));?></span>
		</div>
		
		<div id="loaded_<?=$_issue->getPrimaryKey();?>" class="issue-post-content">
		<?php echo $_issue->get('message');?>
		</div>
		<div class="issue-post-controls">
		</div>
	</div>

<?php
	}
}
?>
