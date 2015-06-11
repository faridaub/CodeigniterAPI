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
		$this->load->model('vacancy_information_m','',true);
		$this->load->model('keyword_information_m','',true);

		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A008;	//ホテル空室数検索API
		//$this->output->enable_profiler(TRUE);
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
			if ($rqst['mode']==$this->config->config['keyword_search']){//目的地
				array_push($ids,'kywrd');
			}
			if ($rqst['mode']==$this->config->config['area_search']){//エリア
				array_push($ids,'cntryCode','areaCode','sttCode','cityCode');
			}
			if ($rqst['mode']==$this->config->config['current_position_search']){//現在地
				array_push($ids,'lngtd','lttd','dstnc');
			}
			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);

			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
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
			$param['cityCode'] = $rqst['cityCode'];          // 都市コード
			$param['lngtd'] = $rqst['lngtd'];                // 現在値の緯度
			$param['lttd'] = $rqst['lttd'];                  // 現在値の経度
			$param['dstnc'] = $rqst['dstnc'];                // 検出範囲
			$param['roomType'] = $rqst['roomType'];			 //　客室タイプ

			//該当するホテル情報取得
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN, Api_const::HOTEL_ONLY);

			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			if ($result['recCnt'] != 0){

				$status = 0;
				$s_hotel_code = '';

				for($i=0;$i<count($result['recList']);$i++){
					$s_hotel_code[] = $result['recList'][$i]['htl_code'];
				}

				//空室の有無を確認する
				$status = $this->vacancy_information_m->selectVacancy($s_hotel_code, $param['chcknDate'], $param['nmbrNghts'], $param['nmbrPpl'], $param['mmbrshpFlag'], $param['roomType'] );

				//空室無しの時
				if ($status == 0 ){
					$error_code = Api_const::BAPI1007;
					$error_description = $this->lang->line($error_code);
					$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
					return;
				}

			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;

			}
			
			$result = array();
			//該当する空室があるホテル情報取得
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN, Api_const::HOTEL_AND_VACANCY);
			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $this->lang->line($error_code);
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
							'lngtd' => $row['lngtd'],
							'lttd' => $row['lttd'],
							'listPrc' => $this->api_util->priceFormat($row['prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'mmbrPrc' => $this->api_util->priceFormat($prcs['mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'offclWebDscntPrc' => $this->api_util->priceFormat($prcs['web_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'mmbrOffclWebDscntPrc' => $this->api_util->priceFormat($prcs['web_mmbr_prc'], $row['crrncy_name'], $row['crrncy_sign'], $param['lngg'], $row['rate_dsply_flag']),
							'dstncCrrntPstn' => $this->api_util->distanceFormat($row['dstnc']),
							'prc' => $row['prc'],
						);
						//ホテルへのアクセス方法追加
						$recKi = array();
						$key_params = array();
						$key_params['htl_infrmtn_unq_id'] = $row['htl_infrmtn_unq_id'];
						$params['kywrd_type >='] = Api_const::KEYWORD_TYPE_EKI;
						$params['kywrd_type <='] = Api_const::KEYWORD_TYPE_BUS;
						$recKi = $this->keyword_information_m->selectListCond($key_params);
						if ( $recKi['recList'] == ''){
							$accssInfmtnList[] =	array(
								'accssInfmtn' => ''
							);
						}
						else {
							foreach ($recKi['recList'] as $value) {
								$accssInfmtnList[] =	array(
									'accssInfmtn' => $value['rt_name'].$value['kywrd_name'].$this->api_util->getMnsTrnsprttnName($value['mns_trnsprttn']).$value['time_rqrd'],
								);
							}
						}
						$lowPrc += $accssInfmtnList;
					}
					// key stock
					$oldRow = $row;
					$idx++;
				}
				$error_code = $this->api_util->setErrorCode($result['errCode']);
				$error_description = $this->lang->line($error_code);
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