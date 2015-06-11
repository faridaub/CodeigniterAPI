<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Cancel_reservation_api extends CI_Controller {
	var $FNCTIN = null;
	function __construct()
	{
		parent::__construct();
		$this->load->helper('log');
		$this->load->helper('date');
		$this->load->helper('url');
		$this->load->library('Api_const');
		$this->load->library('Api_date');
		$this->load->library('Api_com_util');
		$this->load->library('Api_util');
		$this->load->model('apikey_information_m','',true);
		$this->load->model('application_version_control_m','',true);
		$this->load->model('operational_log_m','',true);
		$this->load->model('room_charge_infomation_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A016;//予約キャンセルAPI
		require_once("XML/RPC.php");
	}
	public function index()
	{
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
					'nmbrRsrvtns',
					'rsrvId',
					'htlCode',
					'rsrvtnNmbr'
			);

			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			// TODO:
			for ($i=0; $i<$rqst['nmbrRsrvtns']; $i++){
				$param1 =
				array(
						'rsrvId'=>		new XML_RPC_Value($rqst['rsrvId'][$i],'string'),
						'rsrvtnNmbr'=>	new XML_RPC_Value($rqst['rsrvtnNmbr'][$i],'string','string'),
						'class'=>		new XML_RPC_Value('com.toyokoinn.api.app.entity.result.RoomCancellationInformation')
				);
				$room_info[] = new XML_RPC_Value($param1,'struct');
			}
			
			//予約キャンセルテスト用
			//ホテルコードが9000より大きい時applctnVrsnNmbrに'T'を付加する
			if ( (int)$rqst['htlCode'] >= 9000 ){
				$rqst['applctnVrsnNmbr'] = 'T'.$rqst['applctnVrsnNmbr'];
			}

			$param =
			array(
					new XML_RPC_Value($rqst['applctnVrsnNmbr'],'string'),
					new XML_RPC_Value($rqst['lngg'],'string'),
					new XML_RPC_Value($rqst['rsrvsPrsnUid'],'string'),
					new XML_RPC_Value($room_info,'array')
			);

			$host = $this->config->config['host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationReservationService.cancellationReservationEntry";
			$rpc_path = $this->config->config['bi_service_path'];

			// XML-RPC送信
			$message = new XML_RPC_Message($method,$param);
			$c = new XML_RPC_client($rpc_path, $host, $port);
			// error
			if ($c->errno != 0){
				log_error($this->FNCTIN, 'send_request_error : '.$c->errstr);
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return;
			}
			// WEB 応答
			$response = $c->send($message, $this->config->config['xml_rpc_timeout']);
			if ($response->faultCode() != 0 ){
				log_error($this->FNCTIN, 'send_request_error : '.$c->errstr);
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return;
			}
			$resp = $response->value();
			$error_code = $resp->structmem('errrCode')->scalarval();
			$error_description = $this->lang->line($error_code);
			//Bサービスがエラーを返したとき
			if ($error_code != Api_const::BCMN0000){
				log_error($this->FNCTIN, 'send_request_error : '.$resp->structmem('errrMssg')->scalarval());
				if ($error_code == Api_const::BRSV0013){
					$error_code = Api_const::BAPI1006;
				}
				$error_description = $this->lang->line($error_code);	
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
