<?php
/*
 -------------------------------------------------------------------------------------------------------------------
 * @ 作成者	   							: ファリド　アナムル　ハック
 * @ 作成日	    						: 2014年07月25日
 * @ 更新日								: 2014年08月20日
 * @ Bサービス名							: S003
 * @ 内容								: スマートフォン向けBサービス側のホテル検索を実行し、APIサーバのホテル情報テーブルに反映
 * @ var $host							: XMLRPCホスト
 * @ var $port							: XMLRPC接続ポート番号
 * @ var $method						: XMLRPCメソッド
 * @ var $serviceHotelState				: ホテル状態テーブルの状態番号
 * @ var $serviceHotelStateApiKey		: ホテル状態テーブルの固定APIキー
 * @ var $tableName						: テーブルのリスト
 -------------------------------------------------------------------------------------------------------------------
 */
class Hotelinfo_model extends CI_Model {
	//外部設定値
	private $host;
	private $port;
	private $method;
	private $serviceHotelState;
	private $serviceHotelStateApiKey;
	private $serviceMode;
	private $serviceApiClass;
	//内部設定値
	private $errMsgShowFlag = 0; //　開発者だけ更新 [ 0 = ブラウザにエラーを非表示  ] [ 1 = ブラウザにエラーを表示 ]
	private $errMsg			=	array();
	private $appVersionControlNum;
	private $currentDate;
	private $currentTime;
	private $versionCount;
	private $tableName;
	private $clearDateFlag;
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ コンストラクターメソッド
	//-------------------------------------------------------------------------------------------------------------------
	public function __construct() {
		parent::__construct ();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 実行処理
	//-------------------------------------------------------------------------------------------------------------------
	function run($type){
		$this->start();
		$this->getBasicConfiguration();
		$this->checkPrivateKeys();
		$this->getAllTableExistanceCheck();
		$this->getCheckApiKeyInformation();
		$this->getApplicationVersionControlNum();
		$this->getHotelCodeFromHotelStateAndRun($type);
		$this->getClearOldData($type);
		$this->end();
	}	
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 開始プルグラム
	//-------------------------------------------------------------------------------------------------------------------
	public function start(){
		$this->errMsg[0]="ホテル情報開始";
		$this->saveLog(str_pad($this->errMsg[0], 100, "-"));
		$this->db->trans_begin();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　基本設定
	//@ $this->serviceMode 
	//-------------------------------------------------------------------------------------------------------------------
	public function getBasicConfiguration(){
		$this->host 					=   $this->config->item('bi_service_host');
		$this->port 					=   $this->config->item('bi_service_port');
		$this->serviceHotelState		=   $this->config->item('bi_hotel_state');
		$this->serviceHotelStateApiKey 	=   $this->config->item('bi_api_key');
		$this->serviceMode				= 	$this->config->item('bi_mode');
		$this->serviceApiClass			= 	$this->config->item('bi_api_class');
		$this->method 					=   'com.toyokoinn.api.service.SmartphoneApplicationHotelService.searchHotelInformation';
		$this->versionCount				=   $this->getCountVersion();
		$this->tableName 				=   $this->allTableList();
		$this->currentDate 				=   date("Y-m-d");
		$this->currentTime 				=   date("H:i:s");
		$this->clearDateFlag 			= 	date('Y-m-d', strtotime($this->currentDate.' '.$this->config->item('bi_clear_data')));
	}
	
	public function allTableList(){
		$tables 						=	array();
		$tables['ak'] 					=	"apikey_information";
		$tables['av'] 					=	"application_version_control"; 
		$tables['ht'] 					=	"hotel_state";
		$tables['hi'] 					=	"hotel_info";
		$tables['ci'] 					=	"credit_infomation";
		$tables['ei'] 					=	"equipment_information";
		$tables['rt'] 					=	"room_type_information";
		$tables['ki'] 					=	"keyword_information";
		$tables['ct'] 					=	"consumption_tax_rate";
		$tables['wd'] 					=	"web_discount_information";
		$tables['rc'] 					=	"room_charge_infomation";
		$tables['vi'] 					=	"vacancy_information";
		$tables['oc'] 					=	"option_charge_infomation";
		return $tables;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ データ削除
	//-------------------------------------------------------------------------------------------------------------------
	public function getClearOldData($type){
		$dataArr = array("1","2","3","clear");
		if(in_array($type, $dataArr)){
			$this->delete($this->tableName['ci']);
			$this->delete($this->tableName['ei']);
			$this->delete($this->tableName['rt']);
			$this->delete($this->tableName['ki']);
			$this->delete($this->tableName['ct']);
			$this->delete($this->tableName['wd']);
			$this->delete($this->tableName['rc']);
			$this->delete($this->tableName['hi']);
			$this->delete($this->tableName['oc']);
		}
	}
	//削除処理
	public function delete($table_name){
		$sql = "DELETE
				from {$table_name} 
				WHERE (updt_date IS NULL and entry_date < '{$this->clearDateFlag}')
				OR (updt_date IS NOT NULL and updt_date < '{$this->clearDateFlag}')";
		$this->db->query($sql);
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　テーブル存在あること確認
	//-------------------------------------------------------------------------------------------------------------------
	public function getAllTableExistanceCheck(){
		foreach($this->tableName as $t){
			if (!$this->validateTable($t)){
				$this->errMsg[1] = "【テーブル （ {$t}）】が存在しません";
				$this->msgLog("MySQLエラー",$this->errMsg[1]);
				$this->end();
			}
		}
	}
	
	public function validateTable($tableName){
		foreach ($this->db->list_tables() as $row){
			if ($row == $tableName)
				return true;
		}
		return false;
	}
		
	// -------------------------------------------------------------------------------------------------------------------
	// @　Apiキーインフォメーションデータチェック
	// -------------------------------------------------------------------------------------------------------------------
	public function getCheckApiKeyInformation() {
		$data 					= array ();
		$data ['api_key'] 		= $this->serviceHotelStateApiKey;
		$data ['mode'] 			= $this->serviceMode;
		$data ['api_class'] 	= $this->serviceApiClass;
		
		$this->db->from($this->tableName['ak']);
		$this->db->where("api_key",$this->serviceHotelStateApiKey);
		$query = $this->db->get();
		
		if($query->num_rows()>0){
			$this->db->where('api_key',$this->serviceHotelStateApiKey);
			if (!$this->db->update($this->tableName ['ak'],$data)){
				$this->errMsg[2] = "【テーブル （ {$this->tableName ['ak']}）】  更新が失敗しました";
				$this->msgLog("更新エラー", $this->errMsg[2] );
			}
		}else{
			if (!$this->db->insert( $this->tableName['ak'],$data)){
				$this->errMsg[3] = "【テーブル （ {$this->tableName ['ak']}）】  挿入が失敗しました";
				$this->msgLog("挿入エラー",$this->errMsg[3] );
			}
		}
	}	
	

	//-------------------------------------------------------------------------------------------------------------------
	//@ 「アプリケーションバージョンテーブル」を検索し、「状態」が　“１（サービス中）”　である「アプリバージョン番号」を取得処理
	//-------------------------------------------------------------------------------------------------------------------
	public function getApplicationVersionControlNum(){
		$this->db->from($this->tableName['av']);
		$this->db->where("stt",$this->serviceHotelState);
		$this->db->where("api_key",$this->serviceHotelStateApiKey);
		$query = $this->db->get();
		if(!$query->num_rows() > 0){
			$this->errMsg[4] = "【テーブル （ {$this->tableName ['av']}）】  APIキー（{$this->serviceHotelStateApiKey}）と状態が（{$this->serviceHotelState}）で検索してアプリケーションバージョンを番号取得できません";
			$this->msgLog("サービスエラー",$this->errMsg[4]);
			$this->end();
		}
		
		foreach ($query->result() as $row){
			$this->appVersionControlNum =  $row->applctn_vrsn_nmbr;
		}
	
		if($this->appVersionControlNum == NULL || empty($this->appVersionControlNum)){
			$this->errMsg[5] = "アプリケーションバージョン番号( {$row->applctn_vrsn_nmbr} )が存在しません";
			$this->msgLog("サービスエラー",$this->errMsg[5]);
			$this->end();
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ ホテル状態テーブルに固定データで検索してホテルコード ホテルコード取得処理
	//@ 取得した「ホテルコード」数以下の処理を繰り返す
	//-------------------------------------------------------------------------------------------------------------------
	public function getHotelCodeFromHotelStateAndRun($type){
		$this->db->from($this->tableName['ht']);
		$this->db->where("api_key",$this->serviceHotelStateApiKey);
		$this->db->where("state",$this->serviceHotelState);
		$dataArr = array("1","2","3");
		if(in_array($type, $dataArr)){
			$this->db->where("group_flag",$type);
		}
		$hotel_state 				= $this->db->get();
		if(!$hotel_state->num_rows() > 0){
			$this->errMsg[6] = "【テーブル （{$this->tableName['ht']}）】  APIキー（{$this->serviceHotelStateApiKey}）と状態が（{$this->serviceHotelState}）で検索してホテルコードを取得できません";
			$this->msgLog("サービスエラー",$this->errMsg[6]);
			$this->end();
		}
		
		$loopCount =0;
		foreach($hotel_state->result() as $row){
			$this->currentDate 				=   date("Y-m-d");
			$this->currentTime 				=   date("H:i:s");
			$loopCount++;
			if($loopCount % 10 == 0){
				gc_collect_cycles();
				sleep(5); //sleep for each 10 records
			}
			if(empty($row->htl_code)){
				$this->errMsg[7] = "【テーブル （ {$this->tableName['ht']}）】  ホテルコード（{$row->htl_code}）が存在しません";
				$this->msgLog("サービスエラー",$this->errMsg[7]);
				continue;
			}
			$request_data = array(
					array($this->appVersionControlNum,'string'),
					array($row->htl_code,'string'),
			);
			$xmlrpcData = $this->retrieveXmlrpcDataWithEveryLoop($request_data,$row->htl_code);
			if($xmlrpcData =='DATA_ERROR'){
				$this->errMsg[8] = "ホテルコード（{$row->htl_code}）のデータが存在しません";
				$this->msgLog("Ｂサービスエラー",$this->errMsg[8]);
				continue;
			}else if($xmlrpcData =='CONN_ERROR'){
				$this->errMsg[9] = "Bサービス接続失敗しました";
				$this->msgLog("接続スエラー",$this->errMsg[9]);
				continue;
			}
			$this->initialProcessData($xmlrpcData,$row->htl_code);
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ XMLRPCデータを取得
	//-------------------------------------------------------------------------------------------------------------------
	function retrieveXmlrpcDataWithEveryLoop($request,$htl_code) {
		$this->load->helper('url');
		$this->load->library('xmlrpc');
		$this->xmlrpc->server($this->host,$this->port);
		$this->xmlrpc->method($this->method);
		$this->xmlrpc->request($request);
		if($this->xmlrpc->send_request()) {
			$received_response = $this->xmlrpc->display_response();	
			if($received_response['errrMssg']=='Success!'){
				return $received_response;
			}else{
				return "DATA_ERROR";
			}
		}else{
			return "CONN_ERROR";
		}
	}

	//-------------------------------------------------------------------------------------------------------------------
	//@ 複数データチェック
	//-------------------------------------------------------------------------------------------------------------------
	public function doubleEntryFromHotelInfo($tableName,$data){
 		$this->db->select('*');
		$this->db->from($tableName);
		$this->db->where('applctn_vrsn_nmbr', $data['applctn_vrsn_nmbr']);
		$this->db->where('htl_code', $data['htl_code']);
		$this->db->where('lngg', $data['lngg']);
		$query = $this->db->get();
		if($query->num_rows() > 0){
			return true;
		}
		return false;
	}
	
	public function getHotelInfoUniqueID($tableName,$data){
 		$this->db->select('unq_id');
		$this->db->from($tableName);
		$this->db->where('applctn_vrsn_nmbr', $data['applctn_vrsn_nmbr']);
		$this->db->where('htl_code', $data['htl_code']);
		$this->db->where('lngg', $data['lngg']);
		$this->db->limit(1);
		$query = $this->db->get();
		foreach ($query->result() as $row){
			return $row->unq_id;
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 終了処理
	//-------------------------------------------------------------------------------------------------------------------
	public function end(){
		$this->errMsg[10]="ホテル情報終了<br/>";
		$this->saveLog(str_pad($this->errMsg[10], 100, "-"));
		$this->showErrorDetails();
		die();
	}
	
 	public function showErrorDetails(){
		if($this->errMsgShowFlag!=0){
			echo "<pre>";
			print_r($this->errMsg);
			echo "</pre>";
		}
 	}
 	
	//-------------------------------------------------------------------------------------------------------------------
	//@ ホテル情報にデータを挿入
	//-------------------------------------------------------------------------------------------------------------------
	public function insertDataToHotelInformationTable($tableName,$data){
			$duplicateCheck = $this->doubleEntryFromHotelInfo($tableName,$data);
			if($duplicateCheck){
				$unq_id 					= $this->getHotelInfoUniqueID($tableName,$data);
				$this->db->where('unq_id',$unq_id);
				$data['updt_date'] 			= $this->currentDate;
				$data['updt_time'] 			= $this->currentTime;
				if(!$this->db->update($tableName, $data)){
					$this->errMsg[11] = "【テーブル （{$tableName}）】  更新失敗しました";
					$this->msgLog("更新エラー",$this->errMsg[11]);
					continue;
				}	
			}else{
				$data['entry_date'] 			= $this->currentDate;
				$data['entry_time'] 			= $this->currentTime;
				
 				if ($this->db->_error_message()){
					continue;
				} 
				
				if(!$this->db->insert($tableName, $data)){
					$this->errMsg[12] = "【テーブル （{$tableName}）】  挿入失敗しました";
					$this->msgLog("挿入エラー",$this->errMsg[12]);
					continue;
				}
			}
			if ($this->db->trans_status() === FALSE){
				$this->db->trans_rollback();
			}else{
				$this->db->trans_commit();
			}
	}

	public function initialProcessData($xdata,$htl_code){ 
		$dataTable										=	$this->tableName;		
		$crdtInfrmtnList								=	$xdata['crdtInfrmtnList'];
		$eqpmntInfrmtnList								=	$xdata['eqpmntInfrmtnList'];
		$roomTypeInfrmtnList							=	$xdata['roomTypeInfrmtnList'];
		$rtInfrmtn										= 	$xdata['rtInfrmtn'];
		$cnsmptnTaxRateInfo								=	$xdata['cnsmptnTaxRateInfo'];
		$webDscntInfrmtn								= 	$xdata['webDscntInfrmtn'];
		$option_charge_infomation						= 	$xdata['optnChrgInfrmtn'];
		$optnApplyInfrmtn							    = 	$xdata['optnApplyInfrmtn'];
		$keydata	 									=	$this->getMaxValue($xdata);
		$htl_ckey 										=	$keydata['ckey'];
		$htl_maxVal 									=	$keydata['val'];
       	for($i=0; $i<$htl_maxVal; $i++){ 
       		if(empty($htl_code)){
       			$this->errMsg[13] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコードは存在しません";
       			$this->msgLog("エラー",$this->errMsg[13]);
       			continue;
       		}if(empty($xdata['htlName'][$i]['lngg'])){
       			$this->errMsg[14] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の言語コードが取得できません";
       			$this->msgLog("エラー",$this->errMsg[14]);
       			$xdata['htlName'][$i]['lngg'] =' ';
       			continue;
       		}if(empty($xdata['timeZone'])){
       			$this->errMsg = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の時間帯が取得できません";
       			$this->msgLog("エラー",$this->errMsg[15]);
       			$xdata['timeZone'] =' ';
       		}if(empty($xdata['htlName'][$i]['name'])){
       			$this->errMsg[16] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）のホテル名が取得できません";
       			$this->msgLog("エラー",$this->errMsg[16]);
       			$xdata['htlName'][$i]['name']=' ';
       		}if(empty($xdata['addrss'][$i]['name'])){
       			$this->errMsg[17] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の住所が取得できません";
       			$this->msgLog("エラー",$this->errMsg[17]);
       			$xdata['addrss'][$i]['name'] =' ';
       		}if(empty($xdata['chcknTime'])){
       			$this->errMsg[18] = "【テーブル （ {$this->tableName['hi']}）】  テルコード（".$htl_code."）のチェックイン時間が取得できません";
       			$this->msgLog("エラー",$this->errMsg[18]);
       			$xdata['chcknTime']=' ';
       		}if(empty($xdata['chcktTime'])){
       			$this->errMsg[19] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）のチェックアウト時間が取得できません";
       			$this->msgLog("エラー",$this->errMsg[19]);
       			$xdata['chcktTime']=' ';
       		}if(empty($xdata['phnNmbr'])){
       			$this->errMsg[20] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の電話番号が取得できません";
       			$this->msgLog("エラー",$this->errMsg[20]);
       			$xdata['phnNmbr']=' ';
       		}if(empty($xdata['imgURL'][0]['imgURL'])){
       			$this->errMsg[21] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の画像が取得できません";
       			$this->msgLog("エラー",$this->errMsg[21]);
       			$xdata['imgURL'][0]['imgURL'] = ' ';
       		}if(empty($xdata['cntryCode'])){
       			$this->errMsg[22] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の国コードが取得できません";
       			$this->msgLog("エラー",$this->errMsg[22]);
       			$xdata['cntryCode']=' ';
       		}if(empty($xdata['areaCode'])){
       			$this->errMsg[23] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の市外局番が取得できません";
       			$this->msgLog("エラー",$this->errMsg[23]);
       			$xdata['areaCode'] = ' ';
       		}if(empty($xdata['sttCode'])){
       			$this->errMsg[24] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の都道府県コードが取得できません";
       			$this->msgLog("エラー",$this->errMsg[24]);
       			$xdata['sttCode'] = ' ';
       		}if(empty($xdata['cityCode'])){
       			$this->errMsg[24] = "【テーブル （ {$this->tableName['hi']}）】 都市コード（".$htl_code."）の都道府県コードが取得できません";
       			$this->msgLog("エラー",$this->errMsg[24]);
       			$xdata['cityCode'] = ' ';
       		}if(empty($xdata['crrncyName'])){
       			$this->errMsg[25] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の通貨名が取得できません";
       			$this->msgLog("エラー",$this->errMsg[25]);
       			$xdata['crrncyName'] = ' ';
       		}if(empty($xdata['crrncySign'])){
       			$this->errMsg[26] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の通貨記号が取得できません";
       			$this->msgLog("エラー",$this->errMsg[26]);
       			$xdata['crrncySign'] = ' ';
       		}if(empty($xdata['lngtd'])){
       			$this->errMsg[27] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の経度が取得できません";
       			$this->msgLog("エラー",$this->errMsg[27]);
       			$xdata['lngtd'] = '0';
       		}if(empty($xdata['lttd'])){
       			$this->errMsg[28] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）の緯度が取得できません";
       			$this->msgLog("エラー",$this->errMsg[28]);
       			$xdata['lttd'] = '0';
       		}if(empty($xdata['brkfstTime'])){
       			$xdata['brkfstTime'] = ' ';
       		}if(empty($xdata['prkngInfmtn'][$i]['name'])){
       			$xdata['prkngInfmtn'][$i]['name'] = ' ';
       		}
       		
       		$iosInfo 		= ' ';
       		if(!empty($xdata['isoInfmtn'])){
       			$iosInfo 			                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['isoInfmtn']);
       		}
       		$brrrfrInfmtn	= ' ';
       		if(!empty($xdata['brrrfrInfmtn'])){
       			$brrrfrInfmtn 		                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['brrrfrInfmtn']);
       		}
       		$busInfmtn		= ' ';
       		if(!empty($xdata['busInfmtn'])){
       			$busInfmtn 			                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['busInfmtn']);
       		}
       		$pckpInfmtn		= ' ';
       		if(!empty($xdata['pckpInfmtn'])){
       			$pckpInfmtn 		                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['pckpInfmtn']);
       		}
       		$rntcrInfmtn	= ' ';
       		if(!empty($xdata['rntcrInfmtn'])){
       			$rntcrInfmtn 		                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['rntcrInfmtn']);
       		}
       		$cnclltnPolicy	= ' ';
       		if(!empty($xdata['cnclltnPolicy'])){
       			$cnclltnPolicy 		                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['cnclltnPolicy']);
       		}	
       		
       		$trmsCndtns		= ' ';
       		if(!empty($xdata['trmsCndtns'])){
       			$trmsCndtns 		                         = $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$xdata['trmsCndtns']);
       		}
       		

       	 	//HOTEL INFORMATION [HI]
       		$htl_data[$i]['vrsn_nmbr'] 			             = $this->versionCount;
       		$htl_data[$i]['applctn_vrsn_nmbr'] 	             = $this->appVersionControlNum;
       		$htl_data[$i]['htl_code'] 			             = $htl_code;
       		$htl_data[$i]['lngg'] 				             = $xdata['htlName'][$i]['lngg'];
       		$htl_data[$i]['time_zone'] 			             = $xdata['timeZone'];
       		$htl_data[$i]['htl_name'] 			             = $xdata['htlName'][$i]['name']; 
       		$htl_data[$i]['addrss'] 				         = $xdata['addrss'][$i]['name'];
       		$htl_data[$i]['prkng_infmtn'] 		             = $xdata['prkngInfmtn'][$i]['name'];
       		$htl_data[$i]['bus_infmtn'] 			         = $busInfmtn;
       		$htl_data[$i]['pikp_infmtn'] 			         = $pckpInfmtn;
       		$htl_data[$i]['rntcr_infmtn'] 		             = $rntcrInfmtn;
       		$htl_data[$i]['chckn_time']			             = $xdata['chcknTime'];
       		$htl_data[$i]['chckt_time']			             = $xdata['chcktTime'];
       		$htl_data[$i]['brkfst_time']			         = $xdata['brkfstTime'];
       		$htl_data[$i]['brrrfr_infmtn']		             = $brrrfrInfmtn;
       		$htl_data[$i]['iso_infmtn']			         	 = $iosInfo;
       		$htl_data[$i]['phn_nmbr']				         = $xdata['phnNmbr'];
       		$htl_data[$i]['img_url']				         = $xdata['imgURL'][0]['imgURL'];
       		$htl_data[$i]['cntry_code']			             = $xdata['cntryCode'];
       		$htl_data[$i]['area_code']			             = $xdata['areaCode'];
       		$htl_data[$i]['stt_code']				         = $xdata['sttCode'];
       		$htl_data[$i]['city_code']						 = $xdata['cityCode'];
       		$htl_data[$i]['crrncy_name']			         = $xdata['crrncyName'];
       		$htl_data[$i]['crrncy_sign']			         = $xdata['crrncySign'];
       		$htl_data[$i]['lngtd']				             = $xdata['lngtd'];
       		$htl_data[$i]['lttd']					         = $xdata['lttd'];   
       		$htl_data[$i]['open_date']					     = $this->dateFormate($xdata['openDate']);
       		$htl_data[$i]['cnclltn_policy']					 = $cnclltnPolicy;
       		$htl_data[$i]['trms_cndtns']					 = $trmsCndtns;
 		
       		$this->insertDataToHotelInformationTable($dataTable['hi'],$htl_data[$i]);
       		$unq_id = $this->getHotelInfoUniqueID($dataTable['hi'],$htl_data[$i]);
  			
       		if(empty($unq_id)){
       			$this->errMsg[29] = "【テーブル （ {$this->tableName['hi']}）】  ホテルコード（".$htl_code."）のユニークIDが取得できません";
       			$this->msgLog("エラー",$this->errMsg[29]);
       			continue;
       		}
       		
       		//CREDIT INFORMATION [CI]
       		for($x=0;$x<count($crdtInfrmtnList);$x++){
       			for($y=0;$y<count($crdtInfrmtnList[$x]['crdtName']);$y++){
       				if(!empty($crdtInfrmtnList[$x]['crdtName'][$y]['lngg'])){	
	       				if($xdata[$htl_ckey][$i]['lngg']==$crdtInfrmtnList[$x]['crdtName'][$y]['lngg']){
		       				if(empty($crdtInfrmtnList[$x]['crdtName'][$y]['name'])){
		       					$crdt_name = ' ';
		       				}if(empty($crdtInfrmtnList[$x]['imgURL'])){
		       					$crdtInfrmtnList[$x]['imgURL'] = ' ';
		       				}if(empty($crdtInfrmtnList[$x]['crdtCode'])){
		       					$crdtInfrmtnList[$x]['crdtCode'] = ' ';
		       				}
		       				$cr_datay[$x][$y]['htl_infrmtn_unq_id']  		= $unq_id;
		       				$cr_datay[$x][$y]['vrsn_nmbr'] 			 		= $this->versionCount;
		       				$cr_datay[$x][$y]['crdt_code'] 			 		= $crdtInfrmtnList[$x]['crdtCode'];
		       				$cr_datay[$x][$y]['img_url'] 			 		= $crdtInfrmtnList[$x]['imgURL'];
		       				$cr_datay[$x][$y]['crdt_name'] 					= $crdtInfrmtnList[$x]['crdtName'][$y]['name'];
		       				$this->nowTimeToInsertData($dataTable['ci'],$cr_datay[$x][$y]);
	       				}	
	       			}
       			}
       		}
       		
       		//EQUIPMENT INFORMATION [EI]
       		for($x=0;$x<count($eqpmntInfrmtnList);$x++){
       			for($y=0;$y<count($eqpmntInfrmtnList[$x]['eqpmntName']);$y++){
       				if(!empty($eqpmntInfrmtnList[$x]['eqpmntName'][$y]['lngg'])){
       					if($xdata[$htl_ckey][$i]['lngg']==$eqpmntInfrmtnList[$x]['eqpmntName'][$y]['lngg']){
       						if(empty($eqpmntInfrmtnList[$x]['eqpmntType'])){
       							$eqpmntInfrmtnList[$x]['eqpmntType'] = ' ';
       						}if(empty($eqpmntInfrmtnList[$x]['eqpmntCode'])){
       							$eqpmntInfrmtnList[$x]['eqpmntCode'] = ' ';
       						}if(empty($eqpmntInfrmtnList[$x]['imgURL'])){
       							$eqpmntInfrmtnList[$x]['imgURL'] = ' ';
       						}if(empty($eqpmntInfrmtnList[$x]['eqpmntName'][$y]['name'])){
       							$eqpmntInfrmtnList[$x]['eqpmntName'][$y]['name'] = ' ';
       						}
       						$eq_data[$x][$y]['htl_infrmtn_unq_id']  			= $unq_id;
       						$eq_data[$x][$y]['vrsn_nmbr'] 			 			= $this->versionCount;
       						$eq_data[$x][$y]['eqpmnt_type'] 		 			= $eqpmntInfrmtnList[$x]['eqpmntType'];
       						$eq_data[$x][$y]['eqpmnt_code'] 		 			= $eqpmntInfrmtnList[$x]['eqpmntCode'];
       						$eq_data[$x][$y]['img_url'] 			 			= $eqpmntInfrmtnList[$x]['imgURL'];
       						$eq_data[$x][$y]['eqpmnt_name'] 		 			= $eqpmntInfrmtnList[$x]['eqpmntName'][$y]['name'];
       						$this->nowTimeToInsertData($dataTable['ei'],$eq_data[$x][$y]);
       					}
       				}
       			}
       		}
       		
   			//ROOM TYPE INFORMATION [RT]
       		for($x=0;$x<count($roomTypeInfrmtnList);$x++){
       			if(empty($roomTypeInfrmtnList[$x]['roomTypeCode'])){
       				$roomTypeInfrmtnList[$x]['roomTypeCode'] =' ';
       			} 
       			$roomTypeName = ' ';
       			for($y=0;$y<count($roomTypeInfrmtnList[$x]['roomTypeName']);$y++){
       				if(!empty($roomTypeInfrmtnList[$x]['roomTypeName'][$y]['lngg'])){
       					if($xdata[$htl_ckey][$i]['lngg']==$roomTypeInfrmtnList[$x]['roomTypeName'][$y]['lngg']){
       						if(!empty($roomTypeInfrmtnList[$x]['roomTypeName'][$y]['name'])){
       							$roomTypeName = $roomTypeInfrmtnList[$x]['roomTypeName'][$y]['name'];
       						}
       					}
       				}
       			}
       			if(empty($roomTypeInfrmtnList[$x]['planCode'])){
       				$roomTypeInfrmtnList[$x]['planCode'] =' ';
       			} 

       			$planName = ' ';
       			for($z=0;$z<count($roomTypeInfrmtnList[$x]['planName']);$z++){
       				if(!empty($roomTypeInfrmtnList[$x]['planName'][$z]['lngg'])){
       					if($xdata[$htl_ckey][$i]['lngg']==$roomTypeInfrmtnList[$x]['planName'][$z]['lngg']){
       						if(!empty($roomTypeInfrmtnList[$x]['planName'][$z]['name'])){
       							$planName = $roomTypeInfrmtnList[$x]['planName'][$z]['name'];
       						}
       					}
       				}
       			}
       			
       			$ecoFlag 		= 'N';
       			$vodFlag 		= 'N';
       			$businessFlag 	= 'N';
       			$babyFlag 		= 'N';
       			for($p=0;$p<count($roomTypeInfrmtnList[$x]['optnApplyInfrmtn']);$p++){
       				if(!empty($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId'])){
       					if($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId']=="010"){
       						$vodFlag ='Y';
       					}if($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId']=="020"){
       						$businessFlag ='Y';
       					}if($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId']=="021"){
       						$businessFlag ='Y';
       					}if($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId']=="020"){
       						$businessFlag ='Y';
       					}if($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId']=="030"){
       						$ecoFlag = 'Y';
       					}if($roomTypeInfrmtnList[$x]['optnApplyInfrmtn'][$p]['optnTypId']=="040"){
       						$babyFlag = 'Y';
       					}
       				}
       			}
       			
       			if(empty($roomTypeInfrmtnList[$x]['applLang'])){
       				$roomTypeInfrmtnList[$x]['applLang'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['applMmbr'])){
       				$roomTypeInfrmtnList[$x]['applMmbr'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['ecoUseDvsn'])){
       				$roomTypeInfrmtnList[$x]['ecoUseDvsn'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['vodUseDvsn'])){
       				$roomTypeInfrmtnList[$x]['vodUseDvsn'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['bpUseDvsn'])){
       				$roomTypeInfrmtnList[$x]['bpUseDvsn'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['bdShringUseDvsn'])){
       				$roomTypeInfrmtnList[$x]['bdShringUseDvsn'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['bpJdgmntDvsn'])){
       				$roomTypeInfrmtnList[$x]['bpJdgmntDvsn'] =' ';
       			} $smkngFlag = "N";
       			if(!empty($roomTypeInfrmtnList[$x]['smkngFlag']) && $roomTypeInfrmtnList[$x]['smkngFlag']=="2"){
       				$smkngFlag = "Y";
       			}if(empty($roomTypeInfrmtnList[$x]['imgURL'][0]['imgURL'])){
       				$roomTypeInfrmtnList[$x]['imgURL'][0]['imgURL'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['minPpl'])){
       				$roomTypeInfrmtnList[$x]['minPpl'] =' ';
       			}if(empty($roomTypeInfrmtnList[$x]['maxPpl'])){
       				$roomTypeInfrmtnList[$x]['maxPpl'] = 0;
       			}if(empty($roomTypeInfrmtnList[$x]['minStay'])){
       				$roomTypeInfrmtnList[$x]['minStay'] = 0;
       			}if(empty($roomTypeInfrmtnList[$x]['maxStay'])){
       				$roomTypeInfrmtnList[$x]['maxStay'] = 0;
       			}if(empty($roomTypeInfrmtnList[$x]['mxmm_nmbr_rms'])){
       				$roomTypeInfrmtnList[$x]['mxmm_nmbr_rms'] = 0;
       			}if(empty($roomTypeInfrmtnList[$x]['mimm_nmbr_rms'])){
       				$roomTypeInfrmtnList[$x]['mimm_nmbr_rms'] = 0;
       			}if(empty($roomTypeInfrmtnList[$x]['roomClssId'])){
       				$roomTypeInfrmtnList[$x]['roomClssId'] = ' ';
       			}
       			$rt_data[$x]['htl_infrmtn_unq_id']  			= $unq_id;
       			$rt_data[$x]['vrsn_nmbr'] 			 			= $this->versionCount;
       			$rt_data[$x]['room_type_code'] 		        	= $roomTypeInfrmtnList[$x]['roomTypeCode'];
       			$rt_data[$x]['room_type_name'] 		        	= $roomTypeName;
       			$rt_data[$x]['plan_code'] 				    	= $roomTypeInfrmtnList[$x]['planCode']; 			//plan_code 				- (planCode)
       			$rt_data[$x]['plan_name'] 				    	= $planName; 										//plan_name 				- (planName)
       			$rt_data[$x]['app_lngg'] 				    	= $roomTypeInfrmtnList[$x]['applLang']; 			//app_lngg 					- (applLang)
       			$rt_data[$x]['app_mmbr'] 				    	= $roomTypeInfrmtnList[$x]['applMmbr'];  			//app_lngg 					- (applMmbr)
       			$rt_data[$x]['eco_use_dvsn'] 				    = $roomTypeInfrmtnList[$x]['ecoUseDvsn'];  			//eco_use_dvsn 				- (ecoUseDvsn)
       			$rt_data[$x]['vod_use_dvsn'] 				    = $roomTypeInfrmtnList[$x]['vodUseDvsn'];  			//vod_use_dvsn 				- (vodUseDvsn)
       			$rt_data[$x]['bp_use_dvsn'] 				    = $roomTypeInfrmtnList[$x]['bpUseDvsn'];  			//bp_use_dvsn 				- (bpUseDvsn)
       			$rt_data[$x]['lyng_of_chldrn_use_dvsn'] 		= $roomTypeInfrmtnList[$x]['bdShringUseDvsn'];  	//lyng_of_chldrn_use_dvsn 	- (bdShringUseDvsn)
       			$rt_data[$x]['bp_jdgmnt_dvsn'] 				    = $roomTypeInfrmtnList[$x]['bpJdgmntDvsn'];  		//bp_jdgmnt_dvsn 			- (bpJdgmntDvsn)
       			$rt_data[$x]['smkng_flag'] 			        	= $smkngFlag;
       			$rt_data[$x]['img_url'] 				        = $roomTypeInfrmtnList[$x]['imgURL'][0]['imgURL'];
       			$rt_data[$x]['mimm_occpncy'] 				    = $roomTypeInfrmtnList[$x]['minPpl'];  				//mimm_occpncy 				- (minPpl)
       			$rt_data[$x]['mxmm_occpncy'] 			        = $roomTypeInfrmtnList[$x]['maxPpl'];				//mxmm_occpncy 				- (maxPpl)
       			$rt_data[$x]['mimm_stay'] 				    	= $roomTypeInfrmtnList[$x]['minStay'];				//mimm_stay 				- (minStay)
       			$rt_data[$x]['mxmm_stay'] 				    	= $roomTypeInfrmtnList[$x]['maxStay'];
       			$rt_data[$x]['nmbr_rms'] 				        = 0;
       			$rt_data[$x]['mimm_nmbr_rms'] 				    = $roomTypeInfrmtnList[$x]['minRoom'];				//mimm_nmbr_rms				- (minRoom)
       			$rt_data[$x]['mxmm_nmbr_rms'] 				    = $roomTypeInfrmtnList[$x]['maxRoom'];				//mxmm_nmbr_rms				- (maxRoom)
       			$rt_data[$x]['lyng_of_chldrn_avlbl_flag'] 		= $babyFlag;	
       			$rt_data[$x]['eco_avlbl_flag'] 				    = $ecoFlag;	
       			$rt_data[$x]['vod_avlbl_flag'] 				    = $vodFlag;	
       			$rt_data[$x]['bsnss_pack_avlbl_flag'] 			= $businessFlag;	
       			$rt_data[$x]['room_clss_id'] 				    = $roomTypeInfrmtnList[$x]['roomClssId'];
       			$this->nowTimeToInsertData($dataTable['rt'],$rt_data[$x]);
       		}	
       		
       		//KEYWORD INFORMATION [KI]
       		for($x=0;$x<count($rtInfrmtn);$x++){
				$getKeyVal = $this->getKeywordVal($rtInfrmtn[$x]);
				$maxRecord = $getKeyVal['val'];
				$kkey	   = $getKeyVal['ckey'];
       			for($y=0;$y<$maxRecord;$y++){
       				if(!empty($rtInfrmtn[$x][$kkey][$y]['lngg'])){
       					if($xdata[$htl_ckey][$i]['lngg']==$rtInfrmtn[$x][$kkey][$y]['lngg']){

       						if(empty($rtInfrmtn[$x]['sttnName'])){
       							$rtInfrmtn[$x]['sttnName'] =' ';
       						}if(empty($rtInfrmtn[$x]['rtName'])){
       							$rtInfrmtn[$x]['rtName'] =' ';
       						}if(empty($rtInfrmtn[$x]['sttnType'])){
       							$rtInfrmtn[$x]['sttnType'] =' ';
       						}if(empty($rtInfrmtn[$x]['mnsTrnsprttn'])){
								$rtInfrmtn[$x]['mnsTrnsprttn'] =' ';
							}
						
       						$kywrd_name 	= $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$rtInfrmtn[$x]['sttnName']);
       						$routeName 		= $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$rtInfrmtn[$x]['rtName']);
       						$mns_trnsprttn 	= $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$rtInfrmtn[$x]['mnsTrnsprttn']);
       						$timeRqrd 		= $this->validateIndex($xdata[$htl_ckey][$i]['lngg'],$rtInfrmtn[$x]['timeRqrd']);
       						$ke_data[$x][$y]['htl_infrmtn_unq_id']  			= $unq_id;
       						$ke_data[$x][$y]['vrsn_nmbr'] 			 			= $this->versionCount;
       						$ke_data[$x][$y]['kywrd_type'] 	               	 	= $rtInfrmtn[$x]['sttnType'];
       						$ke_data[$x][$y]['kywrd_name'] 	                	= $kywrd_name;
       						$ke_data[$x][$y]['rt_name'] 			            = $routeName;
       						$ke_data[$x][$y]['mns_trnsprttn']                 	= $mns_trnsprttn;
       						$ke_data[$x][$y]['time_rqrd'] 	                	= $timeRqrd;
       						$this->nowTimeToInsertData($dataTable['ki'],$ke_data[$x][$y]);
       					}
       				}
       			}
       		}     		
       	
       		//OPTION CHARGE INFORMATION  [OC]
       		for($x=0;$x<count($option_charge_infomation);$x++){
       			if(empty($option_charge_infomation[$x]['optnTypId'])){
       				$option_charge_infomation[$x]['optnTypId'] =' ';
       			}if(empty($option_charge_infomation[$x]['applcblStrtDate'])){
       				$option_charge_infomation[$x]['applcblStrtDate'] =' ';
       			}if(empty($option_charge_infomation[$x]['applcblEndDate'])){
       				$option_charge_infomation[$x]['applcblEndDate'] =' ';
       			}if(empty($option_charge_infomation[$x]['optnChrg'])){
       				$option_charge_infomation[$x]['optnChrg'] =' ';
       			}
       			
       			$start_dates 											= $this->dateFormate($option_charge_infomation[$x]['applcblStrtDate']);
       			$end_dates 												= $this->dateFormate($option_charge_infomation[$x]['applcblEndDate']);
       			$oc_data[$x]['vrsn_nmbr'] 			 					= $this->versionCount;
       			$oc_data[$x]['htl_code'] 								= $htl_code;
       			$oc_data[$x]['optn_id'] 								= $option_charge_infomation[$x]['optnTypId'];
       			$oc_data[$x]['start_date'] 								= $start_dates;
       			$oc_data[$x]['end_date'] 								= $end_dates;
       			$oc_data[$x]['optn_chrg'] 								= $option_charge_infomation[$x]['optnChrg'];
       			$this->nowTimeToInsertData($dataTable['oc'],$oc_data[$x]);
       		}
       		
       		
       		//CONSUMPTION TAX RATE [CTR]
       		for($x=0;$x<count($cnsmptnTaxRateInfo);$x++){
       			if(empty($cnsmptnTaxRateInfo[$x]['cnsmptnTaxRate'])){
       				$cnsmptnTaxRateInfo[$x]['cnsmptnTaxRate'] ='0.0';
       			}
       			$start_dates 											= $this->dateFormate($cnsmptnTaxRateInfo[$x]['applcblStrtDate']);
       			$end_dates 												= $this->dateFormate($cnsmptnTaxRateInfo[$x]['applcblEndDate']);
       			$ct_data[$x]['htl_infrmtn_unq_id']  		    		= $unq_id;
       			$ct_data[$x]['vrsn_nmbr'] 			 					= $this->versionCount;
       			$ct_data[$x]['start_dates'] 							= $start_dates;
       			$ct_data[$x]['end_dates'] 								= $end_dates;
       			$ct_data[$x]['cnsmptn_tax_rate'] 						= $cnsmptnTaxRateInfo[$x]['cnsmptnTaxRate'];
       			$this->nowTimeToInsertData($dataTable['ct'],$ct_data[$x]);
       		}

       		//WEB DISCOUNT INFORMATION [WDI]
       		for($x=0;$x<count($webDscntInfrmtn);$x++){
       			if(empty($webDscntInfrmtn[$x]['dscntDmnt'])){
       				$webDscntInfrmtn[$x]['dscntDmnt'] ='0.0';
       			}

       			$start_dates 											= $this->dateFormate($webDscntInfrmtn[$x]['applcblStrtDate']);
       			$end_dates 												= $this->dateFormate($webDscntInfrmtn[$x]['applcblEndDate']);
       			$wd_data[$x]['htl_infrmtn_unq_id']  		    		= $unq_id;
       			$wd_data[$x]['vrsn_nmbr'] 			 					= $this->versionCount;
       			$wd_data[$x]['start_dates'] 							= $start_dates;
       			$wd_data[$x]['end_dates'] 								= $end_dates;
       			$wd_data[$x]['dscnt_amnt'] 								= $webDscntInfrmtn[$x]['dscntDmnt'];
       			$this->nowTimeToInsertData($dataTable['wd'],$wd_data[$x]);
       		}       		
       	}
		
       	//ROOM CHARGE INFORMATION [RCI]
		for($j=0;$j<count($roomTypeInfrmtnList);$j++){
       		for($k=0;$k<count($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList']);$k++){
	        	if(empty($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['prsnsCrgInfrmtnList'])){
	       			continue;
	       		} 
	       		for($l=0;$l<count($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['prsnsCrgInfrmtnList']);$l++){		
	       			$occpncy = 0;
	       			if(!empty($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['prsnsCrgInfrmtnList'][$l]['nmbrPpl'])){
	       				$occpncy = $roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['prsnsCrgInfrmtnList'][$l]['nmbrPpl'];
	       			}
	       			$prc = 0.0;
	       			if(!empty($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['prsnsCrgInfrmtnList'][$l]['nmbrPpl'])){
	       				$prc = $roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['prsnsCrgInfrmtnList'][$l]['listPrcIncldngTax'];
	       			} $target_date ="0000-00-00";
	       			if(!empty($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['trgtDate'])){
	       				$target_date 	= $this->dateFormate($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['trgtDate']);
	       			} $mmbr_dscnt_rate ='0';
	       			if(!empty($roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['mmbrDscntRate'])){
	       				$mmbr_dscnt_rate = $roomTypeInfrmtnList[$j]['roomChrgInfrmtnList'][$k]['mmbrDscntRate'];
	       			}if(empty($roomTypeInfrmtnList[$j]['roomTypeCode'])){
	       				$roomTypeInfrmtnList[$j]['roomTypeCode']= ' ';
	       			}if(empty($roomTypeInfrmtnList[$j]['planCode'])){
	       				$roomTypeInfrmtnList[$j]['planCode']= ' ';
	       			}

	       			$rci_data[$j][$k][$l]['vrsn_nmbr'] 					= $this->versionCount;
	       			$rci_data[$j][$k][$l]['htl_code'] 					= $htl_code;
	       			$rci_data[$j][$k][$l]['room_type_code'] 			= $roomTypeInfrmtnList[$j]['roomTypeCode'];
	       			$rci_data[$j][$k][$l]['plan_code'] 					= $roomTypeInfrmtnList[$j]['planCode'];    // plan_code				- (planCode)
	       			$rci_data[$j][$k][$l]['trgt_date'] 					= $target_date;
	       			$rci_data[$j][$k][$l]['occpncy'] 					= $occpncy;
	       			$rci_data[$j][$k][$l]['prc'] 						= $prc;
	       			$rci_data[$j][$k][$l]['mmbr_dscnt_rate'] 			= $mmbr_dscnt_rate;
	       			$this->nowTimeToInsertData($dataTable['rc'],$rci_data[$j][$k][$l]);
	       		}   		
      		} 		
		} 	
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ Insert 
	//-------------------------------------------------------------------------------------------------------------------
	public function nowTimeToInsertData($tableName,$row){
		$tbl 	= $this->tableName;
 		$result = $this->checkDuplicateEntry($tableName,$row);
		if($result){
			$os = array($tbl['rc'], $tbl['oc']);
			if (!in_array($tableName, $os)) {
				$this->db->where('htl_infrmtn_unq_id', $row['htl_infrmtn_unq_id']);
			}
/*  			if($tableName !=$tbl['rc']){
				$this->db->where('htl_infrmtn_unq_id', $row['htl_infrmtn_unq_id']);
			} */ 
			if($tableName ==$tbl['ci']){
				$this->db->where('crdt_code', $row['crdt_code']);
			}else if($tableName ==$tbl['ei']){
				$this->db->where('eqpmnt_code', $row['eqpmnt_code']);
			}else if($tableName ==$tbl['rt']){
				$this->db->where('room_type_code', $row['room_type_code']);
				$this->db->where('plan_code', $row['plan_code']);
			}else if($tableName ==$tbl['ki']){
 				$this->db->where('kywrd_type', $row['kywrd_type']);
				$this->db->where('kywrd_name', $row['kywrd_name']);
				$this->db->where('rt_name', $row['rt_name']);
				$this->db->where('mns_trnsprttn', $row['mns_trnsprttn']);
				$this->db->where('time_rqrd', $row['time_rqrd']); 
			}else if($tableName ==$tbl['ct']){
				$this->db->where('start_dates', $row['start_dates']);
			}else if($tableName ==$tbl['wd']){
				$this->db->where('start_dates', $row['start_dates']);
			}else if($tableName ==$tbl['rc']){
				$this->db->where('htl_code', $row['htl_code']);
				$this->db->where('room_type_code', $row['room_type_code']);
				$this->db->where('plan_code', $row['plan_code']);
				$this->db->where('trgt_date', $row['trgt_date']);
				$this->db->where('occpncy', $row['occpncy']);
			}else if($tableName ==$tbl['oc']){
				$this->db->where('htl_code', $row['htl_code']);
				$this->db->where('optn_id', $row['optn_id']);
				$this->db->where('start_date', $row['start_date']);
				$this->db->where('end_date', $row['end_date']);
			}
			$row['updt_date'] 			= $this->currentDate;
			$row['updt_time'] 			= $this->currentTime;
			if(!$this->db->update($tableName, $row)){
				$this->errMsg[30] = "【テーブル （ {$tableName}）】  更新が失敗しました";
				$this->msgLog("更新エラー",$this->errMsg[30]);
				continue;
			}
		}else{
			
			$row['entry_date'] 		= $this->currentDate;
			$row['entry_time'] 		= $this->currentTime;
			if(!$this->db->insert($tableName, $row)){
				$this->errMsg[31] = "【テーブル （ {$tableName}）】  挿入が失敗しました";
				$this->msgLog("挿入エラー",$this->errMsg[31]);
				continue;
			}
		}
		if ($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
		}else{
			$this->db->trans_commit();
		}
	}

	//-------------------------------------------------------------------------------------------------------------------
	//@ UX1 Double Entry Check
	//-------------------------------------------------------------------------------------------------------------------
	public function checkDuplicateEntry($tableName,$data){
		$tbl 	= $this->tableName;
		$this->db->from($tableName);
		$os = array($tbl['rc'], $tbl['oc']);
		if (!in_array($tableName, $os)) {
			$this->db->where('htl_infrmtn_unq_id', $data['htl_infrmtn_unq_id']);
		}
/*  		if($tableName !=$tbl['rc']){
			$this->db->where('htl_infrmtn_unq_id', $data['htl_infrmtn_unq_id']);
		} */ 

		if($tableName == $tbl['hi']){ 					//hotel_info
			$this->db->where('applctn_vrsn_nmbr', $data['applctn_vrsn_nmbr']);
			$this->db->where('htl_code', $data['htl_code']);
			$this->db->where('lngg', $data['lngg']);
		}else if($tableName == $tbl['ci']){				//credit_infomation
			$this->db->where('crdt_code', $data['crdt_code']);
		}else if($tableName == $tbl['ei']){				//equipment_information
			$this->db->where('eqpmnt_code', $data['eqpmnt_code']);
		}else if($tableName == $tbl['rt']){				//room_type_information
			$this->db->where('room_type_code', $data['room_type_code']);
			$this->db->where('plan_code', $data['plan_code']);
		}else if($tableName == $tbl['ki']){				//keyword_information
 			$this->db->where('kywrd_type', $data['kywrd_type']);
			$this->db->where('kywrd_name', $data['kywrd_name']);
			$this->db->where('rt_name', $data['rt_name']);
			$this->db->where('mns_trnsprttn', $data['mns_trnsprttn']);
			$this->db->where('time_rqrd', $data['time_rqrd']);	 	
		}else if($tableName == $tbl['ct']){				//consumption_tax_rate
			$this->db->where('start_dates', $data['start_dates']);
		}else if($tableName == $tbl['wd']){				//web_discount_information
			$this->db->where('start_dates', $data['start_dates']);
		}else if($tableName == $tbl['rc']){				//room_charge_infomation
			$this->db->where('htl_code', $data['htl_code']);
			$this->db->where('room_type_code', $data['room_type_code']);
			$this->db->where('plan_code', $data['plan_code']);
			$this->db->where('trgt_date', $data['trgt_date']);
			$this->db->where('occpncy', $data['occpncy']);
		}else if($tableName ==$tbl['oc']){				//option_charge_infomation
			$this->db->where('htl_code', $data['htl_code']);
			$this->db->where('optn_id', $data['optn_id']);
			$this->db->where('start_date', $data['start_date']);
			$this->db->where('end_date', $data['end_date']);
		}else if($tableName == $tbl['ak']){				//apikey_information
			$this->db->where('api_key', $data['api_key']);
		}else if($tableName == "application_version_control"){
			$this->db->where('applctn_vrsn_nmbr', $data['applctn_vrsn_nmbr']);
		}else if($tableName == $tbl['vi']){				//vacancy_information
			$this->db->where('applctn_vrsn_nmbr', $data['applctn_vrsn_nmbr']);
			$this->db->where('htl_code', $data['htl_code']);
			$this->db->where('room_type_code', $data['room_type_code']);
			$this->db->where('trgt_date', $data['trgt_date']);
		}
		
		$query = $this->db->get();
		if($query->num_rows() > 0){
			return true;
		}
		return false;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ その他メソッド
	//-------------------------------------------------------------------------------------------------------------------	
	public function validateIndex($compireBy,$data){
		$resultData 	= ' ';
		if(!empty($data)){
			for($i = 0; $i <count($data); $i++){
				if(!empty($data[$i]['lngg'])){
					if($compireBy == $data[$i]['lngg']){
						$resultData = $data[$i]['name'];
					}
				}
			}
		}
		return $resultData;
	}	
	
	public function getMaxValue($xdata){
		$maxValue	 	= array();
		if(!empty($xdata['addrss'])){
			$maxValue['addrss'] = count($xdata['addrss']);
		}if(!empty($xdata['htlName'])){
			$maxValue['htlName'] = count($xdata['htlName']);
		}if(!empty($xdata['prkngInfmtn'])){
			$maxValue['prkngInfmtn'] = count($xdata['prkngInfmtn']);
		}if(!empty($xdata['busInfmtn'])){
			$maxValue['busInfmtn'] = count($xdata['busInfmtn']);
		}if(!empty($xdata['pckpInfmtn'])){
			$maxValue['pckpInfmtn'] = count($xdata['pckpInfmtn']);
		}if(!empty($xdata['pckpInfmtn'])){
			$maxValue['rntcrInfmtn'] = count($xdata['rntcrInfmtn']);
		}if(!empty($xdata['brrrfrInfmtn'])){
			$maxValue['brrrfrInfmtn'] = count($xdata['brrrfrInfmtn']);
		}if(!empty($xdata['isoInfmtn'])){
			$maxValue['isoInfmtn'] = count($xdata['isoInfmtn']);
		}
		return $this->max_key($maxValue);
	}
	
	
	public function getKeywordVal($xdata){
		$maxValue	 					= array();
		if(!empty($xdata['rtName'])){
			$maxValue['rtName'] 		= count($xdata['rtName']);
		}if(!empty($xdata['sttnName'])){
			$maxValue['sttnName'] 		= count($xdata['sttnName']);
		}if(!empty($xdata['mnsTrnsprttn'])){
			$maxValue['mnsTrnsprttn']	= count($xdata['mnsTrnsprttn']);
		}if(!empty($xdata['timeRqrd'])){
			$maxValue['timeRqrd'] 		= count($xdata['timeRqrd']);
		}
		return $this->max_key($maxValue);
	}
	
	
	public function max_key($data){
		$da 		= array();
		$max 		= max($data);
		foreach($data as $key => $val){
			if ($val == $max){
				$da['ckey'] = $key;
				$da['val'] = $val;
				break;
			}
		}
		return $da;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 「バージョンテーブル」 バージョンカウント
	//-------------------------------------------------------------------------------------------------------------------
	public function getCountVersion(){
		$this->db->select_max('vrsn_nmbr');
		$this->db->limit(1);
		$query = $this->db->get('hotel_info');
		$data = 1;
		foreach ($query->result() as $row){
			if($row->vrsn_nmbr!= 0){
				$data = $row->vrsn_nmbr+1;
			}
		}
		return $data;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　ログメソッド
	//-------------------------------------------------------------------------------------------------------------------
	public function saveLog($log){
		$this->load->library('batch_log_hotelinfo');
		$this->batch_log_hotelinfo->write_log_batch($log);
	}
	
	public function msgLog($errorStatus,$errMsg){
		$errorS = str_pad($errorStatus, 30, " ");
		$message = $errorS." ： ".$errMsg;
		$this->saveLog($message);
	}	

	public function dateFormate($data){
		$dt_formatted = "0000-00-00";
		if(!empty($data)){
			$dt_formatted = substr($data,0,4)."-".substr($data,4,2)."-".substr($data,6,8);
		}	
		return $dt_formatted;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　プライベート値バリデーション
	//-------------------------------------------------------------------------------------------------------------------
	public function checkPrivateKeys(){		
		if(empty($this->host)){
			$this->errMsg[32] 	= "ホスト名が設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[32]);
			$this->end();
		}if(empty($this->port)){
			$this->errMsg[33] 	= "ポートが設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[33]);
			$this->end();
		}if(empty($this->method)){
			$this->errMsg[34] 	= "メソッドが設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[34]);
			$this->end();
		}if(empty($this->serviceHotelStateApiKey)){
			$this->errMsg[35] 	= "APIキーが設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[35]);
			$this->end();
		}if(empty($this->serviceHotelState)){
			$this->errMsg[36] 	= "ホテル状況が設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[36]);
			$this->end();
		}if(empty($this->serviceMode)){
			$this->errMsg[37] 	= "APIキーインフォメーションテーブルのモードが設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[37]);
			$this->end();
		}if(empty($this->serviceMode)){
			$this->errMsg[38] 	= "APIキーインフォメーションテーブルのAPIクラスが設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[38]);
			$this->end();
		}if(empty($this->tableName)){
			$this->errMsg[39] 	= "テーブル情報が設定されていません";
			$this->msgLog("サービスエラー",$this->errMsg[39]);
			$this->end();
		}
	}
}
