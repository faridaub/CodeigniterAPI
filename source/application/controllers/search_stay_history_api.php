<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_stay_history_api extends CI_Controller {

	var $FNCTIN = null;

	function __construct()
	{
		parent::__construct();
		$this->load->helper('log');
		$this->load->helper('date');
		$this->load->helper('url');
		$this->load->library('xmlrpc');
		$this->load->library('Api_const');
		$this->load->library('Api_date');
		$this->load->library('Api_com_util');
		$this->load->library('Api_util');
		$this->load->model('apikey_information_m','',true);
		$this->load->model('application_version_control_m','',true);
		$this->load->model('operational_log_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->load->model('hotel_info_m','',true);
		$this->FNCTIN = Api_const::A018;	//宿泊履歴検索API
		//$this->output->enable_profiler(TRUE);	
	}

	public function index()
	{
		// initialize
		$jsonOut = "";
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
					'rsrvsPrsnUid',
					'pageNmbr'
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);
			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $chk_cmmn['mssg'];
				$jsonOut = "";
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			// BIサービス呼び出し
			$host = $this->config->config['bi_service_host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.searchLodgingHistory";
			$this->xmlrpc->server($host, $port);
			$this->xmlrpc->method($method);
			$request = array(
					array($rqst['applctnVrsnNmbr'], 'string'),
					array($rqst['lngg'], 'string'),
					array($rqst['rsrvsPrsnUid'], 'string'),
					array($rqst['pageNmbr'], 'string')
			);
			$this->xmlrpc->request($request);
			// Call BService
			if (!$this->xmlrpc->send_request()) {

				// error
				log_error($this->FNCTIN, 'send_request_error : '.$this->xmlrpc->display_error());
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
			} else {

				$b_result = $this->xmlrpc->display_response();
				$error_code = $b_result['errrCode'];
				if ($b_result['errrCode'] == Api_const::BCMN0000){
					$jsonOut = array(
	 					'nmbrLdgng' => $b_result['nmbrLdgng'],
	 					'ldgngInfrmtn' => $b_result['ldgngInfrmtn']
					);
					for($i=0; $i<count($b_result['ldgngInfrmtn']);$i++){
						//通貨単位の取得 20141014 add iwamoto
						// ホテル情報取得
						$recHotel = $this->hotel_info_m->select($rqst['applctnVrsnNmbr'], $b_result['ldgngInfrmtn'][$i]['htlCode'], $rqst['lngg']);
						if ($recHotel['errCode']==true){
							$pymntPrcIncldngTax=$jsonOut['ldgngInfrmtn'][$i]['pymntPrcIncldngTax'];
							$pymntPrc=$jsonOut['ldgngInfrmtn'][$i]['pymntPrc'];
							$jsonOut['ldgngInfrmtn'][$i]['pymntPrcIncldngTax']=$this->api_util->priceFormat($pymntPrcIncldngTax, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							$jsonOut['ldgngInfrmtn'][$i]['pymntPrc']=$this->api_util->priceFormat($pymntPrc, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
							$jsonOut['ldgngInfrmtn'][$i]['roomName']=$jsonOut['ldgngInfrmtn'][$i]['roomTypeName'];
							$jsonOut['ldgngInfrmtn'][$i]['imgURL']=$recHotel['rec']['img_url'];
							unset($jsonOut['ldgngInfrmtn'][$i]['roomTypeName']);
						}
					}
				}
				if (array_key_exists('errrMssg', $b_result)){
					$error_description = $this->lang->line($error_code);
				}
			}
		} catch (Exception $e) {
			log_error($this->FNCTIN, 'exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);

	}
}