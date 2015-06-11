<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_hotel_area_api extends CI_Controller {

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
		$this->load->model('vacancy_information_m','',true);
		$this->load->model('area_master_m','',true);
		$this->load->model('country_master_m','',true);
		$this->load->model('room_charge_infomation_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A004;	//空室ホテルをエリア検索API
		$this->output->enable_profiler(TRUE);	
	}

	public function index()
	{
		// initialize
		$jsonOut['nmbrArs'] = 0;
		$jsonOut['areaInfrmtn'] = array();
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
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			// 空室ホテルのエリア検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                 // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                  // 言語
			$param['mmbrshpFlag'] = $rqst['mmbrshpFlag'];    // 会員フラグ
			$param['chcknDate'] = $rqst['chcknDate'];    // チェックイン日付
			$param['nmbrNghts'] = $rqst['nmbrNghts'];      // 宿泊日数
			$param['nmbrPpl'] = $rqst['nmbrPpl'];            // 宿泊者数
			$param['nmbrRms'] = $rqst['nmbrRms'];            // 部屋数

			$country_list = array();
			//全ての国、エリア、都道府県情報の取得
			//東横インホテルのある/なしに関係なく、country_master,area_master,state_master全マスターのデータを取得する。
			$country_list = $this->country_master_m->selectJoinListCond($param);
			if ($country_list['recCnt']==0){
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			for ( $i=0; $i<$country_list['recCnt']; $i++ ){
				$country_list['recList'][$i] += array('nmbrHtl'=>0); 
			}
			
			$areaList = array();
			$oldRow = null;
			$areaCnt = 0;
			$result = array();
			//該当する空室があるホテル情報取得
			$result = $this->vacancy_information_m->selectJoinListCond($this->FNCTIN, $param, Api_const::HOTEL_AND_VACANCY);
			if ( $result['errCode'] !== true ) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$jsonOut['nmbrArs'] = $areaCnt;
				$jsonOut['areaInfrmtn'] = $country_list['recList'];
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return;
			}
			
			if ($result['recCnt'] != 0){
				foreach( $result['recList'] as $row ){
					if ($oldRow != null && ($oldRow['cntry_code'] != $row['cntry_code'] || $oldRow['area_code'] != $row['area_code'] || $oldRow['stt_code'] != $row['stt_code'])){
						$areaList[] = $wk_area;
						$htlList = null;
					}
					$htlList[] = $row['htl_code'];

					$wk_area = array(
						'cntryName' => $row['cntry_name'],
						'cntryCode' => $row['cntry_code'],
						'areaName' => $row['area_name'],
						'areaCode' => $row['area_code'],
						'sttName' => $row['state_name'],
						'sttCode' => $row['stt_code'],
						'cityName' => $row['city_name'],
						'cityCode' => $row['city_code'],
						'nmbrHtl' => (string)count($htlList),
					);
					// key stock
					$oldRow = $row;
				}
				$areaList[] = $wk_area;

				//空室検索結果を全ての国、エリア、都道府県情報データの差分を追加する
				for ( $i=0; $i<$country_list['recCnt']; $i++ ){
					for ( $j=0; $j<count($areaList); $j++ ){
						if ( $country_list['recList'][$i]['cityCode'] == $areaList[$j]['cityCode'] ){
							$country_list['recList'][$i]['nmbrHtl'] = $areaList[$j]['nmbrHtl'];
						}
					}
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);
			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
			}
			$jsonOut['nmbrArs'] = $this->area_master_m->selectCount($param['lngg']);
			$jsonOut['areaInfrmtn'] = $country_list['recList'];
			
			} catch (Exception $e) {
			log_error($this->FNCTIN, 'exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);

	}
}