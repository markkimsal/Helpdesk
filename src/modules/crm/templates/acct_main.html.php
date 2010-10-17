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



<h2>Members</h2>


<?php
echo $t['memberTable']->toHtml();
?>


</div>
</div>

<div class="crm_menu">
<ul >
<li><a href="<?=cgn_sappurl('crm');?>">Overview</a></li>
<li><a href="<?=cgn_sappurl('crm', 'issue');?>">Questions</a></li>
<li><a href="<?=cgn_sappurl('crm', 'file');?>">Files</a></li>
<li><a href="<?=cgn_sappurl('crm', 'acct');?>">Members</a></li>
<!--
<li><a href="#">Corkboard</a></li>
-->
<!--
<li><a href="#">Members</a></li>
-->
</ul>
</div>

