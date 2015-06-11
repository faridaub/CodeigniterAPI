<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_hotel_vacant_api extends CI_Controller {

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
		$this->load->model('hotel_info_m','',true);
		$this->load->model('room_charge_infomation_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A008;	//ホテル空室数検索API
	}

	public function index()
	{
		// initialize
		$jsonOut['htlList'] = array();
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
				'nmbrNghts',
				'nmbrPpl',
				'nmbrRms',
				'mode'
			);
			//20141010 add iwamoto
			if ($rqst['mode']==$this->config->config['keyword_search']){//目的地
				array_push($ids,'kywrd');
			}
			if ($rqst['mode']==$this->config->config['area_search']){//エリア
				array_push($ids,'cntryCode','areaCode','sttCode');
			}
			if ($rqst['mode']==$this->config->config['current_position_search']){//現在地
				array_push($ids,'lngtd','lttd','dstnc');
			}
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $chk_cmmn['mssg'];
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}
			// 部屋タイプ別空室数検索
			$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
			$param['apikey'] = $rqst['key'];                 // APIキー
			$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr']; // アプリのバージョン
			$param['lngg'] = $rqst['lngg'];                  // 言語
			$param['mmbrshpFlag'] = $rqst['mmbrshpFlag'];    // 会員フラグ
			$param['chcknDate'] = $rqst['chcknDate'];        // チェックイン日付
			$param['nmbrNghts'] = $rqst['nmbrNghts'];        // 宿泊日数
			$param['nmbrPpl'] = $rqst['nmbrPpl'];            // 宿泊者数
			$param['nmbrRms'] = $rqst['nmbrRms'];            // 部屋数
			$param['smkngFlag'] = $rqst['smkngFlag'];        // 喫煙・禁煙区分
			$param['mode'] = $rqst['mode'];                  // 検索方法
			$param['kywrd'] = $rqst['kywrd'];                // 目的地のキーワード
			$param['cntryCode'] = $rqst['cntryCode'];        // 国コード
			$param['areaCode'] = $rqst['areaCode'];          // エリアコード
			$param['sttCode'] = $rqst['sttCode'];            // 都道府県コード
			$param['lttd'] = $rqst['lttd'];                  // 現在値の経度
			$param['lngtd'] = $rqst['lngtd'];                // 現在値の緯度
			$param['dstnc'] = $rqst['dstnc'];                // 検出範囲
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN);

			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $result['mssg'];
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			$htlList = array();
			if ($result['recCnt'] != 0){
				$htl = null;
				$oldRow = null;
				$lowPrc = null;
				$vcncy = Api_const::MAX_VCNCY;
				$vcncyHtl = 0;
				$eof = false;
				$idx = 0;
				while (true){
					// get row
					if ($idx < count($result['recList'])){
						$row = $result['recList'][$idx];
					}else{
						$eof = true;
					}
					// Key Break [htl_code , room_type_code]
					if ($eof == true || ($lowPrc != null && ($oldRow['htl_code'] != $row['htl_code'] || $oldRow['room_type_code'] != $row['room_type_code']))){
						$vcncyHtl += $vcncy;
						$vcncy = Api_const::MAX_VCNCY;
					}
					// Key Break [htl_code ]
					if ($eof == true || ($lowPrc != null && $oldRow['htl_code'] != $row['htl_code'])){
						$lowPrc['nmbrRrms'] = $vcncyHtl;
						$vcncyHtl = 0;
						$htlList[] = $lowPrc;
						$lowPrc = null;
						$vcncy = Api_const::MAX_VCNCY;
					}
					// eof exit
					if ($eof == true){
						break;
					}
					// min残数(部屋タイプ単位)を保持
					if ($vcncy > $row['vcncy']) {
						$vcncy = $row['vcncy'];
					}
					// lowPrice Set
					if ($lowPrc == null || $row['prc'] < $lowPrc['prc']){
						// Web室料配列取得
						$prcs = $this->api_util->getPrices($row['prc'], $row['dscnt_amnt'], $row['mmbr_dscnt_rate'], $row['cnsmptn_tax_rate']);
						$lowPrc =
						array(
							'htlCode' => $row['htl_code'],
							'htlName' => $row['htl_name'],
							'imgURL' => $row['htl_img_url'],
							'listPrc' => $this->api_util->priceFormat($row['prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'mmbrPrc' => $this->api_util->priceFormat($prcs['mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'offclWebDscntPrc' => $this->api_util->priceFormat($prcs['web_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'mmbrOffclWebDscntPrc' => $this->api_util->priceFormat($prcs['web_mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'dstncCrrntPstn' => $this->api_util->distanceFormat($row['dstnc']),
							'prc' => $row['prc'],
						);
					}
					// key stock
					$oldRow = $row;
					$idx++;
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $result['mssg'];
			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
			}
			$jsonOut['htlList'] = $htlList;
		} catch (Exception $e) {
			log_error($this->FNCTIN, 'exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);

	}
}