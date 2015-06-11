<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_hotel_api extends CI_Controller {

	var $FNCTIN = null;

	function __construct()
	{
		parent::__construct();
		$this->load->helper('log');
		$this->load->helper('date');
		$this->load->library('Api_const');
		$this->load->library('Api_date');
		$this->load->library('Api_com_util');
		$this->load->library('Api_util');
		$this->load->model('apikey_information_m','',true);
		$this->load->model('application_version_control_m','',true);
		$this->load->model('operational_log_m','',true);
		$this->load->model('hotel_info_m','',true);
		$this->load->model('vacancy_information_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A005;	//空室ホテル検索API
	}

	public function index()
	{
		// initialize
		$jsonOut['num_hotels'] = 0;
		$error_code = true;
		$error_description = '';

		try {
			// API初期処理
			$rqst = $this->api_util->apiInit($this->FNCTIN);

			if ($rqst['errCode'] !== true) {
				$error_code = $this->api_util->setErrorCode($rqst['errCode']);
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			// 必須項目
			$ids = array(
				'mmbrshpFlag',
				'chcknDate',
				'nmbrNghts',
				'nmbrPpl',
				'nmbrRms',
				'lngtd',
				'lttd',
				'dstnc'
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			// 空室ホテル検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                 // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                  // 言語
			$param['mmbrshpFlag'] = $rqst['mmbrshpFlag'];    // 会員フラグ
			$param['chcknDate'] = $rqst['chcknDate'];    // チェックイン日付
			$param['nmbrNghts'] = $rqst['nmbrNghts'];      // 宿泊日数
			$param['nmbrPpl'] = $rqst['nmbrPpl'];            // 宿泊者数
			$param['nmbrRms'] = $rqst['nmbrRms'];            // 部屋数
			$param['smkngFlag'] = $rqst['smkngFlag'];        // 禁煙・喫煙フラグ
			$param['lttd'] = $rqst['lttd'];                  // 現在値の経度
			$param['lngtd'] = $rqst['lngtd'];                // 現在値の緯度
			$param['dstnc'] = $rqst['dstnc'];                // 検出範囲
			$param['roomType'] = $rqst['roomType'];			 //　客室タイプ

			//該当するホテル情報取得
			$result = $this->vacancy_information_m->selectJoinListCond($this->FNCTIN, $param, Api_const::HOTEL_ONLY);

			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			if ($result['recCnt'] != 0){
				$status = 0;
				$s_hotel_code = '';
				
				foreach($result['recList'] as $value ){
					$s_hotel_code[] = $value['htl_code'];
				}

				//空室の有無を確認する
				$status = $this->vacancy_information_m->selectVacancy($s_hotel_code, $param['chcknDate'], $param['nmbrNghts'],  $param['nmbrPpl'], $param['mmbrshpFlag'], $param['roomType'] );
				
				//空室無しの時
				if ($status == 0 ){
					$error_code = Api_const::BAPI1007;
					$error_description = $this->lang->line($error_code);
					$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
					return;
				}

			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;

			}

			$result = array();
			//該当する空室があるホテル情報取得
			$result = $this->vacancy_information_m->selectJoinListCond($this->FNCTIN, $param, Api_const::HOTEL_AND_VACANCY);

			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			if ($result['recCnt'] != 0){

				$htlList = array();
				foreach($result['recList'] as $value ){

					$htlList[] = $value['htl_code'];
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);
			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
			}
			$jsonOut['num_hotels'] = count($htlList);
			
		} catch (Exception $e) {
			log_error($this->FNCTIN, 'exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
	}
}