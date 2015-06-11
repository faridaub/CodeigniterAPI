<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * API
 * 共通処理
 *
 * @package		CodeIgniter
 * @subpackage	libraries
 * @category	libraries
 * @author		Kenichi Tani
 * @url			http://hr.toyoko-inn.co.jp
 *
 * Copyright © 2013 TOYOKO INN IT SHUUKYAKU SOLUTION CO.,LTD All Rights Reserved.
*/
class Api_const {
	// return code(api)
	const BCMN0000 = "BCMN0000";
	const BAPI0001 = "BAPI0001";
	const BAPI0002 = "BAPI0002";
	const BAPI0003 = "BAPI0003";
	const BAPI1001 = "BAPI1001";
	const BAPI1004 = "BAPI1004";
	const BAPI1005 = "BAPI1005";
	const BAPI1006 = "BAPI1006";
	const BAPI1007 = "BAPI1007";
	const BCMN0001 = "BCMN0001";
	const BCMN0002 = "BCMN0002";
	const BCMN1001 = "BCMN1001";
	const BCMN1002 = "BCMN1002";
	const BCMN1003 = "BCMN1003";
	const BCMN1004 = "BCMN1004";
	const BGNL0001 = "BGNL0001";
	const BGNL0002 = "BGNL0002";
	const BGNL0003 = "BGNL0003";
	const BGNL0004 = "BGNL0004";
	const BGNL0005 = "BGNL0005";
	const BGNL0006 = "BGNL0006";
	const BGNL0007 = "BGNL0007";
	const BRSV0001 = "BRSV0001";
	const BRSV0003 = "BRSV0003";
	const BRSV0004 = "BRSV0004";
	const BRSV0005 = "BRSV0005";
	const BRSV0006 = "BRSV0006";
	const BRSV0008 = "BRSV0008";
	const BRSV0010 = "BRSV0010";
	const BRSV0011 = "BRSV0011";
	const BRSV0012 = "BRSV0012";
	const BRSV0013 = "BRSV0013";
	const BRSV1006 = "BRSV1006";
	const BRSV1007 = "BRSV1007";
	const BRSV1008 = "BRSV1008";
	const BRSV1009 = "BRSV1009";

	// apiname(url)
	const A001 = "search_point_api";                    //ポイント検索API
	const A002 = "search_hotel_coordinate_api";         //空室ホテルの座標値検索API
	const A003 = "search_hotel_keyword_api";            //空室ホテルをキーワード検索API
	const A004 = "search_hotel_area_api";               //空室ホテルをエリア検索API
	const A005 = "search_hotel_api";                    //空室ホテル検索API
	const A006 = "search_room_type_api";                //部屋タイプの空室数検索API
	const A007 = "search_hotel_details_api";            //ホテル詳細情報検索API
	const A008 = "search_hotel_vacant_api";             //ホテル空室数検索API
	const A009 = "search_room_type_vacant_api";         //部屋タイプ別空室数検索API
	const A010 = "search_room_type_details_api";        //部屋タイプ詳細検索API
	const A011 = "search_room_type_price_api";          //部屋タイプ価格検索API
	const A012 = "check_booking_api";                   //予約チェックAPI
	const A013 = "register_reservation_api";            //予約登録API
	const A014 = "search_booking_api";                  //予約検索API
	const A015 = "search_booking_details_api";          //予約詳細検索API
	const A016 = "cancel_reservation_api";              //宿泊キャンセルAPI
	const A017 = "change_reservation_api";              //宿泊変更API
	const A018 = "search_stay_history_api";             //宿泊履歴検索API
	const A019 = "search_stay_history_details_api";     //宿泊履歴詳細検索API
	const A020 = "search_favorite_hotel_api";           //お気に入りホテル検索API
	const A021 = "entry_favorite_hotel_api";            //お気に入りホテル登録API
	const A022 = "search_customer_information_api";     //お客様情報検索API
	const A023 = "change_customer_information_api";     //お客様情報変更API
	const A024 = "search_customer_setting_api";         //お客様設定情報検索API
	const A025 = "change_customer_setting_api";         //お客様設定情報変更API
	const A026 = "attests_member_api";                  //会員認証API
	const A027 = "attests_name_birth_date_api";         //姓名・生年月日認証API
	const A028 = "attests_birth_date_password_api";     //生年月日・パスワード認証API
	const A029 = "entry_personal_information_api";      //個人情報登録API
	const A030 = "search_browsing_history_api";         //閲覧履歴検索API
	const A031 = "send_reservation_mail_api";			//予約メール送信API
	const A032 = "login_api";							//ログインAPI
	const A033 = "check_initialization_api";			//初期設定情報入力チェックAPI
	const A034 = "entry_initialization_api";			//初期設定情報登録API
	const A035 = "send_password_forgotten_mail_api";	//パスワード忘れメール送信API
	const A036 = "reset_password_api";					//パスワード再設定API

	// APIキー情報(処理モード)
	const SYORI_MODE_HONBAN = "1";      // 本番
	const SYORI_MODE_TEST = "2";        // テスト

	// アプリバージョン管理(状態)
	const APP_STT_KAIHATSU = "0";       // 開発中
	const APP_STT_TEST = "1";           // テスト中
	const APP_STT_SERVICE = "2";        // サービス中
	const APP_STT_SERVICE_END = "3";    // サービス終了

	// 操作ログ(端末種別)
	const TRMNL_TYPE_IPHONE = "1";      // iphone
	const TRMNL_TYPE_ANDROID = "2";     // Android

	// 設備情報(設備種別)
	const EQPMNT_TYPE_KANNAI = "1";     // 館内設備
	const EQPMNT_TYPE_SHITSUNAI = "2";  // 室内設備

	// ホテル状態(状態)
	const HTL_STT_HANBAITEISHI = "0";   // 販売停止中
	const HTL_STT_HANBAI = "1";         // 販売中
	const HTL_STT_MENTENANCE = "2";     // メンテナンス中
	const HTL_STT_TEST = "3";           // テスト中

	// キーワード情報(キーワードのタイプ)
	const KEYWORD_TYPE_EKI = "1";       // 駅名
	const KEYWORD_TYPE_KUKOU = "2";     // 空港名
	const KEYWORD_TYPE_BUS = "3";       // バス停
	const KEYWORD_TYPE_RANDMARK = "8";  // ランドマーク
	const KEYWORD_TYPE_KODAWARI = "9";  // こだわり

	// 会員フラグ
	const MMBR_FLG_Y = "Y";             // 会員
	const MMBR_FLG_N = "N";             // 非会員

	// 最大残室数
	const MAX_VCNCY = "999999999";

	const RATE_DSPLY_FLG_ON = "1";      // 税抜表示
	const RATE_DSPLY_FLG_OFF = "2";     // 税抜非表示

	// 言語
	const LANG_JP = "ja";               // 日本語
	const LANG_EN = "en";               // 英語

	// Km
	const KILOMETER = "km";

	//ホテル検索モード
	const HOTEL_ONLY = "1";			//ホテル関連情報のみ
	const HOTEL_AND_VACANCY	= "2";	//ホテル関連情報と空室情報
	
	//処理タイプ
	const PRCSSNG_TYPE_MEMBER	= "1";	//1:会員の方
	const PRCSSNG_TYPE_GENERAL	= "2";	//2:一般の方
	
	//喫煙フラグ
	const SMOKING_NO	=	'N';		//禁煙
	const SMOKING_YES	=	'Y';		//喫煙

}