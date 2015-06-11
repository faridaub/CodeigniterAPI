<?php
/**
 * スマホAPIサービス
 * ホテル情報テーブルのアクセスクラス
 *
 * @package     CodeIgniter
 * @subpackage  Model
 * @category    Model
 * @author      TOCOM
 * @url         http://xxxx.co.jp
 *
 * Copyright © 2014 TOYOKO INN IT SHUUKYAKU SOLUTION CO.,LTD All Rights Reserved.
 *
 */

class hotel_info_m extends CI_Model
{

    var $CLASS_NAME = 'hotel_info_m';                   // Class name
    var $TABLE_NAME = 'hotel_info';                     // Table name
    var $SELECT_COLUMN =                               // Select column names
    'unq_id,
    vrsn_nmbr,
    entry_date,
    entry_time,
    updt_date,
    updt_time,
    applctn_vrsn_nmbr,
    htl_code,
    lngg,
    time_zone,
    htl_name,
    addrss,
    prkng_infmtn,
    bus_infmtn,
    pikp_infmtn,
    rntcr_infmtn,
    chckn_time,
    chckt_time,
    brkfst_time,
    brrrfr_infmtn,
    iso_infmtn,
    phn_nmbr,
    img_url,
    cntry_code,
    area_code,
    stt_code,
	city_code,
    crrncy_name,
    crrncy_sign,
    lngtd,
    lttd,
    open_date,
    cnclltn_policy,
    trms_cndtns,
    rate_dsply_flag,
    nshw_crdt_sttmnt_flag';

    function __construct()
    {
        // Model クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /*
     * ホテル情報テーブルにレコードを１件追加する。
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
     * ホテル情報テーブルの指定レコードを更新する。
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
        // hotel_infoの更新処理
        //-------------------------
        if($result['errCode'] == true){
            $rec['vrsn_nmbr'] = $rec['vrsn_nmbr']+1;   // バージョン番号をカウントUP

            // hotel_infoテーブルにupdateする。
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
     * ホテル情報テーブルの指定レコードを削除する。
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
     * ホテル情報テーブルからデータ取得。
     *
     * @param   var     $applctn_vrsn_nmbr                                : 検索キー（アプリケーションバージョン）
     * @param   var      $htl_code                                : 検索キー（ ホテルコード）
     * @param   var      $lngg                                : 検索キー（ 国コード）
     * @return  array   $result                                  : 戻り値
     *                      ['err_code']                         : エラーコード(true=正常終了/ false=異常終了)
     *                      ['mssg']                             : エラーメッセージ
     *                      ['row']                              : 検索で取得したホテル情報テーブルのレコード
     *
     */
    function select($applctn_vrsn_nmbr, $htl_code, $lngg)
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
        $this->db->where('applctn_vrsn_nmbr',$applctn_vrsn_nmbr);
        $this->db->where('htl_code',$htl_code);
        $this->db->where('lngg',$lngg);
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
                $rec['applctn_vrsn_nmbr']                            = $row->applctn_vrsn_nmbr;
                $rec['htl_code']                            = $row->htl_code;
                $rec['lngg']                            = $row->lngg;
                $rec['time_zone']                            = $row->time_zone;
                $rec['htl_name']                            = $row->htl_name;
                $rec['addrss']                            = $row->addrss;
                $rec['prkng_infmtn']                            = $row->prkng_infmtn;
                $rec['bus_infmtn']                            = $row->bus_infmtn;
                $rec['pikp_infmtn']                            = $row->pikp_infmtn;
                $rec['rntcr_infmtn']                            = $row->rntcr_infmtn;
                $rec['chckn_time']                            = $row->chckn_time;
                $rec['chckt_time']                            = $row->chckt_time;
                $rec['brkfst_time']                            = $row->brkfst_time;
                $rec['brrrfr_infmtn']                            = $row->brrrfr_infmtn;
                $rec['iso_infmtn']                            = $row->iso_infmtn;
                $rec['phn_nmbr']                            = $row->phn_nmbr;
                $rec['img_url']                            = $row->img_url;
                $rec['cntry_code']                            = $row->cntry_code;
                $rec['area_code']                            = $row->area_code;
                $rec['stt_code']                            = $row->stt_code;
                $rec['crrncy_name']                            = $row->crrncy_name;
                $rec['crrncy_sign']                            = $row->crrncy_sign;
                $rec['lngtd']                            = $row->lngtd;
                $rec['lttd']                            = $row->lttd;
                $rec['open_date']                            = $row->open_date;
                $rec['cnclltn_policy']                            = $row->cnclltn_policy;
                $rec['trms_cndtns']                            = $row->trms_cndtns;
                $rec['rate_dsply_flag']                            = $row->rate_dsply_flag;
                $rec['nshw_crdt_sttmnt_flag']                            = $row->nshw_crdt_sttmnt_flag;
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
     * ホテル情報テーブルのレコードリストを取得する
     *
     * @param   array   $params : パラメータ
     * @return  array   $result :  戻り値
     *              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
     *              ['mssg']    : エラーメッセージ
     *              ['list']    : 検索で取得したホテル情報テーブルのリスト
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
	* @param   array   $params   : パラメータ
	* @param   string  $api_mode : APIモード
	* @return  array   $result   :  戻り値
	*              ['errCode']   : エラーコード(true=正常終了/ false=異常終了)
	*              ['mssg']      : エラーメッセージ
	*              ['recCnt']    : 検索で取得したホテル情報テーブルの件数
	*              ['recList']   : 検索で取得したホテル情報テーブルのリスト
	*
	*/
	function selectJoinListCond($params, $api_mode)
	{
		//-------
		// 初期値
		//-------
		$FUNC               = 'selectJoinListCond';
		$result['errCode']  = true;
		$result['mssg']     = '';
		$result['recCnt']   = 0;
		$result['recList']  = '';
		$result['recKi']  = '';
		$result['recCi']  = '';
		$result['recEn']  = '';

		//--------------
		// SELECT文の実行
		//--------------
		$sql_select = "";
		$sql_select .= " hi.unq_id";                   // ユニークID
		$sql_select .= ",hi.htl_code";                 // ホテルコード
		$sql_select .= ",hi.htl_name";                 // ホテル名
		$sql_select .= ",hi.addrss";                   // 住所
		$sql_select .= ",hi.prkng_infmtn";             // 駐車場情報
		$sql_select .= ",hi.bus_infmtn";               // バス情報
		$sql_select .= ",hi.pikp_infmtn";              // 送迎情報
		$sql_select .= ",hi.rntcr_infmtn";             // レンタカー情報
		$sql_select .= ",hi.chckn_time";               // チェックイン時間
		$sql_select .= ",hi.chckt_time";               // チェックアウト時間
		$sql_select .= ",hi.brkfst_time";              // 朝食時間
		$sql_select .= ",hi.brrrfr_infmtn";            // バリアフリー情報
		$sql_select .= ",hi.iso_infmtn";              // ISO情報
		$sql_select .= ",hi.phn_nmbr";                 // 電話番号
		$sql_select .= ",hi.img_url";                  // 画像URL（ホテル情報）
		$sql_select .= ",hi.cntry_code";               // 国コード
		$sql_select .= ",hi.area_code";                // エリアコード
		$sql_select .= ",hi.stt_code";                 // 都道府県コード
		$sql_select .= ",hi.city_code";                // 都市コード
		$sql_select .= ",hi.crrncy_name";              // 通貨名
		$sql_select .= ",hi.crrncy_sign";              // 通貨記号
		$sql_select .= ",hi.lngtd";                    // ホテルの経度
		$sql_select .= ",hi.lttd";                     // ホテルの緯度
		$sql_select .= ",hi.nshw_crdt_sttmnt_flag";    // ノーショウクレジット決済フラグ
		$this->db->select($sql_select);

		// Table Join
		$this->db->from("hotel_info hi");                                                  // ホテル情報
		$this->db->join("hotel_state hs", "hi.htl_code=hs.htl_code");                      // ホテル状態

		// Where
		$this->db->where("hi.lngg", $params['lngg']);
		$this->db->where("hs.api_key", $params['apikey']);
		$this->db->where("hi.applctn_vrsn_nmbr", $params['applctnVrsnNmbr']);
		$this->db->where("hi.htl_code", $params['htlCode']);
		$stats = array(Api_const::HTL_STT_HANBAI);
		if ($params['apikey_information_mode'] == Api_const::SYORI_MODE_TEST) {
			// API情報の処理モードが「テスト」の場合
			$stats[] = Api_const::HTL_STT_TEST;
		}
		$this->db->where_in("hs.state", $stats);

		// ホテルコード
		$this->db->order_by("hi.htl_code");

		$query = $this->db->get();
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[SQL]'.$this->db->last_query());

		//-----------------
		// SQL実行結果の取得
		//-----------------
		$params = null;
		$params['htl_infrmtn_unq_id'] = null;
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$recList[]	= $row;
				$params['htl_infrmtn_unq_id'] = $row['unq_id'];
			}
			$result['recList'] = $recList;
			$result['recCnt'] = $query->num_rows();
		}
		// クレジット情報
		$result['recCi'] = $this->credit_infomation_m->selectListCond($params);

		// 設備情報
		$result['recEn'] = $this->equipment_information_m->selectListCond($params);

		// キーワード情報取得
		$params['kywrd_type >='] = Api_const::KEYWORD_TYPE_EKI;
		$params['kywrd_type <='] = Api_const::KEYWORD_TYPE_BUS;
		$result['recKi'] = $this->keyword_information_m->selectListCond($params);
		//-----------
		// 戻り値を返す
		//-----------
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[errCode]'.$result['errCode']);
		log_debug($this->CLASS_NAME.'->'.$FUNC, '[mssg]'.$result['mssg']);
		return $result;
	}
}
