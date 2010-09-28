<?php
echo "<h2>".$t['acctName']."</h2>";
?>

<div id="crm_main">
<div style="text-align:left;float:right;width:83%">

<div class="crm-hdr">Ask a Question
<br/>

<div id="new_question_form" style="display:visible;">
<form method="POST" action="<?=cgn_appurl('crm', 'issue', 'save', '', 'https');?>">
<textarea name="ctx" cols="60" rows="1"></textarea>
<br/>
<input type="submit" name="sbmt_button" id="new_question_btn" value="Post Question" style="display:none;"/>
<input type="hidden" name="redir" id="redir_id" value="issue"/>
</form >
</div>

</div>

		<div class="data_table_pager">
		<form method="GET" action="<?=$t['baseUrl'];?>" style="display:inline;">
		<a href="<?=$t['prevUrl'];?>">
		<img height="12" src="<?=cgn_url();?>media/icons/default/arrow_left_24.png" border="0"/>
		</a> 
		Page <input type="text" name="p" size="1" value="<?=$t['curPage'];?>" style="width:1.5em;height:1em;"/> of  <?=$t['pageCount'];?>
<a href="<?=$t['nextUrl'];?>">
	<img height="12" src="<?=cgn_url();?>media/icons/default/arrow_right_24.png" border="0"/>
		</a>  | 
		Showing <?=$t['rowCount'];?> records | Total records found: <?=sprintf($t['unlimitedRowCount']);?>
		</form></div>

<h3 class="crm-hdr">Browse Questions</h3>

<?php
if (count($t['issueList']) < 1) {
?>
No questions.
<?php
} else {

	$issueCount = 0;
	foreach ($t['issueList'] as $_issue) {
		if ($issueCount == 0) {
			$issueCssClass = 'issue-post-first';
		} else {
			$issueCssClass = 'issue-post';
		}
		$issueCount++;
		$issueCssAdditional = strtolower($_issue->getStatusStyle());
?>

	<div class="<?=$issueCssClass. ' '.$issueCssAdditional;?>">
		<div class="issue-post-metadata">
		<img src="<?= cgn_appurl('account', 'img', '', '', 'https').$_issue->get('user_id');?>" alt="" class="avatar photo" height="50" width="50">
	    <b><?=$_issue->get('user_name');?></b>: <span class="timestamp" style="display:none;"><?=date('c', $_issue->get('post_datetime'));?></span>  <span class="fulldate"><?=date('F d, Y', $_issue->get('post_datetime'));?></span> &mdash; Status: <?=$_issue->getStatusLabel();?><br/>
		
		<div id="content_<?=$_issue->getPrimaryKey();?>" class="issue-post-content">
		<?php echo $_issue->get('preview');?>
		</div>
		<div id="loaded_<?=$_issue->getPrimaryKey();?>" class="issue-post-content" style="display:none;">
		<?php echo $_issue->get('message');?>

			<h5 style="color:#EEE; padding:.25em .5em;background-color:#777">Comments: <?=$_issue->getReplyCount();?></h5>
				<div style="background-color:#eee;border:1px solid black;margin-top:-1em;" id="comments_<?=$_issue->getPrimaryKey();?>"></div>
		</div>
		</div>

		<div class="issue-post-controls">
			<a class="issue-read-all" id="readall_<?=$_issue->getPrimaryKey();?>" href="#">Read all...</a> | 
			<a class="issue-reply" id="ireply_<?=$_issue->getPrimaryKey();?>" href="#">Reply</a>
			<br/>
			<form style="display:none;" method="POST" action="<?=cgn_appurl('crm', 'issue', 'saveReply', '', 'https');?>" id="reply_<?=$_issue->getPrimaryKey();?>">
			<textarea name="ctx" cols="60" rows="6"></textarea>
			<br/>
			<input type="submit" name="sbmt_button" value="Post Reply"/>
			<input type="hidden" name="thread_id" value="<?=$_issue->getPrimaryKey();?>"/>
			</form >

		</div>
		</div>
<?php
	}
}
?>

</div>
</div>

<div class="crm_menu">
<ul >
<li><a href="<?=cgn_appurl('crm');?>">Overview</a></li>
<li><a href="<?=cgn_appurl('crm', 'issue');?>">Questions</a></li>
<li><a href="<?=cgn_appurl('crm', 'file', '', '', 'https');?>">Files</a></li>
<!--
<li><a href="#">Corkboard</a></li>
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


		$(".issue-read-all").bind('click', function(e) {
			var t = e.target.id;
			var temp = new Array();
			temp = t.split('_');
			var id = temp[1];
			$("#content_"+id).toggle();
			$("#loaded_"+id).toggle();
			e.preventDefault();
//			e.stopPropagation();
			//load up comments
			var container = $("#comments_"+id);
			showLoadingPane(container); 
			container.load('<?=cgn_appurl('crm', 'issue', 'quickReplies', array('c'=>$t['c'], 'xhr'=>1), 'https');?>id='+id, humanTime);
			//$("#comments_"+id).attr('src', '<?=cgn_appurl('crm', 'issue', 'quickReplies');?>/id='+id);
		});
		$(".issue-reply").bind('click', function(e) {
			var t = e.target.id;
			var temp = new Array();
			temp = t.split('_');
			var id = temp[1];
			$("#reply_"+id).toggle();
			e.preventDefault();
//			e.stopPropogation();
		});

		//find any anchor tags
		var myFile = document.location.toString();
		if (myFile.match('#')) { // the URL contains an anchor
			// click the navigation item corresponding to the anchor
			var myAnchor =  myFile.split('#')[1];
			$('a[id="readall_' + myAnchor + '"]').click();
		}
		if (myFile.match('ra')) { // the URL contains an anchor
			// click the navigation item corresponding to the anchor
			var myAnchor =  myFile.split('ra')[1];
			$('a[id="readall_' + myAnchor + '"]').click();
		}


		//bubbling
		$(document.body).bind("click", function(e) { 
			var $target = $(e.target); 
			if ($target.is(".issue-comment-pager")) { 
				e.preventDefault(); e.stopPropagation(); 
				var $url = $target.parent()[0].href;
				//need to replace the event in the string because a failure
				//to have jquery run properly should result in a 
				//complete page load
				console.log($url);
				console.log($target.parent());
				$url = $url.replace(".issue", ".issue.quickReplies");
				$parent = $target.parent().parent().parent();
				showLoadingPane($parent); 
//				$parent.empty(); 
				$parent.load($url, humanTime);
				return false;
			}
		});
	});

	function humanTime() {
		$(".timestamp").cuteTime();
		$(".timestamp").css('display', 'inline');
		$(".fulldate").css('display', 'none');
	}

	/**
	 * show a div with a loading graphic
	 */
	function showLoadingPane(par) {
		var w = par.width();
		var h = par.height();
		if (w < 30) {
			w = '100';
		}
		if (h < 30) {
			h = '100';
		}
		par.empty();
//		par.width(w);
//		par.height(h);
		par.html("<div style=\"height:"+h+"px;width:"+w+"px;text-align:center;\">Loading...<br/><img src=\"<?=cgn_url();?>media/icons/default/wait_loading.gif\"> </div>");
	//	$("#loadingpane").
	}
-->
</script>
