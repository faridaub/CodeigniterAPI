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
		$this->lang->load('error', 'japanese');
		$this->FNCTIN = Api_const::A015;	//予約情報詳細検索API
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
					//20141126 必須項目解除　iwamoto
					//'pageNmbr',
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
					//消費税の取得
//					$tax=$this->consumption_tax_rate_m->selectTax($b_result['checknDate'],$rqst['htlCode']);
$tax=8.0;
					$optnPrc = 0; //オプション料金
					$optnPrc = $this->api_util->getOption($b_result['ecoFlag'], $b_result['vodFlag'], $b_result['bsnssPackFlag'],$b_result['bsnssPackType'], $tax/100);
					$prcList = '0'; //税抜室料の配列（1泊毎に設定）
					$prcIncldngTaxList = 0; //税込室料の配列（1泊毎に設定）
					//喫煙フラグ対応　20141028 iwamoto
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
							//喫煙フラグ対応　20141028 iwamoto
							'smkngFlag' => $flag,
							'nmbrPpl' => $b_result['nmberPpl'],
							'fmlyName' => $b_result['fmlyName'],
							'frstName' => $b_result['frstName'],
							'mmbrshpFlag' =>  $b_result['mmbrshpFlag'],
							'mmbrshpNmbr' => $b_result['mmbrshpNmbr'],
							//20140922 iwamoto　性別追加
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
//							'prcList' => $prcList,
//							'prcIncldngTaxList' => $prcIncldngTaxList,
							'optnPrc' => $optnPrc,
							//ttlPrcList→ttlPrcに変更　20141031 iwamoto
							'ttlPrc' => $b_result['ttlPrc'],
							//'ttlPrcList' => $b_result['ttlPrc'],
							//ttPrcIncldngTaxListからttlPrcIncldngTaxListに変更 20141023 iwamoto
							//'ttPrcIncldngTaxList' => $b_result['ttlPrcIncldngTax']
							//ttlPrcIncldngTaxList→ttlPrcIncldngTaxに変更 20141031 iwamoto
							//'ttlPrcIncldngTaxList' => $b_result['ttlPrcIncldngTax']
							'ttlPrcIncldngTax' => $b_result['ttlPrcIncldngTax']
					);
					if (array_key_exists('mmbrshpFlag', $b_result)){
						$jsonOut += array('mmbrshpFlag' => $b_result['mmbrshpFlag']);
					}
					//20141020 通貨記号追加 iwamoto
					// ホテル情報取得
					$recHotel = $this->hotel_info_m->select($rqst['applctnVrsnNmbr'], $rqst['htlCode'], $rqst['lngg']);
					if ($recHotel['errCode']==true){

						$ttlPrcList=$b_result['ttlPrc'];
						$ttPrcIncldngTaxList=$b_result['ttlPrcIncldngTax'];

						$jsonOut['prcList']=$this->api_util->priceFormat($prcList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
						$jsonOut['prcIncldngTaxList']=$this->api_util->priceFormat($prcIncldngTaxList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						$jsonOut['optnPrc']=$this->api_util->priceFormat($optnPrc, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						//ttlPrcIncldngTaxList→ttlPrcIncldngTaxに変更 20141031 iwamoto
						//$jsonOut['ttlPrcList']=$this->api_util->priceFormat($ttlPrcList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						$jsonOut['ttlPrc']=$this->api_util->priceFormat($ttlPrcList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						
						//ttPrcIncldngTaxListからttlPrcIncldngTaxListに変更 20141023 iwamoto
						//$jsonOut['ttPrcIncldngTaxList']=$this->api_util->priceFormat($ttPrcIncldngTaxList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						//ttlPrcIncldngTaxList→ttlPrcIncldngTaxに変更 20141031 iwamoto
						//$jsonOut['ttlPrcIncldngTaxList']=$this->api_util->priceFormat($ttPrcIncldngTaxList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);
						$jsonOut['ttlPrcIncldngTax']=$this->api_util->priceFormat($ttPrcIncldngTaxList, $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg']);

						//20141216 クレジットカード有効期限 追加 iwamoto
						if (array_key_exists('crdtCardexprtnDate',$b_result)){
							$jsonOut['crdtCardexprtnDate']=$b_result['crdtCardexprtnDate'];
						}
						else {
							$jsonOut['crdtCardexprtnDate']='';
						}
						for ($i=0; $i<count($jsonOut['dlyInfrmtn']);$i++){
//							$jsonOut['dlyInfrmtn'][$i]['sbttlPrc']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['sbttlPrc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
//							$jsonOut['dlyInfrmtn'][$i]['sbttlPrcIncldngTax']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['sbttlPrcIncldngTax'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							$jsonOut['dlyInfrmtn'][$i]['prc']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['prc'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);
							$jsonOut['dlyInfrmtn'][$i]['prcIncldngTax']=$this->api_util->priceFormat($jsonOut['dlyInfrmtn'][$i]['prcIncldngTax'], $recHotel['rec']['crrncy_name'], $recHotel['rec']['crrncy_sign'], $rqst['lngg'],Api_const::RATE_DSPLY_FLG_ON);

							//eco適用の取得
							if (!empty($jsonOut['dlyInfrmtn'][$i]['ecoFlag'])){
								$jsonOut['dlyInfrmtn'][$i]['ecoDtsList']=$jsonOut['dlyInfrmtn'][$i]['ecoFlag'];	
							}
						}
					}
				}
				if (array_key_exists('errrMssg', $b_result)){
					$error_description = $b_result['errrMssg'];
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