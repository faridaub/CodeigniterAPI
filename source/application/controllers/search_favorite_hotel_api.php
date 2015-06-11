<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Search_favorite_hotel_api extends CI_Controller {
	var $FNCTIN = null;
	function __construct() {
		parent::__construct();
		$this->load->helper('log');
		$this->load->helper('date');
		$this->load->helper('url');
		$this->load->library('xmlrpc');
		$this->load->library('Api_const');
		$this->load->library('Api_date');
		$this->load->library('Api_com_util');
		$this->load->library('Api_util');
		$this->load->model('apikey_information_m', '', true);
		$this->load->model('application_version_control_m', '', true);
		$this->load->model('operational_log_m', '', true);
		$this->load->model('hotel_info_m','',true);
		$this->load->model('room_charge_infomation_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A020; // お気に入りホテル検索API
	}
	public function index() {
		// initialize
		$jsonOut['nmbrMyFvrts'] = 0;
		$jsonOut['myFvrtsInfrmtnList'] = array();
		$error_code = true;
		$error_description = '';
		try {
			// API初期処理
			$rqst = $this->api_util->apiInit($this->FNCTIN);
			if ($rqst['errCode'] !== true) {
				$error_code = $this->api_util->setErrorCode($rqst['errCode']);
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return;
			}
			// 必須項目
			$ids = array(
					'rsrvsPrsnUid',
					'pageNmbr'
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);
			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$jsonOut = "";
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return;
			}
			$host = $this->config->config['bi_service_host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.searchMyFavorites";
			$this->xmlrpc->server($host, $port);
			$this->xmlrpc->method($method);
			//20141205　iwamoto
			//スカホアプリから fvrtHtlCode が渡ってこない場合
			//fvrtHtlCodeを追加して空文字を設定する
			if ( !array_key_exists('fvrtHtlCode',$rqst)){
				$rqst['fvrtHtlCode']='';
			}
			$request = array(
					array(
							$rqst['applctnVrsnNmbr'],
							'string'
					),
					array(
							$rqst['lngg'],
							'string'
					),
					array(
							$rqst['fvrtHtlCode'],
							'string'
					),
					array(
							$rqst['rsrvsPrsnUid'],
							'string'
					),
					array(
							$rqst['pageNmbr'],
							'string'
					)
			);
			// Call BService
			$this->xmlrpc->request($request);
			if (! $this->xmlrpc->send_request()) {

			// error
				log_error($this->FNCTIN, 'send_request_error : ' . $this->xmlrpc->display_error());
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			$b_result = $this->xmlrpc->display_response();
			$error_code = $b_result['errrCode'];
			if ($b_result['errrCode'] != Api_const::BCMN0000) {
				$error_code = $b_result['errrCode'];
				if (array_key_exists('errrMssg', $b_result)) {
					$error_description = $this->lang->line($error_code);
				}
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			$wkFavorite = array(
					'nmbrMyFvrts' => $b_result['nmbrMyFvrts'],
					'myFvrtsInfrmtnList' => $b_result['myFvrtsInfrmtnList']
			);
			// お気に入りホテル検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                      // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                       // 言語
			$param['nmbrNghts'] = 1;                              // 宿泊日数
			$param['nmbrPpl'] = 1;                                // 宿泊者数
			$recList = array();
			// BIサービスから返却されるホテルリストでループ
			foreach($wkFavorite['myFvrtsInfrmtnList'] as $row ){
				$param['htlCode'] = $row['htlCode'];         // ホテルコード
				// ホテル情報取得
				$recHotel = $this->hotel_info_m->select($param['applctnVrsnNmbr'], $param['htlCode'], $param['lngg']);
				if (count($recHotel['rec']) != 0) {
					$param['chcknDate'] = $this->api_date->getTimeZoneDate($recHotel['rec']['time_zone']);  // チェックイン日付
					$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN);
					if ($result['errCode'] !== true) {
						$error_code = $result['errCode'];
						$error_description = $this->lang->line($error_code);
						$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
						return ;
					}
					if ($result['recCnt'] != 0) {
						// リストに追加
						$recList = array_merge($recList, $result['recList']);
					}
				}
			}
			$myFvrtsInfrmtnList = array();
			if (count($recList) != 0){
				$oldRow = null;
				$lowPrc = null;
				$eof = false;
				$idx = 0;
				while (true){
					// get row
					if ($idx < count($recList)){
						$row = $recList[$idx];
					}else{
						$eof = true;
					}
					// Key Break [htl_code ]
					if ($eof == true || ($lowPrc != null && $oldRow['htl_code'] != $row['htl_code'])){
						$myFvrtsInfrmtnList[] = $lowPrc;
						$lowPrc = null;
					}
					// eof exit
					if ($eof == true){
						break;
					}
					// lowPrice Set
					if ($lowPrc == null || $row['prc'] < $prcs['prc']){
						// Web室料配列取得
						$prcs = $this->api_util->getPrices($row['prc'], $row['dscnt_amnt'], $row['mmbr_dscnt_rate'], $row['cnsmptn_tax_rate']);
						$lowPrc =
						array(
								'htlCode' => $row['htl_code'],
								'htlName' => $row['htl_name'],
								'imgURL' => $row['htl_img_url'],
								'snglrmPrc' => $this->api_util->priceFormat($prcs['prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
								'snglrmPrcIncldngTax' => $this->api_util->priceFormat($this->api_util->getAmountIncTax($prcs['prc'], $row['cnsmptn_tax_rate']), $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
								'mmbrsnglrmPrc' => $this->api_util->priceFormat($prcs['mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
								'mmbrSnglrmPrcIncldngTax' => $this->api_util->priceFormat($this->api_util->getAmountIncTax($prcs['mmbr_prc'], $row['cnsmptn_tax_rate']), $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
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
//			$jsonOut['nmbrMyFvrts'] = count($myFvrtsInfrmtnList);
			$jsonOut['nmbrMyFvrts'] = $b_result['nmbrMyFvrts'];
			
			$jsonOut['myFvrtsInfrmtnList'] = $myFvrtsInfrmtnList;
		} catch(Exception $e) {
			log_error($this->FNCTIN, 'Exception : ' . $e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		$jsonOut = $this->api_util->inputErrArray($jsonOut, $error_code, $error_description);
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
	}
}