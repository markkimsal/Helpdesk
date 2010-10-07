<?php
echo "<h2>".$t['acctName']."</h2>";
?>

<div id="crm_main">

<div id="crm_main_sub">

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


<h3 class="crm-hdr">Browse Files</h3>
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
		<?= $_issue->get('description');?>
		</p>
		</div>
<?php
	}
}
?>


</div>
</div>

<div class="crm_menu">
<ul >
<li><a href="<?=cgn_appurl('crm', '', '', '', 'https');?>">Overview</a></li>
<li><a href="<?=cgn_appurl('crm', 'issue', '', '', 'https');?>">Questions</a></li>
<li><a href="<?=cgn_appurl('crm', 'file', '', '', 'https');?>">Files</a></li>
<!--
<li><a href="#">Corkboard</a></li>
-->
<!--
<li><a href="#">Members</a></li>
-->
</ul>
</div>

