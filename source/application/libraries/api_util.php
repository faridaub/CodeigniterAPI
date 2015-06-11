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
 * Copyright c 2013 TOYOKO INN IT SHUUKYAKU SOLUTION CO.,LTD All Rights Reserved.
*/
class Api_util {

	/*
	 * Api初期処理
	*
	*  $api_mode : Apiモード
	*
	* 	return : parameter配列
	*
	*/
	function apiInit($api_mode){
		//-------
		// 初期値
		//-------
		$func = 'apiInit';
		$result = null;
		$result['errCode']  = true;
		$result['mssg']     = '';

		// 電文展開&ログ出力
		$result = $this->expandMessage($api_mode);

		return $result;
	}

	/*
	 * Api終了処理
	*
	*  $data : jsonObject
	*  $error_code : error
	*  $error_description : message
	*
	*/
	function apiEnd($data, $error_code, $error_description){
		$CI =& get_instance();
		$json_out = $this->inputErrArray($data, $error_code, $error_description);
		// 結果の送信
		$CI->output
		->set_content_type('application/json')
		//20140930 json /エスケープ回避 php5.3用
		//json null文字列を""に変換する　iwamoto
		->set_output(preg_replace('/NULL/i','""',json_encode($json_out,JSON_UNESCAPED_SLASHES)));
	}

	/*
	 * 電文展開&ログ出力
	*
	*  $api_mode : APIモード
	*
	* 	return : parameter配列
	*
	*/
	function expandMessage($api_mode){
		//-------
		// 初期値
		//-------
		$func = 'expandMessage';
		$result = null;
		$result['errCode']  = true;
		$result['mssg']     = '';

		$posts = array();
		// APIリクエストへの設定
		$posts = $this->getPostData($api_mode);

		$CI =& get_instance();
		// 操作ログ初期化
		$log_rec = $CI->operational_log_m->initialRec($posts);

		// result,log_rec編集
		$result_log = $this->editResultLogRec($posts, $api_mode, $result, $log_rec);
		$result = $result_log['result'];
		$log_rec = $result_log['log_rec'];

		// 操作ログテーブルの追加
		$retupd = $CI->operational_log_m->insert($log_rec);
		if ($retupd['errCode'] !== true){
			$result['errCode']	= false;
		}
		//-----------
		// 戻り値を返す
		//-----------
		return $result;
	}

	/*
	 * エラーリスト追加処理
	*
	*  $data        : jsonObject
	*  $code        : errCode
	*  $description : errMssg
	*
	*/
	function inputErrArray($data, $code, $description) {
		$data['errrCode'] = $code;
		$data['errrMssg'] = $description;
		return $data;
	}

	/*
	 * ErrorCode設定
	*
	*  $code : ErrorCOde
	*
	* 	return : ErrorCode
	*
	*/
	function setErrorCode($code){
		if ($code === true){
			return Api_const::BCMN0000;
		} else if ($code === false){
			return Api_const::BAPI1004;
		}
		return $code;
	}


	/*
	 * API共通チェック
	* (アプリケーションバージョンチェック)
	* (必須チェック)
	*
	* $rqst
	* $ids
	*
	*/
	function chkApiCommon($rqst, $ids){
		// Init
		$error_code = true;
		$error_description = '';

		// 必須チェック
		$chk_require = $this->chkRequire($rqst, $ids);
		if ($chk_require['errCode'] !== true) {
			// error
			return  $chk_require;
		}
		// アプリバージョンチェック
		$chk_app = $this->chkAppVersion($rqst['applctnVrsnNmbr'], $rqst['key']);
		if ($chk_app['errCode'] !== true) {
			// error
			return  $chk_app;
		}

		$result['apikey_information_mode'] = $chk_app['apikey_information_mode'];
		$result['errCode'] = $error_code;
		$result['mssg'] = $error_description;
		return $result;
	}

	/*
	 * 必須チェック
	*
	* $rqst
	* $ids
	*
	*/
	function chkRequire($rqst, $ids){
		// Init
		$error_code = true;
		$error_description = '';

		$CI =& get_instance();

		$ids[] = 'key';                  // APIキー
		$ids[] = 'applctnVrsnNmbr';      // アプリのバージョン
		$ids[] = 'osType';               // OSのタイプ（I:iOS /  A: Androido）
		$ids[] = 'osVrsn';               // OSバージョン
		$ids[] = 'mdl';                  // 機種名
		$ids[] = 'lngg';                 // 端末設定言語
		if ($rqst['osType'] == 'I'){
			$ids[] = 'dvcTkn';           // iOSのdevice token
		}else{
			$ids[] = 'rgstrtnId';        // Androidoのregistration id
		}
		foreach ($ids as $id) {
			if ($CI->api_com_util->isNull($rqst[$id])){
				$error_code = Api_const::BAPI0002;
				break;
			}
		}
		if ($error_code !== true){
			$error_description = $CI->lang->line($error_code);
		}
		$result['errCode'] = $error_code;
		$result['mssg'] = $error_description;
		return $result;
	}

	/*
	 * アプリバージョンチェック
	*
	*  $applctn_vrsn_nmbr	: $applctn_vrsn_nmbr
	*  $api_key				: $api_key
	*
	*/
	function chkAppVersion($applctn_vrsn_nmbr, $api_key){
		// Init
		$error_code = true;
		$error_description = '';

		$CI =& get_instance();

		// APIキー情報取得
		$ret_inf = $CI->apikey_information_m->select($api_key);
		if ($ret_inf['errCode'] === false){
			// データ無
			$error_code = Api_const::BAPI0001;			// API不正エラー
		}else{
			// アプリバージョン管理テーブル取得
			$ret_app = $CI->application_version_control_m->select($applctn_vrsn_nmbr, $api_key);
			if ($ret_app['errCode'] === false){
				// データ無
				$error_code = Api_const::BAPI0003;		// バージョンチェックエラー
			}else{
				switch ($ret_app['rec']['stt']){
					case  "3":		// サービス終了
						$error_code = Api_const::BAPI0003;		// バージョンチェックエラー
						break;
					case  "2":		// サービス中
						break;
					case  "1":		// テスト中
						if ($ret_inf['rec']['mode'] != '2'){
							// APIキー情報がテスト中以外はエラー
							$error_code = Api_const::BAPI0003;		// バージョンチェックエラー
						}
						break;
					default:
						// APIキー情報がテスト中以外はエラー
						$error_code = Api_const::BAPI0003;		// バージョンチェックエラー
				}
			}
		}
		if ($error_code !== true){
			$error_description = $CI->lang->line($error_code);
		}
		else {
			$result['apikey_information_mode'] = $ret_inf['rec']['mode'];
		}
		$result['errCode'] = $error_code;
		$result['mssg'] = $error_description;
		return $result;
	}

	/*
	 * Web室料配列取得
	*
	* $prc					室料(税抜)
	* $web_dscnt_amnt		WEB割引額
	* $mmbr_dscnt_rate		会員割引率
	* $tax_rate				消費税率
	*
	*
	*/
	function getPrices($prc, $web_dscnt_amnt, $mmbr_dscnt_rate, $tax_rate){
		$CI =& get_instance();

		// 会員価格(税抜)
		$mmbr_prc = $this->getMemberPrice($prc, $mmbr_dscnt_rate);

		// WEB(税抜)
		$web_prc = $this->getWebPrice($prc, $web_dscnt_amnt);
		//会員価格(税抜)
		$web_mmbr_prc = $this->getMemberPrice($web_prc, $mmbr_dscnt_rate);

		// WEB(税込)
		$web_prc_inc_tax = $this->getAmountIncTax($web_prc, $tax_rate);
		//会員価格(税込)
		$web_mmbr_prc_inc_tax = $this->getAmountIncTax($web_mmbr_prc, $tax_rate);
		$ret = array(
			'prc' => $prc,                                     // 一般価格(税抜)
			'mmbr_prc' => $mmbr_prc,                           // 会員価格(税抜)
			'web_prc' => $web_prc,                             // Web一般価格(税抜)
			'web_prc_inc_tax' => $web_prc_inc_tax,             // Web一般価格(税込)
			'web_mmbr_prc' => $web_mmbr_prc,                   // Web会員価格(税抜)
			'web_mmbr_prc_inc_tax' => $web_mmbr_prc_inc_tax,   // Web会員価格(税込)
		);
		return $ret;
	}

	/*
	 * Web室料(税抜)
	*
	* $prc					室料(税抜)
	* $web_dscnt_amnt				WEB割引額
	*
	*		税抜 - WEB割引額
	*/
	function getWebPrice($prc, $web_dscnt_amnt){
		$CI =& get_instance();
		$CI->load->model('option_charge_infomation_m',true); 
		return $CI->api_com_util->numFloor($prc) - $web_dscnt_amnt;
	}

	/*
	 * 会員室料(税抜)
	*
	* $prc					室料(税抜)
	* $mmbr_dscnt_rate		会員割引率
	*
	*		税抜 * ((100 - 会員割引率) / 100)
	*		↑{小数点以下切り捨て}
	*/
	function getMemberPrice($prc, $mmbr_dscnt_rate){
		$CI =& get_instance();
		return $CI->api_com_util->numFloor($prc * (100 - $mmbr_dscnt_rate) / 100);
	}

	/*
	 * 税込取得
	*
	* $amount				金額(税抜)
	* $tax_rate				消費税率
	*
	*		税抜 * ((100 + 消費税率) / 100)
	*		↑{小数点以下切り捨て}
	*/
	function getAmountIncTax($amount, $tax_rate){
		$CI =& get_instance();
		return $CI->api_com_util->numFloor($amount * (100 + $tax_rate) / 100);
	}

	/*
	 * 税抜取得
	*
	* $amount_inc_tax		金額(税込)
	* $tax_rate				消費税率
	*
	*		税込 - (税込 * 消費税率 / (100 + 消費税率))
	*				↑{小数点以下切り捨て}
	*/
	function getAmount($amount_inc_tax, $tax_rate){
		$CI =& get_instance();
		return $amount_inc_tax - $CI->api_com_util->numFloor($amount_inc_tax * $tax_rate / (100 + $tax_rate));
	}
	/*
	 * Option(税抜)をDBより取得
	*
	* $eco					ecoフラグ
	* $vod					vodフラグ
	* $bsnss_pack_flag		bisnessPackフラグプ
	* $bsnss_pack_type		bisnessPackタイプ
	* $chld_bed_flg         添寝フラグ
	* $tax_rate				消費税
	* $htl_code				ホテルコード
	* $trgt_dat				宿泊日
	*/

	function getOptionByDb($eco, $vod, $bsnss_pack_flag, $bsnss_pack_type, $chld_bed_flg, $tax_rate, $htl_code, $trgt_date){
		$CI =& get_instance();
		$error_code = true;
		$error_description = '';
		$ret = 0;

		//オプション料金情報からデータを取得
		$option = $CI->option_charge_infomation_m->selectListCond($htl_code, $trgt_date); 
		// エラー
		if ($option['errCode'] === false){
			$error_code = Api_const::BAPI0001;			// API不正エラー
			$result['errCode'] = $error_code;
			$result['mssg'] = $CI->lang->line($error_code);
			$result['option'] = 0;
			return $result;
		}

		//オプションデータが存在している時
		if ( $option['recCnt'] > 0 ){
			
			for($i=0; $i<count($option['recList']); $i++){
				//ecoオプション（税込）
				if ($eco=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('option_eco')){
						$ret +=	$this->getAmount($option['recList'][$i]['optn_chrg'], $tax_rate);
					}
				}
				//vodオプション（税込）
				if ($vod=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('option_vod')){
						$ret +=	$this->getAmount($option['recList'][$i]['optn_chrg'], $tax_rate);
					}
				}
				//ビジネスパックオプション（税抜）
				if ($bsnss_pack_flag=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('option_bsnss_pack')[$bsnss_pack_type]){
						$ret +=	$this->getAmount($option['recList'][$i]['optn_chrg'], $tax_rate);
					}
				}
				//添い寝オプション）　※現在は0円
				if ($vod=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('chld_bed_flg')){
						$ret +=	$this->getAmount($option['recList'][$i]['optn_chrg'], $tax_rate);
					}
				}
			}
		}
		$result['option'] = $ret;
		$result['errCode'] = $error_code;
		$result['mssg'] = '';
		return $result;
	}


	/*
	 * Option(税込)をDBより取得
	*
	* $eco					ecoフラグ
	* $vod					vodフラグ
	* $bsnss_pack_flag		bisnessPackフラグプ
	* $bsnss_pack_type		bisnessPackタイプ
	* $chld_bed_flg         添寝フラグ
	* $htl_code				ホテルコード
	* $trgt_dat				宿泊日
	*/

	function getOptionIncTaxByDb($eco, $vod, $bsnss_pack_flag, $bsnss_pack_type, $chld_bed_flg, $htl_code, $trgt_date){
		$CI =& get_instance();
		$error_code = true;
		$error_description = '';
		$ret = 0;

		//オプション料金情報からデータを取得
		$option = $CI->option_charge_infomation_m->selectListCond($htl_code, $trgt_date); 
		// エラー
		if ($option['errCode'] === false){
			$error_code = Api_const::BAPI0001;			// API不正エラー
			$result['errCode'] = $error_code;
			$result['mssg'] = $CI->lang->line($error_code);
			$result['option'] = 0;
			return $result;
		}

		//オプションデータが存在している時
		if ( $option['recCnt'] > 0 ){
			
			for($i=0; $i<count($option['recList']); $i++){
				//ecoオプション
				if ($eco=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('option_eco')){
						$ret += $option['recList'][$i]['optn_chrg'];
					}
				}
				//vodオプション
				if ($vod=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('option_vod')){
						$ret += $option['recList'][$i]['optn_chrg'];
					}
				}
				//ビジネスパックオプション
				if ($bsnss_pack_flag=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('option_bsnss_pack')[$bsnss_pack_type]){
						$ret += $option['recList'][$i]['optn_chrg'];
					}
				}
				//添い寝オプション）　※現在は0円
				if ($vod=='Y'){
					if ( $option['recList'][$i]['optn_id'] == $CI->config->item('chld_bed_flg')){
						$ret += $option['recList'][$i]['optn_chrg'];					}
				}
			}
		}
		$result['option'] = $ret;
		$result['errCode'] = $error_code;
		$result['mssg'] = '';
		return $result;
	}


	/*
	 * Option(税抜)取得
	*
	* $eco					ecoフラグ
	* $vod					vodフラグ
	* $bsnss_pack_flag		bisnessPackフラグプ
	* $bsnss_pack_type		bisnessPackタイプ
	*
	*/
	function getOption($eco, $vod, $bsnss_pack_flag, $bsnss_pack_type, $tax_rate){
		$CI =& get_instance();
		$ret = 0;
		//ecoとvodは税込
		if ($eco == "Y"){
			$ret -= $this->getAmount($CI->config->item('eco_amnt'), $tax_rate);
		}
		if ($vod == "Y"){
			$ret += $this->getAmount($CI->config->item('vod_amnt'), $tax_rate);
		}
		//ビジネスパックは税抜き
		if ($bsnss_pack_flag === "Y"){
			switch ($bsnss_pack_type){
				case  "1":
					$ret += $CI->config->item('bsnss_pack_100');
					break;
				case  "2":
					$ret += $CI->config->item('bsnss_pack_200');
					break;
				case  "3":
					$ret += $CI->config->item('bsnss_pack_300');
					break;
				default:
			}
		}
		return  $ret;
	}

	/*
	 * Option(税込)取得
	*
	* $eco					ecoフラグ
	* $vod					vodフラグ
	* $bsnss_pack_flag		bisnessPackフラグプ
	* $bsnss_pack_type		bisnessPackタイプ
	* $tax_rate				消費税率
	*
	*/
	function getOptionIncTax($eco, $vod, $bsnss_pack_flag, $bsnss_pack_type, $tax_rate){
		$CI =& get_instance();
		$ret = 0;
		//ecoとvodは税込
		if ($eco == "Y"){
			$ret -= $CI->config->item('eco_amnt');
		}
		if ($vod == "Y"){
			$ret += $CI->config->item('vod_amnt');
		}
		//ビジネスパックは税抜き
		if ($bsnss_pack_flag === "Y"){
			switch ($bsnss_pack_type){
				case  "1":
					$ret += $this->getAmountIncTax($CI->config->item('bsnss_pack_100'), $tax_rate);
					break;
				case  "2":
					$ret += $this->getAmountIncTax($CI->config->item('bsnss_pack_200'), $tax_rate);
					break;
				case  "3":
					$ret += $this->getAmountIncTax($CI->config->item('bsnss_pack_300'), $tax_rate);
					break;
				default:
			}
		}
		return  $ret;
	}
	/*
	 * Result,log_rec編集
	*
	* $posts
	* $api_mode
	* $result
	* $log_rec
	*
	*/
	function editResultLogRec($posts, $api_mode, $result, $log_rec){
		$result_out = $result;
		$log_rec_out = $log_rec;
		$CI =& get_instance();
		// Edit result,log_rec
		$idx = 1;
		foreach ($posts as $key => $value){
			$result_out[$key] = $value;
			$log_rec_out['prmtr_vls_'.sprintf("%02d", $idx)] = $this->getLogFieldValue($value);
			$idx++;
		}
		$log_rec_out['api_name'] = $api_mode; // API名
		// 予約者情報UID
		if (($CI->input->post('rsrvsPrsnUid')) != false) {
			$log_rec_out['rservs_prsn_uid']    = $CI->input->post('rsrvsPrsnUid');
		}
		// 会員番号
		if (($CI->input->post('mmbrshpNmbr')) != false) {
			$log_rec_out['mmbrshp_nmbr']    = $CI->input->post('mmbrshpNmbr');
		}
		// 予約者情報UID
		if (($CI->input->post('htlCode')) != false) {
			$log_rec_out['htl_code']    = $CI->input->post('htlCode');
		}
		$result_log['result'] = $result_out;
		$result_log['log_rec'] = $log_rec_out;
		return  $result_log;
	}

	/*
	 * logフィールド編集
	*
	* $rqst			リクエスト
	*
	*/
	function getLogFieldValue($rqst){
		$CI =& get_instance();
		$delimita_rec = '|';
		$delimita_fld = '.';
		if (is_array($rqst)){
			$recs = '';
			foreach ($rqst as $val1) {
				$flds = '';
				if (is_array($val1)){
					foreach ($val1 as $val2) {
						if ($CI->api_com_util->isNotNull($flds)){
							$flds = $flds.$delimita_fld;
						}
						$flds = $flds.$val2;
					}
				} else {
					$flds = $val1;
				}
				if ($CI->api_com_util->isNotNull($recs)){
					$recs = $recs.$delimita_rec;
				}
				$recs = $recs.$flds;
			}
			return $recs;
		}else{
			return $rqst;
		}
	}

	/*
	 * 経度・緯度から距離取得(世界測地系) Km
	*
	* $lttd_fr				緯度（開始地点）
	* $lngtd_fr				経度（開始地点）
	* $lttd_to				緯度（終了地点）
	* $lngtd_to				経度（終了地点）
	*
	*/
	function getDistanceKm($lttd_fr, $lngtd_fr, $lttd_to, $lngtd_to){
		$CI =& get_instance();
		$distance = $this->getDistance($lttd_fr, $lngtd_fr, $lttd_to, $lngtd_to);
		$distance = round($distance, 3) / 1000;
		// 距離取得(km)
		return $distance;
	}

	/*
	 * 経度・緯度から距離取得(世界測地系) Km
	*
	* $params			postdata
	* $lttd				緯度（終了地点）
	* $lngtd			経度（終了地点）
	*
	*/

	function getDistanceKmByParam($params, $lttd, $lngtd){
		$CI =& get_instance();
		if ($CI->api_com_util->isSetNotNull($params, 'lttd') && $CI->api_com_util->isSetNotNull($params, 'lngtd')){
			return  $this->getDistanceKm($params['lttd'],$params['lngtd'],$lttd,$lngtd);
		}
		return null;
	}

	/*
	 * 経度・緯度から距離取得(世界測地系)
	*
	* $lttd_fr				緯度（開始地点）
	* $lngtd_fr				経度（開始地点）
	* $lttd_to				緯度（終了地点）
	* $lngtd_to				経度（終了地点）
	*
	*/
	function getDistance($lttd_fr, $lngtd_fr, $lttd_to, $lngtd_to){
		$lttd_df = deg2rad(abs($lttd_fr - $lttd_to));    // 開始地点～終了地点の緯度差
		$lngtd_df = deg2rad(abs($lngtd_fr - $lngtd_to)); // 開始地点～終了地点の経度差
		$lttd_avg = deg2rad($lttd_fr + (($lttd_to - $lttd_fr) / 2)); // 開始地点～終了地点の平均

		$meridian = 0; // 子午線曲率半径
		$prime = 0;    // 卯酉線曲率半径
		$tmp = 1.0 - 0.00669438 * pow(sin($lttd_avg), 2);
		$meridian = 6335439.0 / sqrt(pow($tmp, 3));
		$prime    = 6378137.0 / sqrt($tmp);

		// 距離取得(m)
		$distance = sqrt(pow($meridian * $lttd_df, 2) + pow($prime * cos($lttd_avg) * $lngtd_df, 2));

		return $distance;
	}

	/*
	 * POSTデータ取得
	*
	* $api_mode				APIモード
	*
	*/
	function getPostData($api_mode) {
		$CI =& get_instance();
		$posts = array();

		$input_post = $CI->input->post();
		// APIリクエスト共通項目
		$posts['key'] = $this->getPostValue($input_post,'key');                          // APIキー
		$posts['applctnVrsnNmbr'] = $this->getPostValue($input_post,'applctnVrsnNmbr');  // アプリのバージョン
		$posts['osType'] = $this->getPostValue($input_post,'osType');                    // OSのタイプ（I:iOS /  A: Androido）
		$posts['osVrsn'] = $this->getPostValue($input_post,'osVrsn');                    // OSバージョン
		$posts['mdl'] = $this->getPostValue($input_post,'mdl');                          // 機種名
		$posts['dvcTkn'] = $this->getPostValue($input_post,'dvcTkn');                    // iOSのdevice token（osTypeが“I(iOS)”の場合必須）
		$posts['rgstrtnId'] = $this->getPostValue($input_post,'rgstrtnId');              // Androidoのregistration id（osTypeが“A(Androido)”の場合必須）
		$posts['lngg'] = $this->getPostValue($input_post,'lngg');                        // 端末設定言語。（ISO639-1のフォーマット形式(ja、zw-TWなど)）

		switch ($api_mode){
			// search_point_api
			case  Api_const::A001:
				$posts['mmbrshpNmbr'] = $this->getPostValue($input_post,'mmbrshpNmbr');                               // 東横インクラブカードの会員番号。（“-”付で入力すること）
				break;
				// search_hotel_coordinate_api
			case  Api_const::A002:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				break;
				// search_hotel_keyword_api
			case  Api_const::A003:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['kywrd'] = $this->getPostValue($input_post,'kywrd');                                           // 検索キーワード
				break;
				// search_hotel_area_api
			case  Api_const::A004:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				break;
				// search_hotel_api
			case  Api_const::A005:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['smkngFlag'] = $this->getPostValue($input_post,'smkngFlag');                                   // 禁煙・喫煙フラグ（Y:禁煙 / N:喫煙）
				$posts['lngtd'] = $this->getPostValue($input_post,'lngtd');                                           // 目的地の経度
				$posts['lttd'] = $this->getPostValue($input_post,'lttd');                                             // 目的地の緯度
				$posts['dstnc'] = $this->getPostValue($input_post,'dstnc');                                           // 目的地からの距離
				//20141211 引数roomType追加
				$posts['roomType'] = $this->getPostValue($input_post,'roomType');                                     // 客室タイプ
				break;
				// search_room_type_api
			case  Api_const::A006:
				//20141211 仕様変更により修正			
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['smkngFlag'] = $this->getPostValue($input_post,'smkngFlag');                                   // 喫煙・禁煙区分（禁煙：N、喫煙：Y）
				$posts['mode'] = $this->getPostValue($input_post,'mode');                                             // 検索方法
				$posts['kywrd'] = $this->getPostValue($input_post,'kywrd');                                           // 目的地のキーワード
				$posts['cntryCode'] = $this->getPostValue($input_post,'cntryCode');                                   // 国コード
				$posts['areaCode'] = $this->getPostValue($input_post,'areaCode');                                     // エリアコード
				$posts['sttCode'] = $this->getPostValue($input_post,'sttCode');                                       // 都道府県コード
				$posts['cityCode'] = $this->getPostValue($input_post,'cityCode');                                     // 都市コード
				$posts['lngtd'] = $this->getPostValue($input_post,'lngtd');                                           // 現在地の経度。degree形式で設定する。
				$posts['lttd'] = $this->getPostValue($input_post,'lttd');                                             // 現在地の緯度。degree形式で設定する。
				$posts['dstnc'] = $this->getPostValue($input_post,'dstnc');                                           // 検出範囲(距離　単位Km)
				$posts['roomType'] = $this->getPostValue($input_post,'roomType');                                     // 客室タイプ
				break;

				// search_hotel_details_api
			case  Api_const::A007:
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');							  //予約者情報UID
				break;
				// search_hotel_vacant_api
			case  Api_const::A008:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['smkngFlag'] = $this->getPostValue($input_post,'smkngFlag');                                   // 喫煙・禁煙区分（禁煙：N、喫煙：Y）
				$posts['mode'] = $this->getPostValue($input_post,'mode');                                             // 検索方法
				$posts['kywrd'] = $this->getPostValue($input_post,'kywrd');                                           // 目的地のキーワード
				$posts['cntryCode'] = $this->getPostValue($input_post,'cntryCode');                                   // 国コード
				$posts['areaCode'] = $this->getPostValue($input_post,'areaCode');                                     // エリアコード
				$posts['sttCode'] = $this->getPostValue($input_post,'sttCode');                                       // 都道府県コード
				$posts['lngtd'] = $this->getPostValue($input_post,'lngtd');                                           // 現在地の経度。degree形式で設定する。
				$posts['lttd'] = $this->getPostValue($input_post,'lttd');                                             // 現在地の緯度。degree形式で設定する。
				$posts['cityCode'] = $this->getPostValue($input_post,'cityCode');                                     // 都市コード
				$posts['dstnc'] = $this->getPostValue($input_post,'dstnc');                                           // 検出範囲(距離　単位Km)
				$posts['roomType'] = $this->getPostValue($input_post,'roomType');                                     // 客室タイプ
				break;
				// search_room_type_vacant_api
			case  Api_const::A009:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['smkngFlag'] = $this->getPostValue($input_post,'smkngFlag');                                   // 喫煙・禁煙区分（禁煙：N、喫煙：Y）
				break;
				// search_room_type_details_api
			case  Api_const::A010:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['roomType'] = $this->getPostValue($input_post,'roomType');                                     // 部屋タイプコード
				$posts['planCode'] = $this->getPostValue($input_post,'planCode');                                     // プランコード
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				break;
				// search_room_type_price_api
			case  Api_const::A011:
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)
				$posts['chcknDate'] = $this->getPostValue($input_post,'chcknDate');                                   // チェックイン日付。（形式：YYYYMMDD形式）
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['roomType'] = $this->getPostValue($input_post,'roomType');                                     // 部屋タイプコード
				$posts['planCode'] = $this->getPostValue($input_post,'planCode');                                     // プランコード
				$posts['nmbrNghts'] = $this->getPostValue($input_post,'nmbrNghts');                                   // 宿泊日数
				$posts['nmbrPpl'] = $this->getPostValue($input_post,'nmbrPpl');                                       // 宿泊者数(1室)
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['ecoFlag'] = $this->getPostValue($input_post,'ecoFlag');                                       // eco適用フラグ　(Y:適用する / N:適用しない)
				$posts['ecoDtsList'] = $this->getPostValue($input_post,'ecoDtsList');                                 // ecoを適用するか宿泊日毎に配列で設定する。
				$posts['vodFlag'] = $this->getPostValue($input_post,'vodFlag');                                       // VOD適用フラグ　(Y:適用する / N:適用しない)
				$posts['bsnssPackFlag'] = $this->getPostValue($input_post,'bsnssPackFlag');                           // ビジネスパック適用フラグ
				$posts['bsnssPackType'] = $this->getPostValue($input_post,'bsnssPackType');                           // ビジネスパックタイプ ( 1:100 / 2:200 / 3:300 )
				break;
				// check_booking_api
			case  Api_const::A012:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['mode'] = $this->getPostValue($input_post,'mode');                                       	  //　モード
				$posts['room1_rsrvtnNmbr'] = $this->getPostValue($input_post,'room1_rsrvtnNmbr');					  // 1部屋目の予約番号
				$posts['room1_chcknDate'] = $this->getPostValue($input_post,'room1_chcknDate');                       // 1部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room1_chcktDate'] = $this->getPostValue($input_post,'room1_chcktDate');                       // 1部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room1_roomType'] = $this->getPostValue($input_post,'room1_roomType');                         // 1部屋目の部屋タイプコード
				$posts['room1_planCode'] = $this->getPostValue($input_post,'room1_planCode');                         // 1部屋目のプランタイプコード
				$posts['room1_nmbrPpl'] = $this->getPostValue($input_post,'room1_nmbrPpl');                           // 1部屋目の1室の宿泊者数
				$posts['room1_fmlyName'] = $this->getPostValue($input_post,'room1_fmlyName');                         // 1部屋目の姓(アルファベット)
				$posts['room1_frstName'] = $this->getPostValue($input_post,'room1_frstName');                         // 1部屋目の名(アルファベット)
				$posts['room1_sex'] = $this->getPostValue($input_post,'room1_sex');                                   // 1部屋目の宿泊者性別
				$posts['room1_mmbrshpFlag'] = $this->getPostValue($input_post,'room1_mmbrshpFlag');                   // 1部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room1_mmbrshpNmbr'] = $this->getPostValue($input_post,'room1_mmbrshpNmbr');                   // 1部屋目の会員番号の配列
				$posts['room1_ntnltyCode'] = $this->getPostValue($input_post,'room1_ntnltyCode');                     // 1部屋目の国籍コード
				$posts['room1_phnNmbr'] = $this->getPostValue($input_post,'room1_phnNmbr');                           // 1部屋目の電話番号
				$posts['room1_ecoFlag'] = $this->getPostValue($input_post,'room1_ecoFlag');                           // 1部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room1_ecoDtsList'] = $this->getPostValue($input_post,'room1_ecoDtsList');                     // 1部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room1_ecoChckn'] = $this->getPostValue($input_post,'room1_ecoChckn');                         // 1部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room1_vodFlag'] = $this->getPostValue($input_post,'room1_vodFlag');                           // 1部屋目のVOD適用フラグ
				$posts['room1_bsnssPackFlag'] = $this->getPostValue($input_post,'room1_bsnssPackFlag');               // 1部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room1_bsnssPackType'] = $this->getPostValue($input_post,'room1_bsnssPackType');               // 1部屋目のビジネスパック種別
				$posts['room1_chldrnShrngBed'] = $this->getPostValue($input_post,'room1_chldrnShrngBed');             // 1部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room2_rsrvtnNmbr'] = $this->getPostValue($input_post,'room2_rsrvtnNmbr');					  // 2部屋目の予約番号
				$posts['room2_chcknDate'] = $this->getPostValue($input_post,'room2_chcknDate');                       // 2部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room2_chcktDate'] = $this->getPostValue($input_post,'room2_chcktDate');                       // 2部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room2_roomType'] = $this->getPostValue($input_post,'room2_roomType');                         // 2部屋目の部屋タイプコード
				$posts['room2_planCode'] = $this->getPostValue($input_post,'room2_planCode');                         // 2部屋目のプランタイプコード
				$posts['room2_nmbrPpl'] = $this->getPostValue($input_post,'room2_nmbrPpl');                           // 2部屋目の1室の宿泊者数
				$posts['room2_fmlyName'] = $this->getPostValue($input_post,'room2_fmlyName');                         // 2部屋目の姓(アルファベット)
				$posts['room2_frstName'] = $this->getPostValue($input_post,'room2_frstName');                         // 2部屋目の名(アルファベット)
				$posts['room2_sex'] = $this->getPostValue($input_post,'room2_sex');                                   // 2部屋目の宿泊者性別
				$posts['room2_mmbrshpFlag'] = $this->getPostValue($input_post,'room2_mmbrshpFlag');                   // 2部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room2_mmbrshpNmbr'] = $this->getPostValue($input_post,'room2_mmbrshpNmbr');                   // 2部屋目の会員番号の配列
				$posts['room2_ntnltyCode'] = $this->getPostValue($input_post,'room2_ntnltyCode');                     // 2部屋目の国籍コード
				$posts['room2_phnNmbr'] = $this->getPostValue($input_post,'room2_phnNmbr');                           // 2部屋目の電話番号
				$posts['room2_ecoFlag'] = $this->getPostValue($input_post,'room2_ecoFlag');                           // 2部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room2_ecoDtsList'] = $this->getPostValue($input_post,'room2_ecoDtsList');                     // 2部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room2_ecoChckn'] = $this->getPostValue($input_post,'room2_ecoChckn');                         // 2部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room2_vodFlag'] = $this->getPostValue($input_post,'room2_vodFlag');                           // 2部屋目のVOD適用フラグ
				$posts['room2_bsnssPackFlag'] = $this->getPostValue($input_post,'room2_bsnssPackFlag');               // 2部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room2_bsnssPackType'] = $this->getPostValue($input_post,'room2_bsnssPackType');               // 2部屋目のビジネスパック種別
				$posts['room2_chldrnShrngBed'] = $this->getPostValue($input_post,'room2_chldrnShrngBed');             // 2部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room3_rsrvtnNmbr'] = $this->getPostValue($input_post,'room3_rsrvtnNmbr');					  // 3部屋目の予約番号
				$posts['room3_chcknDate'] = $this->getPostValue($input_post,'room3_chcknDate');                       // 3部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room3_chcktDate'] = $this->getPostValue($input_post,'room3_chcktDate');                       // 3部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room3_roomType'] = $this->getPostValue($input_post,'room3_roomType');                         // 3部屋目の部屋タイプコード
				$posts['room3_planCode'] = $this->getPostValue($input_post,'room3_planCode');                         // 3部屋目のプランタイプコード
				$posts['room3_nmbrPpl'] = $this->getPostValue($input_post,'room3_nmbrPpl');                           // 3部屋目の1室の宿泊者数
				$posts['room3_fmlyName'] = $this->getPostValue($input_post,'room3_fmlyName');                         // 3部屋目の姓(アルファベット)
				$posts['room3_frstName'] = $this->getPostValue($input_post,'room3_frstName');                         // 3部屋目の名(アルファベット)
				$posts['room3_sex'] = $this->getPostValue($input_post,'room3_sex');                                   // 3部屋目の宿泊者性別			
				$posts['room3_mmbrshpFlag'] = $this->getPostValue($input_post,'room3_mmbrshpFlag');                   // 3部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room3_mmbrshpNmbr'] = $this->getPostValue($input_post,'room3_mmbrshpNmbr');                   // 3部屋目の会員番号の配列
				$posts['room3_ntnltyCode'] = $this->getPostValue($input_post,'room3_ntnltyCode');                     // 3部屋目の国籍コード
				$posts['room3_phnNmbr'] = $this->getPostValue($input_post,'room3_phnNmbr');                           // 3部屋目の電話番号
				$posts['room3_ecoFlag'] = $this->getPostValue($input_post,'room3_ecoFlag');                           // 3部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room3_ecoDtsList'] = $this->getPostValue($input_post,'room3_ecoDtsList');                     // 3部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room3_ecoChckn'] = $this->getPostValue($input_post,'room3_ecoChckn');                         // 3部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room3_vodFlag'] = $this->getPostValue($input_post,'room3_vodFlag');                           // 3部屋目のVOD適用フラグ
				$posts['room3_bsnssPackFlag'] = $this->getPostValue($input_post,'room3_bsnssPackFlag');               // 3部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room3_bsnssPackType'] = $this->getPostValue($input_post,'room3_bsnssPackType');               // 3部屋目のビジネスパック種別
				$posts['room3_chldrnShrngBed'] = $this->getPostValue($input_post,'room3_chldrnShrngBed');             // 3部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room4_rsrvtnNmbr'] = $this->getPostValue($input_post,'room4_rsrvtnNmbr');					  // 4部屋目の予約番号
				$posts['room4_chcknDate'] = $this->getPostValue($input_post,'room4_chcknDate');                       // 4部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room4_chcktDate'] = $this->getPostValue($input_post,'room4_chcktDate');                       // 4部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room4_roomType'] = $this->getPostValue($input_post,'room4_roomType');                         // 4部屋目の部屋タイプコード
				$posts['room4_planCode'] = $this->getPostValue($input_post,'room4_planCode');                         // 4部屋目のプランタイプコード
				$posts['room4_nmbrPpl'] = $this->getPostValue($input_post,'room4_nmbrPpl');                           // 4部屋目の1室の宿泊者数
				$posts['room4_fmlyName'] = $this->getPostValue($input_post,'room4_fmlyName');                         // 4部屋目の姓(アルファベット)
				$posts['room4_frstName'] = $this->getPostValue($input_post,'room4_frstName');                         // 4部屋目の名(アルファベット)
				$posts['room4_sex'] = $this->getPostValue($input_post,'room4_sex');                                   // 4部屋目の宿泊者性別
				$posts['room4_mmbrshpFlag'] = $this->getPostValue($input_post,'room4_mmbrshpFlag');                   // 4部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room4_mmbrshpNmbr'] = $this->getPostValue($input_post,'room4_mmbrshpNmbr');                   // 4部屋目の会員番号の配列
				$posts['room4_ntnltyCode'] = $this->getPostValue($input_post,'room4_ntnltyCode');                     // 4部屋目の国籍コード
				$posts['room4_phnNmbr'] = $this->getPostValue($input_post,'room4_phnNmbr');                           // 4部屋目の電話番号
				$posts['room4_ecoFlag'] = $this->getPostValue($input_post,'room4_ecoFlag');                           // 4部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room4_ecoDtsList'] = $this->getPostValue($input_post,'room4_ecoDtsList');                     // 4部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room4_ecoChckn'] = $this->getPostValue($input_post,'room4_ecoChckn');                         // 4部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room4_vodFlag'] = $this->getPostValue($input_post,'room4_vodFlag');                           // 4部屋目のVOD適用フラグ
				$posts['room4_bsnssPackFlag'] = $this->getPostValue($input_post,'room4_bsnssPackFlag');               // 4部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room4_bsnssPackType'] = $this->getPostValue($input_post,'room4_bsnssPackType');               // 4部屋目のビジネスパック種別
				$posts['room4_chldrnShrngBed'] = $this->getPostValue($input_post,'room4_chldrnShrngBed');             // 4部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room1_chcknTime'] = $this->getPostValue($input_post,'room1_chcknTime');                       // 1部屋目のチェックイン時間
				$posts['ttlPrc'] = $this->getPostValue($input_post,'ttlPrc');                                		  // 合計金額（税抜き）
				$posts['ttlPrcIncldngTax'] = $this->getPostValue($input_post,'ttlPrcIncldngTax');               // 合計金額（税込み）
				$posts['crdtCardNmbr'] = $this->getPostValue($input_post,'crdtCardNmbr');                             // クレジットカード番号
				$posts['crdtCardHldr'] = $this->getPostValue($input_post,'crdtCardHldr');                             // クレジットカード名義
				$posts['crdtCardexprtnDate'] = $this->getPostValue($input_post,'crdtCardexprtnDate');                 // クレジットカード有効期限
				break;
				// register_reservation_api
			case  Api_const::A013:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数
				$posts['room1_chcknDate'] = $this->getPostValue($input_post,'room1_chcknDate');                       // 1部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room1_chcktDate'] = $this->getPostValue($input_post,'room1_chcktDate');                       // 1部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room1_roomType'] = $this->getPostValue($input_post,'room1_roomType');                         // 1部屋目の部屋タイプコード
				$posts['room1_planCode'] = $this->getPostValue($input_post,'room1_planCode');                         // 1部屋目のプランタイプコード
				$posts['room1_nmbrPpl'] = $this->getPostValue($input_post,'room1_nmbrPpl');                           // 1部屋目の1室の宿泊者数
				$posts['room1_fmlyName'] = $this->getPostValue($input_post,'room1_fmlyName');                         // 1部屋目の姓(アルファベット)
				$posts['room1_frstName'] = $this->getPostValue($input_post,'room1_frstName');                         // 1部屋目の名(アルファベット)
				$posts['room1_sex'] = $this->getPostValue($input_post,'room1_sex');                                   // 1部屋目の性別
				$posts['room1_mmbrshpFlag'] = $this->getPostValue($input_post,'room1_mmbrshpFlag');                   // 1部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room1_mmbrshpNmbr'] = $this->getPostValue($input_post,'room1_mmbrshpNmbr');                   // 1部屋目の会員番号の配列
				$posts['room1_ntnltyCode'] = $this->getPostValue($input_post,'room1_ntnltyCode');                     // 1部屋目の国籍コード
				$posts['room1_phnNmbr'] = $this->getPostValue($input_post,'room1_phnNmbr');                           // 1部屋目の電話番号
				$posts['room1_ecoFlag'] = $this->getPostValue($input_post,'room1_ecoFlag');                           // 1部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room1_ecoDtsList'] = $this->getPostValue($input_post,'room1_ecoDtsList');                     // 1部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room1_ecoChckn'] = $this->getPostValue($input_post,'room1_ecoChckn');                         // 1部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room1_vodFlag'] = $this->getPostValue($input_post,'room1_vodFlag');                           // 1部屋目のVOD適用フラグ
				$posts['room1_bsnssPackFlag'] = $this->getPostValue($input_post,'room1_bsnssPackFlag');               // 1部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room1_bsnssPackType'] = $this->getPostValue($input_post,'room1_bsnssPackType');               // 1部屋目のビジネスパック種別
				$posts['room1_chldrnShrngBed'] = $this->getPostValue($input_post,'room1_chldrnShrngBed');             // 1部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room1_chcknTime'] = $this->getPostValue($input_post,'room1_chcknTime');                       // 1部屋目のチェックイン時間
				$posts['room1_prcList'] = $this->getPostValue($input_post,'room1_prcList');                           // 1部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room1_prcIncldngTaxList'] = $this->getPostValue($input_post,'room1_prcIncldngTaxList');       // 1部屋目の税込室料の配列（1泊毎に設定）
				$posts['room1_optnPrc'] = $this->getPostValue($input_post,'room1_optnPrc');                           // 1部屋目のオプション料金（税込）
				$posts['room1_sbttlPrc'] = $this->getPostValue($input_post,'room1_sbttlPrc');                         // 1部屋目の合計金額（税抜）
				$posts['room1_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room1_sbttlPrcIncldngTax');     // 1部屋目の合計金額（税込）
				$posts['room1_receiptType'] = $this->getPostValue($input_post,'room1_receiptType');                 // 領収書種別				
				$posts['room1_receiptName'] = $this->getPostValue($input_post,'room1_receiptName');                 // 領収書名義

				$posts['room2_chcknDate'] = $this->getPostValue($input_post,'room2_chcknDate');                       // 2部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room2_chcktDate'] = $this->getPostValue($input_post,'room2_chcktDate');                       // 2部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room2_roomType'] = $this->getPostValue($input_post,'room2_roomType');                         // 2部屋目の部屋タイプコード
				$posts['room2_planCode'] = $this->getPostValue($input_post,'room2_planCode');                         // 2部屋目のプランタイプコード
				$posts['room2_nmbrPpl'] = $this->getPostValue($input_post,'room2_nmbrPpl');                           // 2部屋目の1室の宿泊者数
				$posts['room2_fmlyName'] = $this->getPostValue($input_post,'room2_fmlyName');                         // 2部屋目の姓(アルファベット)
				$posts['room2_frstName'] = $this->getPostValue($input_post,'room2_frstName');                         // 2部屋目の名(アルファベット)
				$posts['room2_sex'] = $this->getPostValue($input_post,'room2_sex');                                   // 2部屋目の性別
				$posts['room2_mmbrshpFlag'] = $this->getPostValue($input_post,'room2_mmbrshpFlag');                   // 2部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room2_mmbrshpNmbr'] = $this->getPostValue($input_post,'room2_mmbrshpNmbr');                   // 2部屋目の会員番号の配列
				$posts['room2_ntnltyCode'] = $this->getPostValue($input_post,'room2_ntnltyCode');                     // 2部屋目の国籍コード
				$posts['room2_phnNmbr'] = $this->getPostValue($input_post,'room2_phnNmbr');                           // 2部屋目の電話番号
				$posts['room2_ecoFlag'] = $this->getPostValue($input_post,'room2_ecoFlag');                           // 2部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room2_ecoDtsList'] = $this->getPostValue($input_post,'room2_ecoDtsList');                     // 2部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room2_ecoChckn'] = $this->getPostValue($input_post,'room2_ecoChckn');                         // 2部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room2_vodFlag'] = $this->getPostValue($input_post,'room2_vodFlag');                           // 2部屋目のVOD適用フラグ
				$posts['room2_bsnssPackFlag'] = $this->getPostValue($input_post,'room2_bsnssPackFlag');               // 2部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room2_bsnssPackType'] = $this->getPostValue($input_post,'room2_bsnssPackType');               // 2部屋目のビジネスパック種別
				$posts['room2_chldrnShrngBed'] = $this->getPostValue($input_post,'room2_chldrnShrngBed');             // 2部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room2_chcknTime'] = $this->getPostValue($input_post,'room2_chcknTime');                       // 2部屋目のチェックイン時間
				$posts['room2_prcList'] = $this->getPostValue($input_post,'room2_prcList');                           // 2部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room2_prcIncldngTaxList'] = $this->getPostValue($input_post,'room2_prcIncldngTaxList');       // 2部屋目の税込室料の配列（1泊毎に設定）
				$posts['room2_optnPrc'] = $this->getPostValue($input_post,'room2_optnPrc');                           // 2部屋目のオプション料金（税込）
				$posts['room2_sbttlPrc'] = $this->getPostValue($input_post,'room2_sbttlPrc');                         // 2部屋目の合計金額（税抜）
				$posts['room2_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room2_sbttlPrcIncldngTax');     // 2部屋目の合計金額（税込）
				$posts['room2_receiptType'] = $this->getPostValue($input_post,'room2_receiptType');                 // 領収書種別				
				$posts['room2_receiptName'] = $this->getPostValue($input_post,'room2_receiptName');                 // 領収書名義

				$posts['room3_chcknDate'] = $this->getPostValue($input_post,'room3_chcknDate');                       // 3部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room3_chcktDate'] = $this->getPostValue($input_post,'room3_chcktDate');                       // 3部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room3_roomType'] = $this->getPostValue($input_post,'room3_roomType');                         // 3部屋目の部屋タイプコード
				$posts['room3_planCode'] = $this->getPostValue($input_post,'room3_planCode');                         // 3部屋目のプランタイプコード				
				$posts['room3_nmbrPpl'] = $this->getPostValue($input_post,'room3_nmbrPpl');                           // 3部屋目の1室の宿泊者数
				$posts['room3_fmlyName'] = $this->getPostValue($input_post,'room3_fmlyName');                         // 3部屋目の姓(アルファベット)
				$posts['room3_frstName'] = $this->getPostValue($input_post,'room3_frstName');                         // 3部屋目の名(アルファベット)
				$posts['room3_sex'] = $this->getPostValue($input_post,'room3_sex');                                   // 3部屋目の性別
				$posts['room3_mmbrshpFlag'] = $this->getPostValue($input_post,'room3_mmbrshpFlag');                   // 3部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room3_mmbrshpNmbr'] = $this->getPostValue($input_post,'room3_mmbrshpNmbr');                   // 3部屋目の会員番号の配列
				$posts['room3_ntnltyCode'] = $this->getPostValue($input_post,'room3_ntnltyCode');                     // 3部屋目の国籍コード
				$posts['room3_phnNmbr'] = $this->getPostValue($input_post,'room3_phnNmbr');                           // 3部屋目の電話番号
				$posts['room3_ecoFlag'] = $this->getPostValue($input_post,'room3_ecoFlag');                           // 3部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room3_ecoDtsList'] = $this->getPostValue($input_post,'room3_ecoDtsList');                     // 3部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room3_ecoChckn'] = $this->getPostValue($input_post,'room3_ecoChckn');                         // 3部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room3_vodFlag'] = $this->getPostValue($input_post,'room3_vodFlag');                           // 3部屋目のVOD適用フラグ
				$posts['room3_bsnssPackFlag'] = $this->getPostValue($input_post,'room3_bsnssPackFlag');               // 3部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room3_bsnssPackType'] = $this->getPostValue($input_post,'room3_bsnssPackType');               // 3部屋目のビジネスパック種別
				$posts['room3_chldrnShrngBed'] = $this->getPostValue($input_post,'room3_chldrnShrngBed');             // 3部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room3_chcknTime'] = $this->getPostValue($input_post,'room3_chcknTime');                       // 3部屋目のチェックイン時間
				$posts['room3_prcList'] = $this->getPostValue($input_post,'room3_prcList');                           // 3部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room3_prcIncldngTaxList'] = $this->getPostValue($input_post,'room3_prcIncldngTaxList');       // 3部屋目の税込室料の配列（1泊毎に設定）
				$posts['room3_optnPrc'] = $this->getPostValue($input_post,'room3_optnPrc');                           // 3部屋目のオプション料金（税込）
				$posts['room3_sbttlPrc'] = $this->getPostValue($input_post,'room3_sbttlPrc');                         // 3部屋目の合計金額（税抜）
				$posts['room3_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room3_sbttlPrcIncldngTax');     // 3部屋目の合計金額（税込）
				$posts['room3_receiptType'] = $this->getPostValue($input_post,'room3_receiptType');                 // 領収書種別				
				$posts['room3_receiptName'] = $this->getPostValue($input_post,'room3_receiptName');                 // 領収書名義

				$posts['room4_chcknDate'] = $this->getPostValue($input_post,'room4_chcknDate');                       // 4部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room4_chcktDate'] = $this->getPostValue($input_post,'room4_chcktDate');                       // 4部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room4_roomType'] = $this->getPostValue($input_post,'room4_roomType');                         // 4部屋目の部屋タイプコード
				$posts['room4_planCode'] = $this->getPostValue($input_post,'room4_planCode');                         // 4部屋目のプランタイプコード				
				$posts['room4_nmbrPpl'] = $this->getPostValue($input_post,'room4_nmbrPpl');                           // 4部屋目の1室の宿泊者数
				$posts['room4_fmlyName'] = $this->getPostValue($input_post,'room4_fmlyName');                         // 4部屋目の姓(アルファベット)
				$posts['room4_frstName'] = $this->getPostValue($input_post,'room4_frstName');                         // 4部屋目の名(アルファベット)
				$posts['room4_sex'] = $this->getPostValue($input_post,'room4_sex');                                   // 4部屋目の性別
				$posts['room4_mmbrshpFlag'] = $this->getPostValue($input_post,'room4_mmbrshpFlag');                   // 4部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room4_mmbrshpNmbr'] = $this->getPostValue($input_post,'room4_mmbrshpNmbr');                   // 4部屋目の会員番号の配列
				$posts['room4_ntnltyCode'] = $this->getPostValue($input_post,'room4_ntnltyCode');                     // 4部屋目の国籍コード
				$posts['room4_phnNmbr'] = $this->getPostValue($input_post,'room4_phnNmbr');                           // 4部屋目の電話番号
				$posts['room4_ecoFlag'] = $this->getPostValue($input_post,'room4_ecoFlag');                           // 4部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room4_ecoDtsList'] = $this->getPostValue($input_post,'room4_ecoDtsList');                     // 4部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room4_ecoChckn'] = $this->getPostValue($input_post,'room4_ecoChckn');                         // 4部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room4_vodFlag'] = $this->getPostValue($input_post,'room4_vodFlag');                           // 4部屋目のVOD適用フラグ
				$posts['room4_bsnssPackFlag'] = $this->getPostValue($input_post,'room4_bsnssPackFlag');               // 4部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room4_bsnssPackType'] = $this->getPostValue($input_post,'room4_bsnssPackType');               // 4部屋目のビジネスパック種別
				$posts['room4_chldrnShrngBed'] = $this->getPostValue($input_post,'room4_chldrnShrngBed');             // 4部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room4_chcknTime'] = $this->getPostValue($input_post,'room4_chcknTime');                       // 4部屋目のチェックイン時間
				$posts['room4_prcList'] = $this->getPostValue($input_post,'room4_prcList');                           // 4部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room4_prcIncldngTaxList'] = $this->getPostValue($input_post,'room4_prcIncldngTaxList');       // 4部屋目の税込室料の配列（1泊毎に設定）
				$posts['room4_optnPrc'] = $this->getPostValue($input_post,'room4_optnPrc');                           // 4部屋目のオプション料金（税込）
				$posts['room4_sbttlPrc'] = $this->getPostValue($input_post,'room4_sbttlPrc');                         // 4部屋目の合計金額（税抜）
				$posts['room4_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room4_sbttlPrcIncldngTax');     // 4部屋目の合計金額（税込）
				$posts['ttlPrc'] = $this->getPostValue($input_post,'ttlPrc');                                 // 合計金額（税抜き）
				$posts['ttlPrcIncldngTax'] = $this->getPostValue($input_post,'ttlPrcIncldngTax');               // 合計金額（税込み）
				$posts['crdtCardNmbr'] = $this->getPostValue($input_post,'crdtCardNmbr');                             // クレジットカード番号
				$posts['crdtCardHldr'] = $this->getPostValue($input_post,'crdtCardHldr');                             // クレジットカード名義
				$posts['crdtCardexprtnDate'] = $this->getPostValue($input_post,'crdtCardexprtnDate');                 // クレジットカード有効期限
				$posts['room4_receiptType'] = $this->getPostValue($input_post,'room4_receiptType');                 // 領収書種別				
				$posts['room4_receiptName'] = $this->getPostValue($input_post,'room4_receiptName');                 // 領収書名義
				break;
				// search_booking_api
			case  Api_const::A014:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['pageNmbr'] = $this->getPostValue($input_post,'pageNmbr');                                     // ページ番号
				break;
				// search_booking_details_api
			case  Api_const::A015:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['rsrvId'] = $this->getPostValue($input_post,'rsrvId');                                         // 予約ID
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['rsrvtnNmbr'] = $this->getPostValue($input_post,'rsrvtnNmbr');                                 // 予約番号
				break;
				// cancel_reservation_api
			case  Api_const::A016:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['nmbrRsrvtns'] = $this->getPostValue($input_post,'nmbrRsrvtns');                               // キャンセル予約件数
				$posts['rsrvtnId'] = $this->getPostValue($input_post,'rsrvtnId');                                     // 予約ID
				$posts['rsrvId'] = $this->getPostValue($input_post,'rsrvId');                                     	  // 予約ID
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['rsrvtnNmbr'] = $this->getPostValue($input_post,'rsrvtnNmbr');                                 // 予約番号
				break;
				// change_reservation_api
			case  Api_const::A017:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['nmbrRms'] = $this->getPostValue($input_post,'nmbrRms');                                       // 部屋数（最大4部屋まで）
				$posts['room1_htlCode'] = $this->getPostValue($input_post,'room1_htlCode');                           // 1部屋目のホテルコード
				$posts['room1_rsrvId'] = $this->getPostValue($input_post,'room1_rsrvId');                         // 1部屋目の予約ID
				$posts['room1_rsrvtnNmbr'] = $this->getPostValue($input_post,'room1_rsrvtnNmbr');                     // 1部屋目の予約番号
				$posts['room1_chcknDate'] = $this->getPostValue($input_post,'room1_chcknDate');                       // 1部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room1_chcktDate'] = $this->getPostValue($input_post,'room1_chcktDate');                       // 1部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room1_roomType'] = $this->getPostValue($input_post,'room1_roomType');                         // 1部屋目の部屋タイプコード
				$posts['room1_planCode'] = $this->getPostValue($input_post,'room1_planCode');                         // 1部屋目のプランタイプコード				
				$posts['room1_nmbrPpl'] = $this->getPostValue($input_post,'room1_nmbrPpl');                           // 1部屋目の1室の宿泊者数
				$posts['room1_fmlyName'] = $this->getPostValue($input_post,'room1_fmlyName');                         // 1部屋目の姓(アルファベット)
				$posts['room1_frstName'] = $this->getPostValue($input_post,'room1_frstName');                         // 1部屋目の名(アルファベット)
				$posts['room1_sex'] = $this->getPostValue($input_post,'room1_sex');                                   // 1部屋目の性別
				$posts['room1_mmbrshpFlag'] = $this->getPostValue($input_post,'room1_mmbrshpFlag');                   // 1部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room1_mmbrshpNmbr'] = $this->getPostValue($input_post,'room1_mmbrshpNmbr');                   // 1部屋目の会員番号の配列
				$posts['room1_ntnltyCode'] = $this->getPostValue($input_post,'room1_ntnltyCode');                     // 1部屋目の国籍コード
				$posts['room1_phnNmbr'] = $this->getPostValue($input_post,'room1_phnNmbr');                           // 1部屋目の電話番号
				$posts['room1_ecoFlag'] = $this->getPostValue($input_post,'room1_ecoFlag');                           // 1部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room1_ecoDtsList'] = $this->getPostValue($input_post,'room1_ecoDtsList');                     // 1部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room1_ecoChckn'] = $this->getPostValue($input_post,'room1_ecoChckn');                         // 1部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room1_vodFlag'] = $this->getPostValue($input_post,'room1_vodFlag');                           // 1部屋目のVOD適用フラグ
				$posts['room1_bsnssPackFlag'] = $this->getPostValue($input_post,'room1_bsnssPackFlag');               // 1部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room1_bsnssPackType'] = $this->getPostValue($input_post,'room1_bsnssPackType');               // 1部屋目のビジネスパック種別
				$posts['room1_chldrnShrngBed'] = $this->getPostValue($input_post,'room1_chldrnShrngBed');             // 1部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room1_chcknTime'] = $this->getPostValue($input_post,'room1_chcknTime');                       // 1部屋目のチェックイン時間
				$posts['room1_prcList'] = $this->getPostValue($input_post,'room1_prcList');                           // 1部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room1_prcIncldngTaxList'] = $this->getPostValue($input_post,'room1_prcIncldngTaxList');       // 1部屋目の税込室料の配列（1泊毎に設定）
				$posts['room1_optnPrc'] = $this->getPostValue($input_post,'room1_optnPrc');                           // 1部屋目のオプション料金（税込）
				$posts['room1_sbttlPrc'] = $this->getPostValue($input_post,'room1_sbttlPrc');                         // 1部屋目の合計金額（税抜）
				$posts['room1_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room1_sbttlPrcIncldngTax');     // 1部屋目の合計金額（税込）
				$posts['room1_receiptType'] = $this->getPostValue($input_post,'room1_receiptType');                 // 領収書種別				
				$posts['room1_receiptName'] = $this->getPostValue($input_post,'room1_receiptName');                 // 領収書名義

				$posts['room2_htlCode'] = $this->getPostValue($input_post,'room2_htlCode');                           // 2部屋目のホテルコード
				$posts['room2_rsrvId'] = $this->getPostValue($input_post,'room2_rsrvId');                             // 2部屋目の予約ID
				$posts['room2_rsrvtnNmbr'] = $this->getPostValue($input_post,'room2_rsrvtnNmbr');                     // 2部屋目の予約番号
				$posts['room2_chcknDate'] = $this->getPostValue($input_post,'room2_chcknDate');                       // 2部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room2_chcktDate'] = $this->getPostValue($input_post,'room2_chcktDate');                       // 2部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room2_roomType'] = $this->getPostValue($input_post,'room2_roomType');                         // 2部屋目の部屋タイプコード
				$posts['room2_planCode'] = $this->getPostValue($input_post,'room2_planCode');                         // 2部屋目のプランタイプコード
				$posts['room2_nmbrPpl'] = $this->getPostValue($input_post,'room2_nmbrPpl');                           // 2部屋目の1室の宿泊者数
				$posts['room2_fmlyName'] = $this->getPostValue($input_post,'room2_fmlyName');                         // 2部屋目の姓(アルファベット)
				$posts['room2_frstName'] = $this->getPostValue($input_post,'room2_frstName');                         // 2部屋目の名(アルファベット)
				$posts['room2_sex'] = $this->getPostValue($input_post,'room2_sex');                                   // 2部屋目の性別
				$posts['room2_mmbrshpFlag'] = $this->getPostValue($input_post,'room2_mmbrshpFlag');                   // 2部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room2_mmbrshpNmbr'] = $this->getPostValue($input_post,'room2_mmbrshpNmbr');                   // 2部屋目の会員番号の配列
				$posts['room2_ntnltyCode'] = $this->getPostValue($input_post,'room2_ntnltyCode');                     // 2部屋目の国籍コード
				$posts['room2_phnNmbr'] = $this->getPostValue($input_post,'room2_phnNmbr');                           // 2部屋目の電話番号
				$posts['room2_ecoFlag'] = $this->getPostValue($input_post,'room2_ecoFlag');                           // 2部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room2_ecoDtsList'] = $this->getPostValue($input_post,'room2_ecoDtsList');                     // 2部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room2_ecoChckn'] = $this->getPostValue($input_post,'room2_ecoChckn');                         // 2部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room2_vodFlag'] = $this->getPostValue($input_post,'room2_vodFlag');                           // 2部屋目のVOD適用フラグ
				$posts['room2_bsnssPackFlag'] = $this->getPostValue($input_post,'room2_bsnssPackFlag');               // 2部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room2_bsnssPackType'] = $this->getPostValue($input_post,'room2_bsnssPackType');               // 2部屋目のビジネスパック種別
				$posts['room2_chldrnShrngBed'] = $this->getPostValue($input_post,'room2_chldrnShrngBed');             // 2部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room2_chcknTime'] = $this->getPostValue($input_post,'room2_chcknTime');                       // 2部屋目のチェックイン時間
				$posts['room2_prcList'] = $this->getPostValue($input_post,'room2_prcList');                           // 2部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room2_prcIncldngTaxList'] = $this->getPostValue($input_post,'room2_prcIncldngTaxList');       // 2部屋目の税込室料の配列（1泊毎に設定）
				$posts['room2_optnPrc'] = $this->getPostValue($input_post,'room2_optnPrc');                           // 2部屋目のオプション料金（税込）
				$posts['room2_sbttlPrc'] = $this->getPostValue($input_post,'room2_sbttlPrc');                         // 2部屋目の合計金額（税抜）
				$posts['room2_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room2_sbttlPrcIncldngTax');     // 2部屋目の合計金額（税込）
				$posts['room2_receiptType'] = $this->getPostValue($input_post,'room2_receiptType');                 // 領収書種別				
				$posts['room2_receiptName'] = $this->getPostValue($input_post,'room2_receiptName');                 // 領収書名義

				$posts['room3_htlCode'] = $this->getPostValue($input_post,'room3_htlCode');                           // 3部屋目のホテルコード
				$posts['room3_rsrvId'] = $this->getPostValue($input_post,'room3_rsrvId');                         // 3部屋目の予約ID
				$posts['room3_rsrvtnNmbr'] = $this->getPostValue($input_post,'room3_rsrvtnNmbr');                     // 3部屋目の予約番号
				$posts['room3_chcknDate'] = $this->getPostValue($input_post,'room3_chcknDate');                       // 3部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room3_chcktDate'] = $this->getPostValue($input_post,'room3_chcktDate');                       // 3部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room3_roomType'] = $this->getPostValue($input_post,'room3_roomType');                         // 3部屋目の部屋タイプコード
				$posts['room3_planCode'] = $this->getPostValue($input_post,'room3_planCode');                         // 3部屋目のプランタイプコード				
				$posts['room3_nmbrPpl'] = $this->getPostValue($input_post,'room3_nmbrPpl');                           // 3部屋目の1室の宿泊者数
				$posts['room3_fmlyName'] = $this->getPostValue($input_post,'room3_fmlyName');                         // 3部屋目の姓(アルファベット)
				$posts['room3_frstName'] = $this->getPostValue($input_post,'room3_frstName');                         // 3部屋目の名(アルファベット)
				$posts['room3_sex'] = $this->getPostValue($input_post,'room3_sex');                                   // 3部屋目の性別
				$posts['room3_mmbrshpFlag'] = $this->getPostValue($input_post,'room3_mmbrshpFlag');                   // 3部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room3_mmbrshpNmbr'] = $this->getPostValue($input_post,'room3_mmbrshpNmbr');                   // 3部屋目の会員番号の配列
				$posts['room3_ntnltyCode'] = $this->getPostValue($input_post,'room3_ntnltyCode');                     // 3部屋目の国籍コード
				$posts['room3_phnNmbr'] = $this->getPostValue($input_post,'room3_phnNmbr');                           // 3部屋目の電話番号
				$posts['room3_ecoFlag'] = $this->getPostValue($input_post,'room3_ecoFlag');                           // 3部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room3_ecoDtsList'] = $this->getPostValue($input_post,'room3_ecoDtsList');                     // 3部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room3_ecoChckn'] = $this->getPostValue($input_post,'room3_ecoChckn');                         // 3部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room3_vodFlag'] = $this->getPostValue($input_post,'room3_vodFlag');                           // 3部屋目のVOD適用フラグ
				$posts['room3_bsnssPackFlag'] = $this->getPostValue($input_post,'room3_bsnssPackFlag');               // 3部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room3_bsnssPackType'] = $this->getPostValue($input_post,'room3_bsnssPackType');               // 3部屋目のビジネスパック種別
				$posts['room3_chldrnShrngBed'] = $this->getPostValue($input_post,'room3_chldrnShrngBed');             // 3部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room3_chcknTime'] = $this->getPostValue($input_post,'room3_chcknTime');                       // 3部屋目のチェックイン時間
				$posts['room3_prcList'] = $this->getPostValue($input_post,'room3_prcList');                           // 3部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room3_prcIncldngTaxList'] = $this->getPostValue($input_post,'room3_prcIncldngTaxList');       // 3部屋目の税込室料の配列（1泊毎に設定）
				$posts['room3_optnPrc'] = $this->getPostValue($input_post,'room3_optnPrc');                           // 3部屋目のオプション料金（税込）
				$posts['room3_sbttlPrc'] = $this->getPostValue($input_post,'room3_sbttlPrc');                         // 3部屋目の合計金額（税抜）
				$posts['room3_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room3_sbttlPrcIncldngTax');     // 3部屋目の合計金額（税込）
				$posts['room3_receiptType'] = $this->getPostValue($input_post,'room3_receiptType');                 // 領収書種別				
				$posts['room3_receiptName'] = $this->getPostValue($input_post,'room3_receiptName');                 // 領収書名義

				$posts['room4_htlCode'] = $this->getPostValue($input_post,'room4_htlCode');                           // 4部屋目のホテルコード
				$posts['room4_rsrvId'] = $this->getPostValue($input_post,'room4_rsrvId');                         // 4部屋目の予約ID
				$posts['room4_rsrvtnNmbr'] = $this->getPostValue($input_post,'room4_rsrvtnNmbr');                     // 4部屋目の予約番号
				$posts['room4_chcknDate'] = $this->getPostValue($input_post,'room4_chcknDate');                       // 4部屋目のチェックイン日付。（形式：YYYYMMDD形式）
				$posts['room4_chcktDate'] = $this->getPostValue($input_post,'room4_chcktDate');                       // 4部屋目のチェックアウト日付。（形式：YYYYMMDD形式）
				$posts['room4_roomType'] = $this->getPostValue($input_post,'room4_roomType');                         // 4部屋目の部屋タイプコード
				$posts['room4_planCode'] = $this->getPostValue($input_post,'room4_planCode');                         // 4部屋目のプランタイプコード
				$posts['room4_nmbrPpl'] = $this->getPostValue($input_post,'room4_nmbrPpl');                           // 4部屋目の1室の宿泊者数
				$posts['room4_fmlyName'] = $this->getPostValue($input_post,'room4_fmlyName');                         // 4部屋目の姓(アルファベット)
				$posts['room4_frstName'] = $this->getPostValue($input_post,'room4_frstName');                         // 4部屋目の名(アルファベット)
				$posts['room4_sex'] = $this->getPostValue($input_post,'room4_sex');                                   // 4部屋目の性別
				$posts['room4_mmbrshpFlag'] = $this->getPostValue($input_post,'room4_mmbrshpFlag');                   // 4部屋目の会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['room4_mmbrshpNmbr'] = $this->getPostValue($input_post,'room4_mmbrshpNmbr');                   // 4部屋目の会員番号の配列
				$posts['room4_ntnltyCode'] = $this->getPostValue($input_post,'room4_ntnltyCode');                     // 4部屋目の国籍コード
				$posts['room4_phnNmbr'] = $this->getPostValue($input_post,'room4_phnNmbr');                           // 4部屋目の電話番号
				$posts['room4_ecoFlag'] = $this->getPostValue($input_post,'room4_ecoFlag');                           // 4部屋目のｅｃｏフラグ(Y:適用する　/ N:適用しない)
				$posts['room4_ecoDtsList'] = $this->getPostValue($input_post,'room4_ecoDtsList');                     // 4部屋目のecoを適用するか宿泊日毎に配列で設定する。
				$posts['room4_ecoChckn'] = $this->getPostValue($input_post,'room4_ecoChckn');                         // 4部屋目のチェックイン時にｅｃｏ指定するか。指定する:Y / 指定しない:N
				$posts['room4_vodFlag'] = $this->getPostValue($input_post,'room4_vodFlag');                           // 4部屋目のVOD適用フラグ
				$posts['room4_bsnssPackFlag'] = $this->getPostValue($input_post,'room4_bsnssPackFlag');               // 4部屋目のビジネスパック適用フラグ（Y:適用する　/ N:適用しない）
				$posts['room4_bsnssPackType'] = $this->getPostValue($input_post,'room4_bsnssPackType');               // 4部屋目のビジネスパック種別
				$posts['room4_chldrnShrngBed'] = $this->getPostValue($input_post,'room4_chldrnShrngBed');             // 4部屋目のお子様の添い寝フラグ（Y:適用する　/ N:適用しない）
				$posts['room4_chcknTime'] = $this->getPostValue($input_post,'room4_chcknTime');                       // 4部屋目のチェックイン時間
				$posts['room4_prcList'] = $this->getPostValue($input_post,'room4_prcList');                           // 4部屋目の税抜室料の配列（1泊毎に設定）
				$posts['room4_prcIncldngTaxList'] = $this->getPostValue($input_post,'room4_prcIncldngTaxList');       // 4部屋目の税込室料の配列（1泊毎に設定）
				$posts['room4_optnPrc'] = $this->getPostValue($input_post,'room4_optnPrc');                           // 4部屋目のオプション料金（税込）
				$posts['room4_sbttlPrc'] = $this->getPostValue($input_post,'room4_sbttlPrc');                         // 4部屋目の合計金額（税抜）
				$posts['room4_sbttlPrcIncldngTax'] = $this->getPostValue($input_post,'room4_sbttlPrcIncldngTax');     // 4部屋目の合計金額（税込）
				$posts['room4_receiptType'] = $this->getPostValue($input_post,'room4_receiptType');                 // 領収書種別				
				$posts['room4_receiptName'] = $this->getPostValue($input_post,'room4_receiptName');                 // 領収書名義

				$posts['ttlPrc'] = $this->getPostValue($input_post,'ttlPrc');                                 // 合計金額（税抜き）
				$posts['ttlPrcIncldngTax'] = $this->getPostValue($input_post,'ttlPrcIncldngTax');               // 合計金額（税込み）
				$posts['crdtCardNmbr'] = $this->getPostValue($input_post,'crdtCardNmbr');                             // クレジットカード番号
				$posts['crdtCardHldr'] = $this->getPostValue($input_post,'crdtCardHldr');                             // クレジットカード名義
				$posts['crdtCardexprtnDate'] = $this->getPostValue($input_post,'crdtCardexprtnDate');                 // クレジットカード有効期限
				break;
				// search_stay_history_api
			case  Api_const::A018:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['pageNmbr'] = $this->getPostValue($input_post,'pageNmbr');                                     // ページ番号
				break;
				// search_stay_history_details_api
			case  Api_const::A019:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['rsrvId'] = $this->getPostValue($input_post,'rsrvId');                                         // 予約ID
				$posts['htlCode'] = $this->getPostValue($input_post,'htlCode');                                       // ホテルコード
				$posts['rsrvtnNmbr'] = $this->getPostValue($input_post,'rsrvtnNmbr');                                 // 予約番号
				break;
				// search_favorite_hotel_api
			case  Api_const::A020:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['pageNmbr'] = $this->getPostValue($input_post,'pageNmbr');                                     // ページ番号
				$posts['fvrtHtlCode'] = $this->getPostValue($input_post,'fvrtHtlCode');                               // お気に入りホテルコード
				break;
				// entry_favorite_hotel_api
			case  Api_const::A021:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['fvrtHtlCode'] = $this->getPostValue($input_post,'fvrtHtlCode');                               // お気に入りホテルコード
				$posts['dltFlag'] = $this->getPostValue($input_post,'dltFlag');										  //削除フラグ	
				break;
				// search_customer_api
			case  Api_const::A022:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				break;
				// change_customer_api
			case  Api_const::A023:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['prcssngType'] = $this->getPostValue($input_post,'prcssngType');                               // 処理種別(1:登録・更新/2:入力チェックのみ)
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // 姓アルファベット
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // 名アルファベット
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				$posts['sex'] = $this->getPostValue($input_post,'sex');                                               // 性別
				$posts['ntnltyCode'] = $this->getPostValue($input_post,'ntnltyCode');                                 // 国籍コード
				$posts['phnNmbr'] = $this->getPostValue($input_post,'phnNmbr');                                       // 電話番号
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ
				$posts['mmbrshpNmbr'] = $this->getPostValue($input_post,'mmbrshpNmbr');                               // 会員番号
				$posts['pcEmlAddrss'] = $this->getPostValue($input_post,'pcEmlAddrss');                               // ＰＣメールアドレス
				$posts['nwslttr'] = $this->getPostValue($input_post,'nwslttr');                                       // メルマガ送信フラグ
				$posts['mblEmlAddrss'] = $this->getPostValue($input_post,'mblEmlAddrss');                             // 携帯メールアドレス
				$posts['psswrd'] = $this->getPostValue($input_post,'psswrd');                                         // パスワード
				$posts['lgnId'] = $this->getPostValue($input_post,'lgnId');                                           // ログインID
				$posts['lgnPsswrd'] = $this->getPostValue($input_post,'lgnPsswrd');                                   // ログインパスワード
				break;
				// search_customer_setting_api
			case  Api_const::A024:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				break;
				// change_customer_setting_api
			case  Api_const::A025:
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['newsPushFlag'] = $this->getPostValue($input_post,'newsPushFlag');                             // 東横インからのお知らせを受信するか。受信する場合:Y　/受信しない場合:N
				$posts['myFvrtsPushFlag'] = $this->getPostValue($input_post,'myFvrtsPushFlag');                       // お気に入りホテルからのお知らせを受信するか。受信する場合:Y　/受信しない場合:N
				$posts['nrstHtlsPushFlag'] = $this->getPostValue($input_post,'nrstHtlsPushFlag');                     // 最寄のホテルからのお知らせを受信するか。受信する場合:Y　/受信しない場合:N
				$posts['dstnc'] = $this->getPostValue($input_post,'dstnc');                                           // 現在地周辺のホテルの距離範囲。単位はKm。
				break;
				// attests_member_api
			case  Api_const::A026:
				$posts['mmbrshpNmbr'] = $this->getPostValue($input_post,'mmbrshpNmbr');                               // 会員番号
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // お客様の名前(姓アルファベット)
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // お客様の名前(姓アルファベット)
				break;
				// attests_name_birth_date_api
			case  Api_const::A027:
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // お客様の名前(姓アルファベット)
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // お客様の名前(名アルファベット)
				$posts['phnNmbr'] = $this->getPostValue($input_post,'phnNmbr');                                       // 電話番号
				break;
				// attests_birth_date_password_api
			case  Api_const::A028:
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				$posts['psswrd'] = $this->getPostValue($input_post,'psswrd');                                         // パスワード
				break;
				// entry_personal_information_api
			case  Api_const::A029:
				$posts['prcssngType'] = $this->getPostValue($input_post,'prcssngType');                               // 処理種別（新規登録、入力チェックのみ）
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // 姓アルファベット
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // 名アルファベット
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				$posts['sex'] = $this->getPostValue($input_post,'sex');                                               // 性別
				$posts['ntnltyCode'] = $this->getPostValue($input_post,'ntnltyCode');                                 // 国籍コード
				$posts['phnNmbr'] = $this->getPostValue($input_post,'phnNmbr');                                       // 電話番号
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ
				$posts['mmbrshpNmbr'] = $this->getPostValue($input_post,'mmbrshpNmbr');                               // 会員番号
				$posts['pcEmlAddrss'] = $this->getPostValue($input_post,'pcEmlAddrss');                               // ＰＣメールアドレス
				$posts['nwslttr'] = $this->getPostValue($input_post,'nwslttr');                                       // メルマガ送信フラグ
				$posts['mblEmlAddrss'] = $this->getPostValue($input_post,'mblEmlAddrss');                             // 携帯メールアドレス
				$posts['psswrd'] = $this->getPostValue($input_post,'psswrd');                                         // パスワード
				break;
				// search_browsing_history_api
			case  Api_const::A030:
				$posts['rsrvsPrsnUid'] = $CI->input->post('rsrvsPrsnUid');                            // 予約者情報UID
				$posts['pageNmbr'] = $CI->input->post('pageNmbr');                                // ページ番号
				break;
				// login_api
			case  Api_const::A032:
				$posts['lgnId'] = $this->getPostValue($input_post,'lgnId');                                           // ログインID（メールアドレス）
				$posts['lgnPsswrd'] = $this->getPostValue($input_post,'lgnPsswrd');                                   // ログインパスワード
				break;
				// check_initialization_api
			case  Api_const::A033:
				$posts['prcssngType'] = $this->getPostValue($input_post,'prcssngType');                               // 処理タイプ　1:会員の方　2:一般の方　3:初めての方
				$posts['lgnId'] = $this->getPostValue($input_post,'lgnId');                                           // ログインID(メールアドレス)
				$posts['lgnPsswrd'] = $this->getPostValue($input_post,'lgnPsswrd');                                   // ログインパスワード
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['mmbrshpNmbr'] = $this->getPostValue($input_post,'mmbrshpNmbr');                               // 会員番号
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // 姓(アルファベット)
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // 名(アルファベット)
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				$posts['ntnltyCode'] = $this->getPostValue($input_post,'ntnltyCode');                                 // 国籍コード
				$posts['sex'] = $this->getPostValue($input_post,'sex');                                               // 性別（M:男性 /F:女性）
				$posts['phnNmbr'] = $this->getPostValue($input_post,'phnNmbr');                                       // 電話番号
				$posts['emlAddrss'] = $this->getPostValue($input_post,'emlAddrss');                                   // メールアドレス
				$posts['nwslttr'] = $this->getPostValue($input_post,'nwslttr');                                       // メルマガ送信フラグ
				$posts['emlAddrss2'] = $this->getPostValue($input_post,'emlAddrss2');                                 // メールアドレス2
				break;
				// entry_initialization_api
			case  Api_const::A034:
				$posts['prcssngType'] = $this->getPostValue($input_post,'prcssngType');                               // 処理タイプ　1:会員の方　2:一般の方　3:初めての方
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 予約者情報UID
				$posts['lgnId'] = $this->getPostValue($input_post,'lgnId');                                           // ログインID(メールアドレス)
				$posts['lgnPsswrd'] = $this->getPostValue($input_post,'lgnPsswrd');                                   // ログインパスワード
				$posts['mmbrshpFlag'] = $this->getPostValue($input_post,'mmbrshpFlag');                               // 会員フラグ(Y:会員　/　N:非会員)の配列
				$posts['mmbrshpNmbr'] = $this->getPostValue($input_post,'mmbrshpNmbr');                               // 会員番号
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // 姓(アルファベット)
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // 名(アルファベット)
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				$posts['ntnltyCode'] = $this->getPostValue($input_post,'ntnltyCode');                                 // 国籍コード
				$posts['sex'] = $this->getPostValue($input_post,'sex');                                               // 性別（M:男性 /F:女性）
				$posts['phnNmbr'] = $this->getPostValue($input_post,'phnNmbr');                                       // 電話番号
				$posts['emlAddrss'] = $this->getPostValue($input_post,'emlAddrss');                                   // メールアドレス
				$posts['nwslttr'] = $this->getPostValue($input_post,'nwslttr');                                       // メルマガ送信フラグ
				$posts['emlAddrss2'] = $this->getPostValue($input_post,'emlAddrss2');                                 // メールアドレス2
				break;
				// send_password_forgotten_mail_api
			case  Api_const::A035:
				$posts['emlAddrss'] = $this->getPostValue($input_post,'emlAddrss');                                   // メールアドレス
				$posts['fmlyName'] = $this->getPostValue($input_post,'fmlyName');                                     // 姓(アルファベット)
				$posts['frstName'] = $this->getPostValue($input_post,'frstName');                                     // 名(アルファベット)
				$posts['dateBirth'] = $this->getPostValue($input_post,'dateBirth');                                   // 生年月日
				break;
				// reset_password_api
			case  Api_const::A036:
				$posts['athntctnKey'] = $this->getPostValue($input_post,'athntctnKey');                               // 認証キー
				$posts['rsrvsPrsnUid'] = $this->getPostValue($input_post,'rsrvsPrsnUid');                             // 該当する予約者情報UID
				$posts['lgnId'] = $this->getPostValue($input_post,'lgnId');                                           // ログインID(メールアドレス)
				$posts['lgnPsswrd'] = $this->getPostValue($input_post,'lgnPsswrd');                                   // パスワード
				break;
			default:
		}
		return $posts;
	}

	/*
	 * 日付範囲のSQL文を返却
	*
	* $date_item			日付項目
	* $date_start			日付開始(YYYYMMDD形式)
	* $date_range			日付範囲
	*
	*/
	function getSqlDateRange($date_item, $date_start, $date_range){
		// intervalは0から開始のため、1を差し引く
		$date_range = $date_range - 1;
		$ret = $date_item." BETWEEN '".$date_start."' AND '".$date_start."' + interval ".$date_range." day";
		return $ret;
	}

	/**	型指定連想配列の値作成
	 ***	CodeIgniterのxmlrpc用の形式にする
	 *
	 *IN:	$key		連想配列のkey
	 *		$val		連想配列の値
	 *		$type		連想配列の値の型
	 *OUT	$retVal		連想配列
	 */
	function getSpecifyType ($key, $val, $type= ''){
		$retVal = array();
		//配列の場合
		if(is_array( $val)){
			$type = 'struct';
		}
		if($type == ''){
			$type = 'string';
		}
		//構造体の場合
		if($type == 'struct'){
			$retVal = array($key => $val, $type);
		}
		else{
			$retVal = array($key => array($key => $val), $type);
		}
		return $retVal;
	}

	/*
	 * 予約可能最大日付取得
	*
	* $time_zone			ホテルタイムゾーン
	* $rsrv_mnth			予約可能月数
	*
	*/
	function getRsrvMaxDate($time_zone, $rsrv_mnth){
		$CI =& get_instance();
		$date = $CI->api_date->getTimeZoneDate($time_zone);
		return  $CI->api_date->addMonth($date, $rsrv_mnth);
	}

	/*
	 * 最終宿泊日取得
	*
	* $checkin_date			チェックイン日
	* $nmbr_nights			泊数
	*
	*/
	function getLastStayDate($checkin_date, $nmbr_nights){
		$CI =& get_instance();
		return  $CI->api_date->addDay($checkin_date, $nmbr_nights - 1);
	}

	/*
	 * 予約可能期間取得
	*
	* $params			POSTパラメータ群
	*
	*/
	function getRsrvMnth($params){
		$CI =& get_instance();
		if ($CI->api_com_util->isSetNotNull($params, 'mmbrshpFlag')) {
			if ($params['mmbrshpFlag'] == Api_const::MMBR_FLG_Y) {
				return $CI->config->item('mmbr_rsrv_mnth');
			}
		}
		return $CI->config->item('gnrl_rsrv_mnth');
	}

	/*
	 * 予約可能チェック
	*
	* $open_date			オープン日
	* $checkin_date			チェックイン日
	* $time_zone			タイムゾーン
	* $rsrv_mnth			予約可能月数
	* $nmbr_nights			泊数
	* $tmps					リスト
	*
	*/
	function chkRsrvRange($open_date, $checkin_date, $time_zone, $rsrv_mnth, $nmbr_nights, $tmps){
		// 到着日予約可能チェック
		if ($open_date <= $checkin_date) {
			// 予約可能期間チェック(一般or会員の可能期間)
			if ($this->getRsrvMaxDate($time_zone, $rsrv_mnth) >= $checkin_date) {
				// 部屋タイプ単位に泊数のレコード数が一致(全宿泊日)
				if (count($tmps) == $nmbr_nights){
					return true;
				}
			}
		}
		return false;
	}

	/*
	 * POSTデータ取得
	*
	* $input_post	POSTデータ
	* $id			id
	*
	*/
	function getPostValue($input_post, $id){
		$CI =& get_instance();
		if ($CI->api_com_util->chkAryKeyExists($input_post, $id)) {
			return  $input_post[$id];
		}
		return "";
	}

	/*
	 * 日別ecoフラグ取得
	*
	* $ecoFlag	ecoフラグ
	* $idx		宿泊日Index
	* $ecoDtsList	ecoフラグ日別リスト
	*
	*/
	function getDailyEco($ecoFlag, $idx, $ecoDtsList){
		$CI =& get_instance();
		// get daily ecoflag
		$eco = "N";
		if ($ecoFlag == "Y"){
			if ($idx > 0){
				if ($CI->api_com_util->isArrayChkIdex($ecoDtsList, $idx - 1)){
					$eco = $ecoDtsList[$idx - 1];
				}
			}
		}
		return $eco;
	}

	/*
	 * 距離表示用フォーマット
	*
	* $distance				距離
	*
	*/
	function distanceFormat($distance){
		$CI =& get_instance();
		if ($CI->api_com_util->isNull($distance)){
			return "";
		}
		//20141205 dstncCrrntPstnは整数であっても少数第一位で返す。	iwamoto	
		return sprintf("%.1f",$CI->api_com_util->numFloor($distance, 1)).Api_const::KILOMETER;
	}
	/*
	 * 金額表示用フォーマット
	*
	* $prc				金額
	* $crrncy_name		通貨名
	* $crrncy_sign		通貨記号
	* $lngg				言語
	* $rate_dsply_flag	料金表示フラグ
	*
	*/
	function priceFormat($prc, $crrncy_name, $crrncy_sign, $lngg, $rate_dsply_flag = Api_const::RATE_DSPLY_FLG_ON){
		$CI =& get_instance();
		if ($rate_dsply_flag == Api_const::RATE_DSPLY_FLG_OFF){
			return "";
		}
		return $CI->api_com_util->crrncyFullFormat($prc, $crrncy_name, $crrncy_sign, $lngg);
	}
	/*
	 * エラー情報の言語設定
	*/
	function getErrLang(){
		$CI =& get_instance();
		$input_post = $CI->input->post();
		// error_langの言語設定
		$lngg = $this->getPostValue($input_post, 'lngg');
		// 言語判定
		switch ($lngg){
			case  Api_const::LANG_JP:
				$ret = 'japanese';  // 日本語
				break;
			case  Api_const::LANG_EN:
				$ret = 'english';   // 英語
				break;
			default:
				$ret = 'japanese';
		}
		return $ret;
	}

	/*
	* エラー情報の言語設定
	*/
	function getErrLangbyLngg($lngg){
		// 言語判定
		switch ($lngg){
			case  Api_const::LANG_JP:
				$ret = 'japanese';  // 日本語
				break;
			case  Api_const::LANG_EN:
				$ret = 'english';   // 英語
				break;
			default:
				$ret = 'japanese';
		}
		return $ret;
	}
	/*
	* 交通手段名称取得
	*
	* $code			交通手段コード
	*
	*/
	function getMnsTrnsprttnName($code){
		switch ($code){
			case 1:
			case 2:
			case 3:
			case 4:
				break;
			default:
				return "";
		}
		$CI =& get_instance();
		return $CI->lang->line('MNS_TRNPRTTN_'.$code);
	}
	/*
	* ecoプランのチェック
	*
	* $eco_list			ecoプラン適応日付
	*　$chckn_date		チェックイン日
	* $chckt_date		チェッアウト日
	*/
	function ecoCheck($eco_list, $chckn_date, $chckt_date){
		$CI =& get_instance();
		$flg=0;
		$eco_array=array();

		for($i=0;$i<count($eco_list); $i++){
			if ($eco_list[$i] != '' || $eco_list[$i] !=NULL){
				$eco_array[]=$eco_list[$i];
			}	
		}
		if (! is_array($eco_array)){
			return false;
		}
		if (empty($eco_array)){
			return false;
		}
		$eco_list_s=$eco_array;
		sort($eco_list_s);
		
		for ($i=0;$i<count($eco_list_s)-1;$i++){
			if ($eco_list_s[$i+1]==$CI->api_date->addDay($eco_list_s[$i],1)){
				$flg++;
			}
			else {
				$flg=0;
			}
		}
		//ecoプラン適応日が3日間以上連続指定されたらNG
		if ($flg>1){
			return false;
		}
		//ecoプラン適応日がチェックイン日とチェックアウト日の範囲外の場合はNG
		//ecoプラン適応日がチェックイン日の時はNG
		//ecoプラン適応日がチェックアウト日の時はNG
		for ($i=0;$i<count($eco_array);$i++){
			if ($eco_array[$i]<=$chckn_date || $eco_array[$i]>=$chckt_date){
				return false;
			}
		}
		return true;
	}
	/*
	 * 固体識別情報を取得
	*/
	function getSldDstnctn($rqst){
		$osType = $rqst['osType'];

		// OSタイプ判定
		switch ($osType){
			case  "I":
				$ret = $rqst['dvcTkn'];		// iOSのdevice token
				break;
			case  "A":
				$ret = $rqst['rgstrtnId'];	// Androidoのregistration id
				break;
			default:
				$ret = '';
		}
		return $ret;
	}
	/*
	* パスワードチェック
	* $pass	パスワード
	*/
	function chkPassword($pass){
		// 半角英数字記号で6文字～20文字の場合
		if (preg_match("/^[!-~]{6,20}$/", $pass)) {
	        return true;
	    } else {
	        return false;
	    }
	}
		/*
	 * 各Option料金(税込と税抜き)を取得して配列で返す
	*
	* $eco					ecoフラグ
	* $vod					vodフラグ
	* $bsnss_pack_flag		bisnessPackフラグプ
	* $bsnss_pack_type		bisnessPackタイプ
	* $tax_rate				消費税率
	*
	*/
	function getOptionIncArray($eco, $vod, $bsnss_pack_flag, $bsnss_pack_type, $tax_rate){
		$CI =& get_instance();
		$option = array();
		$eco_amnt = 0;
		$vod_amnt = 0;
		$bsnss_pack = 0;
		$bsnss_pack_tax = 0;
		
		if ($eco == "Y"){
			$eco_amnt = -($CI->config->item('eco_amnt'));
		}
		if ($vod == "Y"){
			$vod_amnt = $CI->config->item('vod_amnt');
		}

		if ($bsnss_pack_flag === "Y"){
			switch ($bsnss_pack_type){
				case  "1":
					$bsnss_pack_tax = $this->getAmountIncTax($CI->config->item('bsnss_pack_100'), $tax_rate);
					$bsnss_pack = $CI->config->item('bsnss_pack_100');
					break;
				case  "2":
					$bsnss_pack_tax = $this->getAmountIncTax($CI->config->item('bsnss_pack_200'), $tax_rate);
					$bsnss_pack = $CI->config->item('bsnss_pack_200');
					break;
				case  "3":
					$bsnss_pack_tax = $this->getAmountIncTax($CI->config->item('bsnss_pack_300'), $tax_rate);
					$bsnss_pack = $CI->config->item('bsnss_pack_300');
					break;
				default:
			}
			
		}
		$ret['eco_amnt'] = $eco_amnt;
		$ret['vod_amnt'] = $vod_amnt;
		$ret['bsnss_pack_amnt'] = $bsnss_pack;
		$ret['bsnss_pack_amnt_tax'] = $bsnss_pack_tax;
		return  $ret;
	}
	/*
	 * 日別ecoフラグ判定
	*
	* $targetDay	宿泊日付
	* $ecoDtsList	ecoフラグ日別リスト
	*
	*/
	function checkDailyEco($targetDay,$ecoDtsList){
		// get daily ecoflag
		$eco = "N";
		for ($i=0;$i<count($ecoDtsList);$i++){
			if ($ecoDtsList[$i]==$targetDay){
				$eco = 'Y';
				break;
			}
		}
		return $eco;
	}
}
