<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search_room_type_api extends CI_Controller {

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
		$this->load->model('vacancy_information_m','',true);
		$this->load->model('room_charge_infomation_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A006;	//部屋タイプの空室数検索API
		$this->output->enable_profiler(TRUE);	
	}
	public function index()
	{
		// initialize
		$jsonOut = array();
		$error_code = true;
		$error_description = '';
		$nmbrRoomType = '';
		$vcncyInfrmtn = '';
		
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
			$param['cityCode'] = $rqst['cityCode'];          // 都市コード
			$param['lttd'] = $rqst['lttd'];                  // 現在値の経度
			$param['lngtd'] = $rqst['lngtd'];                // 現在値の緯度
			$param['dstnc'] = $rqst['dstnc'];                // 検出範囲
			$param['roomType'] = $rqst['roomType'];			 //　客室タイプ

			//該当するホテル情報取得
			$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN, Api_const::HOTEL_ONLY);
			if ($result['errCode'] !== true) {
				$error_code = $result['errCode'];
				$error_description = $result['mssg'];
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
				$status = $this->vacancy_information_m->selectVacancy( $s_hotel_code, $param['chcknDate'], $param['nmbrNghts'], $param['nmbrPpl'], $param['mmbrshpFlag'], $param['roomType'] );
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
				return;
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
			if ($result['recCnt'] != 0){
				//20141211 仕様変更
				//取得した$resultをWeb表示様に加工する
				//取得した$resultのroom_type_codeに現在対象となる部屋タイプコード(S,W,T)が存在するかチェックする
				//※追加する場合はconfig.phpに追加すること

				$vcncyInfrmtn=array();
				//表示用部屋タイプコード（S,W,T）	
				$room_type_code=array();
				//表示用部屋タイプ名	
				$room_type_name=array();

				for($i=0;$i<count($result['recList']);$i++){
					//room_type_codeのから表示用部屋タイプコード（S,W,T）を取得する
					$code = '';
					$code = $this->config->config['api_room_type_code'][$result['recList'][$i]['room_clss_id']];
					if (!in_array($code,$room_type_code)){
						$room_type_code[]=$code;
					}
					//room_type_nameを取得
					if (!in_array($result['recList'][$i]['room_type_name_lngg'],$room_type_name)){
						$room_type_name[]=$result['recList'][$i]['room_type_name_lngg'];
					}
				}
				//nmbrRoomType(検索条件に合致する部屋タイプ数)
				$nmbrRoomType=count($room_type_code);

				//vcncyInfrmtn(空室情報)を取得する
				$room_count=array();
				$hotel_count=array();
				//初期化
				for ($j=0;$j<count($room_type_code);$j++){
					$room_count[$j]=0;
				}

				for ($j=0;$j<count($room_type_code);$j++){
					$hotel_code=array();
					for ($i=0;$i<count($result['recList']);$i++){
						//各ルームタイプの部屋数
						if (substr($result['recList'][$i]['room_type_code'],0,1)==$room_type_code[$j]){
							$room_count[$j]=$room_count[$j]+1;
							//各ルームタイプの空室を所有しているuniqueなホテル数取得
							if (!in_array($result['recList'][$i]['htl_code'],$hotel_code)){
								$hotel_code[]=$result['recList'][$i]['htl_code'];
							}
						}
					}
					//各ルームタイプの空室を所有しているuniqueなホテル数
					$hotel_count[$j]=count($hotel_code);
					$vcncyInfrmtn[$j]['roomTypeName']=$room_type_name[$j];
					$vcncyInfrmtn[$j]['roomType']=$room_type_code[$j];
					$vcncyInfrmtn[$j]['nmbrRms']=$room_count[$j];
					$vcncyInfrmtn[$j]['nmbrHtl']=$hotel_count[$j];
					$error_code = $this->api_util->setErrorCode($result['errCode']);
					$error_description = $result['mssg'];

				}

			}else{
				$error_code = Api_const::BAPI1004;
				$error_description = $this->lang->line($error_code);
			}
			
			$jsonOut['nmbrRoomType'] = $nmbrRoomType;
			$jsonOut['vcncyInfrmtn'] = $vcncyInfrmtn;

			} catch (Exception $e) {
		log_error($this->FNCTIN, 'exception : '.$e->getMessage());
			$error_code = Api_const::BAPI1001;
			$error_description = $this->lang->line($error_code);
		}
		// API終了処理
		$this->api_util->apiEnd($jsonOut, $error_code, $error_description);

	}
}