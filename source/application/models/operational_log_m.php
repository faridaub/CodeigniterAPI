<?php
/**
 * スマホAPIサービス
 * 操作ログテーブルのアクセスクラス
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

class operational_log_m extends CI_Model
{

    var $CLASS_NAME = 'operational_log_m';                   // Class name
    var $TABLE_NAME = 'operational_log';                     // Table name
    var $SELECT_COLUMN =                               // Select column names
    'unq_id,
    entry_date,
    entry_time,
    trmnl_type,
    mdl,
    udid,
    imei,
    rservs_prsn_uid,
    mmbrshp_nmbr,
    applctn_vrsn_nmbr,
    api_name,
    htl_code,
    prmtr_vls_01,
    prmtr_vls_02,
    prmtr_vls_03,
    prmtr_vls_04,
    prmtr_vls_05,
    prmtr_vls_06,
    prmtr_vls_07,
    prmtr_vls_08,
    prmtr_vls_09,
    prmtr_vls_10,
    prmtr_vls_11,
    prmtr_vls_12,
    prmtr_vls_13,
    prmtr_vls_14,
    prmtr_vls_15,
    prmtr_vls_16,
    prmtr_vls_17,
    prmtr_vls_18,
    prmtr_vls_19,
    prmtr_vls_20,
    prmtr_vls_21,
    prmtr_vls_22,
    prmtr_vls_23,
    prmtr_vls_24,
    prmtr_vls_25,
    prmtr_vls_26,
    prmtr_vls_27,
    prmtr_vls_28,
    prmtr_vls_29,
    prmtr_vls_30,
    prmtr_vls_31,
    prmtr_vls_32,
    prmtr_vls_33,
    prmtr_vls_34,
    prmtr_vls_35,
    prmtr_vls_36,
    prmtr_vls_37,
    prmtr_vls_38,
    prmtr_vls_39,
    prmtr_vls_40,
    prmtr_vls_41,
    prmtr_vls_42,
    prmtr_vls_43,
    prmtr_vls_44,
    prmtr_vls_45,
    prmtr_vls_46,
    prmtr_vls_47,
    prmtr_vls_48,
    prmtr_vls_49,
    prmtr_vls_50,
    prmtr_vls_51,
    prmtr_vls_52,
    prmtr_vls_53,
    prmtr_vls_54,
    prmtr_vls_55,
    prmtr_vls_56,
    prmtr_vls_57,
    prmtr_vls_58,
    prmtr_vls_59,
    prmtr_vls_60,
    prmtr_vls_61,
    prmtr_vls_62,
    prmtr_vls_63,
    prmtr_vls_64,
    prmtr_vls_65,
    prmtr_vls_66,
    prmtr_vls_67,
    prmtr_vls_68,
    prmtr_vls_69,
    prmtr_vls_70,
    prmtr_vls_71,
    prmtr_vls_72,
    prmtr_vls_73,
    prmtr_vls_74,
    prmtr_vls_75,
    prmtr_vls_76,
    prmtr_vls_77,
    prmtr_vls_78,
    prmtr_vls_79,
    prmtr_vls_80,
    prmtr_vls_81,
    prmtr_vls_82,
    prmtr_vls_83,
    prmtr_vls_84,
    prmtr_vls_85,
    prmtr_vls_86,
    prmtr_vls_87,
    prmtr_vls_88,
    prmtr_vls_89,
    prmtr_vls_90,
    prmtr_vls_91,
    prmtr_vls_92,
    prmtr_vls_93,
    prmtr_vls_94,
    prmtr_vls_95,
    prmtr_vls_96,
    prmtr_vls_97,
    prmtr_vls_98,
    prmtr_vls_99,
    prmtr_vls_100,
    prmtr_vls_101,
    prmtr_vls_102,
    prmtr_vls_103,
    prmtr_vls_104,
    prmtr_vls_105,
    prmtr_vls_106,
    prmtr_vls_107,
    prmtr_vls_108,
    prmtr_vls_109,
    prmtr_vls_110,
    prmtr_vls_111,
    prmtr_vls_112,
    prmtr_vls_113,
    prmtr_vls_114,
    prmtr_vls_115,
    prmtr_vls_116,
    prmtr_vls_117,
    prmtr_vls_118,
    prmtr_vls_119,
    prmtr_vls_120';

    function __construct()
    {
        // Model クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /*
     * 操作ログテーブルにレコードを１件追加する。
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
     * 操作ログテーブルの指定レコードを更新する。
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
        // operational_logの更新処理
        //-------------------------
        if($result['errCode'] == true){

            // operational_logテーブルにupdateする。
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
     * 操作ログテーブルの指定レコードを削除する。
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
     * 操作ログテーブルからデータ取得。
     *
     * @param   var     $unq_id                                : 検索キー（ユニークID）
     * @return  array   $result                                  : 戻り値
     *                      ['err_code']                         : エラーコード(true=正常終了/ false=異常終了)
     *                      ['mssg']                             : エラーメッセージ
     *                      ['row']                              : 検索で取得した操作ログテーブルのレコード
     *
     */
    function select($unq_id)
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
        $this->db->where('unq_id',$unq_id);
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
                $rec['entry_date']                            = $row->entry_date;
                $rec['entry_time']                            = $row->entry_time;
                $rec['trmnl_type']                            = $row->trmnl_type;
                $rec['mdl']                            = $row->mdl;
                $rec['udid']                            = $row->udid;
                $rec['imei']                            = $row->imei;
                $rec['rservs_prsn_uid']                            = $row->rservs_prsn_uid;
                $rec['mmbrshp_nmbr']                            = $row->mmbrshp_nmbr;
                $rec['applctn_vrsn_nmbr']                            = $row->applctn_vrsn_nmbr;
                $rec['api_name']                            = $row->api_name;
                $rec['htl_code']                            = $row->htl_code;
                $rec['prmtr_vls_01']                            = $row->prmtr_vls_01;
                $rec['prmtr_vls_02']                            = $row->prmtr_vls_02;
                $rec['prmtr_vls_03']                            = $row->prmtr_vls_03;
                $rec['prmtr_vls_04']                            = $row->prmtr_vls_04;
                $rec['prmtr_vls_05']                            = $row->prmtr_vls_05;
                $rec['prmtr_vls_06']                            = $row->prmtr_vls_06;
                $rec['prmtr_vls_07']                            = $row->prmtr_vls_07;
                $rec['prmtr_vls_08']                            = $row->prmtr_vls_08;
                $rec['prmtr_vls_09']                            = $row->prmtr_vls_09;
                $rec['prmtr_vls_10']                            = $row->prmtr_vls_10;
                $rec['prmtr_vls_11']                            = $row->prmtr_vls_11;
                $rec['prmtr_vls_12']                            = $row->prmtr_vls_12;
                $rec['prmtr_vls_13']                            = $row->prmtr_vls_13;
                $rec['prmtr_vls_14']                            = $row->prmtr_vls_14;
                $rec['prmtr_vls_15']                            = $row->prmtr_vls_15;
                $rec['prmtr_vls_16']                            = $row->prmtr_vls_16;
                $rec['prmtr_vls_17']                            = $row->prmtr_vls_17;
                $rec['prmtr_vls_18']                            = $row->prmtr_vls_18;
                $rec['prmtr_vls_19']                            = $row->prmtr_vls_19;
                $rec['prmtr_vls_20']                            = $row->prmtr_vls_20;
                $rec['prmtr_vls_21']                            = $row->prmtr_vls_21;
                $rec['prmtr_vls_22']                            = $row->prmtr_vls_22;
                $rec['prmtr_vls_23']                            = $row->prmtr_vls_23;
                $rec['prmtr_vls_24']                            = $row->prmtr_vls_24;
                $rec['prmtr_vls_25']                            = $row->prmtr_vls_25;
                $rec['prmtr_vls_26']                            = $row->prmtr_vls_26;
                $rec['prmtr_vls_27']                            = $row->prmtr_vls_27;
                $rec['prmtr_vls_28']                            = $row->prmtr_vls_28;
                $rec['prmtr_vls_29']                            = $row->prmtr_vls_29;
                $rec['prmtr_vls_30']                            = $row->prmtr_vls_30;
                $rec['prmtr_vls_31']                            = $row->prmtr_vls_31;
                $rec['prmtr_vls_32']                            = $row->prmtr_vls_32;
                $rec['prmtr_vls_33']                            = $row->prmtr_vls_33;
                $rec['prmtr_vls_34']                            = $row->prmtr_vls_34;
                $rec['prmtr_vls_35']                            = $row->prmtr_vls_35;
                $rec['prmtr_vls_36']                            = $row->prmtr_vls_36;
                $rec['prmtr_vls_37']                            = $row->prmtr_vls_37;
                $rec['prmtr_vls_38']                            = $row->prmtr_vls_38;
                $rec['prmtr_vls_39']                            = $row->prmtr_vls_39;
                $rec['prmtr_vls_40']                            = $row->prmtr_vls_40;
                $rec['prmtr_vls_41']                            = $row->prmtr_vls_41;
                $rec['prmtr_vls_42']                            = $row->prmtr_vls_42;
                $rec['prmtr_vls_43']                            = $row->prmtr_vls_43;
                $rec['prmtr_vls_44']                            = $row->prmtr_vls_44;
                $rec['prmtr_vls_45']                            = $row->prmtr_vls_45;
                $rec['prmtr_vls_46']                            = $row->prmtr_vls_46;
                $rec['prmtr_vls_47']                            = $row->prmtr_vls_47;
                $rec['prmtr_vls_48']                            = $row->prmtr_vls_48;
                $rec['prmtr_vls_49']                            = $row->prmtr_vls_49;
                $rec['prmtr_vls_50']                            = $row->prmtr_vls_50;
                $rec['prmtr_vls_51']                            = $row->prmtr_vls_51;
                $rec['prmtr_vls_52']                            = $row->prmtr_vls_52;
                $rec['prmtr_vls_53']                            = $row->prmtr_vls_53;
                $rec['prmtr_vls_54']                            = $row->prmtr_vls_54;
                $rec['prmtr_vls_55']                            = $row->prmtr_vls_55;
                $rec['prmtr_vls_56']                            = $row->prmtr_vls_56;
                $rec['prmtr_vls_57']                            = $row->prmtr_vls_57;
                $rec['prmtr_vls_58']                            = $row->prmtr_vls_58;
                $rec['prmtr_vls_59']                            = $row->prmtr_vls_59;
                $rec['prmtr_vls_60']                            = $row->prmtr_vls_60;
                $rec['prmtr_vls_61']                            = $row->prmtr_vls_61;
                $rec['prmtr_vls_62']                            = $row->prmtr_vls_62;
                $rec['prmtr_vls_63']                            = $row->prmtr_vls_63;
                $rec['prmtr_vls_64']                            = $row->prmtr_vls_64;
                $rec['prmtr_vls_65']                            = $row->prmtr_vls_65;
                $rec['prmtr_vls_66']                            = $row->prmtr_vls_66;
                $rec['prmtr_vls_67']                            = $row->prmtr_vls_67;
                $rec['prmtr_vls_68']                            = $row->prmtr_vls_68;
                $rec['prmtr_vls_69']                            = $row->prmtr_vls_69;
                $rec['prmtr_vls_70']                            = $row->prmtr_vls_70;
                $rec['prmtr_vls_71']                            = $row->prmtr_vls_71;
                $rec['prmtr_vls_72']                            = $row->prmtr_vls_72;
                $rec['prmtr_vls_73']                            = $row->prmtr_vls_73;
                $rec['prmtr_vls_74']                            = $row->prmtr_vls_74;
                $rec['prmtr_vls_75']                            = $row->prmtr_vls_75;
                $rec['prmtr_vls_76']                            = $row->prmtr_vls_76;
                $rec['prmtr_vls_77']                            = $row->prmtr_vls_77;
                $rec['prmtr_vls_78']                            = $row->prmtr_vls_78;
                $rec['prmtr_vls_79']                            = $row->prmtr_vls_79;
                $rec['prmtr_vls_80']                            = $row->prmtr_vls_80;
                $rec['prmtr_vls_81']                            = $row->prmtr_vls_81;
                $rec['prmtr_vls_82']                            = $row->prmtr_vls_82;
                $rec['prmtr_vls_83']                            = $row->prmtr_vls_83;
                $rec['prmtr_vls_84']                            = $row->prmtr_vls_84;
                $rec['prmtr_vls_85']                            = $row->prmtr_vls_85;
                $rec['prmtr_vls_86']                            = $row->prmtr_vls_86;
                $rec['prmtr_vls_87']                            = $row->prmtr_vls_87;
                $rec['prmtr_vls_88']                            = $row->prmtr_vls_88;
                $rec['prmtr_vls_89']                            = $row->prmtr_vls_89;
                $rec['prmtr_vls_90']                            = $row->prmtr_vls_90;
                $rec['prmtr_vls_91']                            = $row->prmtr_vls_91;
                $rec['prmtr_vls_92']                            = $row->prmtr_vls_92;
                $rec['prmtr_vls_93']                            = $row->prmtr_vls_93;
                $rec['prmtr_vls_94']                            = $row->prmtr_vls_94;
                $rec['prmtr_vls_95']                            = $row->prmtr_vls_95;
                $rec['prmtr_vls_96']                            = $row->prmtr_vls_96;
                $rec['prmtr_vls_97']                            = $row->prmtr_vls_97;
                $rec['prmtr_vls_98']                            = $row->prmtr_vls_98;
                $rec['prmtr_vls_99']                            = $row->prmtr_vls_99;
                $rec['prmtr_vls_100']                            = $row->prmtr_vls_100;
                $rec['prmtr_vls_101']                            = $row->prmtr_vls_101;
                $rec['prmtr_vls_102']                            = $row->prmtr_vls_102;
                $rec['prmtr_vls_103']                            = $row->prmtr_vls_103;
                $rec['prmtr_vls_104']                            = $row->prmtr_vls_104;
                $rec['prmtr_vls_105']                            = $row->prmtr_vls_105;
                $rec['prmtr_vls_106']                            = $row->prmtr_vls_106;
                $rec['prmtr_vls_107']                            = $row->prmtr_vls_107;
                $rec['prmtr_vls_108']                            = $row->prmtr_vls_108;
                $rec['prmtr_vls_109']                            = $row->prmtr_vls_109;
                $rec['prmtr_vls_110']                            = $row->prmtr_vls_110;
                $rec['prmtr_vls_111']                            = $row->prmtr_vls_111;
                $rec['prmtr_vls_112']                            = $row->prmtr_vls_112;
                $rec['prmtr_vls_113']                            = $row->prmtr_vls_113;
                $rec['prmtr_vls_114']                            = $row->prmtr_vls_114;
                $rec['prmtr_vls_115']                            = $row->prmtr_vls_115;
                $rec['prmtr_vls_116']                            = $row->prmtr_vls_116;
                $rec['prmtr_vls_117']                            = $row->prmtr_vls_117;
                $rec['prmtr_vls_118']                            = $row->prmtr_vls_118;
                $rec['prmtr_vls_119']                            = $row->prmtr_vls_119;
                $rec['prmtr_vls_120']                            = $row->prmtr_vls_120;
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
     * 操作ログテーブルのレコードリストを取得する
     *
     * @param   array   $params : パラメータ
     * @return  array   $result :  戻り値
     *              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
     *              ['mssg']    : エラーメッセージ
     *              ['list']    : 検索で取得した操作ログテーブルのリスト
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
	 * 操作ログレコード配列初期化
	*
	* @param   array   $params : パラメータ
	* @return  array   $result :  戻り値
	*              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
	*              ['mssg']    : エラーメッセージ
	*              ['list']    : 検索で取得した操作ログテーブルのリスト
	*
	*/
	function initialRec($params){
		// 操作ログテーブル
		if ($params['osType'] == 'I') {
			$osType = '1'; // IOS
		} else {
			$osType = '2'; // Android
		}
		$result['trmnl_type']              = $osType;                    // 端末種別
		$result['mdl']                     = $params['mdl'];             // モデル
		$result['udid']                    = $params['dvcTkn'];          // iOS固体識別子
		$result['imei']                    = $params['rgstrtnId'];       // Andorid系固体識別
		$result['rservs_prsn_uid']         = null;                       // 予約者情報UID
		$result['mmbrshp_nmbr']            = null;                       // 会員番号
		$result['applctn_vrsn_nmbr']       = $params['applctnVrsnNmbr']; // アプリバージョン
		$result['api_name']                = null;                       // API名
		$result['htl_code']                = null;                       // ホテルコード
		$result['prmtr_vls_01']            = null;                       // パラメータ1
		$result['prmtr_vls_02']            = null;                       // パラメータ2
		$result['prmtr_vls_03']            = null;                       // パラメータ3
		$result['prmtr_vls_04']            = null;                       // パラメータ4
		$result['prmtr_vls_05']            = null;                       // パラメータ5
		$result['prmtr_vls_06']            = null;                       // パラメータ6
		$result['prmtr_vls_07']            = null;                       // パラメータ7
		$result['prmtr_vls_08']            = null;                       // パラメータ8
		$result['prmtr_vls_09']            = null;                       // パラメータ9
		$result['prmtr_vls_10']            = null;                       // パラメータ10
		$result['prmtr_vls_11']            = null;                       // パラメータ11
		$result['prmtr_vls_12']            = null;                       // パラメータ12
		$result['prmtr_vls_13']            = null;                       // パラメータ13
		$result['prmtr_vls_14']            = null;                       // パラメータ14
		$result['prmtr_vls_15']            = null;                       // パラメータ15
		$result['prmtr_vls_16']            = null;                       // パラメータ16
		$result['prmtr_vls_17']            = null;                       // パラメータ17
		$result['prmtr_vls_18']            = null;                       // パラメータ18
		$result['prmtr_vls_19']            = null;                       // パラメータ19
		$result['prmtr_vls_20']            = null;                       // パラメータ20
		$result['prmtr_vls_21']            = null;                       // パラメータ21
		$result['prmtr_vls_22']            = null;                       // パラメータ22
		$result['prmtr_vls_23']            = null;                       // パラメータ23
		$result['prmtr_vls_24']            = null;                       // パラメータ24
		$result['prmtr_vls_25']            = null;                       // パラメータ25
		$result['prmtr_vls_26']            = null;                       // パラメータ26
		$result['prmtr_vls_27']            = null;                       // パラメータ27
		$result['prmtr_vls_28']            = null;                       // パラメータ28
		$result['prmtr_vls_29']            = null;                       // パラメータ29
		$result['prmtr_vls_30']            = null;                       // パラメータ30
		$result['prmtr_vls_31']            = null;                       // パラメータ31
		$result['prmtr_vls_32']            = null;                       // パラメータ32
		$result['prmtr_vls_33']            = null;                       // パラメータ33
		$result['prmtr_vls_34']            = null;                       // パラメータ34
		$result['prmtr_vls_35']            = null;                       // パラメータ35
		$result['prmtr_vls_36']            = null;                       // パラメータ36
		$result['prmtr_vls_37']            = null;                       // パラメータ37
		$result['prmtr_vls_38']            = null;                       // パラメータ38
		$result['prmtr_vls_39']            = null;                       // パラメータ39
		$result['prmtr_vls_40']            = null;                       // パラメータ40
		$result['prmtr_vls_41']            = null;                       // パラメータ41
		$result['prmtr_vls_42']            = null;                       // パラメータ42
		$result['prmtr_vls_43']            = null;                       // パラメータ43
		$result['prmtr_vls_44']            = null;                       // パラメータ44
		$result['prmtr_vls_45']            = null;                       // パラメータ45
		$result['prmtr_vls_46']            = null;                       // パラメータ46
		$result['prmtr_vls_47']            = null;                       // パラメータ47
		$result['prmtr_vls_48']            = null;                       // パラメータ48
		$result['prmtr_vls_49']            = null;                       // パラメータ49
		$result['prmtr_vls_50']            = null;                       // パラメータ50
		$result['prmtr_vls_51']            = null;                       // パラメータ51
		$result['prmtr_vls_52']            = null;                       // パラメータ52
		$result['prmtr_vls_53']            = null;                       // パラメータ53
		$result['prmtr_vls_54']            = null;                       // パラメータ54
		$result['prmtr_vls_55']            = null;                       // パラメータ55
		$result['prmtr_vls_56']            = null;                       // パラメータ56
		$result['prmtr_vls_57']            = null;                       // パラメータ57
		$result['prmtr_vls_58']            = null;                       // パラメータ58
		$result['prmtr_vls_59']            = null;                       // パラメータ59
		$result['prmtr_vls_60']            = null;                       // パラメータ60
		$result['prmtr_vls_61']            = null;                       // パラメータ61
		$result['prmtr_vls_62']            = null;                       // パラメータ62
		$result['prmtr_vls_63']            = null;                       // パラメータ63
		$result['prmtr_vls_64']            = null;                       // パラメータ64
		$result['prmtr_vls_65']            = null;                       // パラメータ65
		$result['prmtr_vls_66']            = null;                       // パラメータ66
		$result['prmtr_vls_67']            = null;                       // パラメータ67
		$result['prmtr_vls_68']            = null;                       // パラメータ68
		$result['prmtr_vls_69']            = null;                       // パラメータ69
		$result['prmtr_vls_70']            = null;                       // パラメータ70
		$result['prmtr_vls_71']            = null;                       // パラメータ71
		$result['prmtr_vls_72']            = null;                       // パラメータ72
		$result['prmtr_vls_73']            = null;                       // パラメータ73
		$result['prmtr_vls_74']            = null;                       // パラメータ74
		$result['prmtr_vls_75']            = null;                       // パラメータ75
		$result['prmtr_vls_76']            = null;                       // パラメータ76
		$result['prmtr_vls_77']            = null;                       // パラメータ77
		$result['prmtr_vls_78']            = null;                       // パラメータ78
		$result['prmtr_vls_79']            = null;                       // パラメータ79
		$result['prmtr_vls_80']            = null;                       // パラメータ80
		$result['prmtr_vls_81']            = null;                       // パラメータ81
		$result['prmtr_vls_82']            = null;                       // パラメータ82
		$result['prmtr_vls_83']            = null;                       // パラメータ83
		$result['prmtr_vls_84']            = null;                       // パラメータ84
		$result['prmtr_vls_85']            = null;                       // パラメータ85
		$result['prmtr_vls_86']            = null;                       // パラメータ86
		$result['prmtr_vls_87']            = null;                       // パラメータ87
		$result['prmtr_vls_88']            = null;                       // パラメータ88
		$result['prmtr_vls_89']            = null;                       // パラメータ89
		$result['prmtr_vls_90']            = null;                       // パラメータ90
		$result['prmtr_vls_91']            = null;                       // パラメータ91
		$result['prmtr_vls_92']            = null;                       // パラメータ92
		$result['prmtr_vls_93']            = null;                       // パラメータ93
		$result['prmtr_vls_94']            = null;                       // パラメータ94
		$result['prmtr_vls_95']            = null;                       // パラメータ95
		$result['prmtr_vls_96']            = null;                       // パラメータ96
		$result['prmtr_vls_97']            = null;                       // パラメータ97
		$result['prmtr_vls_98']            = null;                       // パラメータ98
		$result['prmtr_vls_99']            = null;                       // パラメータ99
		$result['prmtr_vls_100']           = null;                       // パラメータ100
		$result['prmtr_vls_101']           = null;                       // パラメータ101
		$result['prmtr_vls_102']           = null;                       // パラメータ102
		$result['prmtr_vls_103']           = null;                       // パラメータ103
		$result['prmtr_vls_104']           = null;                       // パラメータ104
		$result['prmtr_vls_105']           = null;                       // パラメータ105
		$result['prmtr_vls_106']           = null;                       // パラメータ106
		$result['prmtr_vls_107']           = null;                       // パラメータ107
		$result['prmtr_vls_108']           = null;                       // パラメータ108
		$result['prmtr_vls_109']           = null;                       // パラメータ109
		$result['prmtr_vls_110']           = null;                       // パラメータ110
		$result['prmtr_vls_111']           = null;                       // パラメータ111
		$result['prmtr_vls_112']           = null;                       // パラメータ112
		$result['prmtr_vls_113']           = null;                       // パラメータ113
		$result['prmtr_vls_114']           = null;                       // パラメータ114
		$result['prmtr_vls_115']           = null;                       // パラメータ115
		$result['prmtr_vls_116']           = null;                       // パラメータ116
		$result['prmtr_vls_117']           = null;                       // パラメータ117
		$result['prmtr_vls_118']           = null;                       // パラメータ118
		$result['prmtr_vls_119']           = null;                       // パラメータ119
		$result['prmtr_vls_120']           = null;                       // パラメータ120

		//-----------
		// 戻り値を返す
		//-----------
		return $result;
	}
}
