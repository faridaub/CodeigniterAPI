<?php
/**
 * スマホAPIサービス
 * 料金情報テーブルのアクセスクラス
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

class room_charge_infomation_m extends CI_Model
{

	var $CLASS_NAME = 'room_charge_infomation_m';                   // Class name
	var $TABLE_NAME = 'room_charge_infomation';                     // Table name
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
		occpncy,
		prc,
		mmbr_dscnt_rate';

	function __construct()
	{
		// Model クラスのコンストラクタを呼び出す
		parent::__construct();
	}

	/*
	 * 料金情報テーブルにレコードを１件追加する。
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
	 * 料金情報テーブルの指定レコードを更新する。
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
		// room_charge_infomationの更新処理
		//-------------------------
		if($result['errCode'] == true){
			$rec['vrsn_nmbr'] = $rec['vrsn_nmbr']+1;   // バージョン番号をカウントUP

			// room_charge_infomationテーブルにupdateする。
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
	 * 料金情報テーブルの指定レコードを削除する。
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
	 * 料金情報テーブルからデータ取得。
	*
	* @param   var      $htl_code                                : 検索キー（ ホテルコード）
	* @param   var      $room_type_code                                : 検索キー（ 部屋タイプコード）
	* @param   var      $trgt_date                                : 検索キー（ 宿泊日付）
	* @param   var      $occpncy                                : 検索キー（ 宿泊人数(1人～7人)）
	* @return  array   $result                                  : 戻り値
	*                      ['err_code']                         : エラーコード(true=正常終了/ false=異常終了)
	*                      ['mssg']                             : エラーメッセージ
	*                      ['row']                              : 検索で取得した料金情報テーブルのレコード
	*
	*/
	function select( $htl_code, $room_type_code, $trgt_date, $occpncy)
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
		$this->db->where('occpncy',$occpncy);
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
				$rec['trgt_date']                            = $row->trgt_date;
				$rec['occpncy']                            = $row->occpncy;
				$rec['prc']                            = $row->prc;
				$rec['mmbr_dscnt_rate']                            = $row->mmbr_dscnt_rate;
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
	 * 料金情報テーブルのレコードリストを取得する
	*
	* @param   array   $params : パラメータ
	* @return  array   $result :  戻り値
	*              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
	*              ['mssg']    : エラーメッセージ
	*              ['list']    : 検索で取得した料金情報テーブルのリスト
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
	 * 料金情報テーブルのレコードリストを取得する
	*
	* @param   array   $params   : パラメータ
	* @param   string  $api_mode : APIモード
	* @param   var	   $search_mode :HOTEL_ONLY :　検索条件に該当する空室情報無のホテル情報取得する 
	*									 HOTEL_AND_VACANCY :　検索条件に該当する空室情報ありのホテル情報取得する 
	* @return  array   $result   :  戻り値
	*              ['errCode']   : エラーコード(true=正常終了/ false=異常終了)
	*              ['mssg']      : エラーメッセージ
	*              ['recCnt']    : 検索で取得した料金情報テーブルの件数
	*              ['recList']   : 検索で取得した料金情報テーブルのリスト
	*
	*/
	function selectJoinListCond($params, $api_mode, $search_mode=Api_const::HOTEL_AND_VACANCY)
	{
	//-------
		// 初期値
		//-------

		$FUNC               = 'selectJoinListCond';
		$result['errCode']  = true;
		$result['mssg']     = '';
		$result['recCnt']   = 0;
		$result['recList']  = '';
		
		if ($api_mode == Api_const::A008 || $api_mode == Api_const::A006){
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
						if ($params['mode'] == '3' && $dstnc <= $params['dstnc']){
							$htlInfrmtnUnqIds[] = $row['unq_id'];			// unq_id
						}
					}
				}
			}
			// 0件時はreturn
			if (count($htlInfrmtnUnqIds) == 0){
				if ($params['mode'] == '3'){
					return $result;
				}
			}
		}
		//--------------
		// SELECT文の実行
		//--------------
		$sql_select = "";
		$sql_select .= " hi.unq_id htl_infrmtn_unq_id";         // ホテル情報UID
		$sql_select .= ",hi.htl_code";                          // ホテルコード
		$sql_select .= ",hi.htl_name";                          // ホテル名
		$sql_select .= ",hi.img_url htl_img_url";               // 画像URL(ホテル)
		$sql_select .= ",hi.crrncy_name";                       // 通貨名
		$sql_select .= ",hi.crrncy_sign";                       // 通貨記号
		$sql_select .= ",hi.lngtd";                             // ホテルの経度
		$sql_select .= ",hi.lttd";                              // ホテルの緯度
		$sql_select .= ",DATE_FORMAT(hi.open_date, ('%Y%m%d')) open_date"; // オープン日
		$sql_select .= ",hi.time_zone";                         // タイムゾーン
		$sql_select .= ",hi.cnclltn_policy";                    // キャンセルポリシー
		$sql_select .= ",hi.trms_cndtns";                       // 宿泊約款
		$sql_select .= ",hi.rate_dsply_flag";                   // 料金表示フラグ
		$sql_select .= ",rti.unq_id";                   		// ユニークID
		$sql_select .= ",rti.room_type_code";                   // 部屋タイプコード
		$sql_select .= ",rti.room_type_name";                   // 部屋タイプ名
		$sql_select .= ",rti.smkng_flag";                       // 禁煙・喫煙フラグ
		$sql_select .= ",rti.img_url room_type_img_url";        // 画像URL(部屋タイプ)
		$sql_select .= ",rti.mxmm_occpncy";                     // 最大宿泊人数	//20141007 項目追加　iwamoto	
		$sql_select .= ",rti.mxmm_stay";                        // 最大宿泊日数
		$sql_select .= ",rti.lyng_of_chldrn_avlbl_flag";        // 子供の添い寝利用可能フラグ
		$sql_select .= ",rti.lyng_prsns";                       // 添い寝人数
		$sql_select .= ",rti.eco_avlbl_flag";                   // eco利用可能フラグ
		$sql_select .= ",rti.vod_avlbl_flag";                   // VOD利用可能フラグ
		$sql_select .= ",rti.bsnss_pack_avlbl_flag";            // ビジネスパック利用可能フラグ

		$sql_select .= ",rti.plan_code";//プランコード
		$sql_select .= ",rti.plan_name";//プラン名
		$sql_select .= ",rti.app_lngg";//適用言語
		$sql_select .= ",rti.app_mmbr";//適用会員
		$sql_select .= ",rti.eco_use_dvsn";//エコプラン利用区分
		$sql_select .= ",rti.vod_use_dvsn";//VOD利用区分
		$sql_select .= ",rti.bp_use_dvsn";//ビジネスパック利用区分
		$sql_select .= ",rti.lyng_of_chldrn_use_dvsn";//お子様添い寝利用区分
		$sql_select .= ",rti.bp_jdgmnt_dvsn";//ビジネスパック判定区分
		$sql_select .= ",rti.mimm_occpncy";//最小宿泊人数
		$sql_select .= ",rti.mimm_stay";//最少宿泊日数
		$sql_select .= ",rti.mxmm_nmbr_rms";//最大部屋数
		$sql_select .= ",rti.mimm_nmbr_rms";//最小部屋数

		$sql_select .= ",rci.trgt_date";                        // 宿泊日付
		$sql_select .= ",rci.occpncy";                          // 最大宿泊人数
		$sql_select .= ",rci.prc";                              // 定価(税抜)
		$sql_select .= ",rci.mmbr_dscnt_rate";                  // 会員割引率
		$sql_select .= ",IFNULL(ctr.cnsmptn_tax_rate, (0)) cnsmptn_tax_rate";  // 消費税率
		$sql_select .= ",IFNULL(wdi.dscnt_amnt, (0)) dscnt_amnt";              // 割引金額mrtn

		// Table Join
		$this->db->from("hotel_info hi");                                                  // ホテル情報
		$this->db->join("hotel_state hs", "hi.htl_code=hs.htl_code");                      // ホテル状態
		$this->db->join("room_type_information rti", "hi.unq_id=rti.htl_infrmtn_unq_id");  // 部屋情報
		$this->db->join("room_charge_infomation rci", "hi.htl_code =rci.htl_code AND rti.room_type_code =rci.room_type_code and rti.plan_code = rci.plan_code");                          // 料金情報
		$this->db->join("consumption_tax_rate ctr", "hi.unq_id=ctr.htl_infrmtn_unq_id AND ctr.start_dates <= rci.trgt_date AND ctr.end_dates >= rci.trgt_date", 'left');      // 消費税マスタ
		$this->db->join("web_discount_information wdi", "hi.unq_id=wdi.htl_infrmtn_unq_id AND wdi.start_dates <= rci.trgt_date AND wdi.end_dates >= rci.trgt_date", 'left');  // WEB割引情報

		// Where
		$this->db->where("hi.lngg", $params['lngg']);
		$this->db->where("hs.api_key", $params['apikey']);
		$stats = array(Api_const::HTL_STT_HANBAI);
		if ($params['apikey_information_mode'] == Api_const::SYORI_MODE_TEST) {
			// API情報の処理モードが「テスト」の場合
			$stats[] = Api_const::HTL_STT_TEST;
		}
		$this->db->where_in("hs.state", $stats);
		$this->db->where("hi.applctn_vrsn_nmbr", $params['applctnVrsnNmbr']);
		// 宿泊日(FROM～TO)
		if ($search_mode ==Api_const::HOTEL_AND_VACANCY && ($api_mode == Api_const::A006 || $api_mode == Api_const::A008 || $api_mode == Api_const::A009)) {
			$this->db->where($this->api_util->getSqlDateRange("vi.trgt_date", $params['chcknDate'], $params['nmbrNghts']));
		}
		else {
			$this->db->where($this->api_util->getSqlDateRange("rci.trgt_date", $params['chcknDate'], $params['nmbrNghts']));
		}
		
		if ($this->api_com_util->isSetNotNull($params, 'smkngFlag') && $params['smkngFlag']==Api_const::SMOKING_NO){
			$this->db->where("rti.smkng_flag", $params['smkngFlag']);              // 禁煙・喫煙フラグ
		}
		$this->db->where("rci.occpncy", $params['nmbrPpl']);                       // 宿泊人数(料金の利用人数)
		//20150115 最大宿泊日数追加　iwamoto
		$this->db->where("rti.mxmm_stay >=", $params['nmbrNghts']);               // 最大宿泊日数

		// ホテルコード,最大宿泊人数,部屋タイプコード,宿泊日
		$this->db->order_by("hi.htl_code, rti.mxmm_occpncy, rti.room_type_code, rti.plan_code, rci.occpncy, rci.trgt_date");

		switch ($api_mode){
			case  Api_const::A006:	// search_room_type_api
			case  Api_const::A008:	// search_hotel_vacant_api
			case  Api_const::A009:	// search_room_type_vacant_api

				if ($search_mode ==Api_const::HOTEL_AND_VACANCY) {
					$this->db->join("vacancy_information vi", "rci.htl_code=vi.htl_code AND rci.room_type_code=vi.room_type_code AND rci.trgt_date=vi.trgt_date and rci.plan_code=vi.plan_code"); // 空室情報
				}
				if ($this->api_com_util->isSetNotNull($params, 'htlCode')) {
					$this->db->where("hi.htl_code", $params['htlCode']);
				}
				$this->db->where("rti.mxmm_occpncy >=", $params['nmbrPpl']);               // 最大宿泊人数
				if ($search_mode ==Api_const::HOTEL_AND_VACANCY) {				
					// 会員フラグ判定
					if ($this->api_com_util->isSetNotNull($params, 'mmbrshpFlag')) {
						if ($params['mmbrshpFlag'] == Api_const::MMBR_FLG_Y) {
							// 会員の場合
							$sql_select .= ",vi.mmmbrshpVcncy vcncy";
							if ($this->api_com_util->isSetNotNull($params, 'nmbrRms')){
								$this->db->where("vi.mmmbrshpVcncy >=", $params['nmbrRms']);
							}
						} else {
							// 非会員の場合
							$sql_select .= ",vi.gnrlVcncy vcncy";
							if ($this->api_com_util->isSetNotNull($params, 'nmbrRms')){
								$this->db->where("vi.gnrlVcncy >=", $params['nmbrRms']);
							}
						}
					}
				}
				//20141211 仕様変更のため追加　iwamoto
				if ($api_mode == Api_const::A008 || $api_mode == Api_const::A006){
					//20141211 roomType追加　iwamoto
					if ($params['roomType']!='' && array_key_exists('roomType',$params)){
						$this->db->where('rti.room_type_code',$params['roomType']); 
					}
					if ($params['mode'] == '1'){					// 目的地のキーワードからの検索
						if ($this->api_com_util->isSetNotNull($params, 'kywrd')){
							$this->db->join("keyword_information ki", "hi.unq_id=ki.htl_infrmtn_unq_id");      // キーワード情報
							//$this->db->like('ki.kywrd_name',$params['kywrd']);
							//$this->db->or_like('ki.rt_name',$params['kywrd']);
							//$this->db->or_like('hi.htl_name',$params['kywrd']);

							$where_like = '';
							$where_like="concat(ki.kywrd_name,ki.rt_name,hi.htl_name) like "."'%".$params['kywrd']."%'";
							$this->db->where($where_like);
							$this->db->distinct();
						}
					} elseif ($params['mode'] == '2'){				// エリアからの検索
						$this->db->join('country_master cm', 'hi.lngg=cm.lngg AND hi.cntry_code=cm.cntry_code'); // 国マスタ
						$this->db->join('area_master am', 'hi.lngg=am.lngg AND hi.cntry_code=am.cntry_code AND hi.area_code=am.area_code'); // エリア情報
						$this->db->join('state_master sm', 'hi.lngg=sm.lngg AND hi.cntry_code=sm.cntry_code AND hi.area_code=sm.area_code AND hi.stt_code=sm.state_code'); // 都道府県マスタ
						$this->db->join('city_master ctm', 'hi.lngg=ctm.lngg AND hi.cntry_code=ctm.cntry_code AND hi.area_code=ctm.area_code AND hi.stt_code=ctm.state_code AND hi.city_code=ctm.city_code','left'); // 都道府県マスタ
						$this->db->where('cm.cntry_code',$params['cntryCode']);
						$this->db->where('am.area_code',$params['areaCode']);
						$this->db->where('sm.state_code',$params['sttCode']);
						$this->db->where('ctm.city_code',$params['cityCode']);
					} elseif ($params['mode'] == '3'){				// 現在地からの検索
						$this->db->where_in("hi.unq_id", $htlInfrmtnUnqIds);
					}
					//20141218 multilingual_room_type_nameテーブル追加対応
					if ($api_mode == Api_const::A006){
						$sql_select .= ",mrtn.room_type_name as room_type_name_lngg";        // room_type_name
						$sql_select .= ",mrtn.room_clss_id";        // room_clss_id
						$this->db->join("multilingual_room_type_name mrtn", "rti.room_clss_id=mrtn.room_clss_id" ,'left');  // multilingual_room_type_name
						$this->db->where("mrtn.lngg", $params['lngg']);
					}
				}
				break;
			case  Api_const::A010:	// search_room_type_details_api
			case  Api_const::A011:	// search_room_type_price_api
			case  Api_const::A012:	// check_booking_api
			case  Api_const::A013:	// register_reservation_api //20141007 追加　iwamoto
			case  Api_const::A017:	// change_reservation_api
				$this->db->where("hi.htl_code", $params['htlCode']);
				$this->db->where('rti.room_type_code',$params['roomType']);        // 部屋タイプ(最大宿泊人数の条件不要)
				$this->db->where('rti.plan_code',$params['planCode']);        // プランコード
				$this->db->where('rci.plan_code',$params['planCode']);        // プランコード
				break;
			case  Api_const::A020:	// search_favorite_hotel_api
			case  Api_const::A030:	// search_browsing_history_api
				$this->db->where("hi.htl_code", $params['htlCode']);
				$this->db->where("rti.mxmm_occpncy", $params['nmbrPpl']);          // 最大宿泊人数(Single固定)
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
			// 予約可能期間
			$rsrv_mnth = $this->api_util->getRsrvMnth($params);
			$recList = array();
			$oldRow = null;
			$tmps = array();
			$tmpsAdd = false;
			$dstnc = '';
			$list = $query->result_array();
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
				// Key Break [htl_code , room_type_code, occpncy]
				if ($eof === true || (count($tmps) != 0 && ($oldRow['htl_code'] != $row['htl_code'] || $oldRow_plan != $row_plan || $oldRow['occpncy'] != $row['occpncy']))) {
					$add = true;
					// 個別条件
					// 共通条件
					if ($add == true){
						// 到着日予約可能チェック
						if ($this->api_util->chkRsrvRange($oldRow['open_date'], $params['chcknDate'], $oldRow['time_zone'], $rsrv_mnth, $params['nmbrNghts'], $tmps)) {
							$recList = array_merge($recList, $tmps);
							if ($search_mode == Api_const::HOTEL_AND_VACANCY){
								$flag = 1;
							}
						}
					}
					$tmps = array();
					$tmpsAdd = false;
				}

				if ($eof == true){
					break;
				}
				//20141211 仕様変更のため追加　iwamoto
				if ($api_mode == Api_const::A008 || $api_mode == Api_const::A006){
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
		//-----------
		// 戻り値を返す
		//-----------
		if ($search_mode == Api_const::HOTEL_AND_VACANCY && $flag == 0) {
			if ($api_mode == Api_const::A006 || $api_mode == Api_const::A008 || $api_mode == Api_const::A009){
				$result['errCode'] = Api_const::BAPI1007;
			}
		}

		log_debug($this->CLASS_NAME.'->'.$FUNC, '[errCode]'.$result['errCode']);
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[mssg]'.$result['mssg']);
		return $result;
	}
}
