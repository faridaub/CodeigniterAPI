<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="target-densitydpi=device-dpi, width=600px, maximum-scale=1.0, user-scalable=yes">
<title>パスワード再設定</title>
<link media="all" href="<?php echo base_url();?>css/common1.css" rel="stylesheet" type="text/css" />
</head>
<!-- ブラウザの戻るボタンを使用できなくする -->
<script language="JavaScript">
window.onunload = function(){};
history.forward();
</script>
<body>
<div class="container">
	<center>

	<div class="header">
		<div class="title"><?php echo $this->api_com_util->getErrlang('UI_TITLE');?></div>
	</div>
	<?php if ($ex_flag != "error") { ?>
		<table class="step_table">
		<tr>
			<td class="stepNo">1</td>
			<td class="arrow"></td>
			<td class="stepNo">2</td>
			<td class="arrow"></td>
			<?php if ($ex_flag == "index" or $ex_flag == "back") {?>
				<td class="stepText"><?php echo $this->api_com_util->getErrlang('UI_STEP_3');?></td>
				<td class="arrow"></td>
				<td class="stepNo">4</td>
				<td class="arrow"></td>
				<td class="stepNo">5</td>
			<?php } else if ($ex_flag == "conf") {?>
				<td class="stepNo">3</td>
				<td class="arrow"></td>
				<td class="stepText"><?php echo $this->api_com_util->getErrlang('UI_STEP_4');?></td>
				<td class="arrow"></td>
				<td class="stepNo">5</td>
			<?php } else if ($ex_flag == "comp") {?>
				<td class="stepNo">3</td>
				<td class="arrow"></td>
				<td class="stepNo">4</td>
				<td class="arrow"></td>
				<td class="stepText"><?php echo $this->api_com_util->getErrlang('UI_STEP_5');?></td>
			<?php }?>
		</tr>
		</table>
	<?php } ?>
	</center>