<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_booking_details_api extends CI_Controller {

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
		$this->load->model('consumption_tax_rate_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A015;	//予約情報詳細検索API
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
				$error_description = $this->lang->line($error_code);
				$jsonOut = "";
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			// BIサービス呼び出し
			$host = $this->config->config['bi_service_host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationReservationService.searchReservationInformationDtail";
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

					//チェックアウト日取得
					$checktDate = date("Ymd", strtotime($b_result['checknDate']." + ".$b_result['nmbrNghts']." day"));//チェックアウト日付
					$prcList = array(); //税抜室料の配列（1泊毎に設定）
					$prcIncldngTaxList = array(); //税込室料の配列（1泊毎に設定）
					$optnPrc = 0; //オプション料金
					$tax_rate = 0; //消費税

					//喫煙フラグ対応　20141028 iwamoto
					$flag = array_search($b_result['smkngFlag'],$this->config->config['smoking_flag']);
					$jsonOut = array(
							'rsrvtnNmbr' => $rqst['rsrvtnNmbr'],
							'htlCode' => $rqst['htlCode'],
							'htlName' => $b_result['htlName'],
							'chcknDate' => $b_result['checknDate'],
							'chcktDate' => $checktDate,
							'roomType' => $b_result['roomType'],
							'roomName' => $b_result['roomTypeName'],
							'planCode' => $b_result['planCode'],
							'planName' => $b_result['planName'],
							'smkngFlag' => $flag,
							'nmbrPpl' => $b_result['nmberPpl'],
							'fmlyName' => $b_result['fmlyName'],
							'frstName' => $b_result['frstName'],
							'mmbrshpFlag' =>  $b_result['mmbrshpFlag'],
							'mmbrshpNmbr' => $b_result['mmbrshpNmbr'],
							'sex' => $b_result['sex'],
							'ntnltyCode' => $b_result['ntnltyCode'],
							'phnNmbr' => $b_result['phnNmbr'],
							'ecoFlag' => $b_result['ecoFlag'],
							'dlyInfrmtn' => $b_result['dlyInfrmtnList'],
							'ecoChckn' => $b_result['ecoChckn'],
							'vodFlag' => $b_result['vodFlag'],
							'bsnssPackFlag' => $b_result['bsnssPackFlag'],
							'bsnssPackType' => $b_result['bsnssPackType'],
							'chldrnShrngBed' => $b_result['chldrnShrngBed'],
							'chcknTime' => $b_result['checknTime'],
							'optnPrc' => $optnPrc,
							'ttlPrc' => $b_result['ttlPrc'],
							'ttlPrcIncldngTax' => $b_result['ttlPrcIncldngTax']
					);
					
					if (array_key_exists('receiptType', $b_result)){
						$jsonOut += array('receiptType' => $b_result['receiptType']);
					}
					if (array_key_exists('receiptName', $b_result)){
						$jsonOut += array('receiptName' => $b_result['receiptName']);
					}
					if (array_key_exists('mmbrshpFlag', $b_result)){
						$jsonOut += array('mmbrshpFlag' => $b_result['mmbrshpFlag']);
					}
					$recHotel = $this->hotel_info_m->select($rqst['applctnVrsnNmbr'], $rqst['htlCode'], $rqst['lngg']);
					if ($recHotel['errCode']==true){

						$jsonOut['ttlPrc']=$this->api_util->priceFormat($b_result['ttlPrc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						$jsonOut['ttlPrcIncldngTax']=$this->api_util->priceFormat($b_result['ttlPrcIncldngTax'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);

						if (array_key_exists('crdtCardexprtnDate',$b_result)){
							$jsonOut['crdtCardexprtnDate']=$b_result['crdtCardexprtnDate'];
						}
						else {
							$jsonOut['crdtCardexprtnDate']='';
						}
						for ($i=0; $i<count($jsonOut['dlyInfrmtn']);$i++){
							//消費税の取得
							$tax=$this->consumption_tax_rate_m->selectTax($jsonOut['dlyInfrmtn'][$i]['trgtDate'],$rqst['htlCode'],$rqst['lngg']);
							//室料（税抜）
							$jsonOut['dlyInfrmtn'][$i]['prc']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['prc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							//室料（税込））
							$jsonOut['dlyInfrmtn'][$i]['prcIncldngTax']=$this->api_util->priceFormat($this->api_util->getAmountIncTax($jsonOut['dlyInfrmtn'][$i]['prcIncldngTax'],$tax_rate['cnsmptn_tax_rate']), $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							//室料（税抜）の配列
							$jsonOut['prcList'][$i]=$jsonOut['dlyInfrmtn'][$i]['prc'];
							//室料（税込）の配列
							$jsonOut['prcIncldngTaxList'][$i]=$jsonOut['dlyInfrmtn'][$i]['prcIncldngTax'];
							//オプション料金（税込）の集計
							$optnPrc = $optnPrc+$jsonOut['dlyInfrmtn'][$i]['optionPrc'];
						}
						//オプション料金（税込）
						$jsonOut['optnPrc']=$this->api_util->priceFormat($optnPrc, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
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