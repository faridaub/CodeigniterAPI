<?php
	$ses_password = $this->session->userdata('password');
	$ses_password_conf = $this->session->userdata('passwordConf');
	$blank = '<tr><td height="10px"></td></tr>';

	$submitStyle = $this->api_com_util->getSubmitAttr();
	$submitStyleBack = $this->api_com_util->getSubmitBackAttr();
	// 初期画面
	if ($edit_mode == "entry" or $edit_mode == "back" or ($ex_flag == "index" and $edit_mode == "error")) {
		$inputStyle = $this->api_com_util->getInputAttr();
		$password = form_password($this->api_com_util->formInputDefine('password','20',$ses_password,$inputStyle));
		$passwordConf = form_password($this->api_com_util->formInputDefine('passwordConf','20',$ses_password_conf,$inputStyle));
		$subSetting = form_submit($this->api_com_util->formSubmitDefine('subSetting',$this->api_com_util->getErrlang('UI_BTN_SET'),$submitStyle));
	} elseif ($edit_mode == "conf") {
		$subConf = form_submit($this->api_com_util->formSubmitDefine('subConf',$this->api_com_util->getErrlang('UI_BTN_CONF'),$submitStyle));
		$subBack = form_submit($this->api_com_util->formSubmitDefine('subBack',$this->api_com_util->getErrlang('UI_BTN_BACK'),$submitStyleBack));
	}
?>
	<div class="content">
		<center>
<?php
		$attributes = array('name' => $mode, 'id' => $mode,'enctype' =>'multipart/form-data');
		echo form_open('reset_password', $attributes);
		// 入力時
		if ($ex_flag == "index" or $ex_flag == "back") {
			echo form_hidden('ex_flag','conf');
		// 確認時
		} else if ($ex_flag == "conf") {
			$this->session->set_userdata('lgnId', $lgnId);
			echo form_hidden('ex_flag','comp');
		}
?>
		<!-- メッセージエリア -->
		<?php if ($ex_flag != "error") { ?>
			<p class="msg_attntn"><?php echo $mssg; ?></p>
		<?php }?>

		<table class="frame_table">
		<!-- 入力・表示エリア -->
		<?php if ($edit_mode == "entry" or $edit_mode == "back" or $edit_mode == "conf" or ($ex_flag == "index" and $edit_mode == "error")) { ?>
			<tr>
				<td class="head"><?php echo $this->api_com_util->getErrlang('UI_ID');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="item"><?php echo $lgnId;?></td>
			</tr>
			<?php echo $blank; ?>
		<?php } ?>

		<?php if ($edit_mode == "entry" or $edit_mode == "back" or ($ex_flag == "index" and $edit_mode == "error")) { ?>
			<tr>
				<td class="head"><?php echo $this->api_com_util->getErrlang('UI_PASS_CONF');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="msg"><?php echo $this->api_com_util->getErrlang('UI_MESSAGE_1');?></td>
			</tr>
			<tr>
				<td class="msg"><?php echo $this->api_com_util->getErrlang('UI_MESSAGE_2');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="item"><?php echo $password;?></td>
			</tr>
			<tr>
				<td class="msg_red"><?php echo $this->api_com_util->getErrlang('UI_MESSAGE_3');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="item"><?php echo $passwordConf;?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="item"><?php echo $subSetting;?></td>
			</tr>
		<?php } else if ($edit_mode == "conf") { ?>
			<tr>
				<td class="head"><?php echo $this->api_com_util->getErrlang('UI_PASS');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="msg"><?php echo $this->api_com_util->getErrlang('UI_MESSAGE_4');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="item"><?php echo $subConf;?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="item"><?php echo $subBack;?></td>
			</tr>
		<?php } else if ($edit_mode == "comp") { ?>
			<tr>
				<td class="head"><?php echo $this->api_com_util->getErrlang('UI_PASS_COMP');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="msg_info"><?php echo $this->api_com_util->getErrlang('UI_MESSAGE_5');?></td>
			</tr>
		<?php } else if ($ex_flag == "error" and $edit_mode == "error") { ?>
			<?php echo $blank; ?>
			<tr>
				<td class="head"><?php echo $this->api_com_util->getErrlang('UI_PASS_ERR');?></td>
			</tr>
			<?php echo $blank; ?>
			<tr>
				<td class="msg_attntn"><?php echo $mssg;?></td>
			</tr>
		<?php } ?>
		</table>
		<?php echo form_close();?>
		</center>
	</div>
	</div>
	</body>
	</html>