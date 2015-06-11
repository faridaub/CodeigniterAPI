<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_room_type_details_api extends CI_Controller {

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
		$this->load->model('equipment_information_m','',true);
		$this->load->model('room_image_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A010;	//部屋タイプ詳細検索API
	}

	public function index()
	{
		$jsonOut['imgURL'] = '';                         // 部屋画像URL
		$jsonOut['maxStay'] = '';                        // 最大宿泊日数。
		$jsonOut['ttlListPrc'] = '';                     // 1室の税抜一般価格
		$jsonOut['ttlMmbrPrc'] = '';                     // 1室の税抜会員価格
		$jsonOut['ttlListPrcIncldngTax'] = '';           // 1室の税込一般価格
		$jsonOut['ttlMmbrPrcIncldngTax'] = '';           // 1室の税込会員価格
		$jsonOut['brkdwn'] =  array();                   // 内訳
		$jsonOut['eqpmntInfrmtnList'] =  array();        // 客室設備・アメニティ
		$jsonOut['trmsCndtns'] = '';                     // 宿泊約款・利用規約
		$jsonOut['cnclltnPolicy'] = '';                  // キャンセル規定
		$error_code = true;
		$error_description = '';
		//$this->output->enable_profiler(TRUE);	

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

			$roomList = array();
			if ($result['recCnt'] != 0){
				$oldRow = null;
				$brkdwn = array();
				$ttlListPrc = 0;
				$ttlMmbrPrc = 0;
				$ttlListPrcIncldngTax = 0;
				$ttlMmbrPrcIncldngTax = 0;
				$eof = false;
				$idx = 0;
				while (true){
					// get row
					if ($idx < count($result['recList'])){
						$row = $result['recList'][$idx];
					}else{
						$eof = true;
					}
					// eof exit
					if ($eof == true){
						break;
					}
					// price Set
					// Web室料配列取得
					$prcs = $this->api_util->getPrices($row['prc'], $row['dscnt_amnt'], $row['mmbr_dscnt_rate'], $row['cnsmptn_tax_rate']);
					$stayDay = $idx + 1;
					$prcInf =
					array(
						'stayDay' => $stayDay,
						'listPrc' => $this->api_util->priceFormat($prcs['web_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
						'mmbrPrc' => $this->api_util->priceFormat($prcs['web_mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
						'listPrcIncldngTax' => $this->api_util->priceFormat($prcs['web_prc_inc_tax'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
						'mmbrPrcIncldngTax' => $this->api_util->priceFormat($prcs['web_mmbr_prc_inc_tax'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg']),
					);
					$ttlListPrc += $prcs['web_prc'];
					$ttlMmbrPrc += $prcs['web_mmbr_prc'];
					$ttlListPrcIncldngTax += $prcs['web_prc_inc_tax'];
					$ttlMmbrPrcIncldngTax += $prcs['web_mmbr_prc_inc_tax'];

					$brkdwn[] = $prcInf;

					// key stock
					$oldRow = $row;
					$idx++;
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);

				// 設備情報
				$param_ei['htl_infrmtn_unq_id'] = $oldRow['htl_infrmtn_unq_id'];
				$param_ei['eqpmnt_type'] = Api_const::EQPMNT_TYPE_SHITSUNAI;		// 室内設備
				$result_ei = $this->equipment_information_m->selectListCond($param_ei);
				if ($result_ei['errCode'] !== true) {
					$error_code = Api_const::BAPI1001;
					$error_description = $this->lang->line($error_code);
					$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
					return ;
				}

				$eqpmntInfrmtnList =  array();
				if ($result_ei['recCnt'] != 0){
					foreach($result_ei['recList'] as $row ){
						$eqpmntInf =
						array(
							'eqpmntName' => $row['eqpmnt_name'],
							'imgURL' => $row['img_url'],
						);
						$eqpmntInfrmtnList[] = $eqpmntInf;
					}
				}
				//部屋のイメージデータ取得
				$room_img = '';
				$imgList='';
				$room_img = $this->room_image_m->selectListCond($oldRow['unq_id'],$param['lngg']);
				if ($room_img['recCnt'] !=  0){
					for ($i=0; $i<count($room['recList']); $i++){
						$imgList[$i]['imgURL']=$room_img['recList']['img_url'];
						$imgList[$i]['imgName']=$room_img['recList']['img_name'];
						$imgList[$i]['imgDesc']=$room_img['recList']['img_desc'];
					}
				}
				$jsonOut['imgURL'] = $oldRow['room_type_img_url'];
				$jsonOut['imgList'] = $imgList;
				$jsonOut['minStay'] = $oldRow['mimm_stay'];
				$jsonOut['maxStay'] = $oldRow['mxmm_stay'];
				$jsonOut['ttlListPrc'] = $this->api_util->priceFormat($ttlListPrc, $oldRow['crrncy_name'], $oldRow['crrncy_sign'], $param['lngg'], $oldRow['rate_dsply_flag']);
				$jsonOut['ttlMmbrPrc'] = $this->api_util->priceFormat($ttlMmbrPrc, $oldRow['crrncy_name'], $oldRow['crrncy_sign'], $param['lngg'], $oldRow['rate_dsply_flag']);
				$jsonOut['ttlListPrcIncldngTax'] = $this->api_util->priceFormat($ttlListPrcIncldngTax, $oldRow['crrncy_name'], $oldRow['crrncy_sign'], $param['lngg']);
				$jsonOut['ttlMmbrPrcIncldngTax'] = $this->api_util->priceFormat($ttlMmbrPrcIncldngTax, $oldRow['crrncy_name'], $oldRow['crrncy_sign'], $param['lngg']);
				$jsonOut['brkdwn'] =  $brkdwn;
				$jsonOut['eqpmntInfrmtnList'] =  $eqpmntInfrmtnList;
				$jsonOut['trmsCndtns'] = $oldRow['trms_cndtns'];
				$jsonOut['cnclltnPolicy'] = strtr($oldRow['cnclltn_policy'], array_fill_keys(array("\r\n", "\r", "\n"), '\n'));;
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