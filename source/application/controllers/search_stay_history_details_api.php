<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_stay_history_details_api extends CI_Controller {

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
		$this->load->model('hotel_info_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A019;	//宿泊履歴詳細検索API
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
					'rsrvId',
					'htlCode',
					'rsrvtnNmbr'
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
			$method = "com.toyokoinn.api.service.SmartphoneApplicationCustomerService.searchLodgingInformationDtail";
			$this->xmlrpc->server($host, $port);
			$this->xmlrpc->method($method);
			$request = array(
					array($rqst['applctnVrsnNmbr'], 'string'),
					array($rqst['lngg'], 'string'),
					array($rqst['rsrvsPrsnUid'], 'string'),
					array($rqst['rsrvId'], 'string'),
					array($rqst['htlCode'], 'string'),
					array($rqst['rsrvtnNmbr'], 'string')
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
					$checktDate = date("Ymd", strtotime($b_result['checknDate']." + ".$b_result['nmbrNghts']." day")); //チェックアウト日付
					$ttPrcIncldngTax = 0; //（合計金額（税抜き）
					$ttlOptnPrc = 0; //合計オプション料金（税込）
					$flag = array_search($b_result['smkngFlag'],$this->config->config['smoking_flag']);
					$jsonOut = array(
							'rsrvtnNmbr' => $rqst['rsrvtnNmbr'],
							'htlCode' => $rqst['htlCode'],
							'htlName' => $b_result['htlName'],
							'chcknDate' => $b_result['checknDate'],
							'chcktDate' => $checktDate,
							'roomType' => $b_result['roomType'],
							//20141028 add iwamoto 部屋タイプ名追加
							'roomName' => $b_result['roomTypeName'],
							'planCode'	=>	$b_result['planCode'],
							'planName'	=>	$b_result['planName'],
							'nmbrPpl' => $b_result['nmberPpl'],
							//喫煙フラグsmkngFlag追加 20141028 iwamoto
							'smkngFlag' => $flag,
							'fmlyName' => $b_result['fmlyName'],
							'frstName' => $b_result['frstName'],
							'sex' => $b_result['sex'],
							'mmbrshpNmbr' => $b_result['mmbrshpNmbr'],
							'phnNmbr' => $b_result['phnNmbr'],
							'ntnltyCode' => $b_result['ntnltyCode'],
							'ecoFlag' => $b_result['ecoFlag'],
							'dlyInfrmtn' => $b_result['dlyInfrmtnList'],
							'ecoChckn' =>  $b_result['ecoChckn'],
							'vodFlag' => $b_result['vodFlag'],
							'bsnssPackFlag' => $b_result['bsnssPackFlag'],
							'bsnssPackType' => $b_result['bsnssPackType'],
							'chldrnShrngBed' => $b_result['chldrnShrngBed'],
							'chcknTime' => $b_result['checknTime'],
							'ttlOptnPrc' => $ttlOptnPrc,
							'ttlPrc' => $b_result['ttlPrc'],
							//ttPrcIncldngTaxからttlPrcIncldngTaxに変更 20141023 iwamoto
							//'ttPrcIncldngTax' => $b_result['ttlPrcIncldngTax']
							'ttlPrcIncldngTax' => $b_result['ttlPrcIncldngTax'],
							//20150127 htlVldFlag追加
							'htlVldFlag' => $b_result['htlVldFlag']
							
					);
					if (array_key_exists('mmbrshpFlag', $b_result)){
						$jsonOut += array('mmbrshpFlag' => $b_result['mmbrshpFlag']);
					}
					//通貨単位の取得 20141020 add iwamoto
					// ホテル情報取得
					$recHotel = $this->hotel_info_m->select($rqst['applctnVrsnNmbr'], $rqst['htlCode'], $rqst['lngg']);
					if ($recHotel['errCode']==true){
						$jsonOut['ttlOptnPrc']=$this->api_util->priceFormat($ttlOptnPrc, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
						$jsonOut['ttlPrc']=$this->api_util->priceFormat($b_result['ttlPrc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
						//ttPrcIncldngTaxからttlPrcIncldngTaxに変更 20141023 iwamoto		
						//$jsonOut['ttPrcIncldngTax']=$this->api_util->priceFormat($ttPrcIncldngTax, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
						$jsonOut['ttlPrcIncldngTax']=$this->api_util->priceFormat($ttPrcIncldngTax, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
						for ($i=0;$i<count($jsonOut['dlyInfrmtn']);$i++){
							$jsonOut['dlyInfrmtn'][$i]['sbttlPrc']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['sbttlPrc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							$jsonOut['dlyInfrmtn'][$i]['sbttlPrcIncldngTax']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['sbttlPrcIncldngTax'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							$jsonOut['dlyInfrmtn'][$i]['prc']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['prc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							$jsonOut['dlyInfrmtn'][$i]['prcIncldngTax']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['prcIncldngTax'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
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