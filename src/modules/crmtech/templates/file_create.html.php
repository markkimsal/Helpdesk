<?php
echo $t['pageTitle'];
?>


<?php
echo $t['toolbar']->toHtml();
?>


<?php
echo $t['form']->toHtml();
?>


<script type="text/javascript">
$(document).ready(function(){
	var lastfname = ''
		//$("input[@type='file']").change(function(){
		$("#filename").change(function(){
			if ($("#file_title").attr("value") == lastfname ||
				$("#file_title").attr("value") == 'New File') {
					var fname = $("#filename").attr("value");
					if (fname.lastIndexOf('/') != -1) {
						fname = rightFromSubString( fname, '/');
					}
					if (fname.lastIndexOf('\\') != -1) {
						fname = rightFromSubString( fname, '\\');
					}
					lastfname = fname;
					$("#file_title").attr("value", fname);
				}
			$("#description").focus();
		});
});

function rightFromSubString(fullString, subString) {
	if (fullString.lastIndexOf(subString) == -1) {
		return '';
	} else {
		return fullString.substring(fullString.lastIndexOf(subString)+1, fullString.length);
	}
}
</script>

