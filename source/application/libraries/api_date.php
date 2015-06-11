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
class Api_date {

	/*
	 * TimeZone日付取得
	*
	*  $timezone : TimeZone(UTC,etc)
	*
	* //$datestring = "%Y-%m-%d %h:%i %a";
	*
	*/
	function getTimeZoneDate($timezone){
		//return  date("Ymd");
		$now = new DateTime('NOW');
		$now->setTimeZone(new DateTimeZone($timezone));
		return $now->format('Ymd');
	}

	/*
	 * 日付加算(日)
	*
	*  $date : 元日付
	*  $add  : 加算数
	*
	*/
	function addDay($date, $add){
		if ($add == 0) {
			return $date;
		}
		return date("Ymd", strtotime($date." +".$add." day"));
	}

	/*
	 * 日付加算(月)
	*
	*  $date : 元日付
	*  $add  : 加算数
	*
	*/
	function addMonth($date, $add){
		return date("Ymd", strtotime($date." +".$add." month"));
	}

	/*
	 * 日付差取得
	*
	*  $date1 : 日付１
	*  $date2 : 日付２
	*
	*/
	function dayDiff($date1, $date2) {
		// 日付をUNIXタイムスタンプに変換
		$timestamp1 = strtotime($date1);
		$timestamp2 = strtotime($date2);
		// 何秒離れているかを計算
		$seconddiff = abs($timestamp2 - $timestamp1);
		// 日数に変換
		$daydiff = $seconddiff / (60 * 60 * 24);
		// 戻り値
		return $daydiff;
	}

}