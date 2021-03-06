<?php
echo $t['pageNav'];
$issue = $t['issue'];


$issueCssClass = 'issue-post issue-post-first';
$issueCssAdditional = strtolower($issue->getStatusStyle());
?>

<?php
echo "<h2>Question From ".$t['account']->get('org_name')."</h2>";
?>



		<div class="<?=$issueCssClass. ' '.$issueCssAdditional;?>">
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https').$issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">
		Status: <?=$issue->getStatusLabel();?> <br/>
		On <?=date('F d, Y', $issue->get('post_datetime'));?> <a href="#"><?=$issue->get('user_name');?></a> said:
		</div>
		
		<div class="issue-post-delete" style="display:none;">
		<button style="background-color:#0A3767;color:#FFF;" title="delete" data-id="<?php echo $issue->getPrimaryKey();?>" onclick="deleteThread(this);">X</button>
		</div>

		<div id="content_<?=$issue->getPrimaryKey();?>" class="issue-post-content">
		<?php echo $issue->get('message');?>
		</div>
		</div>

<?php
echo $t['replyForm']->toHtml();
?>



<?php
if (count($t['replyList']) < 1) {
?>
No replies.
<?php
} else {
?>
	<h5 style="color:#EEE; padding:.25em .5em;background-color:#777">Comments: <?php echo count($t['replyList']);?></h5>
<?php
	foreach ($t['replyList'] as $_issue) {
?>
		<div class="issue-post-reply" id="reply_<?=$_issue->getPrimaryKey();?>" >
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https').$_issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">
		On <?=date('F d, Y - H:i:s', $_issue->get('post_datetime'));?> <a href="#"><?=$_issue->get('user_name');?></a> said:
		</div>

		<div class="issue-post-reply-delete" style="display:none;">
		<button style="background-color:#0A3767;color:#FFF;" title="delete" data-id="<?php echo $_issue->getPrimaryKey();?>" onclick="deleteReply(this);">X</button>
		</div>
	
		<div id="loaded_<?=$_issue->getPrimaryKey();?>" class="issue-post-content">
		<?php echo $_issue->get('message');?>
		</div>

		<br style="clear:right;"/>
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

		$(".issue-post-reply").bind('mouseover', function(e) {
			var t = $(e.target);
			while (t.attr('class') != 'issue-post-reply') {
				t = t.parent();
			}
			t.css('border', '3px solid #CCF');
			$(".issue-post-reply-delete", t).css('top', (t.position().top+5)+'px');
			$(".issue-post-reply-delete", t).css('left', (t.position().left+t.width()-20)+'px');
			$(".issue-post-reply-delete", t).css('position', 'absolute');
			$(".issue-post-reply-delete", t).css('display', '');
			e.preventDefault();
			e.stopPropagation();
		});

		$(".issue-post-reply").bind('mouseout', function(e) {
			var t = $(e.target);
			while (t.attr('class') != 'issue-post-reply') {
				t = t.parent();
			}
			t.css('border', '3px solid #FFF');
			$(".issue-post-reply-delete", t).css('display', 'none');
			e.preventDefault();
			e.stopPropagation();
		});

		$(".issue-post").bind('mouseout', function(e) {
			var t = $(e.target);
			var kill=10;
			while (t.attr('class').indexOf('issue-post ')==-1 && kill > 0) {
				kill--;
				t = t.parent();
			}
			t.removeClass('issue-post-hover');
			t.css('border', '3px solid #FFF');
			$(".issue-post-delete", t).css('display', 'none');
			e.preventDefault();
			e.stopPropagation();
		});

		$(".issue-post").bind('mouseover', function(e) {
			var t = $(e.target);
			var kill=10;
			while (t.attr('class').indexOf('issue-post ')==-1 && kill > 0) {
				kill--;
				t = t.parent();
			}
			t.addClass('issue-post-hover');
			t.css('border', '3px solid #CCF');
			$(".issue-post-delete", t).css('top', (t.position().top+5)+'px');
			$(".issue-post-delete", t).css('left', (t.position().left+t.width()-20)+'px');
			$(".issue-post-delete", t).css('position', 'absolute');
			$(".issue-post-delete", t).css('display', '');
			e.preventDefault();
			e.stopPropagation();
		});

	});

	function deleteReply(thisObj) {
		if (!confirm("Delete this reply?")) {
			return false;
		}
		$(thisObj).css('display', 'none');
		var delid = $(thisObj).attr('data-id');
		$.ajax({
			async:true,
			data: "id="+delid+"&xhr=1",
			url: "<?php echo cgn_sappurl('crmtech', 'issue', 'delreply');?>",
			success: function(){
				$("#reply_"+delid).empty().remove();
			}});
	}

	function deleteThread(thisObj) {
		if (!confirm("Delete thread and all replies?")) {
			return false;
		}
		$(thisObj).css('display', 'none');
		var delid = $(thisObj).attr('data-id');
		$.ajax({
			async:true,
			data: "id="+delid+"&xhr=1",
			url: "<?php echo cgn_sappurl('crmtech', 'issue', 'del');?>",
			success: function(){
				window.location.href="<?php echo cgn_sappurl('crmtech');?>";
			}});
	}

-->
</script>
