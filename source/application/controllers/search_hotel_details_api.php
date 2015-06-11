<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_hotel_details_api extends CI_Controller {

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
		$this->load->library('xmlrpc');
		$this->load->model('apikey_information_m','',true);
		$this->load->model('application_version_control_m','',true);
		$this->load->model('operational_log_m','',true);
		$this->load->model('hotel_info_m','',true);
		$this->load->model('keyword_information_m','',true);
		$this->load->model('credit_infomation_m','',true);
		$this->load->model('equipment_information_m','',true);
		$this->load->model('hotel_image_m','',true);

		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A007;	//ホテル詳細情報検索API
	}

	public function index()
	{
		// initialize
		$jsonOut['htlName'] = '';
		$jsonOut['addrss'] = '';
		$jsonOut['accssInfmtnList'] = array();
		$jsonOut['prkngInfmtn'] = '';
		$jsonOut['busInfmtn'] = '';
		$jsonOut['pckpInfmtn'] = '';
		$jsonOut['rntcrInfmtn'] = '';
		$jsonOut['chcknTime'] = '';
		$jsonOut['chcktTime'] = '';
		$jsonOut['brkfstTime'] = '';
		$jsonOut['crdtInfrmtnList'] = array();
		$jsonOut['eqpmntInfrmtnList'] = array();
		$jsonOut['brrrfrInfmtn'] = '';
		$jsonOut['isoInfmtn'] = '';
		$jsonOut['phnNmbr'] = '';
		$jsonOut['imgURL'] = '';
		$jsonOut['cntryCode'] = '';
		$jsonOut['areaCode'] = '';
		$jsonOut['sttCode'] = '';
		$jsonOut['cityCode'] = '';
		$jsonOut['crrncyName'] = '';
		$jsonOut['crrncySign'] = '';
		$jsonOut['lngtd'] = '';
		$jsonOut['lttd'] = '';
		$jsonOut['nshwCrdtSttmntFlag'] = '';
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
				'htlCode',
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			// ホテル詳細情報検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                 // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                  // 言語
			$param['htlCode'] = $rqst['htlCode'];            // ホテルコード
			$result = $this->hotel_info_m->selectJoinListCond($param, $this->FNCTIN);

			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			$accssInfmtnList = array();
			$crdtInfrmtnList = array();
			$eqpmntInfrmtnList = array();
			if ($result['recCnt'] != 0){
				// キーワード情報
				//データがない場合　20141017 iwamoto
				if ($result['recKi']['recList']==''){
					$accssInfmtnList[] =	array(
						'accssInfmtn' => ''
					);
				}
				else {
					foreach ($result['recKi']['recList'] as $value) {
						$accssInfmtnList[] =	array(
							'accssInfmtn' => $value['rt_name'].$value['kywrd_name'].$this->api_util->getMnsTrnsprttnName($value['mns_trnsprttn']).$value['time_rqrd'],
						);
					}
				}
				// クレジット情報
				//データがない場合　20141017 iwamoto
				if ($result['recCi']['recList']==''){
					$crdtInfrmtnList[] =	array(
						'crdtCode' => '',
						'creditName' => '',
						'imgURL' => ''
					);
				}
				else{
					foreach ($result['recCi']['recList'] as $value) {
						$crdtInfrmtnList[] =	array(
							'crdtCode' => $value['crdt_code'],
							'creditName' => $value['crdt_name'],
							'imgURL' => $value['img_url'],
						);
					}
				}
				// 設備情報
				//データがない場合　20141017 iwamoto
				if ($result['recEn']['recList']==''){
					$eqpmntInfrmtnList[] =	array(
						'eqpmntType' => '',
						'eqpmntName' => '',
						'imgURL' => ''
					);
				}
				else{
					foreach ($result['recEn']['recList'] as $value) {
						$eqpmntInfrmtnList[] =	array(
							'eqpmntType' => $value['eqpmnt_type'],
							'eqpmntName' => $value['eqpmnt_name'],
							'imgURL' => $value['img_url'],
						);
					}
				}
				// ホテル情報
				foreach ($result['recList'] as $value) {
					$jsonWk =	array(
						'unq_id' => $value['unq_id'],
						'htlName' => $value['htl_name'],
						'addrss' => $value['addrss'],
						'accssInfmtnList' => $accssInfmtnList,
						'prkngInfmtn' => strtr($value['prkng_infmtn'],array_fill_keys(array("\r\n", "\r", "\n"), '\n')),
						'busInfmtn' => $value['bus_infmtn'],
						'pckpInfmtn' => $value['pikp_infmtn'],
						'rntcrInfmtn' => $value['rntcr_infmtn'],
						'chcknTime' => $value['chckn_time'],
						'chcktTime' => $value['chckt_time'],
						'brkfstTime' => $value['brkfst_time'],
						'crdtInfrmtnList' => $crdtInfrmtnList,
						'eqpmntInfrmtnList' => $eqpmntInfrmtnList,
						'brrrfrInfmtn' => $value['brrrfr_infmtn'],
						'isoInfmtn' => $value['iso_infmtn'],
						'phnNmbr' => $value['phn_nmbr'],
						'imgURL' => $value['img_url'],
						'cntryCode' => $value['cntry_code'],
						'areaCode' => $value['area_code'],
						'sttCode' => $value['stt_code'],
						'cityCode' => $value['city_code'],
						'crrncyName' => $value['crrncy_name'],
						'crrncySign' => $value['crrncy_sign'],
						'lngtd' => $value['lngtd'],
						'lttd' => $value['lttd'],
						'nshwCrdtSttmntFlag' => $value['nshw_crdt_sttmnt_flag'],
					);
					break;
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);
				
				//ホテルイメージデータ取得
				$hotel_img　= '';
				$imgList='';
				$hotel_img = $this->hotel_image_m->selectListCond($jsonWk['unq_id'], $param['lngg']);
				if ($hotel_img['recCnt'] !=  0){
					for ($i=0; $i<count($hotel_img['recList']); $i++){
						$imgList[$i]['imgURL']=$hotel_img['recList']['img_url'];
						$imgList[$i]['imgName']=$hotel_img['recList']['img_name'];
						$imgList[$i]['imgDesc']=$hotel_img['recList']['img_desc'];
					}
				}
				
				$jsonOut['htlName'] = $jsonWk['htlName'];
				$jsonOut['addrss'] = $jsonWk['addrss'];
				$jsonOut['accssInfmtnList'] = $jsonWk['accssInfmtnList'];
				$jsonOut['prkngInfmtn'] = $jsonWk['prkngInfmtn'];
				$jsonOut['busInfmtn'] = $jsonWk['busInfmtn'];
				$jsonOut['pckpInfmtn'] = $jsonWk['pckpInfmtn'];
				$jsonOut['rntcrInfmtn'] = $jsonWk['rntcrInfmtn'];
				$jsonOut['chcknTime'] = $jsonWk['chcknTime'];
				$jsonOut['chcktTime'] = $jsonWk['chcktTime'];
				$jsonOut['brkfstTime'] = $jsonWk['brkfstTime'];
				$jsonOut['crdtInfrmtnList'] = $jsonWk['crdtInfrmtnList'];
				$jsonOut['eqpmntInfrmtnList'] = $jsonWk['eqpmntInfrmtnList'];
				$jsonOut['brrrfrInfmtn'] = $jsonWk['brrrfrInfmtn'];
				$jsonOut['isoInfmtn'] = $jsonWk['isoInfmtn'];
				$jsonOut['phnNmbr'] = $jsonWk['phnNmbr'];
				$jsonOut['imgURL'] = $jsonWk['imgURL'];
				$jsonOut['imgList'] = $imgList;
				$jsonOut['cntryCode'] = $jsonWk['cntryCode'];
				$jsonOut['areaCode'] = $jsonWk['areaCode'];
				$jsonOut['sttCode'] = $jsonWk['sttCode'];
				$jsonOut['cityCode'] = $jsonWk['cityCode'];
				$jsonOut['crrncyName'] = $jsonWk['crrncyName'];
				$jsonOut['crrncySign'] = $jsonWk['crrncySign'];
				$jsonOut['lngtd'] = $jsonWk['lngtd'];
				$jsonOut['lttd'] = $jsonWk['lttd'];
				$jsonOut['nshwCrdtSttmntFlag'] = $jsonWk['nshwCrdtSttmntFlag'];

				//閲覧履歴登録追加 20141020 iwamoto
				//予約者情報UIDが設定されている場合閲覧履歴登録追加を行う
				if (array_key_exists('rsrvsPrsnUid',$rqst)){ 
					if ($rqst['rsrvsPrsnUid']!= '' ){
				
						$host = $this->config->config['bi_service_host'];
						$port = $this->config->config['bi_service_port'];
						$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.entryBrowsingHistory";
						$this->xmlrpc->server($host,$port);
						$this->xmlrpc->method($method);
						$request = array(
								array($rqst['applctnVrsnNmbr'], 'string'),
								array($rqst['lngg'], 'string'),
								array($rqst['rsrvsPrsnUid'], 'string'),
								array($rqst['htlCode'], 'string')
						);
						
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
					}
				}
			}else{
				$error_code = Api_const::BAPI1004;
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