<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_room_type_price_api extends CI_Controller {

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
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A011;	//部屋タイプ価格検索API
		//$this->output->enable_profiler(TRUE);
	}

	public function index()
	{
		$jsonOut['ttlPrc'] = '';                         // 合計金額（税抜き）
		$jsonOut['ttlPrcIncldngTax'] = '';                // 合計金額（税込み）
		$jsonOut['dlyPrcInfrmtn'] = array();             // 日別料金情報
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
				'roomType',
				'planCode',
				'nmbrNghts',
				'nmbrPpl',
				'nmbrRms',
				'ecoFlag',
				'vodFlag',
				'bsnssPackFlag'
			);
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			// 部屋タイプ詳細検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                 // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                  // 言語
			$param['mmbrshpFlag'] = $rqst['mmbrshpFlag'];    // 会員フラグ
			$param['chcknDate'] = $rqst['chcknDate'];    // チェックイン日付
			$param['htlCode'] = $rqst['htlCode'];            // ホテルコード
			$param['roomType'] = $rqst['roomType'];          // 部屋タイプコード
			$param['planCode'] = $rqst['planCode'];          // プランコード
			$param['nmbrNghts'] = $rqst['nmbrNghts'];      // 宿泊日数
			$param['nmbrPpl'] = $rqst['nmbrPpl'];            // 宿泊者数
			
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN);
			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			$ecoFlag = $rqst['ecoFlag'];
			$ecoDtsList = $rqst['ecoDtsList'];
			if ($result['recCnt'] != 0){
				$oldRow = null;
				$ttlPrc = 0;
				$ttPrcIncldngTax = 0;
				$dlyPrcInfrmtn = array();
				$idx = 0;

				foreach($result['recList'] as $row ){
					// price Set
					// Web室料配列取得
					$prcs = $this->api_util->getPrices($row['prc'], $row['dscnt_amnt'], $row['mmbr_dscnt_rate'], $row['cnsmptn_tax_rate']);

					if ($rqst['mmbrshpFlag'] == "Y"){
						$prc = $prcs['web_mmbr_prc'];
						$prcIncldngTax = $prcs['web_mmbr_prc_inc_tax'];
					}else{
						$prc = $prcs['web_prc'];
						$prcIncldngTax = $prcs['web_prc_inc_tax'];
					}
					// 日別ecoフラグ取得
					$eco = $this->api_util->getDailyEco($ecoFlag, $idx, $ecoDtsList);
					$optnPrc = $this->api_util->getOptionIncTax($eco, $rqst['vodFlag'], $rqst['bsnssPackFlag'], $rqst['bsnssPackType'], $row['cnsmptn_tax_rate']);
					$sbttPrcIncldngTax = $prcIncldngTax + $optnPrc;
					$sbttlPrc = $this->api_util->getAmount($sbttPrcIncldngTax, $row['cnsmptn_tax_rate']);

					$prcInf =
					array(
						'trgtDate' => $this->api_date->addDay($rqst['chcknDate'], $idx),
						'prc' => $this->api_util->priceFormat($prc, $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
						'prcIncldngTax' => $this->api_util->priceFormat($prcIncldngTax, $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
						'optnPrc' => $this->api_util->priceFormat($optnPrc, $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
						'sbttlPrc' => $this->api_util->priceFormat($sbttlPrc, $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
						'sbttlPrcIncldngTax' => $this->api_util->priceFormat($sbttPrcIncldngTax, $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
						);
					$dlyPrcInfrmtn[] = $prcInf;

					// summary
					$ttlPrc += $sbttlPrc;
					$ttPrcIncldngTax += $sbttPrcIncldngTax;
					// key stock
					$oldRow = $row;
					$idx++;
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);

				$jsonOut['ttlPrc'] = $this->api_util->priceFormat($ttlPrc, $oldRow['crrncy_name'], $oldRow['crrncy_sign'], $param['lngg'], $oldRow['rate_dsply_flag']);
				$jsonOut['ttlPrcIncldngTax'] = $this->api_util->priceFormat($ttPrcIncldngTax, $oldRow['crrncy_name'], $oldRow['crrncy_sign'], $param['lngg']);
				$jsonOut['dlyPrcInfrmtn'] =  $dlyPrcInfrmtn;
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