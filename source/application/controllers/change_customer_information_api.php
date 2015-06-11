<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Change_customer_information_api extends CI_Controller {

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
		$this->FNCTIN = Api_const::A023;	// お客様情報変更API
	}
	public function index()
	{
		// initialize
		$error_code = true;
		$error_description = '';
		$jsonOut = "";
		$request = array();
		try {
			// API初期処理
			$rqst = $this->api_util->apiInit($this->FNCTIN);
			log_d( "apiInit End" );
			if ($rqst['errCode'] !== true) {
				$error_code = $this->api_util->setErrorCode($rqst['errCode']);
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd ($jsonOut, $error_code, $error_description);
				return ;
			}
			// 必須項目
			$ids = array(
					'rsrvsPrsnUid',
					'prcssngType',
					'fmlyName',
					'frstName',
					'dateBirth',
					'sex',
					'ntnltyCode',
					'phnNmbr',
					'mmbrshpFlag',
					'pcEmlAddrss',
					'nwslttr',
					'lgnId',
					'lgnPsswrd'
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
			$apikey_information_mode = $chk_cmmn['apikey_information_mode'];
			// TODO:Bサービス呼び出し
			$host = $this->config->config['bi_service_host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.entryCustomerInformation";
			$this->xmlrpc->server($host,$port);
			$this->xmlrpc->method($method);
			$request = array(
					array($rqst['applctnVrsnNmbr'], 'string'),
					array($rqst['lngg'], 'string'),
					array($rqst['prcssngType'], 'string'),
					array($rqst['rsrvsPrsnUid'], 'string'),
					array($rqst['fmlyName'], 'string'),
					array($rqst['frstName'], 'string'),
					array($rqst['sex'], 'string'),
					array($rqst['ntnltyCode'], 'string'),
					array($rqst['phnNmbr'], 'string'),
					array($rqst['mmbrshpFlag'], 'string')
			);
			if (array_key_exists('mmbrshpNmbr', $rqst)){
				array_push($request,array($rqst['mmbrshpNmbr'], 'string'));
			}
			array_push($request,array($rqst['pcEmlAddrss'], 'string'));
			array_push($request,array($rqst['nwslttr'], 'string'));
			if (array_key_exists('mblEmlAddrss', $rqst)){
				array_push($request,array($rqst['mblEmlAddrss'], 'string'));
			}
			if (array_key_exists('psswrd', $rqst)){
				array_push($request,array($rqst['psswrd'], 'string'));
			}
			array_push($request,array($rqst['lgnId'], 'string'));
			array_push($request,array($rqst['lgnPsswrd'], 'string'));

			// Call BService
			$this->xmlrpc->request($request);
			if (!$this->xmlrpc->send_request()) {
				// error
				log_error($this->FNCTIN, 'send_request_error : '.$this->xmlrpc->display_error());
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
			} else {
				$b_result = $this->xmlrpc->display_response();
			
				$error_code = $b_result['errrCode'];
				if (array_key_exists('errrMssg', $b_result)){
					$error_description = $this->lang->line($error_code);
				}
			}
		} catch (Exception $e) {
			log_error($this->FNCTIN, 'Exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		$jsonOut = $this->api_util->inputErrArray($jsonOut, $error_code, $error_description);
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
	}
}