<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * API
 * 共通処理
 *
 * @package		CodeIgniter
 * @subpackage	libraries
 * @category	libraries
 * @author		Kenichi Tani
 * @url			http://hr.toyoko-inn.co.jp
 *
 * Copyright © 2013 TOYOKO INN IT SHUUKYAKU SOLUTION CO.,LTD All Rights Reserved.
*/
class Api_com_util {

	/*
	 * isSetNotNullチェック
	*
	*  $ary : 配列
	*  $id  : id
	*
	* return : true / false
	*
	*/
	function isSetNotNull($ary, $id){
		if (array_key_exists($id, $ary)){
			if ($this->isNotNull($ary[$id])) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Nullチェック
	*
	*  $obj : Object文字列
	*
	* return : true / false
	*
	*/
	function isNull($obj){
		if( $obj == '' or $obj == null ){
			return true;
		}
		return false;
	}

	/*
	 * NotNullチェック
	*
	*  $obj : Object文字列
	*
	* return : true / false
	*
	*/
	function isNotNull($obj){
		return ($this->isNull($obj) == false);
	}

	/*
	 * Nullチェック(配列データ)
	*
	*  $obj : Object配列文字列
	*
	* return : true / false
	*
	*/
	function isNullArray($array){
		if ($array == null) {
			return  true;
		}
		foreach ($array as $data) {
			if ($this->isNotNull($data)){
				return false;
			}
		}
		return true;
	}

	/*
	 * NotNullチェック(配列データ)
	*
	*  $obj : Object文字列
	*
	* return : true / false
	*
	*/
	function isNotNullArray($array){
		return ($this->isNullArray($array) == false);
	}

	/*
	 * 連想配列値取得
	*
	*  $array 		: 連想配列
	*  $key_name 	: 取得する連想配列名
	*
	* return : 値
	*
	*/
	function getValueArrayByName($array, $key_name){
		foreach ($array as $key => $array2){
			foreach ($array2 as $key2 => $value2){
				if ($key2 == $key_name){
					return $value2;
				}
			}
		}
		return "";
	}

	/*
	 * 数値切捨て
	*
	*  $dec   : 数値
	*  $point : 小数点以下
	*
	* return : 数値
	*
	*/
	function numFloor($dec, $point=0){
		if ($this->isNotNull($dec)){
			if ($point == 0) {
				return floor($dec);
			} else {
				return floor($dec * pow(10, $point)) / pow(10, $point);
			}
		}
		return $dec;
	}

	/*
	 * 通貨名記号付金額フォーマット
	*
	* $num				金額
	* $crrncy_name		通貨名
	* $crrncy_sign		通貨記号
	* $lngg				言語
	*
	*/
	function crrncyFullFormat($num, $crrncy_name, $crrncy_sign, $lngg){
		// 言語判定
		switch ($lngg){
			case  Api_const::LANG_JP:
				return $crrncy_sign.$this->numberFormat($num, $lngg);
			case  Api_const::LANG_EN:
				return $crrncy_sign.$this->numberFormat($num, $lngg);
			default:
				return $crrncy_sign.$this->numberFormat($num, $lngg);
		}
	}


	/*
	 * 金額フォーマット
	*
	* $num				金額
	* $lngg				言語
	*
	*/
	function numberFormat($num, $lang){
		//sample $nombre_format_francais = number_format($num, 2, ',', ' ');
		return number_format($num);
	}

	/*
	 * 配列Indexチェック
	*
	* $ary				配列
	* $idx				Index
	*
	*/
	function isArrayChkIdex($ary, $idx){
		if (is_array($ary)){
			if (count($ary) > $idx){
				return true;
			}
		}
		return false;
	}

	/*
	 * 配列のキー存在チェック
	*
	*  $ary : 配列
	*  $id  : id
	*
	*/
	function chkAryKeyExists($ary, $id){
 		if (array_key_exists($id, $ary)){
 			return true;
 		}
		return false;
	}

	/*
	 * form_inputパラメータ作成
	*
	*  $name  		: Name
	*  $maxlength	: 最大桁
	*  $value  	: 設定値
	*  $style  	: styleプロパティ
	*
	* return : true / false
	*
	*/
	function formInputDefine($name, $maxlength, $value, $style){
		$data = array('name' => $name,'maxlength' => $maxlength,'value' => $value,);
		if ($this->isNotNull($style)){
			$data += array('style'=> $style);
		}
		return $data;
	}

	/*
	 * <INPUT>用の属性取得
	*
	*  $addStyle  	: styleプロパティ
	*
	* return : 文字列
	* remark:InputText用のstyleを指定
	*/
	function getInputAttr($addStyle=""){
		$style = "width:90%;height:35px;font-size:25px;";
		if ($this->isNotNull($addStyle)){
			$style = $style.$addStyle;
		}
		return $style;
	}

	/*
	 * form_submitパラメータ作成
	*
	*  $name  		: Name
	*  $value  	: 設定値
	*  $style  	: styleプロパティ
	*
	* return : true / false
	*
	*/
	function formSubmitDefine($name, $value, $style){
		$data = array('name' => $name,'value' => $value,);
		if ($this->isNotNull($style)){
			$data += array('style'=> $style);
		}
		return $data;
	}

	/*
	 * <SUBMIT>用の属性取得
	*
	*  $addStyle  	: styleプロパティ
	*
	* return : 文字列
	* remark:Submit用のstyleを指定
	*/
	function getSubmitAttr($addStyle=""){
		$style = "background-color:#000099;height:60px;width:92%;font-size:25px;font-weight:bold;color:#FFF;";
		if ($this->isNotNull($addStyle)){
			$style = $style.$addStyle;
		}
		return $style;
	}

	/*
	 * <SUBMIT>用の属性取得(戻る)
	*
	*  $addStyle  	: styleプロパティ
	*
	* return : 文字列
	* remark:Submit用のstyleを指定
	*/
	function getSubmitBackAttr($addStyle=""){
		$style = "background-color:#AEAFC6;height:40px;width:92%;font-size:18px;font-weight:bold;color:#000;";
		if ($this->isNotNull($addStyle)){
			$style = $style.$addStyle;
		}
		return $style;
	}

	/*
	 * error_lang取得用
	*
	* $code			メッセージコード
	*
	*/
	function getErrlang($code){
		$CI =& get_instance();
		return $CI->lang->line($code);
	}
}