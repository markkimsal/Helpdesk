<?php
echo "<h2>".$t['acctName']."</h2>";
?>


<div id="crm_main">

<div id="crm_main_sub">


<h2>Invite organization members</h2>

<p>
You can invite more members of your organization to join your account.
</p>

<?php
echo $t['inviteForm']->toHtml();
?>

<h2>Pending invitations</h2>


<?php
echo $t['inviteTable']->toHtml();
?>


</div>
</div>

<div class="crm_menu">
<ul >
<li><a href="<?=cgn_appurl('crm', '', '', '', 'https');?>">Overview</a></li>
<li><a href="<?=cgn_appurl('crm', 'issue', '', '', 'https');?>">Questions</a></li>
<li><a href="<?=cgn_appurl('crm', 'file', '', '', 'https');?>">Files</a></li>
<li><a href="<?=cgn_appurl('crm', 'acct', '', '', 'https');?>">Members</a></li>
<!--
<li><a href="#">Corkboard</a></li>
-->
<!--
<li><a href="#">Members</a></li>
-->
</ul>
</div>

