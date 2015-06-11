<?php
/**
 * スマホAPIサービス
 * オプション料金情報テーブルのアクセスクラス
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

class option_charge_infomation_m extends CI_Model
{
    var $CLASS_NAME = 'option_charge_infomation_m';                   // Class name
    var $TABLE_NAME = 'option_charge_infomation';                     // Table name
    var $SELECT_COLUMN =                               // Select column names
    'unq_id,	vrsn_nmbr,	entry_date,	entry_time,	updt_date,	updt_time,	htl_code,	optn_id,
	start_date,	end_date,	optn_chrg';

    function __construct()
    {
        // Model クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /*
     * オプション料金情報テーブルにレコードを１件追加する。
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
     * オプション料金情報テーブルの指定レコードを更新する。
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
        // option_charge_infomationの更新処理
        //-------------------------
        if($result['errCode'] == true){
            $rec['vrsn_nmbr'] = $rec['vrsn_nmbr']+1;   // バージョン番号をカウントUP

            // option_charge_infomationテーブルにupdateする。
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
     * オプション料金情報テーブルの指定レコードを削除する。
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
     * オプション料金情報テーブルのレコードリストを取得する
     *
     * @param   var      $htl_code                               : ホテルコード）
     * @param   var      $target_date                            : 適用日
     * @return  array   $result :  戻り値
     *              ['err_code']: エラーコード(true=正常終了/ false=異常終了)
     *              ['mssg']    : エラーメッセージ
     *              ['list']    : 検索で取得したオプション料金情報テーブルのリスト
     *
     */
    function selectListCond($htl_code, $target_date)
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
        $this->db->select($this->SELECT_COLUMN);
        $this->db->where('htl_code',$htl_code);
        $this->db->where('start_date <= ',$target_date);
        $this->db->where('end_date >= ',$target_date);
		$this->db->order_by("optn_id");

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
}
