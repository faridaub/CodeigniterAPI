<?php
/*
 -------------------------------------------------------------------------------------------------------------------
 * @ 開発者						: Anamul Haq Farid
 * @ 日付						: 2014年08月11日
 * @　更新						: 2014年11月06日
 * @ バッチ処理名					: 空室情報更新バッチ
 * @ Bサービス名					: S002
 * @ 内容						: スマートフォン向けBサービス側の空室情報検索を実行し、空室全情報を取得してAPIサーバの空室情報テーブルに反映
 * @ var tableName				: テーブル名
 * @ var host					: ホスト名
 * @ var port					: xmlrpc ポート
 * @ var method					: xmlrpc メソッド
 * @ var api_key				: apiキー
 * @ var appVersionControlNum	: バージョン番号 
 * @ var currentDate			: 現在日付
 * @ var nextDate				: 現在日付から１２か月先
 * @ var serviceHotelState		: アプリケーションバージョンテーブルのstt値
 * @ var searchType				: 空室情報差分flag
 -------------------------------------------------------------------------------------------------------------------
 */

class Vacancydiff_model extends CI_Model {
	private $errMsgShowFlag = 0; //　開発者だけ更新 [ 0 = ブラウザにエラーを非表示  ] [ 1 = ブラウザにエラーを表示 ]
	private $errMsg			=	array();
	private $host;
	private $port;
	private $method;
	private $appVersionControlNum;
	private $searchType;
	private $currentDate;
	private $nextDate;
	private $serviceHotelState;
	private $serviceHotelStateApiKey;
	private $tableName;
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ コンストラクターメソッド
	//-------------------------------------------------------------------------------------------------------------------
	public function __construct() {
		parent::__construct ();
		
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 開始プルグラム
	//-------------------------------------------------------------------------------------------------------------------
	public function start(){
		$this->errMsg[0] ="空室情報差分更新開始";
		$this->saveLog(str_pad($this->errMsg[0], 100, "-"));
		$this->db->trans_begin();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 初期化処理
	//-------------------------------------------------------------------------------------------------------------------
	public function init(){
		$this->getAndSetCommonVariables();
		$this->checkPrivateKeys();
		$this->getCheckTargetTableExistance();
		$this->getApplicationVersionControlNum();
		$this->db->trans_begin();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 開始実行処理
	//-------------------------------------------------------------------------------------------------------------------
	public function run(){
		$this->start();
		$this->init();
		$this->execute();
		$this->end();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 基本値設定メソッド
	//-------------------------------------------------------------------------------------------------------------------	
	public function getAndSetCommonVariables(){
		$this->host 						=   $this->config->item('bi_service_host');
		$this->port 						=   $this->config->item('bi_service_port');
		$this->method 						=	"com.toyokoinn.api.service.SmartphoneApplicationHotelService.getVacancyInformation";
		$this->tableName 					=   $this->allTableList();
		$this->currentDate 					= 	date("Ymd");
		$this->nextDate 					= 	date('Ymd', strtotime($this->currentDate." +12 months"));
		$this->serviceHotelStateApiKey		=	$this->config->item('bi_api_key');
		$this->serviceHotelState			= 	$this->config->item('bi_hotel_state');
		$this->searchType					= 	"1";
	}
	
	public function allTableList(){
		$tables 							=	array();
		$tables['av'] 						=	"application_version_control";
		$tables['vi'] 						=	"vacancy_information";
		return $tables;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ テーブル存在を確認
	//-------------------------------------------------------------------------------------------------------------------
	public function getCheckTargetTableExistance(){
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
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 「アプリケーションバージョンテーブル」を検索し、「状態」が　“１（サービス中）”　である「アプリバージョン番号」を取得処理
	//-------------------------------------------------------------------------------------------------------------------
	public function getApplicationVersionControlNum(){
		$this->db->from($this->tableName['av']);
		$this->db->where("stt",$this->serviceHotelState);
		$this->db->where("api_key",$this->serviceHotelStateApiKey);
		$query = $this->db->get();
		if(!$query->num_rows() > 0){
			$this->errMsg[2] = "APIキー（{$this->serviceHotelStateApiKey}）と状態が（{$this->serviceHotelState}）で検索してアプリケーションバージョンを番号取得できません";
			$this->msgLog("サービスエラー",$this->errMsg[2]);
			$this->end();
		}
		
		foreach ($query->result() as $row){
			$this->appVersionControlNum =  $row->applctn_vrsn_nmbr;
		}
		
		if($this->appVersionControlNum == NULL || empty($this->appVersionControlNum)){
			$this->errMsg[3] = "アプリケーションバージョン番号( {$row->applctn_vrsn_nmbr} )が存在ありません";
			$this->msgLog("サービスエラー",$this->errMsg[3]);
			$this->end();
		}
	}
		
	//-------------------------------------------------------------------------------------------------------------------
	//@ 取得した「ホテルコード」数以下の処理を繰り返す処理
	//-------------------------------------------------------------------------------------------------------------------
	public function execute(){
		$request_data 	= array(
				array($this->appVersionControlNum,'string'),
				array('','string'),
				array('','string'),
				array($this->searchType,'string'),
				array($this->currentDate,'string'),
				array($this->nextDate,'string')
			);
		$this->retrieveData($request_data);
	}

	//-------------------------------------------------------------------------------------------------------------------
	//@ 終了処理
	//-------------------------------------------------------------------------------------------------------------------
	public function end(){
		$this->errMsg[4]="空室情報差分更新終了";
		$this->saveLog(str_pad($this->errMsg[4], 100, "-"));
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
	//@ XMLRPC接続処理
	//-------------------------------------------------------------------------------------------------------------------
	public function retrieveData($request_data){
		$this->xmlrpc->server($this->host,$this->port);
		$this->xmlrpc->method($this->method);
		$this->xmlrpc->request($request_data);
		if ($this->xmlrpc->send_request()) {
			$received_response = $this->xmlrpc->display_response();
			if(!empty($received_response['vcncyInfrmtn'])){
				$this->insertAndUpdateData($received_response);
			}else{
				$this->errMsg[5] = "Bサービスの空室差分情報が存在しません";
				$this->msgLog("Ｂサービスエラー",$this->errMsg[5]);
			}
		}else{
			$this->errMsg[6] = "Bサービス接続失敗しました";
			$this->msgLog("接続スエラー",$this->errMsg[6]);
			$this->end();
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 取得した空室情報の全件と差分を「空室情報テーブル」に挿入と更新
	//-------------------------------------------------------------------------------------------------------------------
	public function insertAndUpdateData($received_data){
		foreach($received_data['vcncyInfrmtn'] as $d){
			$cdate 							= 	date("Y-m-d");
			$ctime 							= 	date("H:i:s");
			$data 							=	array();
			$data['htl_code'] 				=	$d['htlCode'];
			$data['room_type_code'] 		= 	$d['roomType'];
			$data['trgt_date'] 				= 	$d['trgtDate'];
			$data['mmmbrshpVcncy'] 			=	$d['mmbrshpVcncy'];
			$data['gnrlVcncy'] 				=	$d['gnrlVcncy'];
			$qury_result = $this->checkDuplicate($data);
			if($qury_result!=0){
				$data['updt_date'] 			= $cdate;
				$data['updt_time'] 			= $ctime;
				$this->db->where('unq_id',$qury_result);
				if(!$this->db->update($this->tableName['vi'], $data)){
					$this->errMsg[7] = "テーブル(　{$this->tableName['vi']}　)を挿入失敗しました";
					$this->msgLog("更新エラー",$this->errMsg[7]);
				}
			}else{
				$data['entry_date'] 		= $cdate;
				$data['entry_time'] 		= $ctime;
				if(!$this->db->insert($this->tableName['vi'], $data)){
					$this->errMsg[8] = "テーブル（{$this->tableName['vi']}）を挿入失敗しました";
					$this->msgLog("挿入エラー",$this->errMsg[8]);
				}
			}
			// 異常終了の場合
			if ($this->db->trans_status() == FALSE){
				$this->db->trans_rollback();
				$this->msgLog("SQLエラー","エラーコード -" .$this->db->_error_number());
			}else{
				$this->db->trans_commit();
			}
		}
	}
		
	//-------------------------------------------------------------------------------------------------------------------
	//@ 同じデータ存在を確認処理
	//-------------------------------------------------------------------------------------------------------------------
	public function checkDuplicate($d){
		$this->db->select('*');
		$this->db->from($this->tableName['vi']);
		$this->db->where('htl_code', $d['htl_code']);
		$this->db->where('room_type_code', $d['room_type_code']);
		$this->db->where('trgt_date', $d['trgt_date']);
		$this->db->limit(1);
		$query = $this->db->get();
		if($query->num_rows() > 0){
			foreach ($query->result() as $row){
				return $row->unq_id;
			}
		}
		return 0;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　ログメソッド
	//-------------------------------------------------------------------------------------------------------------------
	public function saveLog($log){
		$this->load->library('batch_log_vacancyinfo');
		$this->batch_log_vacancyinfo->write_log_batch($log,1);
	}
	
	public function msgLog($errorStatus,$detailsErrorMessage){
		$message = str_pad($errorStatus, 20, " ")." : ".$detailsErrorMessage;
		$this->saveLog($message);
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　プライベート値バリデーション
	//-------------------------------------------------------------------------------------------------------------------	
	public function checkPrivateKeys(){
		if(empty($this->host)){
			$this->errMsg[9] = "ホスト名が設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[9]);
			$this->end();
		}
	
		if(empty($this->port)){
			$this->errMsg[10] = "ポートが設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[10]);
			$this->end();
		}
	
		if(empty($this->method)){
			$this->errMsg[11] = "メソッドが設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[11]);
			$this->end();
		}
			
		if(empty($this->serviceHotelStateApiKey)){
			$this->errMsg[12] = "APIキーが設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[12]);
			$this->end();
		}
	
		if(empty($this->serviceHotelState)){
			$this->errMsg[13] = "ホテル状況が設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[13]);
			$this->end();
		}
		
		if($this->searchType==NULL){
			$this->errMsg[14] = "検索タイプが設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[14]);
			$this->end();
		}
	
		if(empty($this->tableName)){
			$this->errMsg[15] = "テーブル情報が設定されません";
			$this->msgLog("サービスエラー",$this->errMsg[15]);
			$this->end();
		}
	}
}