<?php
/*
 -------------------------------------------------------------------------------------------------------------------
 * @ 開発者					: Anamul Haq Farid
 * @ 日付					: 2014年08月11日
 * @ バッチ処理名				: 空室情報更新バッチ
 * @ 内容					: スマートフォン向けBサービス側の空室情報検索を実行し、空室情報の全情報を取得してAPIサーバの空室情報テーブルに反映する。
 * @ var tableName			: テーブル名
 * @ var host				: ホスト名
 * @ var port				: xmlrpc ポート
 * @ var method				: xmlrpc メソッド
 * @ var hotelCode			: ホテルコード
 * @ var api_key			: apiキー
 * @ var appVersionNumber	: バージョン番号 
 * @ var searchType			: 検索タイプ
 * @ var currentDate		: 現在日付
 * @ var nextDate			: 現在日付から１２か月先
 * @ var appVerState		: アプリケーションバージョンテーブルのstt値
 -------------------------------------------------------------------------------------------------------------------
 */

class Vacancy_model extends CI_Model {
	private $tableName;
	private $host;
	private $port;
	private $method;
	private $hotelCode;
	private $api_key;
	private $appVersionNumber;
	private $searchType; 
	private $currentDate;
	private $nextDate;
	private $appVerState;
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ コンストラクターメソッド
	//-------------------------------------------------------------------------------------------------------------------
	public function __construct() {
		parent::__construct ();
		
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 基本値設定メソッド
	//-------------------------------------------------------------------------------------------------------------------	
	public function getAndSetCommonVariables(){
		$this->tableName	=	"vacancy_information";
		$this->host 		=	$this->config->item('vinfo_server_host');
		$this->port 		=	$this->config->item('vinfo_server_port');
		$this->method 		=	$this->config->item('vinfo_server_method');
		$this->api_key		=	"webapi.toyoko-inn.com";
		$this->hotelCode 	=	array();
		$this->appVerState	= 	"1";
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 検索タイプでエラーログを差分
	//-------------------------------------------------------------------------------------------------------------------
	public function getInitStartProgramLog(){
		if($this->searchType=="0"){
			$this->saveLog(str_pad("空室情報全件更新開始", 100, "=",STR_PAD_BOTH));
		}else if($this->searchType=="1"){
			$this->saveLog(str_pad("空室情報差分更新開始", 100, "=",STR_PAD_BOTH));
		}else{
			$this->saveLog("バッチ開始		|	検索タイプコード	: ".str_pad($this->searchType, 20, " ")."|	エラー		:	空室情報検索タイプが見つかりまっせん");
			$this->end();
		}
	}	
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 「アプリケーションバージョンテーブル」を検索し、「状態」が　“１（サービス中）”　である「アプリバージョン番号」を取得処理
	//-------------------------------------------------------------------------------------------------------------------
	public function getVersionNumber(){
		$query = $this->db->get_where('application_version_control',array("stt"=>$this->appVerState),"1");
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row){
				$this->appVersionNumber =  $row->applctn_vrsn_nmbr;
			}
		}else{
			$this->saveLog("エラー			|	".str_pad("アプリケーションバージョンテーブルの状態", 20, " ")."|	エラーコード		:	アプリケーションバージョンテーブル「状態」データを存在ありません ");
			$this->end();
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 「ホテル状態テーブル」を検索し「ホテルコード」を取得処理
	//-------------------------------------------------------------------------------------------------------------------
	public function getHotelCodeFromHotelState(){
		$this->db->from('hotel_state');
		$this->db->where("api_key",$this->api_key);
		$query 				= $this->db->get();
		if($query->num_rows() == 0){
			$this->saveLog("APIキーエラー	|	APIコード		: ".str_pad($this->api_key, 20, " ")."|	エラー内容 		:	ホテル状態テーブルにAPIキー が存在ありません ");
			$this->end();
		}
		$data 				= array();
		$i					=0;
		foreach ($query->result() as $row){
			$data[$i] = $row->htl_code;
			$i++;
		}
		$this->hotelCode = $data;
	}

	//-------------------------------------------------------------------------------------------------------------------
	//@ 検索タイプセット
	//-------------------------------------------------------------------------------------------------------------------	
	public function setSearchType($data){
		$this->searchType = $data;
	}
	
	public function getSearchType(){
		return $this->searchType;
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 日付設定
	//-------------------------------------------------------------------------------------------------------------------
	public function getDateDifference(){
		$this->currentDate 	= date("Ymd");
		$this->nextDate 	= date('Ymd', strtotime($this->currentDate." +12 months"));
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 初期化処理
	//-------------------------------------------------------------------------------------------------------------------
	public function init(){
		$this->getAndSetCommonVariables();
		$this->getInitStartProgramLog();
		$this->getVersionNumber();
		$this->getHotelCodeFromHotelState();
		$this->getSearchType();
		$this->getDateDifference();
		$this->db->trans_begin();
	}

	//-------------------------------------------------------------------------------------------------------------------
	//@ 開始実行処理
	//-------------------------------------------------------------------------------------------------------------------	
	public function run(){
		$this->init();
		if($this->searchType=="0"){
			$this->executeAll();
		}else if($this->searchType=="1"){
			$this->executeDiff();
		}
		$this->end();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 取得した「ホテルコード」数以下の処理を繰り返す処理
	//-------------------------------------------------------------------------------------------------------------------
	public function executeAll(){
		foreach($this->hotelCode as $htl_code){
			$request_data = array(
				array($this->appVersionNumber,'string'),
				array($htl_code,'string'),
				array('S','string'),
				array($this->searchType,'string'),
				array($this->currentDate,'string'),
				array($this->nextDate,'string')
			);
			$this->retrieveData($request_data,$htl_code);
		}
	}

	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 取得した「ホテルコード」数以下の処理を繰り返す処理
	//-------------------------------------------------------------------------------------------------------------------
	public function executeDiff(){
		$htl_code = NULL;
		$request_data = array(
			array($this->searchType,'string'),
			array($this->currentDate,'string'),
			array($this->nextDate,'string')
		);
		$this->retrieveData($request_data,$htl_code);
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 終了処理
	//-------------------------------------------------------------------------------------------------------------------
	public function end(){
		$this->db->trans_complete();
		if($this->searchType=="0"){
			$this->saveLog(str_pad("空室情報全件更新終了", 100, "=",STR_PAD_BOTH));
		}else if($this->searchType=="1"){
			$this->saveLog(str_pad("空室情報差分更新終了", 100, "=",STR_PAD_BOTH));
		}
		die();
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ XMLRPC接続処理
	//-------------------------------------------------------------------------------------------------------------------
	public function retrieveData($request_data,$htl_code){
		$this->xmlrpc->server($this->host,$this->port);
		$this->xmlrpc->method($this->method);
		$this->xmlrpc->request($request_data);
		if ($this->xmlrpc->send_request()) {
			$received_response = $this->xmlrpc->display_response();
			if(!empty($received_response['vcncyInfrmtn'])){
				$this->insertAndUpdateData($received_response);
			}else{
				if(!empty($received_response['errrMssg'])){
					$this->saveLog("Ｂサービスエラー	|	ホテルコード 	: ".str_pad($htl_code, 20, " ")."|	エラーコード 	:	".$received_response['errrMssg']."");
				}else{
					$this->saveLog("Ｂサービスエラー	|	ホテルコード 	: ".str_pad($htl_code, 20, " ")."|	エラー 		:	Ｂサービスの情報が存在ありません");
				}
			}
		}else{
			$this->saveLog("Ｂサービスエラー	|	ホテルコード	: ".str_pad($htl_code, 20, " ")."|	エラーコード 	:	".$this->xmlrpc->display_error()."");
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 取得した空室情報の全件と差分を「空室情報テーブル」に挿入と更新
	//-------------------------------------------------------------------------------------------------------------------
	public function insertAndUpdateData($received_data){
		foreach($received_data['vcncyInfrmtn'] as $d){
			$data 							=	array();
			$data['applctn_vrsn_nmbr']		= 	$this->appVersionNumber;
			$data['htl_code'] 				=	$d['htlCode'];
			$data['room_type_code'] 		= 	$d['roomType'];
			$data['trgt_date'] 				= 	$d['trgtDate'];
			$data['mmmbrshpVcncy'] 			=	$d['mmbrshpVcncy'];
			$data['gnrlVcncy'] 				=	$d['gnrlVcncy'];
			$qury_result = $this->checkDuplicate($data);
			if($qury_result!=0){
				$this->db->where('unq_id',$qury_result);
				if(!$this->db->update($this->tableName, $data)){
					$this->saveLog("更新エラー		|	ホテルコード 	: ".str_pad($data['htl_code'], 20, " ")."|	エラーコード 	:	".$this->db->_error_number()."");
				}
			}else{
				if(!$this->db->insert($this->tableName, $data)){
					$this->saveLog("挿入エラー		|	ホテルコード 	: ".str_pad($data['htl_code'], 20, " ")."|	エラーコード 	:	".$this->db->_error_number()."");
				}
			}
			
			// 異常終了の場合
			if ($this->db->trans_status() == FALSE){
				$this->db->trans_rollback();
				$this->saveLog("SQLエラー		|	ホテルコード 	: ".str_pad($data['htl_code'], 20, " ")."|	エラーコード 	:	".$this->db->_error_number()."");
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
		$this->db->from($this->tableName);
		$this->db->where('applctn_vrsn_nmbr', $d['applctn_vrsn_nmbr']);
		$this->db->where('htl_code', $d['htl_code']);
		$this->db->where('room_type_code', $d['room_type_code']);
		$this->db->where('trgt_date', $d['trgt_date']);
		$this->db->limit(1);
		$query = $this->db->get();
		if($query->num_rows() > 0){
			foreach ($query->result() as $row){
				return $row->unq_id;
			}
		}else{
			return 0;
		}
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ 一時的データ確認処理
	//-------------------------------------------------------------------------------------------------------------------
	public function pr($data){
		echo "<pre>";
		print_r($data);
		echo "<pre>";
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@　ログメソッド
	//-------------------------------------------------------------------------------------------------------------------
	public function saveLog($log){
		$this->load->library('batch_log_hotelinfo');
		$this->batch_log_hotelinfo->write_log_batch($log,$this->searchType);
	}
	
	//-------------------------------------------------------------------------------------------------------------------
	//@ ＸＭＬデータテスト機能
	//-------------------------------------------------------------------------------------------------------------------
	public function testingData(){
		return $this->run();
	}
}