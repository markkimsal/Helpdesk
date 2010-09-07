
<br style="clear:both;"/>

<?php
echo $t['toolbar']->toHtml();
?>


<h3>Find Questions by Status</h3>
<?php
//var_dump($t['questStatus']);

$_links = array();
foreach ($t['questStatus'] as $_rec) {
	if ($_rec['total_count'] > 0) {
		$_links[] =  '<a href="'.cgn_appurl('crmtech', 'issue', '', array('status_id'=>$_rec['status_id'])).'">'.$_rec['display_name'].' ('.$_rec['total_count'].')</a>';
	}
}
echo implode(' | ', $_links)
?>

<div style="clear:both;width:100%">
<?php
echo $t['questHeader'];
echo $t['quest']->toHtml();
?>
</div>

<div style="float:left;width:48%">
<?php
echo $t['orgsHeader'];
echo $t['orgs']->toHtml();
?>
</div>


<div style="float:right;width:48%">
<?php
echo $t['filesHeader'];
echo $t['files']->toHtml();
?>
</div>

