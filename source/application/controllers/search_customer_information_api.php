<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_customer_information_api extends CI_Controller {

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
		$this->FNCTIN = Api_const::A022;	//お客様情報検索API
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
					'rsrvsPrsnUid'
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$jsonOut = "";
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			// BIサービス呼び出し
			$host = $this->config->config['bi_service_host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.searchCustomerInformation";
			$this->xmlrpc->server($host, $port);
			$this->xmlrpc->method($method);
			$request = array(
					array($rqst['applctnVrsnNmbr'], 'string'),
					array($rqst['lngg'], 'string'),
					array($rqst['rsrvsPrsnUid'], 'string')
			);
			$this->xmlrpc->request($request);
			// Call BService
			if (!$this->xmlrpc->send_request()) {
				// error
				log_error($this->FNCTIN, 'send_request_error : '.$this->xmlrpc->display_error());
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
			} 
			else {
				$b_result = $this->xmlrpc->display_response();
				$error_code = $b_result['errrCode'];
				if ($b_result['errrCode'] == Api_const::BCMN0000){
					$jsonOut = array(
	 					'rsrvsPrsnUid' => $b_result['rsrvsPrsnUid'],
	 					'fmlyName' => $b_result['fmlyName'],
	 					'frstName' => $b_result['frstName'],
	 					'dateBirth' => $b_result['dateBirth'],
	 					'sex' => $b_result['sex'],
	 					'ntnltyCode' => $b_result['ntnltyCode'],
	 					'phnNmbr' => $b_result['phnNmbr'],
	 					'mmbrshpFlag' => $b_result['mmbrshpFlag'],
	 					'pcEmlAddrss' => $b_result['pcEmlAddrss'],
	 					'nwslttr' => $b_result['nwslttr']

					);
					if (array_key_exists('psswrd', $b_result)){
						$jsonOut += array('psswrd' => $b_result['psswrd']);
						}
					else {
						$jsonOut += array('psswrd' => "");
					}
					//end
					if (array_key_exists('mmbrshpNmbr', $b_result)){
						$jsonOut += array('mmbrshpNmbr' => $b_result['mmbrshpNmbr']);
					}
					if (array_key_exists('mblEmlAddrss', $b_result)){
						$jsonOut += array('mblEmlAddrss' => $b_result['mblEmlAddrss']);
					}
					if (array_key_exists('lgnId', $b_result)){
						$jsonOut += array('lgnId' => $b_result['lgnId']);
					}
					else {
						$jsonOut += array('lgnId' => '');
					}
					if (array_key_exists('lgnPsswrd', $b_result)){
						$jsonOut += array('lgnPsswrd' => $b_result['lgnPsswrd']);
					}		
					else {
						$jsonOut += array('lgnPsswrd' => '');
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