<h2>Terms of Service</h2>
<div style="overflow-y:scroll; height:400px;font-family:serif">

<?php

echo $t['tos'];
?>

</div>

<form action="<?=cgn_appurl('crm', 'apply', 'savetos');?>">
<input type="checkbox" name="agree" value="on" /> I have read and agree to the above terms of service.
<br/>
<input type="submit" name="sbmt-button" value="Finish"/>
</form>
