<?php
/**
 * スマホAPIサービス
 * 空室情報テーブルのアクセスクラス
 *
 * @package     CodeIgniter
 * @subpackage  Model
 * @category    Model
 * @author      TOCOM
 * @url         http://xxxx.co.jp
 *
 * Copyright c 2014 TOYOKO INN IT SHUUKYAKU SOLUTION CO.,LTD All Rights Reserved.
 *
 */

class vacancy_information_m extends CI_Model
{

	var $CLASS_NAME = 'vacancy_information_m';                   // Class name
	var $TABLE_NAME = 'vacancy_information';                     // Table name
	var $SELECT_COLUMN =                               // Select column names
	'unq_id,
		vrsn_nmbr,
		entry_date,
		entry_time,
		updt_date,
		updt_time,
		htl_code,
		room_type_code,
		plan_code,
		trgt_date,
		mmmbrshpVcncy,
		gnrlVcncy,
		blcklssVcncy';

	function __construct()
	{
		// Model クラスのコンストラクタを呼び出す
		parent::__construct();
	}

	/*
	 * 空室情報テーブルにレコードを１件追加する。
	*
	* @param   array   $rec                                : レコードの内容
	* @return  array   $result                             : 戻り値
	*                      ['err_code']                    : エラーコード(true=正常終了/ false=異常終了)
	*                      ['mssg']                        : エラーメッセージ
	*                      ['unq_id']                         : 追加したレコードのUID
	*
	*/
	function insert($rec)
	{
		//-------
		// 初期値
		//-------
		$FUNC                = 'insert';
		$result['errCode']   = true;
		$result['mssg']      = '';
		$result['unq_id'] = null;

		//-------------------
		// トランザクション処理開始
		//-------------------
		$this->db->trans_begin();

		//----------------
		// レコードの追加処理
		//----------------
		$rec['entry_date']     = date('Y-m-d');
		$rec['entry_time']     = date('H:i:s');

		$this->db->insert($this->TABLE_NAME, $rec);
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

		// UIDを取得する
		$result['unq_id'] = $this->db->insert_id();
		log_debug($this->CLASS_NAME.'->'.$FUNC,'[unq_id]'.$result['unq_id']);
		$this->db->trans_commit();

		// 異常終了の場合
		if ($this->db->trans_status() == FALSE){
			$this->db->trans_rollback();
			$result['errCode']  = false;
			$result['mssg']     = $this->lang->line('RECORD_INSERT_ERROR');
			log_error($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());
		}

		//-----------
		// 戻り値を返す
		//-----------
		return $result;
	}

	/*
	 * 空室情報テーブルの指定レコードを更新する。
	*
	* @param   array   $rec                                : レコードの更新内容
	* @return  array   $result： 戻り値
	*                      ['err_code']                    : エラーコード(true=正常終了/ false=異常終了)
	*                      ['mssg']                        : エラーメッセージ
	*/
	function update($rec){

		//-------
		// 初期値
		//-------
		$FUNC               = 'update';
		$result['errCode']  = true;
		$result['mssg']     = '';

		//-------------------
		// トランザクション処理開始
		//-------------------
		$this->db->trans_begin();

		//------------
		// 更新前チェック
		//------------
		$query = $this->db->query('select vrsn_nmbr from '.$this->TABLE_NAME.' where unq_id='.$rec['unq_id'].' for update');
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if($row->vrsn_nmbr != $rec['vrsn_nmbr'])
				{
					$result['errCode']  = false;
					$result['mssg']     = $this->lang->line('VRSN_NMBR_ERROR');
				}
			}
		}
		else
		{
			$result['errCode']  = false;
			$result['mssg']     = $this->lang->line('RECORD_NOT_FOUND');
		}

		//-------------------------
		// vacancy_informationの更新処理
		//-------------------------
		if($result['errCode'] == true){
			$rec['vrsn_nmbr'] = $rec['vrsn_nmbr']+1;   // バージョン番号をカウントUP

			// vacancy_informationテーブルにupdateする。
			$this->db->where('unq_id', $unq_id);
			$this->db->update($this->TABLE_NAME, $rec);
			log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

			// トランザクションエラーチェック
			if ($this->db->trans_status() === FALSE){
				$result['errCode']    = false;
				$result['mssg']        = $this->lang->line('RECORD_UPDATE_ERROR');
				log_error($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());
			}
		}

		//---------------------
		// トランザクションの終了処理
		//---------------------
		if($result['errCode'] == true){
			$this->db->trans_commit();
		}else{
			$this->db->trans_rollback();
		}

		//-----------
		// 戻り値を返す
		//-----------
		return $result;
	}

	/*
	 * 空室情報テーブルの指定レコードを削除する。
	*
	* @param   array   $params                             : パラメータ
	* @return  array   $result                             : 戻り値
	*                      ['err_code']                    : エラーコード(true=正常終了/ false=異常終了)
	*                      ['mssg']                        : エラーメッセージ
	*
	*/
	function delete($params)
	{
		//-------
		// 初期値
		//-------
		$FUNC                = 'delete';
		$result['errCode']   = true;
		$result['mssg']      = '';

		//-------------------
		// トランザクション処理開始
		//-------------------
		$this->db->trans_begin();

		foreach($params as $key => $value ){
			$this->db->where($key, $value);
		}
		$this->db->delete($this->TABLE_NAME);
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

		$this->db->trans_commit();

		// 異常終了の場合
		if ($this->db->trans_status() == FALSE){
			$this->db->trans_rollback();
			$result['errCode']  = false;
			$result['mssg']     = $this->lang->line('RECORD_INSERT_ERROR');
			log_error($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());
		}

		//-----------
		// 戻り値を返す
		//-----------
		return $result;
	}

	/*
	 * 空室情報テーブルからデータ取得。
	*
	* @param   var      $htl_code                                : 検索キー（ ホテルコード）
	* @param   var      $room_type_code                                : 検索キー（ 部屋タイプコード）
	* @param   var      $trgt_date                                : 検索キー（ 宿泊日付）
	* @return  array   $result                                  : 戻り値
	*                      ['err_code']                         : エラーコード(true=正常終了/ false=異常終了)
	*                      ['mssg']                             : エラーメッセージ
	*                      ['row']                              : 検索で取得した空室情報テーブルのレコード
	*
	*/
	function select( $htl_code, $room_type_code, $trgt_date)
	{
		//-------
		// 初期値
		//-------
		$FUNC               = 'select';
		$result['errCode']  = true;
		$result['mssg']     = '';

		//--------------
		// SELECT文の実行
		//--------------
		$this->db->select($this->SELECT_COLUMN);
		$this->db->where('htl_code',$htl_code);
		$this->db->where('room_type_code',$room_type_code);
		$this->db->where('trgt_date',$trgt_date);
		$this->db->from($this->TABLE_NAME);
		$query = $this->db->get();
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

		//-----------------
		// SQL実行結果の取得
		//-----------------
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$rec['unq_id']                            = $row->unq_id;
				$rec['vrsn_nmbr']                            = $row->vrsn_nmbr;
				$rec['entry_date']                            = $row->entry_date;
				$rec['entry_time']                            = $row->entry_time;
				$rec['updt_date']                            = $row->updt_date;
				$rec['updt_time']                            = $row->updt_time;
				$rec['htl_code']                            = $row->htl_code;
				$rec['room_type_code']                            = $row->room_type_code;
				$rec['plan_code']                            = $row->plan_code;
				$rec['trgt_date']                            = $row->trgt_date;
				$rec['mmmbrshpVcncy']                            = $row->mmmbrshpVcncy;
				$rec['gnrlVcncy']                            = $row->gnrlVcncy;
				$rec['blcklssVcncy']                            = $row->blcklssVcncy;
			}
		}
		else
		{    // 該当データなし
			$rec = array();
			$result['errCode']  = false;
			$result['mssg']     = $this->lang->line('RECORD_NOT_FOUND');
			log_error($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());
		}
		$result['rec'] = $rec;

		//-----------
		// 戻り値を返す
		//-----------
		return $result;
	}
		/*
	 * 空室情報テーブルから空室有無を確認する。
	*
	* @param   array    $htl_code                               : 検索キー（ホテルコード）
	* @param   var      $trgt_date                              : 検索キー（宿泊日付）
	* @param   var      $nmbr_Nghts                             : 検索キー（宿泊数） 
	* @param   var      $nmbrPpl                             	: 検索キー（宿泊人数） 
	* @param   var      $mmbrshp_flag							: 検索キー（会員フラグ） 
	* @param   var      $room_type_code                         : 検索キー（部屋タイプコード）
	* @return  var		$recCount                               : 0 空室なし
	*
	*/

	function selectVacancy($htl_code, $trgt_date, $nmbr_Nght, $nmbrPpl, $mmbrshp_flag, $room_type_code='')
	{
		//-------
		// 初期値
		//-------
		$FUNC               = 'selectVacancy';
		$recCount    = 0;
		//--------------
		// SELECT文の実行
		//--------------
		$this->db->select($this->SELECT_COLUMN);
		$this->db->where_in('htl_code',$htl_code);
		if ($mmbrshp_flag != '' && $mmbrshp_flag != NULL) {
			// 会員の場合
			if ($mmbrshp_flag == Api_const::MMBR_FLG_Y) {
				$this->db->where("mmmbrshpVcncy >=", $nmbrPpl);
			// 非会員の場合	
			} else {
				$this->db->where("gnrlVcncy >=", $nmbrPpl);
			}
		}
		
		if ($room_type_code!= ''){
			$this->db->where('room_type_code',$room_type_code);
		}
		$this->db->where($this->api_util->getSqlDateRange('trgt_date', $trgt_date, $nmbr_Nght));
		$this->db->from($this->TABLE_NAME);
		$query = $this->db->get();
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

		//-----------------
		// SQL実行結果の取得
		//-----------------
		if ($query->num_rows() > 0)
		{
			$recCount = 1;
		}

		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());
		return $recCount;
	}

	/*
	 * 空室情報テーブルのレコードリストを取得する
	*
	* @param   array   $params : パラメータ
	* @return  array   $result :  戻り値
	*              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
	*              ['mssg']    : エラーメッセージ
	*              ['list']    : 検索で取得した空室情報テーブルのリスト
	*
	*/
	function selectListCond($params)
	{
		//-------
		// 初期値
		//-------
		$FUNC               = 'selectListCond';
		$result['errCode']  = true;
		$result['mssg']     = '';
		$result['recCnt']   = 0;
		$result['recList']  = '';

		//--------------
		// SELECT文の実行
		//--------------
		$this->db->select($this->SELECT_COLUMN);
		$this->db->from($this->TABLE_NAME);
		foreach($params as $key => $value ){
			if($value != '' and $value != null ){
				$this->db->where($key, $value);
			}
		}
		$query = $this->db->get();
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

		//-----------------
		// SQL実行結果の取得
		//-----------------
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$recList[]	= $row;
			}
			$result['recList'] = $recList;
			$result['recCnt'] = $query->num_rows();
		}

		//-----------
		// 戻り値を返す
		//-----------
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[errCode]'.$result['errCode']);
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[mssg]'.$result['mssg']);
		return $result;
	}

	/*
	 * ホテル情報テーブルのレコードリストを取得する
	*
	* @api_mode    var     $api_mode : APIモード
	* @param       array   $params   : パラメータ
	* @param       var	   $search_mode :HOTEL_ONLY :　検索条件に該当する空室情報無しのホテル情報取得する 
	*									 HOTEL_AND_VACANCY :　検索条件に該当する空室情報ありのホテル情報取得する 
	* @return      array   $result   :  戻り値
	*              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
	*              ['mssg']    : エラーメッセージ
	*              ['recCnt']     : 検索で取得したホテル情報の件数
	*              ['recList']    : 検索で取得したホテル情報テーブルのリスト
	*
	*/
	function selectJoinListCond($api_mode, $params, $search_mode=Api_const::HOTEL_AND_VACANCY)
	{
		//-------
		// 初期値
		//-------
		$FUNC               = 'selectJoinListCond';
		$result['errCode']  = true;
		$result['mssg']     = '';
		$result['recCnt']   = 0;
		$result['recList']  = '';

		if ($api_mode == Api_const::A005){
			// 距離でホテル情報抽出
			$param_hi['lngg'] = $params['lngg'];
			$result_hi = $this->hotel_info_m->selectListCond($param_hi);
			if ($result_hi['errCode'] !== true) {
				return $result;
			}
			$htlInfrmtnUnqIds = array();
			$dstncList =  array();
			if ($result_hi['recCnt'] != 0){
				foreach($result_hi['recList'] as $row ){
					// 距離取得(parameter指定有[lngtd,lttd])
					$dstnc = $this->api_util->getDistanceKmByParam($params, $row['lttd'], $row['lngtd']);
					if ($dstnc != null){
						$dstncList[] = array($row['htl_code'] => $dstnc);	// 距離
						// 検出範囲内
						if ($dstnc <= $params['dstnc']){
							$htlInfrmtnUnqIds[] = $row['unq_id'];			// unq_id
						}
					}
				}
			}
			// 0件時はreturn
			if (count($htlInfrmtnUnqIds) == 0){
				return $result;
			}
		}

		//--------------
		// SELECT文の実行
		//--------------
		$sql_select = "";
		$sql_select .= "hi.htl_code";
		$sql_select .= ",hi.htl_name";
		$sql_select .= ",hi.lngtd";
		$sql_select .= ",hi.lttd";
		$sql_select .= ",DATE_FORMAT(hi.open_date, ('%Y%m%d')) open_date"; // オープン日
		$sql_select .= ",hi.time_zone";                         // タイムゾーン
		$sql_select .= ",rti.room_type_code";                   // 部屋タイプコード
		$sql_select .= ",rti.room_type_name";                   // 部屋タイプ名
		$sql_select .= ",rti.plan_code";                   		// プランコード
		$sql_select .= ",rti.plan_name";                   		// プラン名

		if ($search_mode == Api_const::HOTEL_AND_VACANCY) {
			// 空室数
			if ($this->api_com_util->isSetNotNull($params, 'mmbrshpFlag')) {
				// 会員の場合
				if ($params['mmbrshpFlag'] == Api_const::MMBR_FLG_Y) {
					$sql_select .= ",vi.mmmbrshpVcncy vcncy";
				}else{
					$sql_select .= ",vi.gnrlVcncy vcncy";
				}
			}
		}
		$this->db->from('hotel_info hi');                                                  // ホテル情報
		$this->db->join('hotel_state hs', 'hi.htl_code=hs.htl_code');                      // ホテル状態
		$this->db->join('room_type_information rti', 'hi.unq_id=rti.htl_infrmtn_unq_id');  // 部屋情報

		if ($search_mode == Api_const::HOTEL_AND_VACANCY) {
			//$this->db->join('vacancy_information vi', 'hi.htl_code=vi.htl_code AND rti.room_type_code=vi.room_type_code'); // 空室情報
			$this->db->join('vacancy_information vi', 'hi.htl_code=vi.htl_code AND rti.room_type_code=vi.room_type_code and rti.plan_code = vi.plan_code'); // 空室情報
		}

		$this->db->where('hi.lngg', $params['lngg']);
		$this->db->where("hs.api_key", $params['apikey']);
		$stats = array(Api_const::HTL_STT_HANBAI);
		if ($params['apikey_information_mode'] == Api_const::SYORI_MODE_TEST) {
			// API情報の処理モードが「テスト」の場合
			$stats[] = Api_const::HTL_STT_TEST;
		}
		$this->db->where_in('hs.state', $stats);
		$this->db->where("hi.applctn_vrsn_nmbr", $params['applctnVrsnNmbr']);
		
		if ($search_mode == Api_const::HOTEL_AND_VACANCY) {	
			// 宿泊日(FROM～TO)
			$this->db->where($this->api_util->getSqlDateRange('vi.trgt_date', $params['chcknDate'], $params['nmbrNghts']));
			// 会員フラグ判定
			if ($this->api_com_util->isSetNotNull($params, 'mmbrshpFlag')) {
				if ($params['mmbrshpFlag'] == Api_const::MMBR_FLG_Y) {
					// 会員の場合
					$this->db->where("vi.mmmbrshpVcncy >=", $params['nmbrRms']);
				} else {
					// 非会員の場合
					$this->db->where("vi.gnrlVcncy >=", $params['nmbrRms']);
				}
			}
		}
		// 禁煙・喫煙フラグ
		if ($this->api_com_util->isSetNotNull($params, 'smkngFlag') && $params['smkngFlag']==Api_const::SMOKING_NO){
			$this->db->where("rti.smkng_flag", $params['smkngFlag']);
		}
		$this->db->where("rti.mxmm_occpncy >=", $params['nmbrPpl']);              // 最大宿泊人数
		$this->db->where("rti.mxmm_stay >=", $params['nmbrNghts']);               // 最大宿泊日数
		switch ($api_mode){
			case  Api_const::A002:	// search_hotel_coordinate_api
				$sql_select = $sql_select.",('') kywrd_name";
				
				if ($search_mode == Api_const::HOTEL_AND_VACANCY) {	
					$this->db->order_by("hi.htl_code, rti.mxmm_occpncy, rti.room_type_code, vi.trgt_date");
				}
				else {
					$this->db->order_by("hi.htl_code, rti.mxmm_occpncy, rti.room_type_code");
				}
				break;
			case  Api_const::A003:	// search_hotel_keyword_api
				$sql_select = $sql_select.",ki.kywrd_name";
				$this->db->join('keyword_information ki', 'hi.unq_id=ki.htl_infrmtn_unq_id');      // キーワード情報
				// 検索キーワード
				if ($this->api_com_util->isSetNotNull($params, 'kywrd')) {
					$where_like = '';
					$where_like="concat(ki.kywrd_name,ki.rt_name,hi.htl_name) like "."'%".$params['kywrd']."%'";
					$this->db->where($where_like);
				}
				$this->db->distinct();

				if ($search_mode == Api_const::HOTEL_AND_VACANCY) {	
					$sql_select = $sql_select.",vi.trgt_date";
					$this->db->group_by("ki.kywrd_name, hi.htl_code, vi.trgt_date");
					$this->db->order_by("ki.kywrd_name, hi.htl_code, rti.mxmm_occpncy, vi.trgt_date");
				}
				else {
					$this->db->group_by("ki.kywrd_name, hi.htl_code");
					$this->db->order_by("ki.kywrd_name, hi.htl_code, rti.mxmm_occpncy");
				}
				break;
			case  Api_const::A004:	// search_hotel_area_api
				$sql_select = $sql_select.",('') kywrd_name";
				$sql_select = $sql_select.",hi.cntry_code";
				$sql_select = $sql_select.",hi.area_code";
				$sql_select = $sql_select.",hi.stt_code";
				$sql_select = $sql_select.",cm.cntry_name";
				$sql_select = $sql_select.",am.area_name";
				$sql_select = $sql_select.",sm.state_name";
				$sql_select = $sql_select.",ctm.city_code";
				$sql_select = $sql_select.",ctm.city_name";

				$this->db->join('country_master cm', 'hi.lngg=cm.lngg AND hi.cntry_code=cm.cntry_code'); // 国マスタ
				$this->db->join('area_master am', 'hi.lngg=am.lngg AND hi.cntry_code=am.cntry_code AND hi.area_code=am.area_code'); // エリア情報
				$this->db->join('state_master sm', 'hi.lngg=sm.lngg AND hi.cntry_code=sm.cntry_code AND hi.area_code=sm.area_code AND hi.stt_code=sm.state_code'); // 都道府県マスタ
				$this->db->join('city_master ctm', 'hi.lngg=ctm.lngg AND hi.cntry_code=ctm.cntry_code AND hi.stt_code=ctm.state_code AND hi.city_code=ctm.city_code','left'); // 都市マスタ

				if ($search_mode == Api_const::HOTEL_AND_VACANCY) {	
					$this->db->order_by("hi.cntry_code, hi.area_code, hi.stt_code, hi.htl_code, rti.mxmm_occpncy, rti.room_type_code, vi.trgt_date", "ctm.dsply_ordr");
				}
				else {
					$this->db->group_by("hi.htl_code");
					$this->db->order_by("hi.cntry_code, hi.area_code, hi.stt_code, hi.htl_code, rti.mxmm_occpncy, rti.room_type_code");
				}
				break;
			case  Api_const::A005:	// search_hotel_api
				$sql_select = $sql_select.",('') kywrd_name";
				$this->db->where_in("hi.unq_id", $htlInfrmtnUnqIds);
				//20141211 検索条件にroomTypeを追加
				if ($params['roomType']!=''&&array_key_exists('roomType',$params)){
					$this->db->where("rti.room_type_code", $params['roomType']);
				}
				if ($search_mode == Api_const::HOTEL_AND_VACANCY) {	
					$this->db->order_by("hi.htl_code, rti.mxmm_occpncy, rti.room_type_code, vi.trgt_date");
				}
				else {
					$this->db->order_by("hi.htl_code, rti.mxmm_occpncy, rti.room_type_code");
				}
				break;
				default:
		}

		$this->db->select($sql_select);
		$query = $this->db->get();
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());
		$flag = 0;
		//-----------------
		// SQL実行結果の取得
		//-----------------
		if ($query->num_rows() > 0) {
			$list = $query->result_array();
			if ($search_mode == Api_const::HOTEL_AND_VACANCY) {	
				// 予約可能期間
				$rsrv_mnth = $this->api_util->getRsrvMnth($params);
				$recList = array();
				$oldRow = null;
				$tmps = array();
				$tmpsAdd = false;
				$tmpsHtl = array();
				$dstnc = 0;
				$eof = false;
				$idx = 0;
				while (true){
					// get row
					if ($idx < count($list)){
						$row = $list[$idx];
					}else{
						$eof = true;
					}
					
					$row_plan = "";
					$oldRow_plan = "";
					$row_plan = $row['room_type_code'].$row['plan_code'];
					$oldRow_plan = $oldRow['room_type_code'].$oldRow['plan_code'];
					
					// Key Break [htl_code , room_type_code]
					if ($eof === true || (count($tmps) != 0 && ($oldRow_plan != $row_plan || $oldRow['htl_code'] != $row['htl_code'] || $oldRow['room_type_code'] != $row['room_type_code']))) {
						// 共通条件
						// 到着日予約可能チェック
						if ($this->api_util->chkRsrvRange($oldRow['open_date'], $params['chcknDate'], $oldRow['time_zone'], $rsrv_mnth, $params['nmbrNghts'], $tmps)) {
							if ($api_mode == Api_const::A006){
								$recList = array_merge($recList, $tmps);
							}else{
								$tmpsHtl[] = $oldRow;
							}
							$flag = 1;
						}
						// Key Break [htl_code]
						if ($eof === true || (count($tmps) != 0 && ($oldRow['kywrd_name'] != $row['kywrd_name'] || $oldRow['htl_code'] != $row['htl_code']))) {
							if (count($tmpsHtl) != 0){
								if ($api_mode != Api_const::A006 || $api_mode == Api_const::A003){
									$recList[] = $oldRow;
								}
							}
							$tmpsHtl = array();
						}
						$tmps = array();
						$tmpsAdd = false;
					}
					if ($eof == true){
						break;
					}

					if ($api_mode == Api_const::A005){
						$row['dstnc'] = $this->api_com_util->getValueArrayByName($dstncList, $row['htl_code']);
					}
					$tmps[] = $row;
					// key stock
					$oldRow = $row;
					$idx++;
				}
				$result['recList'] = $recList;
				$result['recCnt'] = count($recList);
			}
			else{
				$result['recList'] = $list;
				$result['recCnt'] = count($list);
			}	
			if ($search_mode == Api_const::HOTEL_AND_VACANCY && $flag == 0) {
				$result['errCode'] = Api_const::BAPI1007;
			}
		}
		//-----------
		// 戻り値を返す
		//-----------
		else {
			$result['errCode'] = Api_const::BAPI1004;
		}
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[errCode]'.$result['errCode']);
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[mssg]'.$result['mssg']);
		return $result;
	}
}
