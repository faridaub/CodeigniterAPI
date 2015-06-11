<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_room_type_vacant_api extends CI_Controller {

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
		$this->load->model('room_charge_infomation_m','',true);
		$this->load->model('vacancy_information_m','',true);

		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A009;	//部屋タイプ別空室数検索API
		//$this->output->enable_profiler(TRUE);	
	}

	public function index()
	{
		// initialize
		$jsonOut['roomList'] = array();
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
				'htlCode',
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
			// 部屋タイプ別空室数検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                 // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                  // 言語
			$param['mmbrshpFlag'] = $rqst['mmbrshpFlag'];    // 会員フラグ
			$param['chcknDate'] = $rqst['chcknDate'];    // チェックイン日付
			$param['htlCode'] = $rqst['htlCode'];            // ホテルコード
			$param['nmbrNghts'] = $rqst['nmbrNghts'];      // 宿泊日数
			$param['nmbrPpl'] = $rqst['nmbrPpl'];            // 宿泊者数
			$param['nmbrRms'] = $rqst['nmbrRms'];            // 部屋数
			$param['smkngFlag'] = $rqst['smkngFlag'];        // 喫煙・禁煙区分

			//該当するホテル情報取得
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN, Api_const::HOTEL_ONLY);
			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			if ($result['recCnt'] != 0){
				$status = 0;
				$s_hotel_code = '';

				for($i=0;$i<count($result['recList']);$i++){
					$s_hotel_code[] = $result['recList'][$i]['htl_code'];
				}

				//空室の有無を確認する
				$status = $this->vacancy_information_m->selectVacancy( $s_hotel_code, $param['chcknDate'], $param['nmbrNghts'], $param['nmbrPpl'], $param['mmbrshpFlag'] );
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
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN, Api_const::HOTEL_AND_VACANCY);
			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			$roomList = array();
			if ($result['recCnt'] != 0){
				$oldRow = null;
				$lowPrc = null;
				$vcncy = Api_const::MAX_VCNCY;
				$eof = false;
				$idx = 0;
				while (true){
					// get row
					if ($idx < count($result['recList'])){
						$row = $result['recList'][$idx];
					}else{
						$eof = true;
					}
					$row_plan = "";
					$oldRow_plan = "";
					$row_plan = $row['room_type_code'].$row['plan_code'];
					$oldRow_plan = $oldRow['room_type_code'].$oldRow['plan_code'];
					
					// Key Break [room_type_code ]
					if ($eof == true || ($lowPrc != null && $oldRow_plan!= $row_plan)){
						$roomList[] = $lowPrc;
						$lowPrc = null;
						$vcncy = Api_const::MAX_VCNCY;
					}
					// eof exit
					if ($eof == true){
						break;
					}
					// min残数を保持
					if ($vcncy > $row['vcncy']) {
						$vcncy = $row['vcncy'];
					}
					// lowPrice Set
					if ($lowPrc == null || $row['prc'] < $lowPrc['prc']){
						// Web室料配列取得
						$prcs = $this->api_util->getPrices($row['prc'], $row['dscnt_amnt'], $row['mmbr_dscnt_rate'], $row['cnsmptn_tax_rate']);
						$lowPrc =
						array(
							'roomType' => $row['room_type_code'],
							'roomName' => $row['room_type_name'],
							
							'planCode' => $row['plan_code'],
							'planName' => $row['plan_name'], 
							'applLang' =>  $row['app_lngg'],
							'applMmbr' =>  $row['app_mmbr'],
							'ecoUseDvsn' =>  $row['eco_use_dvsn'],
							'vodUseDvsn' =>  $row['vod_use_dvsn'],
							'bpUseDvsn' =>  $row['bp_use_dvsn'],
							'bdShringUseDvsn' =>  $row['lyng_of_chldrn_use_dvsn'],
							'bpJdgmntDvsn' =>  $row['bp_jdgmnt_dvsn'],

							
							'imgURL' => $row['room_type_img_url'], 
							'listPrc' => $this->api_util->priceFormat($row['prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'mmbrPrc' => $this->api_util->priceFormat($prcs['mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'offclWebDscntPrc' => $this->api_util->priceFormat($prcs['web_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'mmbrOffclWebDscntPrc' => $this->api_util->priceFormat($prcs['web_mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'smkngFlag' => $row['smkng_flag'],
							
							'minPpl' =>  $row['mimm_occpncy'],
							'maxPpl' =>  $row['mxmm_occpncy'],
							'minRoom' =>  $row['mimm_nmbr_rms'],
							'maxRoom' =>  $row['mxmm_nmbr_rms'],
							'nmbrRrms' => $vcncy,
							'lyngOfChldrnAvlblFlag' => $row['lyng_of_chldrn_avlbl_flag'],
							'lyngPrsns' => $row['lyng_prsns'],
							'ecoAvlblFlag' => $row['eco_avlbl_flag'],
							'vodAvlblFlag' => $row['vod_avlbl_flag'],
							'bsnssPackAvlblFlag' => $row['bsnss_pack_avlbl_flag'],
							'prc' => $row['prc'],
						);
					}
					// key stock
					$oldRow = $row;
					$idx++;
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);

			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
			}
			$jsonOut['roomList'] = $roomList;
		} catch (Exception $e) {
			log_error($this->FNCTIN, 'exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
	}
}