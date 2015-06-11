<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Register_reservation_api extends CI_Controller {

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
		$this->load->model('option_charge_infomation_m','',true);
		$this->lang->load('error', $this->api_util->getErrLang());
		$this->FNCTIN = Api_const::A013;	//予約登録API
//$this->output->enable_profiler(TRUE);	
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
					'nmbrRms',
					'htlCode',
					'room1_chcknDate',
					'rsrvsPrsnUid',
					'room1_chcktDate',
					'room1_roomType',
					'room1_planCode',
					'room1_nmbrPpl',
					'room1_fmlyName',
					'room1_frstName',
					'room1_sex',
					'room1_mmbrshpFlag',
					'room1_ntnltyCode',
					'room1_phnNmbr',
					'room1_ecoFlag',
					'room1_ecoChckn',
					'room1_vodFlag',
					'room1_bsnssPackFlag',
					'room1_chldrnShrngBed',
					'room1_chcknTime',
					'room1_prcIncldngTaxList',
					'room1_receiptType'
			);

			if ( $rqst['room1_receiptType'] == '3' ){
				array_push( $ids, "room1_receiptName" );		
			}

			// API共通チェック
			$chk_cmmn = $this->api_util->chkApiCommon($rqst, $ids);
			if ($chk_cmmn['errCode'] !== true) {
				$error_code = $chk_cmmn['errCode'];
				$error_description = $this->lang->line($error_code);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return ;
			}

			//キャッシュDBよりデータ取得
			$ttlPrc = 0;
			$ttPrcIncldngTax = 0;
			$room_info = "";

			for ($nmbr=1; $nmbr<=$rqst['nmbrRms']; $nmbr++ ) {
				
				$rmNmbr = 'room'.$nmbr;
				// 部屋タイプ詳細検索
				$param['apikey_information_mode'] = $chk_cmmn['apikey_information_mode'];
				$param['apikey'] = $rqst['key'];                         // APIキー
				$param['applctnVrsnNmbr'] = $rqst['applctnVrsnNmbr'];    // アプリのバージョン
				$param['lngg'] = $rqst['lngg'];                          // 言語
				$param['htlCode'] = $rqst['htlCode'];                    // ホテルコード
				$mmbrshpFlag = $rqst[$rmNmbr.'_mmbrshpFlag'];
				$param['mmbrshpFlag'] = $mmbrshpFlag;                    // 会員フラグ
				$param['chcknDate'] = $rqst[$rmNmbr.'_chcknDate'];       // チェックイン日付
				$param['roomType'] = $rqst[$rmNmbr.'_roomType'];         // 部屋タイプコード
				$param['planCode'] = $rqst[$rmNmbr.'_planCode'];         // プランコード				
				$nmbrNghts = $this->api_date->dayDiff($rqst[$rmNmbr.'_chcknDate'], $rqst[$rmNmbr.'_chcktDate']);
				$param['nmbrNghts'] = $nmbrNghts;                        // 宿泊日数
				$param['nmbrPpl'] = $rqst[$rmNmbr.'_nmbrPpl'];           // 宿泊者数
				$param['receiptType'] = $rqst[$rmNmbr.'_receiptType'];   // 領収書タイプ
				$param['receiptName'] = $rqst[$rmNmbr.'_receiptName'];   // 領収書宛名
				
				
				//$param['nmbrRms']=1;                                 // 部屋数(空室を見ない為)

				$result = $this->room_charge_infomation_m->selectJoinListCond($param, $this->FNCTIN);
				if ($result['errCode'] !== true) {
					$error_code = $result['errCode'];
					$error_description = $this->lang->line($error_code);
					$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
					return ;
				}
				$ecoFlag = $rqst[$rmNmbr.'_ecoFlag'];
				$ecoDtsList = $rqst[$rmNmbr.'_ecoDtsList'];
				$vodFlag = $rqst[$rmNmbr.'_vodFlag'];
				$bsnssPackFlag = $rqst[$rmNmbr.'_bsnssPackFlag'];
				$bsnssPackType = $rqst[$rmNmbr.'_bsnssPackType'];
				$chldrnShrngBed =$rqst[$rmNmbr.'_chldrnShrngBed'];


				$optnPrc = 0;
				$optnPrcTax = 0;
				$optnttPrcTax = 0;
				$prc = 0;
				$sbttPrc = 0;
				$sbttPrcIncldngTax = 0;
				
				//ecoプランのチック　20141106　iwamoto
				if ($ecoFlag=='Y') {
					$ret=$this->api_util->ecoCheck($ecoDtsList,$rqst[$rmNmbr.'_chcknDate'],$rqst[$rmNmbr.'_chcktDate']);
					//ecoプランの適用外の場合は終了
					if (!$ret){
						$error_code = Api_const::BAPI1005;
						$error_description = $this->lang->line($error_code);
						$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
						return ;
					}
				}
				if ($result['recCnt'] != 0){
					$trgtDate = $param['chcknDate'];
					$oldRow = null;
					$idx = 0;
					$day = "";
					
					foreach($result['recList'] as $row ){
						// price Set
						// Web室料配列取得
						$prcs = $this->api_util->getPrices($row['prc'], $row['dscnt_amnt'], $row['mmbr_dscnt_rate'], $row['cnsmptn_tax_rate']);
						if ($mmbrshpFlag=="Y"){
							//室料税抜
							$prc = $prcs['web_mmbr_prc'];
							//室料税込
							$prcIncldngTax = $prcs['web_mmbr_prc_inc_tax'];
						}else{
							//室料税抜
							$prc = $prcs['web_prc'];
							//室料税込							
							$prcIncldngTax = $prcs['web_prc_inc_tax'];
						}
						// 日別ecoフラグ取得
						//eco宿泊金額計算ロジック修正　20141106　iwamoto
						//$eco = $this->api_util->getDailyEco($ecoFlag, $idx, $ecoDtsList);
						//$optnPrcTax = $this->api_util->getOptionIncTax($eco, $vodFlag, $bsnssPackFlag, $bsnssPackType, $row['cnsmptn_tax_rate']);
						$eco = 'N';
						if ($ecoFlag=='Y') {
							$eco=$this->api_util->checkDailyEco($trgtDate,$ecoDtsList);
						}
						//オプション税抜
						$optnPrcRet = $this->api_util->getOptionByDb($eco, $vodFlag, $bsnssPackFlag, $bsnssPackType, $chldrnShrngBed, $row['cnsmptn_tax_rate'], $param['htlCode'], $trgtDate);
						if ($optnPrcRet['errCode'] !== true) {
							$error_code = $result['errCode'];
							$error_description = $this->lang->line($error_code);
							$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
							return ;
						}
						else {
						$optnPrc = $optnPrcRet['option'];
						}

						//オプション税込
						$optnPrcTaxRet = $this->api_util->getOptionIncTaxByDb($eco, $vodFlag, $bsnssPackFlag, $bsnssPackType, $chldrnShrngBed, $param['htlCode'], $trgtDate);
						if ($optnPrcTaxRet['errCode'] !== true) {
							$error_code = $result['errCode'];
							$error_description = $this->lang->line($error_code);
							$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
							return ;
						}
						else {
							$$optnPrcTax = $optnPrcTaxRet['option'];
						}
						//小計税抜
						$sbttlPrc = $prc + $optnPrc;
						//小計税込
						$sbttlPrcIncldngTax = $prcIncldngTax + $optnPrcTax;

						// summary
						$optnttPrcTax += $optnPrcTax;
						$sbttPrc += $sbttlPrc;
						$sbttPrcIncldngTax += $sbttlPrcIncldngTax;
						// key stock
						$oldRow = $row;
						$idx++;
						
						$option_Array = array();
						$option_Array = $this->api_util->getOptionIncArray($eco, $vodFlag, $bsnssPackFlag, $bsnssPackType, $row['cnsmptn_tax_rate']);

						$mmbrDscntRate=(int)(($prcs['web_prc']-$prcs['web_mmbr_prc'])/$prcs['web_prc']*100);
						// TODO:
						$param2 =
						array(
								'trgtDate'=>			new XML_RPC_Value($trgtDate, 'string'),
								'prc'=>					new XML_RPC_Value($prcs['web_prc'], 'string'),//宿泊料金(税抜き)
								'prcIncldngTax'=>		new XML_RPC_Value($prcs['web_prc_inc_tax'], 'string'),//宿泊料金(税込)
								'prcDscnt'=>			new XML_RPC_Value($prcs['web_mmbr_prc'], 'string'),
								'prcDscntIncldngTax'=>	new XML_RPC_Value($prcs['web_mmbr_prc_inc_tax'], 'string'),
								'mmbrDscntRate'=>		new XML_RPC_Value($mmbrDscntRate, 'string'),
								'webDscntDmnt'=>		new XML_RPC_Value($row['dscnt_amnt'], 'string'),
								'roomChrgVod'=>			new XML_RPC_Value($option_Array['vod_amnt'], 'string'),
								'roomChrgBiz'=>			new XML_RPC_Value($option_Array['bsnss_pack_amnt_tax'], 'string'),
								'roomChrgEco'=>			new XML_RPC_Value($option_Array['eco_amnt'], 'string'),
							
								'sbttlPrc'=>			new XML_RPC_Value($sbttlPrc, 'string'),//小計
								'sbttlPrcIncldngTax'=>	new XML_RPC_Value($sbttlPrcIncldngTax, 'string'),//小計(税込み)
								'ecoFlag'=>				new XML_RPC_Value($eco, 'string'),//ecoフラグ
								'class'=>				new XML_RPC_Value('com.toyokoinn.api.app.entity.DailyInformation')
						);
						
						$day[] = new XML_RPC_Value($param2,'struct');
						$trgtDate = $this->api_date->addDay($trgtDate, 1);
					}

					//$sbttlPrc=$this->api_util->getAmount($sbttPrcIncldngTax, $oldRow['cnsmptn_tax_rate']);
					//$ttlPrc += $sbttPrc;
					$ttPrcIncldngTax += $sbttPrcIncldngTax;
					$ttlPrc = $this->api_util->getAmount($ttPrcIncldngTax, $oldRow['cnsmptn_tax_rate']);

				}else {
					// 4部屋の内,一部屋でも0件の場合,データ無エラー
					$error_code = Api_const::BAPI1004;
					$error_description = $this->lang->line($error_code);
					$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
					return ;
				}
				//宿泊日数
				$nmbrNghts = (strtotime($rqst[$rmNmbr.'_chcktDate'])-strtotime($rqst[$rmNmbr.'_chcknDate']))/(3600*24);
				// TODO:
				$param1 =
				array(
						'srlNmbr'=>				new XML_RPC_Value($nmbr, 'string'),
						'htlCode'=>		 		new XML_RPC_Value($rqst['htlCode'], 'string')
				);
				//必須項目以外
				if (array_key_exists($rmNmbr.'_rsrvtnNmbr',$rqst)&&$rqst[$rmNmbr.'_rsrvtnNmbr'] != ""){
					$param1 += array('rsrvtnNmbr'=>new XML_RPC_Value($rqst[$rmNmbr.'_rsrvtnNmbr'], 'string'));
				}
				else {
					$param1 += array('rsrvtnNmbr'=>new XML_RPC_Value("", 'string'));
				}
			
				$param1 += array('roomType'=>			new XML_RPC_Value($rqst[$rmNmbr.'_roomType'], 'string'));
				$param1 += array('planCode'=>			new XML_RPC_Value($rqst[$rmNmbr.'_planCode'], 'string'));
				$param1 += array('chcknDate'=>			new XML_RPC_Value($rqst[$rmNmbr.'_chcknDate'], 'string'));
				$param1 += array('chcknTime'=>			new XML_RPC_Value($rqst[$rmNmbr.'_chcknTime'], 'string'));
				$param1 += array('nmbrNghts'=>			new XML_RPC_Value($nmbrNghts, 'string'));
				$param1 += array('nmberPpl'=>			new XML_RPC_Value($rqst[$rmNmbr.'_nmbrPpl'], 'string'));
				//必須項目以外
				if (array_key_exists($rmNmbr.'_mmbrshpFlag',$rqst)&&$rqst[$rmNmbr.'_mmbrshpFlag'] != ""){
					$param1 += array('mmbrshpFlag'=>new XML_RPC_Value($rqst[$rmNmbr.'_mmbrshpFlag'], 'string'));
				}
				else {
					$param1 += array('mmbrshpFlag'=>new XML_RPC_Value("", 'string'));
				}
				$param1 += array('ecoFlag'=>			new XML_RPC_Value($rqst[$rmNmbr.'_ecoFlag'], 'string'));
				$param1 += array('vodFlag'=>			new XML_RPC_Value($rqst[$rmNmbr.'_vodFlag'], 'string'));
				$param1 += array('bsnssPackFlag'=>		new XML_RPC_Value($rqst[$rmNmbr.'_bsnssPackFlag'], 'string'));
				//必須項目以外
				if (array_key_exists($rmNmbr.'_bsnssPackType',$rqst)&&$rqst[$rmNmbr.'_bsnssPackType'] != ""){
					$param1 += array('bsnssPackType'=>new XML_RPC_Value($rqst[$rmNmbr.'_bsnssPackType'], 'string'));
				}
				else {
					$param1 += array('bsnssPackType'=>new XML_RPC_Value("", 'string'));
				}

				$param1 += array('fmlyName'=>			new XML_RPC_Value($rqst[$rmNmbr.'_fmlyName'], 'string'));
				$param1 += array('frstName'=>			new XML_RPC_Value($rqst[$rmNmbr.'_frstName'], 'string'));
				$param1 += array('sex'=>				new XML_RPC_Value($rqst[$rmNmbr.'_sex'], 'string'));
				$param1 += array('ntnltyCode'=>			new XML_RPC_Value($rqst[$rmNmbr.'_ntnltyCode'], 'string'));
				$param1 += array('phnNmbr'=>			new XML_RPC_Value($rqst[$rmNmbr.'_phnNmbr'], 'string'));
				if (array_key_exists($rmNmbr.'_mmbrshpNmbr',$rqst)&&$rqst[$rmNmbr.'_mmbrshpNmbr'] != ""){
					$param1 += array('mmbrshpNmbr'=>new XML_RPC_Value($rqst[$rmNmbr.'_mmbrshpNmbr'], 'string'));
				}
				else {
					$param1 += array('mmbrshpNmbr'=>new XML_RPC_Value("", 'string'));
				}
				$param1 += array('ecoChckn'=>			new XML_RPC_Value($rqst[$rmNmbr.'_ecoChckn'], 'string'));
				$param1 += array('ttlPrc'=>				new XML_RPC_Value($ttlPrc, 'string'));
				$param1 += array('ttlPrcIncldngTax'=>	new XML_RPC_Value($ttPrcIncldngTax, 'string'));
				$param1 += array('chldrnShrngBed'=>		new XML_RPC_Value($rqst[$rmNmbr.'_chldrnShrngBed'], 'string'));
				if (array_key_exists('crdtCardNmbr',$rqst)&&$rqst['crdtCardNmbr'] != ""){
					$param1 += array('crdtCardNmbr'=>new XML_RPC_Value($rqst['crdtCardNmbr'], 'string'));
				}
				else {
					$param1 += array('crdtCardNmbr'=>new XML_RPC_Value("", 'string'));
				}
				if (array_key_exists('crdtCardHldr',$rqst)&&$rqst['crdtCardHldr'] != ""){
					$param1 += array('crdtCardHldr'=>new XML_RPC_Value($rqst['crdtCardHldr'], 'string'));
				}
				else {
					$param1 += array('crdtCardHldr'=>new XML_RPC_Value("", 'string'));
				}
				if (array_key_exists('crdtCardexprtnDate',$rqst)&&$rqst['crdtCardexprtnDate'] != ""){
					$param1 += array('crdtCardexprtnDate'=> new XML_RPC_Value($rqst['crdtCardexprtnDate'],'string'));
				}
				else {
					$param1 += array('crdtCardexprtnDate'=> new XML_RPC_Value("",'string'));
				}

				if (array_key_exists($rmNmbr.'_receiptType',$rqst)&&$rqst[$rmNmbr.'_receiptType'] != ""){
					$param1 += array('receiptType'=>new XML_RPC_Value($rqst[$rmNmbr.'_receiptType'], 'string'));
				}
				else {
					$param1 += array('receiptType'=>new XML_RPC_Value("", 'string'));
				}
				if (array_key_exists($rmNmbr.'_receiptName',$rqst)&&$rqst[$rmNmbr.'_receiptName'] != ""){
					$param1 += array('receiptName'=>new XML_RPC_Value($rqst[$rmNmbr.'_receiptName'], 'string'));
				}
				else {
					$param1 += array('receiptName'=>new XML_RPC_Value("", 'string'));
				}

				$param1 += array('dlyInfrmtnList'=>		new XML_RPC_Value($day,'array'));
				$param1 += array('class'=>				new XML_RPC_Value('com.toyokoinn.api.app.entity.RoomReservationInformation','string'));
				$room_info[] = new XML_RPC_Value($param1,'struct');
			}

			//予約テスト用
			//ホテルコードが9000より大きい時applctnVrsnNmbrに'T'を付加する
			if ( (int)$param['htlCode'] >= 9000 ){
					$rqst['applctnVrsnNmbr'] = 'T'.$rqst['applctnVrsnNmbr'];
			}
			$param =
			array(
					new XML_RPC_Value($rqst['applctnVrsnNmbr'],'string'),
					new XML_RPC_Value($rqst['lngg'],'string'),
					new XML_RPC_Value($this->config->config['prcssng_type_reservation'],'string'),
					new XML_RPC_Value("",'string'),//必須項目ではないため
					new XML_RPC_Value($rqst['rsrvsPrsnUid'],'string'),
					new XML_RPC_Value($rqst['nmbrRms'],'string'),
					new XML_RPC_Value($ttlPrc,'string'),
					new XML_RPC_Value($ttPrcIncldngTax,'string'),
					new XML_RPC_Value($room_info,'array')
			);
			
			$host = $this->config->config['host'];
			$port = $this->config->config['bi_service_port'];
			$method = "com.toyokoinn.api.service.SmartphoneApplicationReservationService.registrationReservationEntry";
			$rpc_path = $this->config->config['bi_service_path'];
		
			// Call BService
			$message = new XML_RPC_Message($method,$param);
			// XML-RPC送信
			$c = new XML_RPC_client($rpc_path, $host, $port);
			// error
			if ($c->errno != 0){
			log_error($this->FNCTIN, 'send_request_error : '.$c->errstr);
				$error_code = Api_const::BAPI1001;
				$error_description = $this->lang->line($error_code);
				log_error($this->FNCTIN, 'send_request_error : '.$c->errstr);
				$this->api_util->apiEnd($jsonOut, $error_code, $error_description);
				return;
			}
			// WEB 応答
			$response = $c->send($message, $this->config->config['xml_rpc_timeout']);
			if ($response->faultCode()){
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
			else{
				$values=XML_RPC_decode( $resp );
				for($i=0;$i<4;$i++){
					$rsrv_nmbr[$i]="";
				}
				for($i=0;$i<count($values['rsrvtnNmbrInfrmtnList']);$i++){
					$rsrv_nmbr[$i]=$values['rsrvtnNmbrInfrmtnList'][$i]['rsrvtnNmbr'];
				}
				$jsonOut=array(
					'room1_rsrv_nmbr'=>$rsrv_nmbr[0],
					'room2_rsrv_nmbr'=>$rsrv_nmbr[1],
					'room3_rsrv_nmbr'=>$rsrv_nmbr[2],
					'room4_rsrv_nmbr'=>$rsrv_nmbr[3]
				);
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
