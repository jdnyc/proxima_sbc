<?php
set_time_limit(0);
function _fn_check_db_connection($data){
		
	// 0: Postgres || 1: Oracle
	if($data['db_driver'] == '0'){
		$host        = "host=".$data['db_host'];
		$port        = "port=".$data['db_port'];
		$dbname      = "dbname=".$data['db_name'];
		$credentials = "user=".$data['db_user']." password=".$data['db_user_pw'];

		$db = @pg_connect( "$host $port $dbname $credentials"  );
		if(!$db){
			return false;
			//return array('success'=> true,'result' =>"$av_folder_path is a directory");
		} else {
			return true;
		}
		
	}else{
		$host = $data['db_host'].":".$data['db_port']."/".$data['db_service_id'];
		$user = $data['db_user'];
		$pass = $data['db_user_pw'];
		
		$db = @oci_connect("$user", "$pass", "$host");
		if (!$db) {
			return false;
		}else{
			return true;
		}

	}
}

function fn_create_user_and_schema($data){
	// 0: Postgres || 1: Oracle
	if($data['db_driver'] == '0'){
		//Postgres
		$host        = "host=".$data['db_host'];
		$port        = "port=".$data['db_port'];
		$dbname      = "dbname=".$data['db_name'];
		$credentials = "user=".$data['db_user']." password=".$data['db_user_pw'];
		
		$db = @pg_connect( "$host $port $dbname $credentials"  );
		if(!$db){
			// false
			return false;
			
		} else {
			// true
			$create_new_user_and_schema_sql = "
				CREATE ROLE ".$data['db_new_user']." LOGIN
				ENCRYPTED PASSWORD '".$data['db_new_user_pw']."'
				NOSUPERUSER INHERIT NOCREATEDB NOCREATEROLE NOREPLICATION;
				CREATE SCHEMA ".$data['db_new_user']."
				AUTHORIZATION ".$data['db_new_user'].";
			";
			$ret = @pg_query($db, $create_new_user_and_schema_sql);
			if(!$ret){
				return false;
			} else {
				return true;
			}
			
		} 
	} else {
		
	}	
}

function fn_create_user_and_set_tablespace($data){
	//Oracle
	$host = $data['db_host'].":".$data['db_port']."/".$data['db_service_id'];	
	$user = $data['db_user'];
	$pass = $data['db_user_pw'];
	$db_service_id = $data['db_service_id'];
	$db_user = $data['db_new_user'];
	$db_user_pw = $data['db_new_user_pw'];
	$db_oracle_path = '';//

	$db = @oci_connect("$user", "$pass", "$host");
	
	$all_query = "CREATE TABLESPACE ".$db_user." 
		DATAFILE '".$db_user.".DBF'
		SIZE 100M AUTOEXTEND ON NEXT 50M MAXSIZE UNLIMITED;

		CREATE USER ".$db_user." IDENTIFIED BY ".$db_user_pw." DEFAULT TABLESPACE ".$db_user.";

		GRANT RESOURCE, CONNECT, CREATE VIEW TO ".$db_user;
	$arr_query = explode(';', $all_query);
	foreach($arr_query as $one_query) {
		$stid = oci_parse($db, $one_query);
		$ret = oci_execute($stid);	
	}

	return true;
}

function fn_write_config($data) {
    // Config path
    $template_path 	= '../config/database.php';
    $output_path 	= '../../application/config/database.php';
    
    // Open the file
    $database_file = file_get_contents($template_path);
    
    $new  = str_replace("%HOSTNAME%",$data['db_host'],$database_file);
    $new  = str_replace("%USERNAME%",$data['db_user'],$new);
    $new  = str_replace("%PASSWORD%",$data['db_user_pw'],$new);
    $new  = str_replace("%DATABASE%",$data['db_name'],$new);
    
    // Write the new database.php file
    $handle = fopen($output_path,'w+');
    
    // Chmod the file, in case the user forgot
    @chmod($output_path,0777);
    
    // Verify file permissions
    if(is_writable($output_path)) {
        // Write the file
        if(fwrite($handle,$new)) {
        	return true;
        } else {
        	return false;
        }
    
    } else {
        return false;
    }
}

function fn_install_schema_and_data($data){

	// PROGRESQL
	$host        = "host=".$data['db_host'];
	$port        = "port=".$data['db_port'];
	$dbname      = "dbname=".$data['db_name'];
	$credentials = "user=".$data['db_user']." password=".$data['db_user_pw'];
	
	$result = false;
	//Connection to DB
	$db = @pg_connect( "$host $port $dbname $credentials"  );

	$sql_create_table_example ="
		CREATE TABLE bc_bs_content
		(
		  bs_content_id double precision NOT NULL,
		  bs_content_title character varying(200) NOT NULL,
		  show_order double precision,
		  allowed_extension character varying(100),
		  description character varying(1000),
		  created_date character(14),
		  bs_content_code character varying(1000),
		  CONSTRAINT bc_bs_content_pk PRIMARY KEY (bs_content_id)
		)
		WITH (
		  OIDS=FALSE
		);
		ALTER TABLE bc_bs_content
			OWNER TO ".$data['db_user'].";
			
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (506,'동영상',1,null,'모든동영상',null,'MOVIE');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (515,'사운드',2,null,'모든음향파일',null,'SOUND');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (518,'이미지',3,null,'모든 사진',null,'IMAGE');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (57057,'문서',6,null,'모든 문서 파일',null,'DOCUMENT');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (57078,'시퀀스',7,null,'모든 시퀀스 파일','20120424205401','SEQUENCE');
	";
	
	$ret = @pg_query($db, $sql_create_table_example);
	if(!$ret){
		$result = false;
	} else {
		$result = true;
	}
	
	$sql_create_seq_example = "
		CREATE SEQUENCE SEQ_ORD_REQUEST
		  INCREMENT 1
		  MINVALUE 0
		  MAXVALUE 99999
		  START 1
		  CACHE 1;
		ALTER TABLE SEQ_ORD_REQUEST OWNER TO ".$data['db_user'].";
	";

	$ret = @pg_query($db, $sql_create_seq_example);
	if(!$ret){
		$result = false;
	} else {
		$result = true;
	}

	pg_close($db);
	return $result;
}

function fn_install_schema_and_data_oracle($data){
	
	
	$host = $data['db_host'].":".$data['db_port']."/".$data['db_service_id'];
	$user = $data['db_user'];
	$pass = $data['db_user_pw'];
	
	$result = false;
	
	$db = @oci_connect("$user", "$pass", "$host", "AL32UTF8");
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".date('Y-m-d H:i:s')." Install database table and data START.", FILE_APPEND);
	//Sequence	 200~ Line
	//Table		 400~ Line
	//Trigger	5600~ Line
	//View		6000~ Line
	//Data		6300~ Line	
	$all_query = '
		--------------------------------------------------------
		--  1이 아닌 시퀀스 모음
		-- NOTICE_SEQ 10
		-- SEQ 1000
		-- SEQ_BC_CATEGORY_ID 10
		-- SEQ_BC_CODE_ID 1000
		-- SEQ_BC_CODE_TYPE_ID 1000
		-- SEQ_MEMBER_GROUP_ID 10
		-- SEQ_USR_META_FIELD_ID 1000
		-- TASK_WORKFLOW_ID 100
		-- TASK_WORKFLOW_RULE_ID_SEQ 100
		--------------------------------------------------------
		--------------------------------------------------------
		--  DDL for Sequence ARCHIVE_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "ARCHIVE_SEQ"  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence BC_LOG_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "BC_LOG_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence CONTENT_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "CONTENT_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence EMAIL_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "EMAIL_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence FILENAME_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "FILENAME_SEQ"  MINVALUE 0 MAXVALUE 99999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  CYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence IM_SCHEDULE_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "IM_SCHEDULE_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence INGEST_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "INGEST_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence LINK_CMS_TC_XML_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "LINK_CMS_TC_XML_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence NOTICE_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "NOTICE_SEQ"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 10 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ"  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1000 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_AD
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_AD"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_CATEGORY_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_CATEGORY_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 10 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_CODE_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_CODE_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1000 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_CODE_TYPE_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_CODE_TYPE_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1000 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_CUESHEET_CONTENT_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_CUESHEET_CONTENT_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_CUESHEET_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_CUESHEET_ID"  MINVALUE 1 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_FAVORITE_CATEGORY_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_FAVORITE_CATEGORY_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_LOG_DETAIL_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_LOG_DETAIL_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_MEDIA_QUALITY_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_MEDIA_QUALITY_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 CACHE 20 NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BC_META_MULTI_XML_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BC_META_MULTI_XML_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_BS_CONTENT_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_BS_CONTENT_ID"  MINVALUE 1 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_CONTENT_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_CONTENT_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_INTERFACE_CH_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_INTERFACE_CH_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_INTERFACE_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_INTERFACE_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_MEDIA_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_MEDIA_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_MEMBER_GROUP_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_MEMBER_GROUP_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 10 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_NOTE_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_NOTE_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 CACHE 20 NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_NPS_REVIEW_DAILY_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_NPS_REVIEW_DAILY_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 CACHE 20 NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_NPS_WORK_LIST_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_NPS_WORK_LIST_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 CACHE 20 NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_ORD_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_ORD_ID"  MINVALUE 1 MAXVALUE 99999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  CYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_ORD_REQUEST
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_ORD_REQUEST"  MINVALUE 1 MAXVALUE 99999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  CYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_RP_LIST_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_RP_LIST_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_SYS_META_FIELD_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_SYS_META_FIELD_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_SYS_META_VALUE_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_SYS_META_VALUE_ID"  MINVALUE 0 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_TB_ORD_TRANSMISSION
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_TB_ORD_TRANSMISSION"  MINVALUE 1 MAXVALUE 99999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  CYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence SEQ_USR_META_FIELD_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "SEQ_USR_META_FIELD_ID"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 1000 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence TASK_DEFINE_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "TASK_DEFINE_ID"  MINVALUE 1 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence TASK_LOG_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "TASK_LOG_SEQ"  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence TASK_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "TASK_SEQ"  MINVALUE 0 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 1 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence TASK_WORKFLOW_ID
		--------------------------------------------------------

		   CREATE SEQUENCE  "TASK_WORKFLOW_ID"  MINVALUE 1 MAXVALUE 999999999999999999999999999 INCREMENT BY 1 START WITH 100 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  DDL for Sequence TASK_WORKFLOW_RULE_ID_SEQ
		--------------------------------------------------------

		   CREATE SEQUENCE  "TASK_WORKFLOW_RULE_ID_SEQ"  MINVALUE 1 MAXVALUE 9999999999999999999999999999 INCREMENT BY 1 START WITH 100 NOCACHE  NOORDER  NOCYCLE ;
		--------------------------------------------------------
		--  파일이 생성됨 - 화요일-2월-02-2016   
		--------------------------------------------------------
		--------------------------------------------------------
		--  DDL for Table ARCHIVE_REQUEST
		--------------------------------------------------------

		  CREATE TABLE "ARCHIVE_REQUEST" 
		   (	"REQUEST_ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"REQUEST_TYPE" VARCHAR2(20 BYTE), 
			"COMMENTS" VARCHAR2(4000 BYTE), 
			"STATUS" NUMBER DEFAULT 0, 
			"REQUEST_USER_ID" VARCHAR2(20 BYTE), 
			"INTERFACE_ID" NUMBER, 
			"DAS_CONTENT_ID" NUMBER, 
			"CATEGORY_FULL_PATH" VARCHAR2(1000 BYTE), 
			"CREATED" DATE DEFAULT sysdate, 
			"REJECT_COMMENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."REQUEST_ID" IS \'요청 고유ID\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."CONTENT_ID" IS \'콘대상 콘텐츠아이디\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."REQUEST_TYPE" IS \'요청 타입 archive restore pfr_restore\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."COMMENTS" IS \'요청내용\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."STATUS" IS \'요청상태\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."REQUEST_USER_ID" IS \'요청 사용자ID\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."INTERFACE_ID" IS \'워크플로우 인터페이스ID\';
		 
		   COMMENT ON COLUMN "ARCHIVE_REQUEST"."DAS_CONTENT_ID" IS \'DAS 콘텐츠ID\';
		--------------------------------------------------------
		--  DDL for Table BC_BS_CONTENT
		--------------------------------------------------------

		  CREATE TABLE "BC_BS_CONTENT" 
		   (	"BS_CONTENT_ID" NUMBER, 
			"BS_CONTENT_TITLE" VARCHAR2(200 BYTE), 
			"SHOW_ORDER" NUMBER(*,0), 
			"ALLOWED_EXTENSION" VARCHAR2(100 BYTE), 
			"DESCRIPTION" VARCHAR2(1000 BYTE), 
			"CREATED_DATE" CHAR(14 BYTE), 
			"BS_CONTENT_CODE" VARCHAR2(1000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_BS_CONTENT"."BS_CONTENT_ID" IS \'콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "BC_BS_CONTENT"."BS_CONTENT_TITLE" IS \'콘텐츠 명\';
		--------------------------------------------------------
		--  DDL for Table BC_CATEGORY
		--------------------------------------------------------

		  CREATE TABLE "BC_CATEGORY" 
		   (	"CATEGORY_ID" NUMBER, 
			"PARENT_ID" NUMBER, 
			"CATEGORY_TITLE" VARCHAR2(100 BYTE), 
			"CODE" VARCHAR2(20 BYTE), 
			"SHOW_ORDER" NUMBER(*,0), 
			"NO_CHILDREN" CHAR(1 CHAR), 
			"EXTRA_ORDER" NUMBER, 
			"IS_DELETED" NUMBER(1,0) DEFAULT 0
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 196608 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CATEGORY"."CATEGORY_ID" IS \'카테고리ID\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY"."PARENT_ID" IS \'상위카테고리ID\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY"."CATEGORY_TITLE" IS \'카테고리명\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY"."SHOW_ORDER" IS \'노출순서\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY"."NO_CHILDREN" IS \'하위카테고리존재유무
		0:없음,1:있음\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY"."EXTRA_ORDER" IS \'추가순서
		NPS CG 2차 정렬이 필요해서..\';
		--------------------------------------------------------
		--  DDL for Table BC_CATEGORY_ENV
		--------------------------------------------------------

		  CREATE TABLE "BC_CATEGORY_ENV" 
		   (	"CATEGORY_ID" NUMBER, 
			"UD_CONTENT_ID" NUMBER, 
			"IS_ARCHIVE" CHAR(1 BYTE), 
			"ARCHIVE_GROUP" VARCHAR2(100 BYTE), 
			"ARCHIVE_PRIORITY" VARCHAR2(20 BYTE), 
			"ARCHIVE_TIME" VARCHAR2(20 BYTE), 
			"STORAGE_DELETE_TIME" VARCHAR2(20 BYTE), 
			"ARCHIVE_DELETE_TIME" VARCHAR2(20 BYTE), 
			"RESTORE_PRIORITY" VARCHAR2(20 BYTE), 
			"RESTORE_DELETE_TIME" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CATEGORY_ENV"."ARCHIVE_PRIORITY" IS \'1-100 아카이브 우선순위\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_ENV"."ARCHIVE_TIME" IS \'0 : 즉시 아카이브 1 : 하루\';
		--------------------------------------------------------
		--  DDL for Table BC_CATEGORY_GRANT
		--------------------------------------------------------

		  CREATE TABLE "BC_CATEGORY_GRANT" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER, 
			"CATEGORY_ID" NUMBER, 
			"GROUP_GRANT" NUMBER DEFAULT 0, 
			"CATEGORY_FULL_PATH" VARCHAR2(500 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CATEGORY_GRANT"."UD_CONTENT_ID" IS \'사용자정의 메타테이블 아이디\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_GRANT"."MEMBER_GROUP_ID" IS \'사용자 그룹 아이디\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_GRANT"."GROUP_GRANT" IS \'그룹 권한:
		0: 권한 없음 , 1: 읽기, 2: 읽기, 생성,수정,이동
		3: 읽기,생성, 수정, 이동, 삭제\';
		--------------------------------------------------------
		--  DDL for Table BC_CATEGORY_MAPPING
		--------------------------------------------------------

		  CREATE TABLE "BC_CATEGORY_MAPPING" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"CATEGORY_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_CATEGORY_TOPIC
		--------------------------------------------------------

		  CREATE TABLE "BC_CATEGORY_TOPIC" 
		   (	"CATEGORY_ID" NUMBER, 
			"EXPIRED_DATE" CHAR(14 BYTE), 
			"BROAD_DATE" CHAR(14 BYTE), 
			"CONTENTS" VARCHAR2(4000 BYTE), 
			"STATUS" VARCHAR2(20 BYTE), 
			"REQ_USER_ID" VARCHAR2(50 BYTE), 
			"IS_DELETED" NUMBER(1,0) DEFAULT 0
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CATEGORY_TOPIC"."CATEGORY_ID" IS \'카테고리 ID\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_TOPIC"."EXPIRED_DATE" IS \'만료일\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_TOPIC"."BROAD_DATE" IS \'방송예정일\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_TOPIC"."CONTENTS" IS \'내용\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_TOPIC"."STATUS" IS \'승인여부\';
		 
		   COMMENT ON COLUMN "BC_CATEGORY_TOPIC"."REQ_USER_ID" IS \'토픽 신청자\';
		--------------------------------------------------------
		--  DDL for Table BC_CODE
		--------------------------------------------------------

		  CREATE TABLE "BC_CODE" 
		   (	"ID" NUMBER, 
			"CODE" VARCHAR2(2000 BYTE), 
			"NAME" VARCHAR2(2000 BYTE), 
			"CODE_TYPE_ID" NUMBER, 
			"SORT" NUMBER, 
			"HIDDEN" NUMBER DEFAULT 0, 
			"ENAME" VARCHAR2(2000 BYTE),
			"REF1" VARCHAR2(2000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CODE"."ID" IS \'코드ID
		일련번호\';
		 
		   COMMENT ON COLUMN "BC_CODE"."CODE" IS \'코드값\';
		 
		   COMMENT ON COLUMN "BC_CODE"."NAME" IS \'코드명\';
		 
		   COMMENT ON COLUMN "BC_CODE"."CODE_TYPE_ID" IS \'코드유형ID\';
		 
		   COMMENT ON COLUMN "BC_CODE"."SORT" IS \'정렬
		\';
		 
		   COMMENT ON COLUMN "BC_CODE"."HIDDEN" IS \'숨김\';
		 
		   COMMENT ON COLUMN "BC_CODE"."ENAME" IS \'코드명 영문\';
		   COMMENT ON COLUMN "BC_CODE"."REF1" IS \'BC_SYS_CODE와 연계하기 위한 코드값\';
		--------------------------------------------------------
		--  DDL for Table BC_CODE_TYPE
		--------------------------------------------------------

		  CREATE TABLE "BC_CODE_TYPE" 
		   (	"ID" NUMBER, 
			"CODE" VARCHAR2(2000 BYTE), 
			"NAME" VARCHAR2(2000 BYTE),
			"REF1" VARCHAR2(2000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CODE_TYPE"."ID" IS \'코드유형ID\';
		 
		   COMMENT ON COLUMN "BC_CODE_TYPE"."CODE" IS \'코드유형값\';
		 
		   COMMENT ON COLUMN "BC_CODE_TYPE"."NAME" IS \'코드유형명\';
		--------------------------------------------------------
		--  DDL for Table BC_COMMENTS
		--------------------------------------------------------

		  CREATE TABLE "BC_COMMENTS" 
		   (	"CONTENT_ID" NUMBER, 
			"USER_ID" VARCHAR2(20 BYTE), 
			"COMMENTS" VARCHAR2(4000 BYTE), 
			"SEQ" NUMBER, 
			"DATETIME" CHAR(14 BYTE), 
			"USER_NM" VARCHAR2(200 BYTE), 
			"PARENT_CONTENT_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_CONTENT
		--------------------------------------------------------

		  CREATE TABLE "BC_CONTENT" 
		   (	"CATEGORY_ID" NUMBER DEFAULT 0, 
			"CATEGORY_FULL_PATH" VARCHAR2(500 BYTE) DEFAULT \'/0\', 
			"BS_CONTENT_ID" NUMBER, 
			"UD_CONTENT_ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"TITLE" VARCHAR2(300 BYTE), 
			"IS_DELETED" CHAR(1 BYTE) DEFAULT \'N\', 
			"IS_HIDDEN" CHAR(1 BYTE) DEFAULT 0, 
			"REG_USER_ID" VARCHAR2(25 BYTE), 
			"EXPIRED_DATE" CHAR(14 BYTE), 
			"LAST_MODIFIED_DATE" CHAR(14 BYTE), 
			"CREATED_DATE" CHAR(14 BYTE), 
			"STATUS" CHAR(2 BYTE), 
			"READED" CHAR(1 BYTE), 
			"LAST_ACCESSED_DATE" CHAR(14 BYTE), 
			"PARENT_CONTENT_ID" NUMBER, 
			"MANAGER_STATUS" VARCHAR2(20 BYTE), 
			"IS_GROUP" CHAR(1 BYTE) DEFAULT \'I\', 
			"GROUP_COUNT" NUMBER, 
			"STATE" NUMBER DEFAULT 0, 
			"ARCHIVE_DATE" VARCHAR2(20 BYTE), 
			"DEL_STATUS" VARCHAR2(20 BYTE), 
			"DEL_YN" VARCHAR2(20 BYTE), 
			"RESTORE_DATE" VARCHAR2(20 BYTE), 
			"UAN" VARCHAR2(100 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 26214400 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CONTENT"."CATEGORY_ID" IS \'카테고리ID\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."CATEGORY_FULL_PATH" IS \'카테고리전체경로\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."BS_CONTENT_ID" IS \'기본콘텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."UD_CONTENT_ID" IS \'사용자정의콘텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."CONTENT_ID" IS \'콘텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."TITLE" IS \'콘텐츠명\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."IS_DELETED" IS \'삭제여부
		MAM 을 통한 삭제시 Y로 SET\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."IS_HIDDEN" IS \'숨김여부\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."REG_USER_ID" IS \'등록사용자ID\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."EXPIRED_DATE" IS \'폐기일시\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."LAST_MODIFIED_DATE" IS \'최종수정일시\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."STATUS" IS \'상태
		-2 ingest, -1 dmc, 0 등록대기\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."READED" IS \'READED\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."LAST_ACCESSED_DATE" IS \'LAST_ACCESSED_DATE\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."PARENT_CONTENT_ID" IS \'PARENT_CONTENT_ID\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."MANAGER_STATUS" IS \'승인반려 여부. accept, decline\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."IS_GROUP" IS \'개별: I / 그룹: G / 그룹하위컨텐츠: C\';
		 
		   COMMENT ON COLUMN "BC_CONTENT"."GROUP_COUNT" IS \'그룹일 경우 하위 파일들의 개수\';
		--------------------------------------------------------
		--  DDL for Table BC_CUESHEET
		--------------------------------------------------------

		  CREATE TABLE "BC_CUESHEET" 
		   (	"CUESHEET_ID" NUMBER, 
			"CUESHEET_TITLE" VARCHAR2(150 BYTE), 
			"BROAD_DATE" VARCHAR2(14 BYTE), 
			"CREATED_DATE" VARCHAR2(14 BYTE), 
			"USER_ID" VARCHAR2(50 BYTE), 
			"TYPE" VARCHAR2(1 BYTE), 
			"SUBCONTROL_ROOM" VARCHAR2(6 CHAR), 
			"CREATE_SYSTEM" VARCHAR2(50 BYTE), 
			"PROG_ID" VARCHAR2(50 BYTE), 
			"PROG_NM" VARCHAR2(100 BYTE), 
			"EPSD_NO" NUMBER, 
			"TRFF_YMD" VARCHAR2(20 BYTE), 
			"TRFF_SEQ" VARCHAR2(20 BYTE), 
			"TRFF_NO" VARCHAR2(20 BYTE), 
			"DURATION" VARCHAR2(20 BYTE), 
			"MODIFIED_DATE" VARCHAR2(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CUESHEET"."CUESHEET_ID" IS \'큐시트ID\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."CUESHEET_TITLE" IS \'큐시트제목\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."BROAD_DATE" IS \'방송일\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."CREATED_DATE" IS \'생성일\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."USER_ID" IS \'작성자\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."TYPE" IS \'큐시트구분항목
		A: 오디오,  M:  미디어\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."SUBCONTROL_ROOM" IS \'부조정실 
		대형: large, 중형: middle, 소형: small\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."CREATE_SYSTEM" IS \'큐시트 작성 시스템\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."PROG_ID" IS \'프로그램ID\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."PROG_NM" IS \'프로그램명\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."EPSD_NO" IS \'회차정보\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."TRFF_YMD" IS \'편성표에서 duration을 조회하기위한 정보\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."TRFF_SEQ" IS \'편성표에서 duration을 조회하기위한 정보\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."TRFF_NO" IS \'편성표에서 duration을 조회하기위한 정보\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."DURATION" IS \'편성길이\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET"."MODIFIED_DATE" IS \'수정일시\';
		 
		   COMMENT ON TABLE "BC_CUESHEET"  IS \'부조정실 
		대형:large, 중형:middle, 소형:small\';
		--------------------------------------------------------
		--  DDL for Table BC_CUESHEET_CONTENT
		--------------------------------------------------------

		  CREATE TABLE "BC_CUESHEET_CONTENT" 
		   (	"CUESHEET_ID" NUMBER, 
			"SHOW_ORDER" NUMBER, 
			"TITLE" VARCHAR2(150 BYTE), 
			"CONTENT_ID" NUMBER, 
			"CUESHEET_CONTENT_ID" NUMBER, 
			"TASK_ID" NUMBER, 
			"CONTROL" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 327680 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."CUESHEET_ID" IS \'큐시트ID\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."SHOW_ORDER" IS \'노출 순서\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."TITLE" IS \'컨텐츠명\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."CONTENT_ID" IS \'컨텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."CUESHEET_CONTENT_ID" IS \'큐시트컨텐츠ID
		테이블내 고유 ID\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."TASK_ID" IS \'작업ID\';
		 
		   COMMENT ON COLUMN "BC_CUESHEET_CONTENT"."CONTROL" IS \'제어항목(goto:처음으로/pause:일시정지/next:다음으로)\';
		--------------------------------------------------------
		--  DDL for Table BC_FAVORITE
		--------------------------------------------------------

		  CREATE TABLE "BC_FAVORITE" 
		   (	"USER_ID" VARCHAR2(25 BYTE), 
			"SHOW_ORDER" CHAR(14 BYTE), 
			"CONTENT_ID" NUMBER, 
			"FAVORITE_CATEGORY_ID" NUMBER, 
			"CONTENT_TYPE" CHAR(1 CHAR) DEFAULT \'M\'
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_FAVORITE"."USER_ID" IS \'즐겨찾기를 가지고 있는 사용자 ID (OWNER)\';
		 
		   COMMENT ON COLUMN "BC_FAVORITE"."SHOW_ORDER" IS \'정렬순서\';
		 
		   COMMENT ON COLUMN "BC_FAVORITE"."CONTENT_TYPE" IS \'미디어(M) / 오디오(A) 컨텐츠구분값\';
		 
		   COMMENT ON TABLE "BC_FAVORITE"  IS \'사용자별로 카테고리에서 즐겨찾기를 설정할 때 사용되는 테이블\';
		--------------------------------------------------------
		--  DDL for Table BC_FAVORITE_CATEGORY
		--------------------------------------------------------

		  CREATE TABLE "BC_FAVORITE_CATEGORY" 
		   (	"FAVORITE_CATEGORY_ID" NUMBER, 
			"FAVORITE_CATEGORY_TITLE" VARCHAR2(100 BYTE), 
			"USER_ID" VARCHAR2(25 BYTE), 
			"CONTENT_TYPE" VARCHAR2(1 CHAR) DEFAULT \'M\'
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_FAVORITE_CATEGORY"."CONTENT_TYPE" IS \'M: 미디어 / A : 오디오\';
		--------------------------------------------------------
		--  DDL for Table BC_GRANT
		--------------------------------------------------------

		  CREATE TABLE "BC_GRANT" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER, 
			"GRANT_TYPE" VARCHAR2(2000 BYTE), 
			"GROUP_GRANT" NUMBER, 
			"CATEGORY_ID" NUMBER DEFAULT 0, 
			"CATEGORY_FULL_PATH" VARCHAR2(2000 BYTE) DEFAULT \'/0\'
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_GRANT_ACCESS
		--------------------------------------------------------

		  CREATE TABLE "BC_GRANT_ACCESS" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER, 
			"GRANT_TYPE" VARCHAR2(2000 BYTE), 
			"GROUP_GRANT" NUMBER, 
			"CATEGORY_ID" NUMBER, 
			"CATEGORY_FULL_PATH" VARCHAR2(2000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_HTML_CONFIG
		--------------------------------------------------------

		  CREATE TABLE "BC_HTML_CONFIG" 
		   (	"TYPE" VARCHAR2(20 BYTE), 
			"VALUE" VARCHAR2(30 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_LOG
		--------------------------------------------------------

		  CREATE TABLE "BC_LOG" 
		   (	"LOG_ID" NUMBER, 
			"ACTION" VARCHAR2(20 BYTE), 
			"USER_ID" VARCHAR2(30 BYTE), 
			"BS_CONTENT_ID" VARCHAR2(20 BYTE), 
			"CONTENT_ID" NUMBER, 
			"CREATED_DATE" CHAR(14 BYTE), 
			"UD_CONTENT_ID" NUMBER, 
			"DESCRIPTION" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 100663296 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;

		   
		  CREATE TABLE "BC_LOG_DETAIL" 
		   (	"DETAIL_LOG_ID" NUMBER, 
			"LOG_ID" NUMBER, 
			"ACTION" VARCHAR2(200 BYTE), 
			"USR_META_FIELD_ID" VARCHAR2(20 BYTE), 
			"OLD_CONTENTS" VARCHAR2(4000 BYTE), 
			"NEW_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT) ;
		 

		--------------------------------------------------------
		--  DDL for Table BC_MEDIA
		--------------------------------------------------------

		  CREATE TABLE "BC_MEDIA" 
		   (	"CONTENT_ID" NUMBER, 
			"MEDIA_ID" NUMBER, 
			"STORAGE_ID" NUMBER, 
			"MEDIA_TYPE" VARCHAR2(100 BYTE), 
			"PATH" VARCHAR2(500 BYTE), 
			"FILESIZE" VARCHAR2(100 BYTE) DEFAULT 0, 
			"CREATED_DATE" CHAR(14 BYTE), 
			"REG_TYPE" VARCHAR2(260 BYTE), 
			"STATUS" CHAR(1 CHAR), 
			"DELETE_DATE" CHAR(14 BYTE), 
			"FLAG" CHAR(10 BYTE), 
			"DELETE_STATUS" VARCHAR2(4000 BYTE), 
			"VR_START" NUMBER, 
			"VR_END" NUMBER, 
			"EXPIRED_DATE" VARCHAR2(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 75497472 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_MEDIA"."CONTENT_ID" IS \'컨텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."MEDIA_ID" IS \'미디어ID
		고유일련번호\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."STORAGE_ID" IS \'스토리지ID
		현재사용되지않음\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."MEDIA_TYPE" IS \'미디어유형
		original, proxy, thumb\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."PATH" IS \'미디어경로\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."FILESIZE" IS \'미디어파일용량\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."REG_TYPE" IS \'등록유형
		edius_plugin, ingest 등\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."STATUS" IS \'상태\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."DELETE_DATE" IS \'삭제일시\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."FLAG" IS \'Flag\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."DELETE_STATUS" IS \'삭제상태\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."VR_START" IS \'VRSTART\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."VR_END" IS \'VREND\';
		 
		   COMMENT ON COLUMN "BC_MEDIA"."EXPIRED_DATE" IS \'폐기일시\';
		--------------------------------------------------------
		--  DDL for Table BC_MEDIA_QUALITY
		--------------------------------------------------------

		  CREATE TABLE "BC_MEDIA_QUALITY" 
		   (	"QUALITY_ID" NUMBER, 
			"MEDIA_ID" NUMBER, 
			"QUALITY_TYPE" VARCHAR2(200 BYTE), 
			"START_TC" NUMBER, 
			"END_TC" NUMBER, 
			"SHOW_ORDER" NUMBER, 
			"IS_DELETED" CHAR(1 BYTE), 
			"NO_ERROR" CHAR(1 BYTE), 
			"SOUND_CHANNEL" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."QUALITY_ID" IS \'고유값\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."MEDIA_ID" IS \'미디어 ID\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."QUALITY_TYPE" IS \'퀄리티체크 종류\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."START_TC" IS \'에러검출시 에러부분 시작 타임코드\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."END_TC" IS \'에러검출시 에러부분 종료 타임코드\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."SHOW_ORDER" IS \'정렬순서\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."IS_DELETED" IS \'삭제여부\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."NO_ERROR" IS \'사용자확인에러여부(1:확인/null: 미확인)\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY"."SOUND_CHANNEL" IS \'오류발생 채널\';
		--------------------------------------------------------
		--  DDL for Table BC_MEDIA_QUALITY_INFO
		--------------------------------------------------------

		  CREATE TABLE "BC_MEDIA_QUALITY_INFO" 
		   (	"CONTENT_ID" NUMBER, 
			"ERROR_COUNT" NUMBER, 
			"IS_CHECKED" CHAR(1 CHAR) DEFAULT \'N\', 
			"REVIEW_COMMENT" VARCHAR2(4000 BYTE), 
			"CREATED_DATE" CHAR(14 BYTE), 
			"LAST_MODIFY_DATE" CHAR(14 BYTE), 
			"MEDIA_ID" NUMBER, 
			"USER_ID" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."CONTENT_ID" IS \'컨텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."ERROR_COUNT" IS \'QC검출된 에러갯수 중 사용자확인이 안된 에러 갯수\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."IS_CHECKED" IS \'사용자확인여부(Y:확인/N:미확인)\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."REVIEW_COMMENT" IS \'사용자 검토의견\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."CREATED_DATE" IS \'생성일\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."LAST_MODIFY_DATE" IS \'최종수정일시\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."MEDIA_ID" IS \'미디어 ID ( original / TM ) 구분하기 위한 값\';
		 
		   COMMENT ON COLUMN "BC_MEDIA_QUALITY_INFO"."USER_ID" IS \'QC사용자 아이디\';
		--------------------------------------------------------
		--  DDL for Table BC_MEMBER
		--------------------------------------------------------

		  CREATE TABLE "BC_MEMBER" 
		   (	"MEMBER_ID" NUMBER, 
			"USER_ID" VARCHAR2(20 BYTE), 
			"PASSWORD" VARCHAR2(42 BYTE), 
			"USER_NM" VARCHAR2(20 BYTE), 
			"DEPT_NM" VARCHAR2(100 BYTE), 
			"EMAIL" VARCHAR2(100 BYTE), 
			"IS_DENIED" VARCHAR2(20 BYTE), 
			"EXPIRED_DATE" VARCHAR2(20 BYTE), 
			"LAST_LOGIN_DATE" VARCHAR2(20 BYTE), 
			"IS_ADMIN" VARCHAR2(20 BYTE) DEFAULT \'N\', 
			"CREATED_DATE" VARCHAR2(20 BYTE), 
			"EXTRA_VARS" VARCHAR2(4000 BYTE), 
			"OCCU_KIND" VARCHAR2(1000 BYTE), 
			"JOB_RANK" VARCHAR2(1000 BYTE), 
			"JOB_POSITION" VARCHAR2(1000 BYTE), 
			"JOB_DUTY" VARCHAR2(1000 BYTE), 
			"HIRED_DATE" VARCHAR2(20 BYTE), 
			"DEP_TEL_NUM" VARCHAR2(20 BYTE), 
			"BREAKE" VARCHAR2(20 BYTE), 
			"RETIRE_DATE" VARCHAR2(20 BYTE), 
			"MEMBER_NO" VARCHAR2(20 BYTE), 
			"SESSION_ID" VARCHAR2(2000 BYTE), 
			"ORI_PASSWORD" VARCHAR2(100 BYTE), 
			"PHONE" VARCHAR2(20 BYTE), 
			"LOGIN_FAIL_CNT" NUMBER, 
			"PASSWORD_CHANGE_DATE" VARCHAR2(14 BYTE), 
			"LANG" VARCHAR2(20 BYTE) DEFAULT \'ko\'
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 10485760 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_MEMBER"."MEMBER_ID" IS \'회원ID
		일련번호\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."USER_ID" IS \'사용자ID\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."PASSWORD" IS \'비밀번호\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."USER_NM" IS \'사용자명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."DEPT_NM" IS \'부서명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."EMAIL" IS \'이메일\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."IS_DENIED" IS \'접속거부여부\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."EXPIRED_DATE" IS \'탈퇴일\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."LAST_LOGIN_DATE" IS \'최종로그인일시\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."IS_ADMIN" IS \'관리자여부\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."EXTRA_VARS" IS \'부가정보\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."OCCU_KIND" IS \'직종명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."JOB_RANK" IS \'직급명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."JOB_POSITION" IS \'직위명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."JOB_DUTY" IS \'직무명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."HIRED_DATE" IS \'입사일자\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."DEP_TEL_NUM" IS \'부서전화번호\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."BREAKE" IS \'휴직구분\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."RETIRE_DATE" IS \'퇴사날짜\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."MEMBER_NO" IS \'사번\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."SESSION_ID" IS \'세션ID\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."ORI_PASSWORD" IS \'최초비밀번호\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."PHONE" IS \'연락처\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."LOGIN_FAIL_CNT" IS \'로그인 실패 횟수\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."PASSWORD_CHANGE_DATE" IS \'패스워드 최종변경일시\';
		 
		   COMMENT ON COLUMN "BC_MEMBER"."LANG" IS \'사용할 언어 선택 ko : 한국어 / en : 영어\';
		
		--------------------------------------------------------
		--  DDL for Table BC_MEMBER_GROUP
		--------------------------------------------------------

		  CREATE TABLE "BC_MEMBER_GROUP" 
		   (	"MEMBER_GROUP_ID" NUMBER, 
			"MEMBER_GROUP_NAME" VARCHAR2(100 BYTE), 
			"IS_DEFAULT" CHAR(1 BYTE), 
			"IS_ADMIN" CHAR(1 BYTE), 
			"DESCRIPTION" VARCHAR2(500 BYTE), 
			"CREATED_DATE" CHAR(14 BYTE), 
			"ALLOW_LOGIN" CHAR(1 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."MEMBER_GROUP_ID" IS \'회원그룹ID\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."MEMBER_GROUP_NAME" IS \'회원그룹명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."IS_DEFAULT" IS \'기본사용여부\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."IS_ADMIN" IS \'관리그룹여부\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."DESCRIPTION" IS \'설명\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP"."ALLOW_LOGIN" IS \'로그인허용여부\';
		--------------------------------------------------------
		--  DDL for Table BC_MEMBER_GROUP_MEMBER
		--------------------------------------------------------

		  CREATE TABLE "BC_MEMBER_GROUP_MEMBER" 
		   (	"MEMBER_ID" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_MEMBER_GROUP_MEMBER"."MEMBER_ID" IS \'회원ID\';
		 
		   COMMENT ON COLUMN "BC_MEMBER_GROUP_MEMBER"."MEMBER_GROUP_ID" IS \'회원그룹ID\';
		--------------------------------------------------------
		--  DDL for Table BC_META_MULTI_XML
		--------------------------------------------------------

		  CREATE TABLE "BC_META_MULTI_XML" 
		   (	"CONTENT_ID" NUMBER, 
			"META_FIELD_ID" NUMBER, 
			"META_MULTI_XML_ID" NUMBER, 
			"SHOW_ORDER" NUMBER, 
			"XML_VALUE" "SYS"."XMLTYPE" 
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 11534336 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   
		 XMLTYPE COLUMN "XML_VALUE" STORE AS BASICFILE CLOB (
		   ENABLE STORAGE IN ROW CHUNK 8192 PCTVERSION 10
		  NOCACHE LOGGING 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)) ;
		--------------------------------------------------------
		--  DDL for Table BC_MODULE_INFO
		--------------------------------------------------------

		  CREATE TABLE "BC_MODULE_INFO" 
		   (	"NAME" VARCHAR2(50 BYTE) DEFAULT NULL, 
			"ACTIVE" CHAR(1 BYTE), 
			"MAIN_IP" VARCHAR2(50 BYTE), 
			"SUB_IP" VARCHAR2(50 BYTE) DEFAULT NULL, 
			"DESCRIPTION" VARCHAR2(255 BYTE) DEFAULT NULL, 
			"MODULE_INFO_ID" NUMBER, 
			"LAST_ACCESS" CHAR(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_NOTICE
		--------------------------------------------------------

		  CREATE TABLE "BC_NOTICE" 
		   (	"NOTICE_ID" NUMBER, 
			"NOTICE_TITLE" VARCHAR2(500 BYTE), 
			"NOTICE_CONTENT" VARCHAR2(4000 BYTE), 
			"CREATED_DATE" CHAR(14 BYTE), 
			"NOTICE_TYPE" VARCHAR2(100 BYTE), 
			"FROM_USER_ID" VARCHAR2(100 BYTE), 
			"TO_USER_ID" VARCHAR2(100 BYTE), 
			"MEMBER_GROUP_ID" NUMBER, 
			"DEPCD" VARCHAR2(20 BYTE), 
			"FST_ORDER" NUMBER DEFAULT 0
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_NOTICE"."NOTICE_ID" IS \'공지사항ID\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."NOTICE_TITLE" IS \'공지사항제목\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."NOTICE_CONTENT" IS \'공지사항내용\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."NOTICE_TYPE" IS \'공지사항유형
		all\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."FROM_USER_ID" IS \'작성자ID\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."TO_USER_ID" IS \'대상자ID\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."MEMBER_GROUP_ID" IS \'회원그룹ID\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."DEPCD" IS \'부서코드\';
		 
		   COMMENT ON COLUMN "BC_NOTICE"."FST_ORDER" IS \'상위글여부\';
		--------------------------------------------------------
		--  DDL for Table BC_PATH_AVAILABLE
		--------------------------------------------------------

		  CREATE TABLE "BC_PATH_AVAILABLE" 
		   (	"MODULE_INFO_ID" NUMBER, 
			"AVAILABLE_STORAGE" NUMBER, 
			"AUTH" NUMBER(3,0) DEFAULT 3
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_PATH_AVAILABLE"."MODULE_INFO_ID" IS \'모듈ID\';
		 
		   COMMENT ON COLUMN "BC_PATH_AVAILABLE"."AVAILABLE_STORAGE" IS \'사용가능스토리지\';
		 
		   COMMENT ON COLUMN "BC_PATH_AVAILABLE"."AUTH" IS \'접근권한
		1bit 연산(1=r , 2=w)\';
		 
		   COMMENT ON TABLE "BC_PATH_AVAILABLE"  IS \'사용자별 스토리 권한 설정\';
		--------------------------------------------------------
		--  DDL for Table BC_PERSONAL_TASK
		--------------------------------------------------------

		  CREATE TABLE "BC_PERSONAL_TASK" 
		   (	"CONTENT_ID" NUMBER, 
			"MATERIAL_ID" VARCHAR2(20 BYTE), 
			"PROGRAM_ID" VARCHAR2(20 BYTE), 
			"REG_USER_ID" VARCHAR2(20 BYTE), 
			"STATUS" VARCHAR2(20 BYTE), 
			"PROGRESS" NUMBER, 
			"CREATED_DATE" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_RESTORE_ING
		--------------------------------------------------------

		  CREATE TABLE "BC_RESTORE_ING" 
		   (	"CONTENT_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_SCENE
		--------------------------------------------------------

		  CREATE TABLE "BC_SCENE" 
		   (	"SCENE_ID" NUMBER, 
			"MEDIA_ID" NUMBER, 
			"SHOW_ORDER" NUMBER(*,0), 
			"PATH" VARCHAR2(255 BYTE), 
			"START_FRAME" NUMBER(*,0), 
			"END_FRAME" NUMBER(*,0), 
			"START_TC" VARCHAR2(11 BYTE), 
			"TITLE" VARCHAR2(255 BYTE), 
			"COMMENTS" VARCHAR2(500 BYTE), 
			"FILESIZE" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 185597952 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_SCENE"."SCENE_ID" IS \'카탈로그ID\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."MEDIA_ID" IS \'미디어ID\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."SHOW_ORDER" IS \'노출순서\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."PATH" IS \'파일경로\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."START_FRAME" IS \'시작프레임\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."END_FRAME" IS \'종료프레임\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."START_TC" IS \'시작TC\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."TITLE" IS \'제목
		title_xxx 로 구성\';
		 
		   COMMENT ON COLUMN "BC_SCENE"."COMMENTS" IS \'설명\';
		--------------------------------------------------------
		--  DDL for Table BC_STORAGE
		--------------------------------------------------------

		  CREATE TABLE "BC_STORAGE" 
		   (	"STORAGE_ID" NUMBER, 
			"TYPE" VARCHAR2(10 BYTE), 
			"LOGIN_ID" VARCHAR2(255 BYTE), 
			"LOGIN_PW" VARCHAR2(255 BYTE), 
			"PATH" VARCHAR2(255 BYTE), 
			"NAME" VARCHAR2(255 BYTE), 
			"GROUP_NAME" VARCHAR2(50 BYTE), 
			"MAC_ADDRESS" CHAR(17 BYTE), 
			"AUTHORITY" VARCHAR2(10 BYTE), 
			"DESCRIBE" VARCHAR2(255 BYTE), 
			"DESCRIPTION" VARCHAR2(255 BYTE), 
			"VIRTUAL_PATH" VARCHAR2(255 BYTE), 
			"PATH_FOR_MAC" VARCHAR2(255 BYTE), 
			"PATH_FOR_UNIX" VARCHAR2(255 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_STORAGE"."STORAGE_ID" IS \'스토리지ID\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."TYPE" IS \'스토리지접속유형
		FTP,SAN,NAS\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."LOGIN_ID" IS \'로그인ID\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."LOGIN_PW" IS \'로그인PW\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."PATH" IS \'스토리지경로\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."NAME" IS \'스토리지명\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."GROUP_NAME" IS \'그룹명
		예:NPS\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."MAC_ADDRESS" IS \'맥어드레스\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."AUTHORITY" IS \'권한
		예:r/w\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."DESCRIBE" IS \'절차\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."DESCRIPTION" IS \'설명\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."VIRTUAL_PATH" IS \'가상경로\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."PATH_FOR_MAC" IS \'MAC경로\';
		 
		   COMMENT ON COLUMN "BC_STORAGE"."PATH_FOR_UNIX" IS \'UNIX경로\';
		--------------------------------------------------------
		--  DDL for Table BC_STORAGE_MANAGEMENT
		--------------------------------------------------------

		  CREATE TABLE "BC_STORAGE_MANAGEMENT" 
		   (	"CATEGORY_ID" NUMBER, 
			"TYPE" VARCHAR2(10 BYTE) DEFAULT \'AD\', 
			"PATH" VARCHAR2(255 BYTE), 
			"QUOTA" NUMBER, 
			"USAGE" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER, 
			"STORAGE_GROUP" VARCHAR2(20 BYTE), 
			"USING_REVIEW" VARCHAR2(20 BYTE), 
			"UD_STORAGE_GROUP_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."CATEGORY_ID" IS \'카테고리ID\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."TYPE" IS \'스토리지 구분
		기본값: AD\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."PATH" IS \'파일경로명
		폴더명\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."QUOTA" IS \'스토리지할당량\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."USAGE" IS \'스토리지할당량중사용량\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."MEMBER_GROUP_ID" IS \'회원그룹ID\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."STORAGE_GROUP" IS \'스토리지그룹\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."USING_REVIEW" IS \'USING_REVIEW\';
		 
		   COMMENT ON COLUMN "BC_STORAGE_MANAGEMENT"."UD_STORAGE_GROUP_ID" IS \'사용자정의스토리지그룹ID\';
		--------------------------------------------------------
		--  DDL for Table BC_SYS_CODE
		--------------------------------------------------------

		  CREATE TABLE "BC_SYS_CODE" 
		   (	"ID" NUMBER, 
			"CODE" VARCHAR2(200 BYTE), 
			"CODE_NM" VARCHAR2(4000 BYTE), 
			"TYPE_ID" NUMBER, 
			"SORT" NUMBER, 
			"USE_YN" CHAR(1 BYTE) DEFAULT \'Y\', 
			"MEMO" VARCHAR2(4000 BYTE), 
			"REF1" VARCHAR2(200 BYTE), 
			"REF2" VARCHAR2(200 BYTE), 
			"REF3" VARCHAR2(200 BYTE), 
			"REF4" VARCHAR2(200 BYTE), 
			"REF5" VARCHAR2(200 BYTE), 
			"CREATED_DATE" VARCHAR2(14 BYTE), 
			"CREATED_USER" NUMBER, 
			"UPDATED_DATE" VARCHAR2(14 BYTE), 
			"UPDATED_USER" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 0 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_SYS_CODE"."ID" IS \'테이블고유ID\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."CODE" IS \'코드\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."CODE_NM" IS \'코드명(값)\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."TYPE_ID" IS \'코드유형ID\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."SORT" IS \'정렬\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."USE_YN" IS \'사용여부\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."MEMO" IS \'설명\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."REF1" IS \'추가항목1\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."REF2" IS \'추가항목2\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."REF3" IS \'추가항목3\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."REF4" IS \'추가항목4\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."REF5" IS \'추가항목5\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."CREATED_USER" IS \'생성자\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."UPDATED_DATE" IS \'수정일시\';
		 
		   COMMENT ON COLUMN "BC_SYS_CODE"."UPDATED_USER" IS \'수정자\';
		 
		   COMMENT ON TABLE "BC_SYS_CODE"  IS \'사용자정의 코드관리\';
		--------------------------------------------------------
		--  DDL for Table BC_SYS_META_FIELD
		--------------------------------------------------------

		  CREATE TABLE "BC_SYS_META_FIELD" 
		   (	"BS_CONTENT_ID" NUMBER, 
			"SYS_META_FIELD_ID" NUMBER, 
			"SYS_META_FIELD_TITLE" VARCHAR2(200 BYTE), 
			"FIELD_INPUT_TYPE" VARCHAR2(100 BYTE), 
			"SHOW_ORDER" NUMBER, 
			"IS_REQUIRED" CHAR(1 BYTE), 
			"EDITABLE" CHAR(1 BYTE), 
			"IS_SHOW" CHAR(1 BYTE), 
			"IS_VISIBLE" CHAR(1 BYTE) DEFAULT \'Y\', 
			"DEFAULT_VALUE" VARCHAR2(500 BYTE), 
			"DESCRIPTION" VARCHAR2(500 BYTE), 
			"SUMMARY_FIELD_CD" NUMBER, 
			"SYS_META_FIELD_CODE" VARCHAR2(1000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_SYS_META_FIELD"."DESCRIPTION" IS \'설명(신규추가임)\';
		--------------------------------------------------------
		--  DDL for Table BC_SYS_META_VALUE
		--------------------------------------------------------

		  CREATE TABLE "BC_SYS_META_VALUE" 
		   (	"CONTENT_ID" NUMBER, 
			"SYS_META_FIELD_ID" NUMBER, 
			"SYS_META_VALUE_ID" NUMBER, 
			"SYS_META_VALUE" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 42991616 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_SYSMETA_DOCUMENT
		--------------------------------------------------------

		  CREATE TABLE "BC_SYSMETA_DOCUMENT" 
		   (	"SYS_CONTENT_ID" NUMBER, 
			"SYS_DOCUMENT_FORMAT" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_SYSMETA_IMAGE
		--------------------------------------------------------

		  CREATE TABLE "BC_SYSMETA_IMAGE" 
		   (	"SYS_CONTENT_ID" NUMBER, 
			"SYS_IMAGE_FORMAT" VARCHAR2(4000 BYTE), 
			"SYS_RESOLUTION" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_SYSMETA_MOVIE
		--------------------------------------------------------

		  CREATE TABLE "BC_SYSMETA_MOVIE" 
		   (	"SYS_CONTENT_ID" NUMBER, 
			"SYS_FRAME_RATE" VARCHAR2(4000 BYTE), 
			"SYS_VIDEO_RT" VARCHAR2(4000 BYTE), 
			"SYS_FILENAME" VARCHAR2(4000 BYTE), 
			"SYS_STORAGE" VARCHAR2(4000 BYTE), 
			"SYS_DISPLAY_SIZE" VARCHAR2(4000 BYTE), 
			"SYS_VIDEO_CODEC" VARCHAR2(4000 BYTE), 
			"SYS_AUDIO_CODEC" VARCHAR2(4000 BYTE), 
			"SYS_VIDEO_BITRATE" VARCHAR2(4000 BYTE), 
			"SYS_AUDIO_BITRATE" VARCHAR2(4000 BYTE), 
			"SYS_FILESIZE" VARCHAR2(4000 BYTE), 
			"SYS_AUDIO_CHANNEL" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 10485760 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_SYSMETA_SEQUENCE
		--------------------------------------------------------

		  CREATE TABLE "BC_SYSMETA_SEQUENCE" 
		   (	"SYS_CONTENT_ID" NUMBER, 
			"SYS_IMAGE_FORMAT" VARCHAR2(4000 BYTE), 
			"SYS_RESOLUTION" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_SYSMETA_SOUND
		--------------------------------------------------------

		  CREATE TABLE "BC_SYSMETA_SOUND" 
		   (	"SYS_CONTENT_ID" NUMBER, 
			"SYS_DURATION" VARCHAR2(4000 BYTE), 
			"SYS_AUDIO_CODEC" VARCHAR2(4000 BYTE), 
			"SYS_AUDIO_BITRATE" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TASK
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK" 
		   (	"MEDIA_ID" NUMBER, 
			"TASK_ID" NUMBER, 
			"TYPE" VARCHAR2(20 BYTE), 
			"SOURCE" VARCHAR2(500 BYTE), 
			"SOURCE_ID" VARCHAR2(255 BYTE), 
			"SOURCE_PW" VARCHAR2(255 BYTE), 
			"TARGET" VARCHAR2(500 BYTE), 
			"TARGET_ID" VARCHAR2(255 BYTE), 
			"TARGET_PW" VARCHAR2(255 BYTE), 
			"PARAMETER" VARCHAR2(200 BYTE), 
			"PROGRESS" NUMBER(*,0), 
			"STATUS" VARCHAR2(20 BYTE), 
			"PRIORITY" NUMBER(*,0), 
			"START_DATETIME" CHAR(14 BYTE), 
			"COMPLETE_DATETIME" CHAR(14 BYTE), 
			"CREATION_DATETIME" CHAR(14 BYTE), 
			"DESTINATION" VARCHAR2(40 BYTE), 
			"TASK_WORKFLOW_ID" NUMBER, 
			"JOB_PRIORITY" NUMBER, 
			"TASK_RULE_ID" NUMBER, 
			"ASSIGN_IP" VARCHAR2(50 BYTE), 
			"ROOT_TASK" NUMBER, 
			"TASK_USER_ID" VARCHAR2(20 BYTE), 
			"WORKFLOW_RULE_ID" NUMBER, 
			"SRC_CONTENT_ID" NUMBER, 
			"SRC_MEDIA_ID" NUMBER, 
			"SRC_STORAGE_ID" NUMBER, 
			"TRG_STORAGE_ID" NUMBER, 
			"TRG_MEDIA_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 369098752 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TASK_AVAILABLE
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_AVAILABLE" 
		   (	"MODULE_INFO_ID" NUMBER, 
			"TASK_RULE_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TASK_LOG
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_LOG" 
		   (	"TASK_LOG_ID" NUMBER, 
			"TASK_ID" NUMBER, 
			"DESCRIPTION" VARCHAR2(1000 BYTE), 
			"CREATION_DATE" CHAR(14 BYTE), 
			"STATUS" VARCHAR2(20 BYTE), 
			"PROGRESS" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 587202560 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_TASK_LOG"."TASK_ID" IS \'작업 UID\';
		 
		   COMMENT ON COLUMN "BC_TASK_LOG"."DESCRIPTION" IS \'설명\';
		--------------------------------------------------------
		--  DDL for Table BC_TASK_RULE
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_RULE" 
		   (	"TASK_RULE_ID" NUMBER, 
			"JOB_NAME" VARCHAR2(1000 BYTE), 
			"PARAMETER" VARCHAR2(260 BYTE), 
			"SOURCE_PATH" VARCHAR2(500 BYTE), 
			"TARGET_PATH" VARCHAR2(500 BYTE), 
			"TASK_TYPE_ID" NUMBER, 
			"SOURCE_OPT" VARCHAR2(255 BYTE), 
			"TARGET_OPT" VARCHAR2(255 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TASK_STORAGE
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_STORAGE" 
		   (	"TASK_ID" NUMBER, 
			"SRC_MEDIA_ID" NUMBER, 
			"TRG_MEDIA_ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"SRC_STORAGE_ID" NUMBER, 
			"TRG_STORAGE_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TASK_TYPE
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_TYPE" 
		   (	"TASK_TYPE_ID" NUMBER, 
			"TYPE" VARCHAR2(5 BYTE), 
			"NAME" VARCHAR2(50 BYTE), 
			"SHOW_ORDER" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_TASK_TYPE"."TASK_TYPE_ID" IS \'작업유형ID\';
		 
		   COMMENT ON COLUMN "BC_TASK_TYPE"."TYPE" IS \'작업유형\';
		 
		   COMMENT ON COLUMN "BC_TASK_TYPE"."NAME" IS \'작업유형명\';
		 
		   COMMENT ON COLUMN "BC_TASK_TYPE"."SHOW_ORDER" IS \'노출순서\';
		--------------------------------------------------------
		--  DDL for Table BC_TASK_WORKFLOW
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_WORKFLOW" 
		   (	"TASK_WORKFLOW_ID" NUMBER, 
			"USER_TASK_NAME" VARCHAR2(260 BYTE), 
			"REGISTER" VARCHAR2(260 BYTE), 
			"ACTIVITY" CHAR(1 BYTE) DEFAULT 0, 
			"DESCRIPTION" VARCHAR2(255 BYTE), 
			"CONTENT_STATUS" NUMBER, 
			"TYPE" VARCHAR2(100 CHAR), 
			"ICON_URL" VARCHAR2(100 BYTE), 
			"PRESET_TYPE" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_TASK_WORKFLOW"."TYPE" IS \'i-ingest / c-contextmenu / p-preset\';
		--------------------------------------------------------
		--  DDL for Table BC_TASK_WORKFLOW_RULE
		--------------------------------------------------------

		  CREATE TABLE "BC_TASK_WORKFLOW_RULE" 
		   (	"TASK_WORKFLOW_ID" NUMBER, 
			"TASK_RULE_ID" NUMBER, 
			"JOB_PRIORITY" NUMBER, 
			"WORKFLOW_RULE_ID" NUMBER, 
			"CONDITION" VARCHAR2(2000 BYTE), 
			"TASK_RULE_PARANT_ID" NUMBER DEFAULT 0, 
			"CONTENT_STATUS" NUMBER, 
			"SOURCE_PATH_ID" NUMBER, 
			"TARGET_PATH_ID" NUMBER, 
			"STORAGE_GROUP" NUMBER, 
			"WORKFLOW_RULE_PARENT_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TOP_MENU
		--------------------------------------------------------

		  CREATE TABLE "BC_TOP_MENU" 
		   (	"ID" NUMBER, 
			"MENU_NAME" VARCHAR2(20 BYTE), 
			"SORT" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_TOP_MENU_GRANT
		--------------------------------------------------------

		  CREATE TABLE "BC_TOP_MENU_GRANT" 
		   (	"MENU_ID" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_UD_CONTENT
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_CONTENT" 
		   (	"BS_CONTENT_ID" NUMBER, 
			"UD_CONTENT_ID" NUMBER, 
			"SHOW_ORDER" NUMBER(*,0), 
			"UD_CONTENT_TITLE" VARCHAR2(255 BYTE), 
			"ALLOWED_EXTENSION" VARCHAR2(255 BYTE), 
			"DESCRIPTION" VARCHAR2(255 BYTE), 
			"CREATED_DATE" CHAR(14 BYTE), 
			"EXPIRE_DATE" NUMBER, 
			"SMEF_DM" VARCHAR2(128 BYTE), 
			"EXPIRED_DATE" VARCHAR2(14 BYTE), 
			"STORAGE_ID" NUMBER, 
			"CON_EXPIRE_DATE" VARCHAR2(14 BYTE), 
			"UD_CONTENT_CODE" VARCHAR2(1000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_CONTENT"."BS_CONTENT_ID" IS \'기본콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."UD_CONTENT_ID" IS \'사용자정의 콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."SHOW_ORDER" IS \'노출순서\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."UD_CONTENT_TITLE" IS \'사용자정의 콘텐츠명\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."ALLOWED_EXTENSION" IS \'허용 확장자\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."DESCRIPTION" IS \'설명\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."CREATED_DATE" IS \'생성일시\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."EXPIRE_DATE" IS \'EXPIRE_DATE
		사용안함\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."SMEF_DM" IS \'SMEF_DM
		사용안함\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."EXPIRED_DATE" IS \'폐기만료기한\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."STORAGE_ID" IS \'스토리지ID\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."CON_EXPIRE_DATE" IS \'콘텐츠삭제기한\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT"."UD_CONTENT_CODE" IS \'사용자정의콘텐츠코드
		테이블명\';
		 
		   COMMENT ON TABLE "BC_UD_CONTENT"  IS \'사용자 정의 콘텐츠\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_CONTENT_DELETE_INFO
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_CONTENT_DELETE_INFO" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"TYPE_CODE" VARCHAR2(10 BYTE), 
			"DATE_CODE" VARCHAR2(10 BYTE), 
			"CODE_TYPE" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_CONTENT_DELETE_INFO"."UD_CONTENT_ID" IS \'사용자정의콘텐츠ID\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_DELETE_INFO"."TYPE_CODE" IS \'유형 코드
		30 : 30일
		60 : 60일
		-1 : 영구보존\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_DELETE_INFO"."DATE_CODE" IS \'일자 코드
		30 : 30일
		60 : 60일
		-1 : 영구보존\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_DELETE_INFO"."CODE_TYPE" IS \'코드유형
		BC_CODE_TYPE.CODE
		UCDDDT	콘텐츠 별 삭제기한
		UCSDDT	사용자콘텐츠 삭제기한\';
		 
		   COMMENT ON TABLE "BC_UD_CONTENT_DELETE_INFO"  IS \'사용자 정의 콘텐츠 삭제 정책\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_CONTENT_GRANT
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_CONTENT_GRANT" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"GRANTED_RIGHT" VARCHAR2(30 BYTE), 
			"MEMBER_GROUP_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_CONTENT_GRANT"."UD_CONTENT_ID" IS \'사용자 정의 콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_GRANT"."GRANTED_RIGHT" IS \'부여 권한
		regist
		delete
		download
		read
		modify\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_GRANT"."MEMBER_GROUP_ID" IS \'사용자 그룹 아이디\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_CONTENT_STORAGE
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_CONTENT_STORAGE" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"STORAGE_ID" NUMBER, 
			"US_TYPE" VARCHAR2(100 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_CONTENT_STORAGE"."UD_CONTENT_ID" IS \'사용자 정의 콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_STORAGE"."STORAGE_ID" IS \'스토리지 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_STORAGE"."US_TYPE" IS \'스토리지 유형
		download
		highres
		lowres
		upload\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_CONTENT_TAB
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_CONTENT_TAB" 
		   (	"CATEGORY_ID" NUMBER, 
			"NAME" VARCHAR2(20 BYTE) DEFAULT \'program\'
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_CONTENT_TAB"."CATEGORY_ID" IS \'카테고리 ID\';
		 
		   COMMENT ON COLUMN "BC_UD_CONTENT_TAB"."NAME" IS \'topic, program 등 해당 카테고리가 속한 그룹\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_GROUP
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_GROUP" 
		   (	"UD_GROUP_CODE" NUMBER, 
			"UD_CONTENT_ID" NUMBER, 
			"ROOT_CATEGORY_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_GROUP"."ROOT_CATEGORY_ID" IS \'유형별 루트 카테고리\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_STORAGE_GROUP
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_STORAGE_GROUP" 
		   (	"STORAGE_GROUP_ID" NUMBER, 
			"STORAGE_GROUP_NM" VARCHAR2(1000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_STORAGE_GROUP"."STORAGE_GROUP_ID" IS \'스토리지 그룹 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_STORAGE_GROUP"."STORAGE_GROUP_NM" IS \'스토리지 그룹 아이디\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_STORAGE_GROUP_MAP
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_STORAGE_GROUP_MAP" 
		   (	"STORAGE_GROUP_ID" NUMBER, 
			"SOURCE_STORAGE_ID" NUMBER, 
			"UD_STORAGE_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_UD_STORAGE_GROUP_MAP"."STORAGE_GROUP_ID" IS \'스토리지 그룹 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_STORAGE_GROUP_MAP"."SOURCE_STORAGE_ID" IS \'소스 스토리지 아이디\';
		 
		   COMMENT ON COLUMN "BC_UD_STORAGE_GROUP_MAP"."UD_STORAGE_ID" IS \'사용자정의 스토리지 아이디\';
		--------------------------------------------------------
		--  DDL for Table BC_UD_SYSTEM
		--------------------------------------------------------

		  CREATE TABLE "BC_UD_SYSTEM" 
		   (	"CONTENT_ID" NUMBER, 
			"UD_SYSTEM_CODE" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_USR_META_CODE
		--------------------------------------------------------

		  CREATE TABLE "BC_USR_META_CODE" 
		   (	"USR_META_FIELD_CODE" VARCHAR2(1000 BYTE), 
			"USR_CODE_KEY" VARCHAR2(1000 BYTE), 
			"USR_CODE_VALUE" VARCHAR2(4000 BYTE), 
			"SHOW_ORDER" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_USR_META_CODE"."USR_META_FIELD_CODE" IS \'사용자 정의 필드 의 CODE 값\';
		 
		   COMMENT ON COLUMN "BC_USR_META_CODE"."USR_CODE_KEY" IS \'코드 값\';
		 
		   COMMENT ON COLUMN "BC_USR_META_CODE"."USR_CODE_VALUE" IS \'코드 내용\';
		 
		   COMMENT ON COLUMN "BC_USR_META_CODE"."SHOW_ORDER" IS \'노출 순서\';
		--------------------------------------------------------
		--  DDL for Table BC_USR_META_FIELD
		--------------------------------------------------------

		  CREATE TABLE "BC_USR_META_FIELD" 
		   (	"UD_CONTENT_ID" NUMBER, 
			"USR_META_FIELD_ID" NUMBER, 
			"SHOW_ORDER" NUMBER(*,0), 
			"USR_META_FIELD_TITLE" VARCHAR2(255 BYTE), 
			"USR_META_FIELD_TYPE" VARCHAR2(255 BYTE), 
			"IS_REQUIRED" CHAR(1 BYTE) DEFAULT \'N\', 
			"IS_EDITABLE" CHAR(1 BYTE) DEFAULT \'Y\', 
			"IS_SHOW" CHAR(1 BYTE) DEFAULT \'Y\', 
			"IS_SEARCH_REG" CHAR(1 BYTE) DEFAULT \'Y\', 
			"DEFAULT_VALUE" VARCHAR2(255 BYTE), 
			"CONTAINER_ID" NUMBER, 
			"DEPTH" NUMBER, 
			"META_GROUP_TYPE" CHAR(3 BYTE), 
			"DESCIPTION" VARCHAR2(500 BYTE), 
			"SMEF_DM_CODE" VARCHAR2(128 BYTE), 
			"SUMMARY_FIELD_CD" NUMBER, 
			"USR_META_FIELD_CODE" VARCHAR2(1000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "BC_USR_META_FIELD"."UD_CONTENT_ID" IS \'사용자정의 콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."USR_META_FIELD_ID" IS \'사용자 메타 필드 아이디\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."SHOW_ORDER" IS \'노출 순서\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."USR_META_FIELD_TITLE" IS \'사용자 메타 필드 제목\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."USR_META_FIELD_TYPE" IS \'사용자 메타 필드 유형
		textarea
		datefield
		combo
		container\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."IS_REQUIRED" IS \'필수 항목 여부\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."IS_EDITABLE" IS \'수정 허용 여부\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."IS_SHOW" IS \'노출 여부\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."IS_SEARCH_REG" IS \'검색 허용 여부\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."DEFAULT_VALUE" IS \'디폴트 값\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."CONTAINER_ID" IS \'컨테이너 아이디\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."DEPTH" IS \'컨테이너 구분
		0 : 컨테이너 그룹
		1 : 컨테이너 내 메타 필드 항목\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."META_GROUP_TYPE" IS \'메타 그룹 유형\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."DESCIPTION" IS \'설명\';
		 
		   COMMENT ON COLUMN "BC_USR_META_FIELD"."USR_META_FIELD_CODE" IS \'사용자 메타 필드 영문명\';



		   CREATE TABLE "BC_USR_META_VALUE" 
		   (	"CONTENT_ID" NUMBER NOT NULL ENABLE, 
			"UD_CONTENT_ID" NUMBER NOT NULL ENABLE, 
			"USR_META_FIELD_ID" NUMBER NOT NULL ENABLE, 
			"USR_META_VALUE_ID" NUMBER NOT NULL ENABLE, 
			"USR_META_VALUE" VARCHAR2(4000 CHAR)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 58720256 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		;
		
		--------------------------------------------------------
		--  파일이 생성됨 - 목요일-2월-11-2016   
		--------------------------------------------------------
		--------------------------------------------------------
		--  DDL for Table BC_USRMETA_DOCUMENT
		--------------------------------------------------------

		  CREATE TABLE "BC_USRMETA_DOCUMENT" 
		   (	"USR_CONTENT_ID" NUMBER, 
			"USR_TITLE" VARCHAR2(4000 BYTE), 
			"USR_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_USRMETA_EDIT
		--------------------------------------------------------

		  CREATE TABLE "BC_USRMETA_EDIT" 
		   (	"USR_CONTENT_ID" NUMBER, 
			"USR_TITLE" VARCHAR2(4000 BYTE), 
			"USR_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_USRMETA_IMAGE
		--------------------------------------------------------

		  CREATE TABLE "BC_USRMETA_IMAGE" 
		   (	"USR_CONTENT_ID" NUMBER, 
			"USR_TITLE" VARCHAR2(4000 BYTE), 
			"USR_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_USRMETA_MASTER
		--------------------------------------------------------

		  CREATE TABLE "BC_USRMETA_MASTER" 
		   (	"USR_CONTENT_ID" NUMBER, 
			"USR_TITLE" VARCHAR2(4000 BYTE), 
			"USR_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_USRMETA_SOUND
		--------------------------------------------------------

		  CREATE TABLE "BC_USRMETA_SOUND" 
		   (	"USR_CONTENT_ID" NUMBER, 
			"USR_TITLE" VARCHAR2(4000 BYTE), 
			"USR_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Table BC_USRMETA_SOURCE
		--------------------------------------------------------

		  CREATE TABLE "BC_USRMETA_SOURCE" 
		   (	"USR_CONTENT_ID" NUMBER, 
			"USR_TITLE" VARCHAR2(4000 BYTE), 
			"USR_CONTENTS" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USRMETA_DOCUMENT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USRMETA_DOCUMENT_PK" ON "BC_USRMETA_DOCUMENT" ("USR_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USRMETA_EDIT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USRMETA_EDIT_PK" ON "BC_USRMETA_EDIT" ("USR_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USRMETA_IMAGE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USRMETA_IMAGE_PK" ON "BC_USRMETA_IMAGE" ("USR_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USRMETA_MASTER_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USRMETA_MASTER_PK" ON "BC_USRMETA_MASTER" ("USR_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USRMETA_SOUND_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USRMETA_SOUND_PK" ON "BC_USRMETA_SOUND" ("USR_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USRMETA_SOURCE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USRMETA_SOURCE_PK" ON "BC_USRMETA_SOURCE" ("USR_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		   ;
		--------------------------------------------------------
		--  Constraints for Table BC_USRMETA_DOCUMENT
		--------------------------------------------------------

		  ALTER TABLE "BC_USRMETA_DOCUMENT" ADD CONSTRAINT "BC_USRMETA_DOCUMENT_PK" PRIMARY KEY ("USR_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		  ;
		 
		  ALTER TABLE "BC_USRMETA_DOCUMENT" MODIFY ("USR_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USRMETA_EDIT
		--------------------------------------------------------

		  ALTER TABLE "BC_USRMETA_EDIT" ADD CONSTRAINT "BC_USRMETA_EDIT_PK" PRIMARY KEY ("USR_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		  ;
		 
		  ALTER TABLE "BC_USRMETA_EDIT" MODIFY ("USR_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USRMETA_IMAGE
		--------------------------------------------------------

		  ALTER TABLE "BC_USRMETA_IMAGE" ADD CONSTRAINT "BC_USRMETA_IMAGE_PK" PRIMARY KEY ("USR_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		  ;
		 
		  ALTER TABLE "BC_USRMETA_IMAGE" MODIFY ("USR_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USRMETA_MASTER
		--------------------------------------------------------

		  ALTER TABLE "BC_USRMETA_MASTER" ADD CONSTRAINT "BC_USRMETA_MASTER_PK" PRIMARY KEY ("USR_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		  ;
		 
		  ALTER TABLE "BC_USRMETA_MASTER" MODIFY ("USR_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USRMETA_SOUND
		--------------------------------------------------------

		  ALTER TABLE "BC_USRMETA_SOUND" ADD CONSTRAINT "BC_USRMETA_SOUND_PK" PRIMARY KEY ("USR_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		  ;
		 
		  ALTER TABLE "BC_USRMETA_SOUND" MODIFY ("USR_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USRMETA_SOURCE
		--------------------------------------------------------

		  ALTER TABLE "BC_USRMETA_SOURCE" ADD CONSTRAINT "BC_USRMETA_SOURCE_PK" PRIMARY KEY ("USR_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 NOCOMPRESS LOGGING
		  ;
		 
		  ALTER TABLE "BC_USRMETA_SOURCE" MODIFY ("USR_CONTENT_ID" NOT NULL ENABLE);

		
		--------------------------------------------------------
		--  DDL for Table CONTENT_CODE_INFO
		--------------------------------------------------------

		  CREATE TABLE "CONTENT_CODE_INFO" 
		   (	"CONTENT_ID" NUMBER, 
			"MEDCD" VARCHAR2(20 BYTE), 
			"PROGCD" VARCHAR2(20 BYTE), 
			"SUBPROGCD" VARCHAR2(20 BYTE), 
			"PROGPARNTCD" VARCHAR2(20 BYTE), 
			"FORMBASEYMD" VARCHAR2(20 BYTE), 
			"BRODYMD" VARCHAR2(20 BYTE), 
			"FILE_CREATED_DATE" VARCHAR2(14 BYTE), 
			"IS_OVERLAY" VARCHAR2(1 BYTE), 
			"DATAGRADE" VARCHAR2(100 BYTE), 
			"STORTERM" VARCHAR2(100 BYTE), 
			"REGISTER_TYPE" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 6291456 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."MEDCD" IS \'매체코드 정보\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."PROGCD" IS \'프로그램코드 정보\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."SUBPROGCD" IS \'부제코드 정보\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."PROGPARNTCD" IS \'프로그램모코드정보\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."FORMBASEYMD" IS \'편성일자 정보\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."BRODYMD" IS \'방송일자 정보\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."FILE_CREATED_DATE" IS \'원본파일 생성일자\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."IS_OVERLAY" IS \'오버레이트랜스코딩 여부 1 = true\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."DATAGRADE" IS \'다스 전송시 자료등급\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."STORTERM" IS \'다스 전송시 보관기간\';
		 
		   COMMENT ON COLUMN "CONTENT_CODE_INFO"."REGISTER_TYPE" IS \'콘텐츠 등록 구분(인제스트-I , NLE Export : E, E-DAS 다운로드 : D , CM(DMC전송) - C ) \';
		--------------------------------------------------------
		--  DDL for Table CONTEXT_MENU
		--------------------------------------------------------

		  CREATE TABLE "CONTEXT_MENU" 
		   (	"CODE_ID" NUMBER, 
			"WORKFLOW_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table CT_AD_JOB
		--------------------------------------------------------

		  CREATE TABLE "CT_AD_JOB" 
		   (	"NO" NUMBER, 
			"TYPE" NUMBER, 
			"USER_NAME" VARCHAR2(20 BYTE), 
			"ID" VARCHAR2(20 BYTE), 
			"FLAG" NUMBER, 
			"GROUP_NAME" VARCHAR2(100 BYTE), 
			"DC" VARCHAR2(100 BYTE), 
			"PW" VARCHAR2(20 BYTE), 
			"STORAGE_PATH" VARCHAR2(20 BYTE), 
			"ISCSI_PATH" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "CT_AD_JOB"."NO" IS \'자동 생성 넘버\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."TYPE" IS \'1 - Add User, 2 - Delete User, 3 - Add Group,
		4 - Delete Group, 5 - Add User to Group, 6 - Delete User From Group\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."USER_NAME" IS \'사용자 이름\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."ID" IS \'그룹 또는 사용자 ID\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."FLAG" IS \'0 - 작업대기, 1 - 작업성공, 2 - 작업실패\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."GROUP_NAME" IS \'그룹명\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."DC" IS \'도메인 명\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."PW" IS \'사용자 일경우 패스워드\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."STORAGE_PATH" IS \'스토리지 명\';
		 
		   COMMENT ON COLUMN "CT_AD_JOB"."ISCSI_PATH" IS \'볼륨명\';
		--------------------------------------------------------
		--  DDL for Table DELETE_CONTENT_LIST
		--------------------------------------------------------

		  CREATE TABLE "DELETE_CONTENT_LIST" 
		   (	"CONTENT_ID" NUMBER, 
			"USER_ID" VARCHAR2(255 BYTE), 
			"REASON" VARCHAR2(4000 BYTE), 
			"CREATED_DATE" VARCHAR2(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table EBS_INTERFACE
		--------------------------------------------------------

		  CREATE TABLE "EBS_INTERFACE" 
		   (	"INTERFACE_ID" NUMBER, 
			"INTERFACE_TYPE" VARCHAR2(20 BYTE), 
			"TARGET_ID" NUMBER, 
			"INTERFACE_USER_ID" VARCHAR2(100 BYTE), 
			"CREATE_DATE" VARCHAR2(20 BYTE), 
			"CONTENT_ID" NUMBER, 
			"MEDIA_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "EBS_INTERFACE"."INTERFACE_ID" IS \'고유 아이디\';
		 
		   COMMENT ON COLUMN "EBS_INTERFACE"."INTERFACE_TYPE" IS \'참조 유형
		일반 작업,에이전트 작업 , 타 시스템 작업 구분
		NPS_WORK,NPS_TASK,DMC_TASK,DAS_TASK
		\';
		 
		   COMMENT ON COLUMN "EBS_INTERFACE"."TARGET_ID" IS \'연동 작업 아이디
		작업리스트아이디, 에이전트작업아이디, 타 시스템 인터페이스아이디
		nps_work_list_id,task_id,interface_id
		\';
		 
		   COMMENT ON COLUMN "EBS_INTERFACE"."INTERFACE_USER_ID" IS \'작업자\';
		 
		   COMMENT ON COLUMN "EBS_INTERFACE"."CREATE_DATE" IS \'생성일자
		\';
		--------------------------------------------------------
		--  DDL for Table EXPORTER_SETTING
		--------------------------------------------------------

		  CREATE TABLE "EXPORTER_SETTING" 
		   (	"ID" NUMBER, 
			"NAME" VARCHAR2(50 BYTE), 
			"VBITRATE" NUMBER, 
			"RENDER_ENGINE_SD" NUMBER, 
			"RENDER_ENGINE_HD" NUMBER, 
			"OUTPUT_PATH" VARCHAR2(260 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table IM_SCHEDULE_METADATA
		--------------------------------------------------------

		  CREATE TABLE "IM_SCHEDULE_METADATA" 
		   (	"SCHEDULE_ID" NUMBER, 
			"META_TABLE_ID" NUMBER, 
			"META_FIELD_ID" NUMBER, 
			"META_VALUE" VARCHAR2(4000 BYTE), 
			"UD_CONTENT_ID" NUMBER, 
			"BC_USR_META_FIELD_ID" NUMBER, 
			"USR_META_VALUE" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table INGESTMANAGER_SCHEDULE
		--------------------------------------------------------

		  CREATE TABLE "INGESTMANAGER_SCHEDULE" 
		   (	"SCHEDULE_ID" NUMBER, 
			"INGEST_SYSTEM_IP" VARCHAR2(50 BYTE), 
			"CHANNEL" VARCHAR2(260 BYTE), 
			"SCHEDULE_TYPE" VARCHAR2(20 BYTE), 
			"DATE_TIME" VARCHAR2(20 BYTE), 
			"START_TIME" VARCHAR2(6 BYTE), 
			"DURATION" NUMBER, 
			"CREATE_TIME" VARCHAR2(14 BYTE), 
			"STATUS" VARCHAR2(5 BYTE) DEFAULT 0, 
			"CATEGORY_ID" NUMBER DEFAULT 0, 
			"CONTENT_TYPE_ID" NUMBER, 
			"META_TABLE_ID" NUMBER, 
			"TITLE" VARCHAR2(300 BYTE), 
			"USER_ID" VARCHAR2(25 BYTE), 
			"IS_USE" VARCHAR2(1 BYTE) DEFAULT 1, 
			"UD_CONTENT_ID" NUMBER, 
			"BS_CONTENT_ID" NUMBER, 
			"UD_CONTENT_TAB" VARCHAR2(20 BYTE), 
			"CRON" VARCHAR2(200 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."SCHEDULE_ID" IS \'스케줄 ID\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."INGEST_SYSTEM_IP" IS \'인제스트 시스템 IP\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."CHANNEL" IS \'작업 워크플로우 채널\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."SCHEDULE_TYPE" IS \'스케줄 타입
		0 : 일회성 스케줄
		1 : 일 단위 반복 스케줄
		2 : 요일 단위 반복 스케줄
		3 : 월 단위 반복 스케줄\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."DATE_TIME" IS \'스케줄 타입에 따른 일자
		요일시 : 0 ~ 6 , 일요일 ~ 토요일
		이외 해당하는 일자 및 월\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."START_TIME" IS \'시작시간
		시분초 (6자리)\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."DURATION" IS \'초단위 듀레이션\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."CREATE_TIME" IS \'스케줄 등록시간\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."STATUS" IS \'스케줄 작업상태\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."CATEGORY_ID" IS \'카테고리 아이디\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."CONTENT_TYPE_ID" IS \'시스템 메타 유형 아이디\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."META_TABLE_ID" IS \'콘텐츠 유형 아이디\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."TITLE" IS \'제목\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."USER_ID" IS \'등록자\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."IS_USE" IS \'스케줄 사용여부
		1: 사용
		0: 사용안함\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."UD_CONTENT_ID" IS \'사용자 정의 컨텐츠\';
		 
		   COMMENT ON COLUMN "INGESTMANAGER_SCHEDULE"."BS_CONTENT_ID" IS \'컨텐츠 유형(동영상,사운드 등)\';
		--------------------------------------------------------
		--  DDL for Table INGESTMANAGER_SCHEDULE_META
		--------------------------------------------------------

		  CREATE TABLE "INGESTMANAGER_SCHEDULE_META" 
		   (	"SCHEDULE_ID" NUMBER, 
			"USR_PROGRAM" VARCHAR2(4000 BYTE), 
			"USR_PROG_ID" VARCHAR2(4000 BYTE), 
			"USR_MATERIALID" VARCHAR2(4000 BYTE), 
			"USR_TURN" VARCHAR2(4000 BYTE), 
			"USR_SUBPROG" VARCHAR2(4000 BYTE), 
			"USR_BROD_DATE" VARCHAR2(4000 BYTE), 
			"USR_CONTENT" VARCHAR2(4000 BYTE), 
			"USR_MEDNM" VARCHAR2(4000 BYTE), 
			"USR_PRODUCER" VARCHAR2(4000 BYTE), 
			"USR_GRADE" VARCHAR2(4000 BYTE), 
			"USR_KEYWORD" VARCHAR2(4000 BYTE), 
			"USR_INGEST_DATE" VARCHAR2(4000 BYTE), 
			"USR_INGEST_WORKER" VARCHAR2(4000 BYTE), 
			"USR_INGEST_SYSTEM" VARCHAR2(4000 BYTE), 
			"USR_NOT_SIGNIFICANT" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table INTERFACE
		--------------------------------------------------------

		  CREATE TABLE "INTERFACE" 
		   (	"INTERFACE_ID" NUMBER, 
			"INTERFACE_TITLE" VARCHAR2(1000 BYTE), 
			"INTERFACE_FROM_TYPE" VARCHAR2(100 BYTE), 
			"INTERFACE_FROM_ID" VARCHAR2(1000 BYTE), 
			"INTERFACE_TARGET_TYPE" VARCHAR2(100 BYTE), 
			"INTERFACE_TARGET_ID" VARCHAR2(1000 BYTE), 
			"INTERFACE_BASE_CONTENT_ID" NUMBER, 
			"CREATE_DATE" VARCHAR2(14 BYTE), 
			"INTERFACE_WORK_TYPE" VARCHAR2(20 BYTE), 
			"INTERFACE_WORKFLOW_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 7340032 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_ID" IS \'고유아이디\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_TITLE" IS \'인터페이스 명 대표 제목\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_FROM_TYPE" IS \'인터페이스 등록자 유형 USER ,GROUP\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_FROM_ID" IS \'인터페이스 등록자 user_id, group_id\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_TARGET_TYPE" IS \'인터페이스 대상자 유형 USER , GROUP\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_TARGET_ID" IS \'인터페이스 대상자 user_id, group_id\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_BASE_CONTENT_ID" IS \'인터페이스 기본 콘텐츠 ID content_id\';
		 
		   COMMENT ON COLUMN "INTERFACE"."CREATE_DATE" IS \'생성일자 YmdHis\';
		 
		   COMMENT ON COLUMN "INTERFACE"."INTERFACE_WORKFLOW_ID" IS \'워크플로우 id\';
		--------------------------------------------------------
		--  DDL for Table INTERFACE_CH
		--------------------------------------------------------

		  CREATE TABLE "INTERFACE_CH" 
		   (	"INTERFACE_ID" NUMBER, 
			"INTERFACE_CH_ID" NUMBER, 
			"INTERFACE_CHANNEL" VARCHAR2(100 BYTE), 
			"INTERFACE_TYPE" VARCHAR2(100 BYTE), 
			"TARGET_ID" NUMBER, 
			"TARGET_CONTENT_ID" NUMBER, 
			"CREATE_DATE" VARCHAR2(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 20971520 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "INTERFACE_CH"."INTERFACE_ID" IS \'인터페이스 고유 아이디 seq\';
		 
		   COMMENT ON COLUMN "INTERFACE_CH"."INTERFACE_CH_ID" IS \'하위 인터페이스 고유 아이디 seq\';
		 
		   COMMENT ON COLUMN "INTERFACE_CH"."INTERFACE_CHANNEL" IS \'인터페이스 채널명 NPS,DAS,DMC\';
		 
		   COMMENT ON COLUMN "INTERFACE_CH"."INTERFACE_TYPE" IS \'인터페이스 유형 WORK,TASK,INTERFACE\';
		 
		   COMMENT ON COLUMN "INTERFACE_CH"."TARGET_ID" IS \'인터페이스 대상 ID task_id,nps_work_id,interface_id\';
		 
		   COMMENT ON COLUMN "INTERFACE_CH"."TARGET_CONTENT_ID" IS \'인터페이스 대상 콘텐츠 ID content_id\';
		 
		   COMMENT ON COLUMN "INTERFACE_CH"."CREATE_DATE" IS \'생성일자 YmdHis\';
		--------------------------------------------------------
		--  DDL for Table LINK_CMS
		--------------------------------------------------------

		  CREATE TABLE "LINK_CMS" 
		   (	"LINK_CMS_ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"LINK_TYPE" VARCHAR2(100 BYTE), 
			"SOURCE" VARCHAR2(1000 BYTE), 
			"TARGET" VARCHAR2(1000 BYTE), 
			"CREATED_DATE" VARCHAR2(14 BYTE), 
			"XML" "SYS"."XMLTYPE" , 
			"TARGET_CONTENT_ID" NUMBER, 
			"STATUS" NUMBER, 
			"TASK_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 2097152 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   
		 XMLTYPE COLUMN "XML" STORE AS BASICFILE CLOB (
		   ENABLE STORAGE IN ROW CHUNK 8192 PCTVERSION 10
		  NOCACHE LOGGING 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)) ;
		--------------------------------------------------------
		--  DDL for Table LINK_CMS_TC_XML
		--------------------------------------------------------

		  CREATE TABLE "LINK_CMS_TC_XML" 
		   (	"LINK_CMS_ID" NUMBER, 
			"SHOW_ORDER" NUMBER, 
			"TC_XML" "SYS"."XMLTYPE" , 
			"LINK_CMS_TC_XML_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 7340032 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   
		 XMLTYPE COLUMN "TC_XML" STORE AS BASICFILE CLOB (
		   ENABLE STORAGE IN ROW CHUNK 8192 PCTVERSION 10
		  NOCACHE LOGGING 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)) ;
		--------------------------------------------------------
		--  DDL for Table LINK_XML_ERROR_LIST
		--------------------------------------------------------

		  CREATE TABLE "LINK_XML_ERROR_LIST" 
		   (	"LINK_XML_ERROR_LIST_ID" NUMBER, 
			"HOST" VARCHAR2(1000 BYTE), 
			"PORT" VARCHAR2(20 BYTE), 
			"PAGE" VARCHAR2(1000 BYTE), 
			"STATUS" VARCHAR2(20 BYTE), 
			"TRY" NUMBER, 
			"REQUEST_XML" "SYS"."XMLTYPE" , 
			"RESPONSE_XML" "SYS"."XMLTYPE" , 
			"LOG" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   
		 XMLTYPE COLUMN "REQUEST_XML" STORE AS BASICFILE CLOB (
		   ENABLE STORAGE IN ROW CHUNK 8192 PCTVERSION 10
		  NOCACHE LOGGING 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)) 
		 XMLTYPE COLUMN "RESPONSE_XML" STORE AS BASICFILE CLOB (
		   ENABLE STORAGE IN ROW CHUNK 8192 PCTVERSION 10
		  NOCACHE LOGGING 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)) ;
		--------------------------------------------------------
		--  DDL for Table LOG_SEQ_ID
		--------------------------------------------------------

		  CREATE TABLE "LOG_SEQ_ID" 
		   (	"TYPE" VARCHAR2(20 BYTE), 
			"HEADER" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table LOG_SOAP_PARAMS
		--------------------------------------------------------

		  CREATE TABLE "LOG_SOAP_PARAMS" 
		   (	"CREATED" DATE, 
			"LOG" VARCHAR2(2000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table MASTER_MONITOR
		--------------------------------------------------------

		  CREATE TABLE "MASTER_MONITOR" 
		   (	"MATERIAL_ID" VARCHAR2(20 BYTE), 
			"PROGRAM_ID" VARCHAR2(20 BYTE), 
			"STATUS" NUMBER DEFAULT 0, 
			"REQ_COMMENT" VARCHAR2(1000 BYTE), 
			"MAS_COMMENT" VARCHAR2(1000 BYTE), 
			"IS_APPR" VARCHAR2(5 BYTE) DEFAULT \'N\', 
			"PD_NM" VARCHAR2(20 BYTE), 
			"CREATED_DATE" VARCHAR2(20 BYTE), 
			"CONTENT_ID" NUMBER, 
			"PROGRESS" NUMBER DEFAULT 0, 
			"REQUEST_ID" NUMBER, 
			"SEND_USER_ID" VARCHAR2(20 BYTE), 
			"INTERFACE_ID" NUMBER, 
			"SEND_PHONE" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "MASTER_MONITOR"."STATUS" IS \'의뢰 상태\';
		 
		   COMMENT ON COLUMN "MASTER_MONITOR"."REQUEST_ID" IS \'고유ID\';
		 
		   COMMENT ON COLUMN "MASTER_MONITOR"."INTERFACE_ID" IS \'인터페이스 ID\';
		--------------------------------------------------------
		--  DDL for Table MEMBER_EXTRA
		--------------------------------------------------------

		  CREATE TABLE "MEMBER_EXTRA" 
		   (	"MEMBER_ID" NUMBER, 
			"DEPCD" VARCHAR2(20 BYTE), 
			"JJCD" VARCHAR2(20 BYTE), 
			"JGCD" VARCHAR2(20 BYTE), 
			"JYCD" VARCHAR2(20 BYTE), 
			"JMCD" VARCHAR2(20 BYTE), 
			"KORDEPNM" VARCHAR2(100 BYTE), 
			"JJNM" VARCHAR2(100 BYTE), 
			"JGNM" VARCHAR2(100 BYTE), 
			"JYNM" VARCHAR2(100 BYTE), 
			"JMNM" VARCHAR2(100 BYTE), 
			"INYMD" VARCHAR2(20 BYTE), 
			"RETIREYMD" VARCHAR2(20 BYTE), 
			"BREAKGU" VARCHAR2(20 BYTE), 
			"OFFINTELNO" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table NPS_WORK_LIST
		--------------------------------------------------------

		  CREATE TABLE "NPS_WORK_LIST" 
		   (	"NPS_WORK_LIST_ID" NUMBER, 
			"FROM_USER_ID" VARCHAR2(20 BYTE), 
			"CONTENT_ID" NUMBER, 
			"WORK_TYPE" VARCHAR2(20 BYTE), 
			"CONNOTE" VARCHAR2(4000 BYTE), 
			"STATUS" VARCHAR2(20 BYTE), 
			"CREATED_DATE" VARCHAR2(14 BYTE), 
			"WORK_TITLE" VARCHAR2(4000 BYTE), 
			"FILE_PATH" VARCHAR2(4000 BYTE), 
			"TO_USER_ID" VARCHAR2(20 BYTE), 
			"IS_SEND_TO" NUMBER DEFAULT 0, 
			"CATEGORY_ID" NUMBER, 
			"MEMBER_GROUP_ID" NUMBER, 
			"TASK_ID" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table PATH_MAPPING
		--------------------------------------------------------

		  CREATE TABLE "PATH_MAPPING" 
		   (	"CATEGORY_ID" NUMBER, 
			"PATH" VARCHAR2(200 CHAR), 
			"MEMBER_GROUP_ID" NUMBER, 
			"STORAGE_GROUP" VARCHAR2(20 BYTE), 
			"USING_REVIEW" VARCHAR2(20 BYTE) DEFAULT 0, 
			"UD_STORAGE_GROUP_ID" NUMBER DEFAULT 0, 
			"TYPE" VARCHAR2(10 BYTE) DEFAULT \'AD\', 
			"QUOTA" NUMBER, 
			"USAGE" NUMBER DEFAULT 0
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "PATH_MAPPING"."CATEGORY_ID" IS \'카테고리 ID\';
		 
		   COMMENT ON COLUMN "PATH_MAPPING"."PATH" IS \'폴더명
		프로그램ID\';
		 
		   COMMENT ON COLUMN "PATH_MAPPING"."MEMBER_GROUP_ID" IS \'사용자 그룹 아이디\';
		 
		   COMMENT ON COLUMN "PATH_MAPPING"."STORAGE_GROUP" IS \'스토리지 그룹\';
		 
		   COMMENT ON COLUMN "PATH_MAPPING"."TYPE" IS \'스토리지 구분
		디폴트: AD\';
		 
		   COMMENT ON COLUMN "PATH_MAPPING"."QUOTA" IS \'스토리지 할당량\';
		 
		   COMMENT ON COLUMN "PATH_MAPPING"."USAGE" IS \'스토리지 할당량 중 사용량\';
		--------------------------------------------------------
		--  DDL for Table PREVIEW_MAIN
		--------------------------------------------------------

		  CREATE TABLE "PREVIEW_MAIN" 
		   (	"NOTE_ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"TALENT" VARCHAR2(100 BYTE), 
			"SHOTGU" VARCHAR2(100 BYTE), 
			"SHOTLO" VARCHAR2(100 BYTE), 
			"SHOTDATE" VARCHAR2(14 BYTE), 
			"SEASON" VARCHAR2(100 BYTE), 
			"SHOTMET" VARCHAR2(100 BYTE), 
			"SHOTOR" VARCHAR2(100 BYTE), 
			"SOURCE" VARCHAR2(100 BYTE), 
			"REGISTDATE" VARCHAR2(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "PREVIEW_MAIN"."NOTE_ID" IS \'노트 작업 ID\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."TALENT" IS \'탈랜트 메타값\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SHOTGU" IS \'촬영구분\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SHOTLO" IS \'촬영장소\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SHOTDATE" IS \'촬영일자\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SEASON" IS \'계절\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SHOTMET" IS \'촬영방법\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SHOTOR" IS \'촬영자\';
		 
		   COMMENT ON COLUMN "PREVIEW_MAIN"."SOURCE" IS \'소재구분\';
		--------------------------------------------------------
		--  DDL for Table PREVIEW_NOTE
		--------------------------------------------------------

		  CREATE TABLE "PREVIEW_NOTE" 
		   (	"CONTENT_ID" NUMBER, 
			"NOTE_ID" NUMBER, 
			"SORT" NUMBER, 
			"TYPE" VARCHAR2(20 BYTE), 
			"START_TC" VARCHAR2(100 BYTE), 
			"CONTENT" VARCHAR2(1000 BYTE), 
			"VIGO" VARCHAR2(100 BYTE), 
			"TALENT" VARCHAR2(100 BYTE), 
			"SHOTGU" VARCHAR2(100 BYTE), 
			"SHOTLO" VARCHAR2(100 BYTE), 
			"SHOTDATE" VARCHAR2(14 BYTE), 
			"SEASON" VARCHAR2(100 BYTE), 
			"SHOTMET" VARCHAR2(100 BYTE), 
			"SHOTOR" VARCHAR2(100 BYTE), 
			"SOURCE" VARCHAR2(100 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "PREVIEW_NOTE"."TALENT" IS \'출연자\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SHOTGU" IS \'촬영구분\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SHOTLO" IS \'촬영장소\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SHOTDATE" IS \'촬영일자\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SEASON" IS \'계절\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SHOTMET" IS \'촬영방법\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SHOTOR" IS \'촬영자\';
		 
		   COMMENT ON COLUMN "PREVIEW_NOTE"."SOURCE" IS \'소재구분\';
		--------------------------------------------------------
		--  DDL for Table REQUEST_CAPTION
		--------------------------------------------------------

		  CREATE TABLE "REQUEST_CAPTION" 
		   (	"REQUEST_ID" NUMBER, 
			"SEND_USER_ID" VARCHAR2(20 BYTE), 
			"REQUEST_DATE" VARCHAR2(14 BYTE), 
			"CONTENT_ID" NUMBER, 
			"INTERFACE_ID" NUMBER, 
			"COMMENTS" VARCHAR2(4000 BYTE), 
			"PROGID" VARCHAR2(4000 BYTE), 
			"PROGRAM" VARCHAR2(4000 BYTE), 
			"TURN" VARCHAR2(4000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table REVIEW
		--------------------------------------------------------

		  CREATE TABLE "REVIEW" 
		   (	"ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"STATE" NUMBER, 
			"COMMENTS" VARCHAR2(2000 BYTE), 
			"CREATED" DATE DEFAULT sysdate, 
			"REQUESTER" VARCHAR2(20 BYTE), 
			"ACCEPTER" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table ROUGHCUT_PREVIEW_LIST
		--------------------------------------------------------

		  CREATE TABLE "ROUGHCUT_PREVIEW_LIST" 
		   (	"LIST_ID" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"TYPE" VARCHAR2(20 BYTE), 
			"STARTTC" VARCHAR2(8 BYTE), 
			"ENDTC" VARCHAR2(8 BYTE), 
			"INTC" VARCHAR2(8 BYTE), 
			"OUTTC" VARCHAR2(8 BYTE), 
			"DURATION" VARCHAR2(8 BYTE), 
			"NOTE" VARCHAR2(4000 BYTE), 
			"LIST_USER_ID" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."LIST_ID" IS \'목록 아이디\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."CONTENT_ID" IS \'소재 아이디\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."TYPE" IS \'유형
		roughcut/preview\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."STARTTC" IS \'Start TC\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."ENDTC" IS \'End TC\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."INTC" IS \'IN TC\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."OUTTC" IS \'OUT TC\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."DURATION" IS \'실행 시간\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."NOTE" IS \'내용\';
		 
		   COMMENT ON COLUMN "ROUGHCUT_PREVIEW_LIST"."LIST_USER_ID" IS \'등록자\';
		--------------------------------------------------------
		--  DDL for Table SCROLL_MONITOR
		--------------------------------------------------------

		  CREATE TABLE "SCROLL_MONITOR" 
		   (	"PROGRAM" VARCHAR2(1000 BYTE), 
			"PROGRAM_ID" VARCHAR2(1000 BYTE), 
			"TURN" VARCHAR2(1000 BYTE), 
			"BRODYMD_START" VARCHAR2(8 BYTE), 
			"BRODYMD_END" VARCHAR2(8 BYTE), 
			"STATUS" NUMBER DEFAULT 0, 
			"REQ_COMMENT" VARCHAR2(1000 BYTE), 
			"MAS_COMMENT" VARCHAR2(1000 BYTE), 
			"CREATED_DATE" VARCHAR2(20 BYTE), 
			"REQUEST_ID" NUMBER, 
			"SEND_USER_ID" VARCHAR2(20 BYTE), 
			"ACCEPT_USER_ID" VARCHAR2(20 BYTE), 
			"SEND_PHONE" VARCHAR2(20 BYTE), 
			"SCROLL_TOP" VARCHAR2(20 BYTE), 
			"SCROLL_BOTTOM" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "SCROLL_MONITOR"."PROGRAM" IS \'프로그램 명\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."PROGRAM_ID" IS \'프로그램ID\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."TURN" IS \'회차\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."BRODYMD_START" IS \'방송시작일\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."BRODYMD_END" IS \'방송종료일\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."STATUS" IS \'상태\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."REQ_COMMENT" IS \'의뢰내용\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."MAS_COMMENT" IS \'주조 의견\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."CREATED_DATE" IS \'의뢰일시\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."REQUEST_ID" IS \'요청ID\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."SEND_USER_ID" IS \'의뢰자ID\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."ACCEPT_USER_ID" IS \'승인자 ID\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."SEND_PHONE" IS \'전화번호\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."SCROLL_TOP" IS \'상단\';
		 
		   COMMENT ON COLUMN "SCROLL_MONITOR"."SCROLL_BOTTOM" IS \'하단\';
		--------------------------------------------------------
		--  DDL for Table SGL_ARCHIVE
		--------------------------------------------------------

		  CREATE TABLE "SGL_ARCHIVE" 
		   (	"UNIQUE_ID" VARCHAR2(255 BYTE), 
			"SESSION_ID" NUMBER, 
			"MEDIA_ID" NUMBER, 
			"TASK_ID" NUMBER, 
			"LOGKEY" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table SUBPROG_MAPPING
		--------------------------------------------------------

		  CREATE TABLE "SUBPROG_MAPPING" 
		   (	"CATEGORY_ID" NUMBER, 
			"PARENT_ID" NUMBER, 
			"PROGCD" VARCHAR2(20 BYTE), 
			"FORMBASEYMD" VARCHAR2(20 BYTE), 
			"SUBPROGCD" VARCHAR2(20 BYTE), 
			"SUBPROGNM" VARCHAR2(1000 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 262144 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "SUBPROG_MAPPING"."CATEGORY_ID" IS \'카테고리아이디\';
		 
		   COMMENT ON COLUMN "SUBPROG_MAPPING"."PARENT_ID" IS \'제작프로그램 카테고리 아이디\';
		 
		   COMMENT ON COLUMN "SUBPROG_MAPPING"."PROGCD" IS \'프로그램코드\';
		 
		   COMMENT ON COLUMN "SUBPROG_MAPPING"."FORMBASEYMD" IS \'편성일자\';
		 
		   COMMENT ON COLUMN "SUBPROG_MAPPING"."SUBPROGCD" IS \'부제코드\';
		 
		   COMMENT ON COLUMN "SUBPROG_MAPPING"."SUBPROGNM" IS \'부제명\';
		--------------------------------------------------------
		--  DDL for Table SYNC_PROGRAM
		--------------------------------------------------------

		  CREATE TABLE "SYNC_PROGRAM" 
		   (	"PGM_ID" VARCHAR2(500 BYTE), 
			"IS_CHECK" NUMBER(1,0) DEFAULT 1
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table TB_CLASSIFICATION
		--------------------------------------------------------

		  CREATE TABLE "TB_CLASSIFICATION" 
		   (	"C_ID" NUMBER(10,0), 
			"C_NAME" VARCHAR2(128 BYTE), 
			"C_PID" NUMBER(10,0), 
			"C_NAMEID" NUMBER(10,0)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "TB_CLASSIFICATION"."C_ID" IS \'분류체계 아이디\';
		 
		   COMMENT ON COLUMN "TB_CLASSIFICATION"."C_NAME" IS \'분류체계 항목명\';
		 
		   COMMENT ON COLUMN "TB_CLASSIFICATION"."C_PID" IS \'분류체계 부모 아이디\';
		 
		   COMMENT ON COLUMN "TB_CLASSIFICATION"."C_NAMEID" IS \'분류체계 대제목 아이디\';
		 
		   COMMENT ON TABLE "TB_CLASSIFICATION"  IS \'분류체계 항목명 테이블\';
		--------------------------------------------------------
		--  DDL for Table TB_CLASSIFICATIONMASTER
		--------------------------------------------------------

		  CREATE TABLE "TB_CLASSIFICATIONMASTER" 
		   (	"CID" NUMBER(10,0), 
			"C_ID" NUMBER(10,0)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 720896 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "TB_CLASSIFICATIONMASTER"."CID" IS \'콘텐츠 아이디\';
		 
		   COMMENT ON COLUMN "TB_CLASSIFICATIONMASTER"."C_ID" IS \'분류체계 아이디\';
		 
		   COMMENT ON TABLE "TB_CLASSIFICATIONMASTER"  IS \'분류체계 적용 목록 테이블\';
		--------------------------------------------------------
		--  DDL for Table TB_CLASSIFICATIONNAME
		--------------------------------------------------------

		  CREATE TABLE "TB_CLASSIFICATIONNAME" 
		   (	"C_ID" NUMBER(10,0), 
			"C_NAME" VARCHAR2(255 BYTE), 
			"C_PID" NUMBER(10,0)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "TB_CLASSIFICATIONNAME"."C_ID" IS \'분류체계 대 제목 아이디\';
		 
		   COMMENT ON COLUMN "TB_CLASSIFICATIONNAME"."C_NAME" IS \'분류체계 대제목\';
		 
		   COMMENT ON COLUMN "TB_CLASSIFICATIONNAME"."C_PID" IS \'분류체계 대제목 부모 아이디\';
		 
		   COMMENT ON TABLE "TB_CLASSIFICATIONNAME"  IS \'분류체계 대제목 테이블\';
		--------------------------------------------------------
		--  DDL for Table TB_ORD
		--------------------------------------------------------

		  CREATE TABLE "TB_ORD" 
		   (	"ORD_ID" VARCHAR2(19 BYTE), 
			"ORD_CTT" VARCHAR2(4000 BYTE), 
			"ORD_DIV_CD" CHAR(3 BYTE), 
			"INPUT_DTM" CHAR(14 BYTE), 
			"INPUTR_ID" VARCHAR2(20 BYTE), 
			"ORD_META_CD" VARCHAR2(10 BYTE), 
			"ORD_STATUS" VARCHAR2(30 BYTE) DEFAULT \'001\', 
			"TITLE" VARCHAR2(500 BYTE), 
			"CH_DIV_CD" VARCHAR2(4 BYTE), 
			"ORD_WORK_CTT" VARCHAR2(1000 BYTE), 
			"ARTCL_ID" VARCHAR2(21 BYTE), 
			"RD_ID" VARCHAR2(21 BYTE), 
			"RD_SEQ" NUMBER(5,0), 
			"UPDTR_ID" VARCHAR2(20 BYTE), 
			"UPDT_DTM" VARCHAR2(14 BYTE), 
			"EXPT_ORD_END_DTM" VARCHAR2(14 BYTE), 
			"ORD_WORK_ID" VARCHAR2(20 BYTE), 
			"DEPT_CD" VARCHAR2(20 BYTE), 
			"ARTCL_TITL" VARCHAR2(300 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "TB_ORD"."ORD_ID" IS \'의뢰 목록ID 형식 : 날자(yyyyMMdd) + ZOR(보도) or POR(CMS) + 시퀀스(5자리 : SEQ_ORD_REQUEST)\';
		 
		   COMMENT ON COLUMN "TB_ORD"."ORD_CTT" IS \'업무 의뢰 내역\';
		 
		   COMMENT ON COLUMN "TB_ORD"."INPUT_DTM" IS \'의뢰 시각\';
		 
		   COMMENT ON COLUMN "TB_ORD"."INPUTR_ID" IS \'의뢰자\';
		 
		   COMMENT ON COLUMN "TB_ORD"."ORD_STATUS" IS \'의뢰 상태\';
		 
		   COMMENT ON COLUMN "TB_ORD"."TITLE" IS \'제목\';
		 
		   COMMENT ON COLUMN "TB_ORD"."ARTCL_ID" IS \'기사ID\';
		 
		   COMMENT ON COLUMN "TB_ORD"."RD_ID" IS \'런다운(큐시트)ID\';
		 
		   COMMENT ON COLUMN "TB_ORD"."RD_SEQ" IS \'런다운(큐시트) 시퀀스\';
		 
		   COMMENT ON COLUMN "TB_ORD"."UPDTR_ID" IS \'수정자\';
		 
		   COMMENT ON COLUMN "TB_ORD"."UPDT_DTM" IS \'수정시각\';
		 
		   COMMENT ON COLUMN "TB_ORD"."EXPT_ORD_END_DTM" IS \'종료시각\';
		 
		   COMMENT ON COLUMN "TB_ORD"."ORD_WORK_ID" IS \'편집자\';
		 
		   COMMENT ON COLUMN "TB_ORD"."DEPT_CD" IS \'부서코드\';
		 
		   COMMENT ON COLUMN "TB_ORD"."ARTCL_TITL" IS \'기사제목\';
		--------------------------------------------------------
		--  DDL for Table TB_ORD_EDL
		--------------------------------------------------------

		  CREATE TABLE "TB_ORD_EDL" 
		   (	"ORD_ID" VARCHAR2(19 BYTE), 
			"MARK_IN" VARCHAR2(20 BYTE), 
			"MARK_OUT" VARCHAR2(20 BYTE), 
			"EDL_TITL" VARCHAR2(100 BYTE), 
			"VIDEO_ID" VARCHAR2(30 BYTE), 
			"ORD_NO" NUMBER(3,0)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table TB_ORD_FILE
		--------------------------------------------------------

		  CREATE TABLE "TB_ORD_FILE" 
		   (	"ORD_ID" VARCHAR2(20 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table TB_ORD_GRPHC
		--------------------------------------------------------

		  CREATE TABLE "TB_ORD_GRPHC" 
		   (	"GRPHC_ID" VARCHAR2(30 BYTE), 
			"ORD_ID" VARCHAR2(19 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table TB_ORD_TRANSMISSION
		--------------------------------------------------------

		  CREATE TABLE "TB_ORD_TRANSMISSION" 
		   (	"ORD_TR_ID" VARCHAR2(19 BYTE), 
			"CONTENT_ID" NUMBER, 
			"CREATE_TIME" VARCHAR2(14 BYTE), 
			"REQUEST_TIME" VARCHAR2(14 BYTE), 
			"COMPLETE_TIME" VARCHAR2(14 BYTE), 
			"DELETE_TIME" VARCHAR2(14 BYTE), 
			"KEEP_YN" CHAR(1 BYTE) DEFAULT \'N\', 
			"TASK_ID" NUMBER, 
			"CREATE_USER" VARCHAR2(20 BYTE), 
			"UPDATE_TIME" VARCHAR2(14 BYTE), 
			"UPDATE_USER" VARCHAR2(20 BYTE), 
			"DELETE_TASK_ID" NUMBER, 
			"DELETE_USER" VARCHAR2(20 BYTE), 
			"TR_STATUS" VARCHAR2(20 BYTE), 
			"TR_PROGRESS" NUMBER
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."ORD_TR_ID" IS \'송출ID\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."CONTENT_ID" IS \'콘텐츠ID\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."CREATE_TIME" IS \'생성시각\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."REQUEST_TIME" IS \'전송요청시각\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."COMPLETE_TIME" IS \'전송완료시각\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."DELETE_TIME" IS \'삭제시각\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."KEEP_YN" IS \'영구보관 여부 Y:영구보관 N:기본값\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."TASK_ID" IS \'TASK_ID 송출시 파일명 생성시 구분값\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."CREATE_USER" IS \'송출자(매칭한 사람)\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."UPDATE_TIME" IS \'수정시각(영구보관 요청/취소)\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."UPDATE_USER" IS \'수정자(영구보관 요청/취소)\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."DELETE_TASK_ID" IS \'삭제시 생기는 task_id\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."DELETE_USER" IS \'삭제자\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."TR_STATUS" IS \'전송 상태\';
		 
		   COMMENT ON COLUMN "TB_ORD_TRANSMISSION"."TR_PROGRESS" IS \'전송 진행율\';
		--------------------------------------------------------
		--  DDL for Table TB_ORD_VIDEO
		--------------------------------------------------------

		  CREATE TABLE "TB_ORD_VIDEO" 
		   (	"VIDEO_ID" VARCHAR2(30 BYTE), 
			"ORD_ID" VARCHAR2(19 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table TC_LIST
		--------------------------------------------------------

		  CREATE TABLE "TC_LIST" 
		   (	"TC_NO" NUMBER, 
			"CONTENT_ID" NUMBER, 
			"TC_IN" VARCHAR2(20 BYTE), 
			"TC_OUT" VARCHAR2(20 BYTE), 
			"CREATED_DATE" VARCHAR2(14 BYTE)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Table TOAD_PLAN_TABLE
		--------------------------------------------------------

		  CREATE TABLE "TOAD_PLAN_TABLE" 
		   (	"STATEMENT_ID" VARCHAR2(30 BYTE), 
			"PLAN_ID" NUMBER, 
			"TIMESTAMP" DATE, 
			"REMARKS" VARCHAR2(4000 BYTE), 
			"OPERATION" VARCHAR2(30 BYTE), 
			"OPTIONS" VARCHAR2(255 BYTE), 
			"OBJECT_NODE" VARCHAR2(128 BYTE), 
			"OBJECT_OWNER" VARCHAR2(30 BYTE), 
			"OBJECT_NAME" VARCHAR2(30 BYTE), 
			"OBJECT_ALIAS" VARCHAR2(65 BYTE), 
			"OBJECT_INSTANCE" NUMBER(*,0), 
			"OBJECT_TYPE" VARCHAR2(30 BYTE), 
			"OPTIMIZER" VARCHAR2(255 BYTE), 
			"SEARCH_COLUMNS" NUMBER, 
			"ID" NUMBER(*,0), 
			"PARENT_ID" NUMBER(*,0), 
			"DEPTH" NUMBER(*,0), 
			"POSITION" NUMBER(*,0), 
			"COST" NUMBER(*,0), 
			"CARDINALITY" NUMBER(*,0), 
			"BYTES" NUMBER(*,0), 
			"OTHER_TAG" VARCHAR2(255 BYTE), 
			"PARTITION_START" VARCHAR2(255 BYTE), 
			"PARTITION_STOP" VARCHAR2(255 BYTE), 
			"PARTITION_ID" NUMBER(*,0), 
			"OTHER" LONG, 
			"DISTRIBUTION" VARCHAR2(30 BYTE), 
			"CPU_COST" NUMBER(*,0), 
			"IO_COST" NUMBER(*,0), 
			"TEMP_SPACE" NUMBER(*,0), 
			"ACCESS_PREDICATES" VARCHAR2(4000 BYTE), 
			"FILTER_PREDICATES" VARCHAR2(4000 BYTE), 
			"PROJECTION" VARCHAR2(4000 BYTE), 
			"TIME" NUMBER(*,0), 
			"QBLOCK_NAME" VARCHAR2(30 BYTE), 
			"OTHER_XML" CLOB
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   
		 LOB ("OTHER_XML") STORE AS BASICFILE (
		   ENABLE STORAGE IN ROW CHUNK 8192 PCTVERSION 10
		  NOCACHE LOGGING 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)) ;
		--------------------------------------------------------
		--  DDL for Table USER_MAPPING
		--------------------------------------------------------

		  CREATE TABLE "USER_MAPPING" 
		   (	"CATEGORY_ID" NUMBER, 
			"USER_ID" VARCHAR2(20 CHAR)
		   ) 
		  PCTFREE 10 PCTUSED 40 INITRANS 1 MAXTRANS 255 NOCOMPRESS LOGGING
		  STORAGE(INITIAL 5242880 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		 

		   COMMENT ON COLUMN "USER_MAPPING"."CATEGORY_ID" IS \'카테고리 아이디\';
		 
		   COMMENT ON COLUMN "USER_MAPPING"."USER_ID" IS \'사용자 아이디\';
		--------------------------------------------------------
		--  DDL for Index BC_BS_CONTENT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_BS_CONTENT_PK" ON "BC_BS_CONTENT" ("BS_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_CATEGORY_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_CATEGORY_PK" ON "BC_CATEGORY" ("CATEGORY_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_CATEGORY_INDEX1
		--------------------------------------------------------

		  CREATE INDEX "BC_CATEGORY_INDEX1" ON "BC_CATEGORY" ("PARENT_ID", "CATEGORY_ID", "CODE", "CATEGORY_TITLE", "EXTRA_ORDER") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 196608 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_CATEGORY_ENV_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_CATEGORY_ENV_PK" ON "BC_CATEGORY_ENV" ("CATEGORY_ID", "UD_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_CATEGORY_GRANT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_CATEGORY_GRANT_PK" ON "BC_CATEGORY_GRANT" ("UD_CONTENT_ID", "MEMBER_GROUP_ID", "CATEGORY_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_CODE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_CODE_PK" ON "BC_CODE" ("ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_CODE_TYPE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_CODE_TYPE_PK" ON "BC_CODE_TYPE" ("ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index TABLE1_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "TABLE1_PK" ON "BC_CONTENT" ("CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 3145728 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_FAVORITE_CATEGORY_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_FAVORITE_CATEGORY_PK" ON "BC_FAVORITE_CATEGORY" ("FAVORITE_CATEGORY_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_GRANT_UK1
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_GRANT_UK1" ON "BC_GRANT" ("UD_CONTENT_ID", "MEMBER_GROUP_ID", "CATEGORY_ID", "GRANT_TYPE") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_GRANT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_GRANT_PK" ON "BC_GRANT" ("UD_CONTENT_ID", "MEMBER_GROUP_ID", "GRANT_TYPE") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_LOG_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_LOG_PK" ON "BC_LOG" ("LOG_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 28311552 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_MEDIA_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_MEDIA_PK" ON "BC_MEDIA" ("CONTENT_ID", "MEDIA_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 17825792 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index PD_MEDIA_QUALITY_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "PD_MEDIA_QUALITY_PK" ON "BC_MEDIA_QUALITY" ("QUALITY_ID", "MEDIA_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 851968 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_MEMBER_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_MEMBER_PK" ON "BC_MEMBER" ("MEMBER_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		
		--------------------------------------------------------
		--  DDL for Index BC_MEMBER_GROUP_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_MEMBER_GROUP_PK" ON "BC_MEMBER_GROUP" ("MEMBER_GROUP_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_MODULE_INFO_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_MODULE_INFO_PK" ON "BC_MODULE_INFO" ("MODULE_INFO_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_NOTICE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_NOTICE_PK" ON "BC_NOTICE" ("NOTICE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_PATH_AVAILABLE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_PATH_AVAILABLE_PK" ON "BC_PATH_AVAILABLE" ("MODULE_INFO_ID", "AVAILABLE_STORAGE", "AUTH") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SCENE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SCENE_PK" ON "BC_SCENE" ("SCENE_ID", "MEDIA_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 48234496 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_STORAGE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_STORAGE_PK" ON "BC_STORAGE" ("STORAGE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_STORAGE_MANAGEMENT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_STORAGE_MANAGEMENT_PK" ON "BC_STORAGE_MANAGEMENT" ("CATEGORY_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYS_CODE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYS_CODE_PK" ON "BC_SYS_CODE" ("ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYS_META_FIELD_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYS_META_FIELD_PK" ON "BC_SYS_META_FIELD" ("BS_CONTENT_ID", "SYS_META_FIELD_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYS_META_VALUE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYS_META_VALUE_PK" ON "BC_SYS_META_VALUE" ("CONTENT_ID", "SYS_META_FIELD_ID", "SYS_META_VALUE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 62914560 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYSMETA_DOCUMENT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYSMETA_DOCUMENT_PK" ON "BC_SYSMETA_DOCUMENT" ("SYS_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYSMETA_IMAGE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYSMETA_IMAGE_PK" ON "BC_SYSMETA_IMAGE" ("SYS_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYSMETA_MOVIE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYSMETA_MOVIE_PK" ON "BC_SYSMETA_MOVIE" ("SYS_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 2097152 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYSMETA_SEQUENCE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYSMETA_SEQUENCE_PK" ON "BC_SYSMETA_SEQUENCE" ("SYS_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_SYSMETA_SOUND_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_SYSMETA_SOUND_PK" ON "BC_SYSMETA_SOUND" ("SYS_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_INDEX1
		--------------------------------------------------------

		  CREATE INDEX "BC_TASK_INDEX1" ON "BC_TASK" ("CREATION_DATETIME" DESC) 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 11534336 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_TASK_PK" ON "BC_TASK" ("TASK_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 27262976 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_INDEX2
		--------------------------------------------------------

		  CREATE INDEX "BC_TASK_INDEX2" ON "BC_TASK" ("TASK_WORKFLOW_ID", "SRC_CONTENT_ID", "TASK_USER_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 11534336 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_AVAILABLE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_TASK_AVAILABLE_PK" ON "BC_TASK_AVAILABLE" ("MODULE_INFO_ID", "TASK_RULE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index TASK_LOG_IDX1
		--------------------------------------------------------

		  CREATE INDEX "TASK_LOG_IDX1" ON "BC_TASK_LOG" ("TASK_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 526385152 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_LOG_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_TASK_LOG_PK" ON "BC_TASK_LOG" ("TASK_LOG_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 452984832 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_RULE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_TASK_RULE_PK" ON "BC_TASK_RULE" ("TASK_RULE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index SYS_C0028227
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "SYS_C0028227" ON "BC_TASK_TYPE" ("TASK_TYPE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_WORKFLOW_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_TASK_WORKFLOW_PK" ON "BC_TASK_WORKFLOW" ("TASK_WORKFLOW_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_TASK_WORKFLOW_RULE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_TASK_WORKFLOW_RULE_PK" ON "BC_TASK_WORKFLOW_RULE" ("WORKFLOW_RULE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_UD_CONTENT_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_UD_CONTENT_PK" ON "BC_UD_CONTENT" ("UD_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_UD_CONTENT_STORAGE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_UD_CONTENT_STORAGE_PK" ON "BC_UD_CONTENT_STORAGE" ("UD_CONTENT_ID", "STORAGE_ID", "US_TYPE") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_UD_GROUP_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_UD_GROUP_PK" ON "BC_UD_GROUP" ("UD_GROUP_CODE", "UD_CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_UD_STORAGE_GROUP_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_UD_STORAGE_GROUP_PK" ON "BC_UD_STORAGE_GROUP" ("STORAGE_GROUP_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_UD_STORAGE_GROUP_MAP_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_UD_STORAGE_GROUP_MAP_PK" ON "BC_UD_STORAGE_GROUP_MAP" ("STORAGE_GROUP_ID", "SOURCE_STORAGE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_UD_SYSTEM_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_UD_SYSTEM_PK" ON "BC_UD_SYSTEM" ("CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USR_META_CODE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USR_META_CODE_PK" ON "BC_USR_META_CODE" ("USR_META_FIELD_CODE", "USR_CODE_KEY") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_USR_META_FIELD_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_USR_META_FIELD_PK" ON "BC_USR_META_FIELD" ("UD_CONTENT_ID", "USR_META_FIELD_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		
		--------------------------------------------------------
		--  DDL for Index NPS_CONTENT_CODE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "NPS_CONTENT_CODE_PK" ON "CONTENT_CODE_INFO" ("CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 3145728 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index DELETE_CONTENT_LIST_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "DELETE_CONTENT_LIST_PK" ON "DELETE_CONTENT_LIST" ("CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index EBS_INTERFACE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "EBS_INTERFACE_PK" ON "EBS_INTERFACE" ("INTERFACE_ID", "INTERFACE_TYPE", "TARGET_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index EDIUS_EXPORTER_SETTING_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "EDIUS_EXPORTER_SETTING_PK" ON "EXPORTER_SETTING" ("ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index IM_SCHEDULE_METADATA_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "IM_SCHEDULE_METADATA_PK" ON "IM_SCHEDULE_METADATA" ("SCHEDULE_ID", "UD_CONTENT_ID", "BC_USR_META_FIELD_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index INGESTMANAGER_SCHEDULE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "INGESTMANAGER_SCHEDULE_PK" ON "INGESTMANAGER_SCHEDULE" ("SCHEDULE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index INGESTMANAGER_SCHEDULE_ME_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "INGESTMANAGER_SCHEDULE_ME_PK" ON "INGESTMANAGER_SCHEDULE_META" ("SCHEDULE_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index BC_MASTERROOM_MONITOR_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "BC_MASTERROOM_MONITOR_PK" ON "MASTER_MONITOR" ("REQUEST_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index NPS_WORK_LIST_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "NPS_WORK_LIST_PK" ON "NPS_WORK_LIST" ("NPS_WORK_LIST_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index PATH_MAPPING_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "PATH_MAPPING_PK" ON "PATH_MAPPING" ("CATEGORY_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index PREVIEW_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "PREVIEW_PK" ON "PREVIEW_MAIN" ("NOTE_ID", "CONTENT_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index REVIEW_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "REVIEW_PK" ON "REVIEW" ("ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index SGL_ARCHIVE_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "SGL_ARCHIVE_PK" ON "SGL_ARCHIVE" ("TASK_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index SUBPROG_MAPPING_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "SUBPROG_MAPPING_PK" ON "SUBPROG_MAPPING" ("CATEGORY_ID", "PROGCD", "SUBPROGCD", "FORMBASEYMD") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index SYS_C0028373
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "SYS_C0028373" ON "SYNC_PROGRAM" ("PGM_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index TB_ORD_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "TB_ORD_PK" ON "TB_ORD" ("INPUT_DTM", "ORD_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  DDL for Index USER_MAPPING_PK
		--------------------------------------------------------

		  CREATE UNIQUE INDEX "USER_MAPPING_PK" ON "USER_MAPPING" ("CATEGORY_ID", "USER_ID") 
		  PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 7340032 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
		   ;
		--------------------------------------------------------
		--  Constraints for Table ARCHIVE_REQUEST
		--------------------------------------------------------

		  ALTER TABLE "ARCHIVE_REQUEST" MODIFY ("REQUEST_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_BS_CONTENT
		--------------------------------------------------------

		  ALTER TABLE "BC_BS_CONTENT" ADD CONSTRAINT "BC_BS_CONTENT_PK" PRIMARY KEY ("BS_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_BS_CONTENT" MODIFY ("BS_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_BS_CONTENT" MODIFY ("BS_CONTENT_TITLE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_CATEGORY
		--------------------------------------------------------

		  ALTER TABLE "BC_CATEGORY" ADD CONSTRAINT "BC_CATEGORY_PK" PRIMARY KEY ("CATEGORY_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_CATEGORY" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_CATEGORY_ENV
		--------------------------------------------------------

		  ALTER TABLE "BC_CATEGORY_ENV" ADD CONSTRAINT "BC_CATEGORY_ENV_PK" PRIMARY KEY ("CATEGORY_ID", "UD_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_CATEGORY_ENV" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CATEGORY_ENV" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_CATEGORY_GRANT
		--------------------------------------------------------

		  ALTER TABLE "BC_CATEGORY_GRANT" ADD CONSTRAINT "BC_CATEGORY_GRANT_PK" PRIMARY KEY ("UD_CONTENT_ID", "MEMBER_GROUP_ID", "CATEGORY_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_CATEGORY_GRANT" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CATEGORY_GRANT" MODIFY ("MEMBER_GROUP_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CATEGORY_GRANT" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CATEGORY_GRANT" MODIFY ("GROUP_GRANT" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_CODE
		--------------------------------------------------------

		  ALTER TABLE "BC_CODE" ADD CONSTRAINT "BC_CODE_PK" PRIMARY KEY ("ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_CODE" MODIFY ("ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CODE" MODIFY ("CODE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CODE" MODIFY ("NAME" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CODE" MODIFY ("CODE_TYPE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_CODE_TYPE
		--------------------------------------------------------

		  ALTER TABLE "BC_CODE_TYPE" ADD CONSTRAINT "BC_CODE_TYPE_PK" PRIMARY KEY ("ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_CODE_TYPE" MODIFY ("ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CODE_TYPE" MODIFY ("CODE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CODE_TYPE" MODIFY ("NAME" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_CONTENT
		--------------------------------------------------------

		  ALTER TABLE "BC_CONTENT" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CONTENT" MODIFY ("BS_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CONTENT" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CONTENT" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CONTENT" MODIFY ("TITLE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CONTENT" MODIFY ("REG_USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_CONTENT" ADD CONSTRAINT "TABLE1_PK" PRIMARY KEY ("CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 3145728 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		--------------------------------------------------------
		--  Constraints for Table BC_FAVORITE
		--------------------------------------------------------

		  ALTER TABLE "BC_FAVORITE" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_FAVORITE" MODIFY ("SHOW_ORDER" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_FAVORITE" MODIFY ("FAVORITE_CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_FAVORITE" MODIFY ("CONTENT_TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_FAVORITE_CATEGORY
		--------------------------------------------------------

		  ALTER TABLE "BC_FAVORITE_CATEGORY" ADD CONSTRAINT "BC_FAVORITE_CATEGORY_PK" PRIMARY KEY ("FAVORITE_CATEGORY_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_FAVORITE_CATEGORY" MODIFY ("FAVORITE_CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_FAVORITE_CATEGORY" MODIFY ("FAVORITE_CATEGORY_TITLE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_FAVORITE_CATEGORY" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_FAVORITE_CATEGORY" MODIFY ("CONTENT_TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_GRANT
		--------------------------------------------------------

		  ALTER TABLE "BC_GRANT" ADD CONSTRAINT "BC_GRANT_PK" PRIMARY KEY ("UD_CONTENT_ID", "MEMBER_GROUP_ID", "GRANT_TYPE")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_GRANT" ADD CONSTRAINT "BC_GRANT_UK1" UNIQUE ("UD_CONTENT_ID", "MEMBER_GROUP_ID", "CATEGORY_ID", "GRANT_TYPE")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_GRANT" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_GRANT" MODIFY ("MEMBER_GROUP_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_GRANT" MODIFY ("GRANT_TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_GRANT" MODIFY ("GROUP_GRANT" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_GRANT_ACCESS
		--------------------------------------------------------

		  ALTER TABLE "BC_GRANT_ACCESS" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_GRANT_ACCESS" MODIFY ("MEMBER_GROUP_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_GRANT_ACCESS" MODIFY ("GRANT_TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_GRANT_ACCESS" MODIFY ("GROUP_GRANT" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_HTML_CONFIG
		--------------------------------------------------------

		  ALTER TABLE "BC_HTML_CONFIG" MODIFY ("TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_HTML_CONFIG" MODIFY ("VALUE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_LOG
		--------------------------------------------------------

		  ALTER TABLE "BC_LOG" ADD CONSTRAINT "BC_LOG_PK" PRIMARY KEY ("LOG_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 28311552 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_LOG" MODIFY ("LOG_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_LOG" MODIFY ("ACTION" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_LOG" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_LOG" MODIFY ("CREATED_DATE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_MEDIA
		--------------------------------------------------------

		  ALTER TABLE "BC_MEDIA" ADD CONSTRAINT "BC_MEDIA_PK" PRIMARY KEY ("CONTENT_ID", "MEDIA_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 17825792 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_MEDIA" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA" MODIFY ("MEDIA_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA" MODIFY ("STORAGE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA" MODIFY ("MEDIA_TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA" MODIFY ("PATH" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA" MODIFY ("CREATED_DATE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_MEDIA_QUALITY
		--------------------------------------------------------

		  ALTER TABLE "BC_MEDIA_QUALITY" ADD CONSTRAINT "PD_MEDIA_QUALITY_PK" PRIMARY KEY ("QUALITY_ID", "MEDIA_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 851968 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_MEDIA_QUALITY" MODIFY ("QUALITY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA_QUALITY" MODIFY ("MEDIA_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEDIA_QUALITY" MODIFY ("QUALITY_TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_MEMBER
		--------------------------------------------------------

		  ALTER TABLE "BC_MEMBER" ADD CONSTRAINT "BC_MEMBER_PK" PRIMARY KEY ("MEMBER_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_MEMBER" MODIFY ("MEMBER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEMBER" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEMBER" MODIFY ("CREATED_DATE" NOT NULL ENABLE);
		
		--------------------------------------------------------
		--  Constraints for Table BC_MEMBER_GROUP
		--------------------------------------------------------

		  ALTER TABLE "BC_MEMBER_GROUP" ADD CONSTRAINT "BC_MEMBER_GROUP_PK" PRIMARY KEY ("MEMBER_GROUP_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_MEMBER_GROUP" MODIFY ("MEMBER_GROUP_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEMBER_GROUP" MODIFY ("MEMBER_GROUP_NAME" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEMBER_GROUP" MODIFY ("CREATED_DATE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_MEMBER_GROUP_MEMBER
		--------------------------------------------------------

		  ALTER TABLE "BC_MEMBER_GROUP_MEMBER" MODIFY ("MEMBER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MEMBER_GROUP_MEMBER" MODIFY ("MEMBER_GROUP_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_META_MULTI_XML
		--------------------------------------------------------

		  ALTER TABLE "BC_META_MULTI_XML" MODIFY ("META_MULTI_XML_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_MODULE_INFO
		--------------------------------------------------------

		  ALTER TABLE "BC_MODULE_INFO" ADD CONSTRAINT "BC_MODULE_INFO_PK" PRIMARY KEY ("MODULE_INFO_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_MODULE_INFO" MODIFY ("ACTIVE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MODULE_INFO" MODIFY ("MAIN_IP" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_MODULE_INFO" MODIFY ("MODULE_INFO_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_NOTICE
		--------------------------------------------------------

		  ALTER TABLE "BC_NOTICE" ADD CONSTRAINT "BC_NOTICE_PK" PRIMARY KEY ("NOTICE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_NOTICE" MODIFY ("NOTICE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_NOTICE" MODIFY ("NOTICE_TITLE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_PATH_AVAILABLE
		--------------------------------------------------------

		  ALTER TABLE "BC_PATH_AVAILABLE" ADD CONSTRAINT "BC_PATH_AVAILABLE_PK" PRIMARY KEY ("MODULE_INFO_ID", "AVAILABLE_STORAGE", "AUTH")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_PATH_AVAILABLE" MODIFY ("MODULE_INFO_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_PATH_AVAILABLE" MODIFY ("AVAILABLE_STORAGE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_PATH_AVAILABLE" MODIFY ("AUTH" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_RESTORE_ING
		--------------------------------------------------------

		  ALTER TABLE "BC_RESTORE_ING" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SCENE
		--------------------------------------------------------

		  ALTER TABLE "BC_SCENE" ADD CONSTRAINT "BC_SCENE_PK" PRIMARY KEY ("SCENE_ID", "MEDIA_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 48234496 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SCENE" MODIFY ("SCENE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SCENE" MODIFY ("MEDIA_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SCENE" MODIFY ("PATH" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_STORAGE
		--------------------------------------------------------

		  ALTER TABLE "BC_STORAGE" ADD CONSTRAINT "BC_STORAGE_PK" PRIMARY KEY ("STORAGE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_STORAGE" MODIFY ("STORAGE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_STORAGE" MODIFY ("PATH" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_STORAGE" MODIFY ("NAME" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_STORAGE_MANAGEMENT
		--------------------------------------------------------

		  ALTER TABLE "BC_STORAGE_MANAGEMENT" ADD CONSTRAINT "BC_STORAGE_MANAGEMENT_PK" PRIMARY KEY ("CATEGORY_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_STORAGE_MANAGEMENT" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_STORAGE_MANAGEMENT" MODIFY ("PATH" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYS_CODE
		--------------------------------------------------------

		  ALTER TABLE "BC_SYS_CODE" ADD CONSTRAINT "BC_SYS_CODE_PK" PRIMARY KEY ("ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYS_CODE" MODIFY ("ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_CODE" MODIFY ("CODE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_CODE" MODIFY ("CODE_NM" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_CODE" MODIFY ("TYPE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYS_META_FIELD
		--------------------------------------------------------

		  ALTER TABLE "BC_SYS_META_FIELD" ADD CONSTRAINT "BC_SYS_META_FIELD_PK" PRIMARY KEY ("BS_CONTENT_ID", "SYS_META_FIELD_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYS_META_FIELD" MODIFY ("BS_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_META_FIELD" MODIFY ("SYS_META_FIELD_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_META_FIELD" MODIFY ("SYS_META_FIELD_TITLE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_META_FIELD" MODIFY ("FIELD_INPUT_TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYS_META_VALUE
		--------------------------------------------------------

		  ALTER TABLE "BC_SYS_META_VALUE" ADD CONSTRAINT "BC_SYS_META_VALUE_PK" PRIMARY KEY ("CONTENT_ID", "SYS_META_FIELD_ID", "SYS_META_VALUE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 62914560 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYS_META_VALUE" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_META_VALUE" MODIFY ("SYS_META_FIELD_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_SYS_META_VALUE" MODIFY ("SYS_META_VALUE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYSMETA_DOCUMENT
		--------------------------------------------------------

		  ALTER TABLE "BC_SYSMETA_DOCUMENT" ADD CONSTRAINT "BC_SYSMETA_DOCUMENT_PK" PRIMARY KEY ("SYS_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYSMETA_DOCUMENT" MODIFY ("SYS_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYSMETA_IMAGE
		--------------------------------------------------------

		  ALTER TABLE "BC_SYSMETA_IMAGE" ADD CONSTRAINT "BC_SYSMETA_IMAGE_PK" PRIMARY KEY ("SYS_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYSMETA_IMAGE" MODIFY ("SYS_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYSMETA_MOVIE
		--------------------------------------------------------

		  ALTER TABLE "BC_SYSMETA_MOVIE" ADD CONSTRAINT "BC_SYSMETA_MOVIE_PK" PRIMARY KEY ("SYS_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 2097152 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYSMETA_MOVIE" MODIFY ("SYS_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYSMETA_SEQUENCE
		--------------------------------------------------------

		  ALTER TABLE "BC_SYSMETA_SEQUENCE" ADD CONSTRAINT "BC_SYSMETA_SEQUENCE_PK" PRIMARY KEY ("SYS_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYSMETA_SEQUENCE" MODIFY ("SYS_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_SYSMETA_SOUND
		--------------------------------------------------------

		  ALTER TABLE "BC_SYSMETA_SOUND" ADD CONSTRAINT "BC_SYSMETA_SOUND_PK" PRIMARY KEY ("SYS_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_SYSMETA_SOUND" MODIFY ("SYS_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK" ADD CONSTRAINT "BC_TASK_PK" PRIMARY KEY ("TASK_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 27262976 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_TASK" MODIFY ("TASK_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK" MODIFY ("TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK" MODIFY ("PARAMETER" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK" MODIFY ("PRIORITY" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_AVAILABLE
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_AVAILABLE" ADD CONSTRAINT "BC_TASK_AVAILABLE_PK" PRIMARY KEY ("MODULE_INFO_ID", "TASK_RULE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_TASK_AVAILABLE" MODIFY ("MODULE_INFO_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_AVAILABLE" MODIFY ("TASK_RULE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_LOG
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_LOG" ADD CONSTRAINT "BC_TASK_LOG_PK" PRIMARY KEY ("TASK_LOG_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 452984832 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_TASK_LOG" MODIFY ("TASK_LOG_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_LOG" MODIFY ("TASK_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_LOG" MODIFY ("DESCRIPTION" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_LOG" MODIFY ("CREATION_DATE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_RULE
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_RULE" ADD CONSTRAINT "BC_TASK_RULE_PK" PRIMARY KEY ("TASK_RULE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_TASK_RULE" MODIFY ("TASK_RULE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_RULE" MODIFY ("JOB_NAME" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_STORAGE
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_STORAGE" MODIFY ("TASK_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_TYPE
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_TYPE" MODIFY ("TASK_TYPE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_TYPE" MODIFY ("TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_TYPE" MODIFY ("NAME" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_TYPE" ADD PRIMARY KEY ("TASK_TYPE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_WORKFLOW
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_WORKFLOW" ADD CONSTRAINT "BC_TASK_WORKFLOW_PK" PRIMARY KEY ("TASK_WORKFLOW_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_TASK_WORKFLOW" MODIFY ("TASK_WORKFLOW_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW" MODIFY ("USER_TASK_NAME" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW" MODIFY ("REGISTER" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW" MODIFY ("ACTIVITY" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TASK_WORKFLOW_RULE
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" ADD CONSTRAINT "BC_TASK_WORKFLOW_RULE_PK" PRIMARY KEY ("WORKFLOW_RULE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" MODIFY ("TASK_WORKFLOW_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" MODIFY ("TASK_RULE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" MODIFY ("JOB_PRIORITY" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" MODIFY ("WORKFLOW_RULE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" MODIFY ("TASK_RULE_PARANT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_TOP_MENU
		--------------------------------------------------------

		  ALTER TABLE "BC_TOP_MENU" MODIFY ("ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_TOP_MENU" MODIFY ("MENU_NAME" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_CONTENT
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT" ADD CONSTRAINT "BC_UD_CONTENT_PK" PRIMARY KEY ("UD_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_UD_CONTENT" MODIFY ("BS_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT" MODIFY ("UD_CONTENT_TITLE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_CONTENT_DELETE_INFO
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT_DELETE_INFO" ADD CONSTRAINT "BC_UD_CONTENT_DELETE_INFO_C01" CHECK (TYPE_CODE IN (\'30\', \'60\', \'-1\')) ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_UD_CONTENT_DELETE_INFO" ADD CONSTRAINT "BC_UD_CONTENT_DELETE_INFO_C02" CHECK (DATE_CODE IN (\'30\', \'60\', \'-1\')) ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_UD_CONTENT_DELETE_INFO" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT_DELETE_INFO" MODIFY ("TYPE_CODE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT_DELETE_INFO" MODIFY ("DATE_CODE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_CONTENT_GRANT
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT_GRANT" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT_GRANT" MODIFY ("GRANTED_RIGHT" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT_GRANT" MODIFY ("MEMBER_GROUP_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_CONTENT_STORAGE
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT_STORAGE" ADD CONSTRAINT "BC_UD_CONTENT_STORAGE_PK" PRIMARY KEY ("UD_CONTENT_ID", "STORAGE_ID", "US_TYPE")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_UD_CONTENT_STORAGE" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT_STORAGE" MODIFY ("STORAGE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_CONTENT_STORAGE" MODIFY ("US_TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_CONTENT_TAB
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT_TAB" MODIFY ("NAME" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_GROUP
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_GROUP" ADD CONSTRAINT "BC_UD_GROUP_PK" PRIMARY KEY ("UD_GROUP_CODE", "UD_CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_UD_GROUP" MODIFY ("UD_GROUP_CODE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_GROUP" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_STORAGE_GROUP
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_STORAGE_GROUP" ADD CONSTRAINT "BC_UD_STORAGE_GROUP_PK" PRIMARY KEY ("STORAGE_GROUP_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_UD_STORAGE_GROUP" MODIFY ("STORAGE_GROUP_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_STORAGE_GROUP" MODIFY ("STORAGE_GROUP_NM" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_STORAGE_GROUP_MAP
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_STORAGE_GROUP_MAP" ADD CONSTRAINT "BC_UD_STORAGE_GROUP_MAP_PK" PRIMARY KEY ("STORAGE_GROUP_ID", "SOURCE_STORAGE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_UD_STORAGE_GROUP_MAP" MODIFY ("STORAGE_GROUP_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_STORAGE_GROUP_MAP" MODIFY ("SOURCE_STORAGE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_UD_STORAGE_GROUP_MAP" MODIFY ("UD_STORAGE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_UD_SYSTEM
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_SYSTEM" ADD CONSTRAINT "BC_UD_SYSTEM_PK" PRIMARY KEY ("CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_UD_SYSTEM" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USR_META_CODE
		--------------------------------------------------------

		  ALTER TABLE "BC_USR_META_CODE" ADD CONSTRAINT "BC_USR_META_CODE_PK" PRIMARY KEY ("USR_META_FIELD_CODE", "USR_CODE_KEY")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_USR_META_CODE" MODIFY ("USR_META_FIELD_CODE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_USR_META_CODE" MODIFY ("USR_CODE_KEY" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table BC_USR_META_FIELD
		--------------------------------------------------------

		  ALTER TABLE "BC_USR_META_FIELD" ADD CONSTRAINT "BC_USR_META_FIELD_PK" PRIMARY KEY ("UD_CONTENT_ID", "USR_META_FIELD_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "BC_USR_META_FIELD" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_USR_META_FIELD" MODIFY ("USR_META_FIELD_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_USR_META_FIELD" MODIFY ("SHOW_ORDER" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_USR_META_FIELD" MODIFY ("USR_META_FIELD_TITLE" NOT NULL ENABLE);
		 
		  ALTER TABLE "BC_USR_META_FIELD" MODIFY ("USR_META_FIELD_TYPE" NOT NULL ENABLE);
			
		--------------------------------------------------------
		--  Constraints for Table CONTENT_CODE_INFO
		--------------------------------------------------------

		  ALTER TABLE "CONTENT_CODE_INFO" ADD CONSTRAINT "NPS_CONTENT_CODE_PK" PRIMARY KEY ("CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 3145728 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "CONTENT_CODE_INFO" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table DELETE_CONTENT_LIST
		--------------------------------------------------------

		  ALTER TABLE "DELETE_CONTENT_LIST" ADD CONSTRAINT "DELETE_CONTENT_LIST_PK" PRIMARY KEY ("CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "DELETE_CONTENT_LIST" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "DELETE_CONTENT_LIST" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "DELETE_CONTENT_LIST" MODIFY ("CREATED_DATE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table EBS_INTERFACE
		--------------------------------------------------------

		  ALTER TABLE "EBS_INTERFACE" ADD CONSTRAINT "EBS_INTERFACE_PK" PRIMARY KEY ("INTERFACE_ID", "INTERFACE_TYPE", "TARGET_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "EBS_INTERFACE" MODIFY ("INTERFACE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "EBS_INTERFACE" MODIFY ("INTERFACE_TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "EBS_INTERFACE" MODIFY ("TARGET_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table EXPORTER_SETTING
		--------------------------------------------------------

		  ALTER TABLE "EXPORTER_SETTING" ADD CONSTRAINT "EDIUS_EXPORTER_SETTING_PK" PRIMARY KEY ("ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "EXPORTER_SETTING" MODIFY ("ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "EXPORTER_SETTING" MODIFY ("NAME" NOT NULL ENABLE);
		 
		  ALTER TABLE "EXPORTER_SETTING" MODIFY ("VBITRATE" NOT NULL ENABLE);
		 
		  ALTER TABLE "EXPORTER_SETTING" MODIFY ("RENDER_ENGINE_SD" NOT NULL ENABLE);
		 
		  ALTER TABLE "EXPORTER_SETTING" MODIFY ("RENDER_ENGINE_HD" NOT NULL ENABLE);
		 
		  ALTER TABLE "EXPORTER_SETTING" MODIFY ("OUTPUT_PATH" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table IM_SCHEDULE_METADATA
		--------------------------------------------------------

		  ALTER TABLE "IM_SCHEDULE_METADATA" ADD CONSTRAINT "IM_SCHEDULE_METADATA_PK" PRIMARY KEY ("SCHEDULE_ID", "UD_CONTENT_ID", "BC_USR_META_FIELD_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "IM_SCHEDULE_METADATA" MODIFY ("SCHEDULE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "IM_SCHEDULE_METADATA" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "IM_SCHEDULE_METADATA" MODIFY ("BC_USR_META_FIELD_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table INGESTMANAGER_SCHEDULE
		--------------------------------------------------------

		  ALTER TABLE "INGESTMANAGER_SCHEDULE" ADD CONSTRAINT "INGESTMANAGER_SCHEDULE_PK" PRIMARY KEY ("SCHEDULE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("SCHEDULE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("CHANNEL" NOT NULL ENABLE);
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("SCHEDULE_TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("TITLE" NOT NULL ENABLE);
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("UD_CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE" MODIFY ("BS_CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table INGESTMANAGER_SCHEDULE_META
		--------------------------------------------------------

		  ALTER TABLE "INGESTMANAGER_SCHEDULE_META" ADD CONSTRAINT "INGESTMANAGER_SCHEDULE_ME_PK" PRIMARY KEY ("SCHEDULE_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "INGESTMANAGER_SCHEDULE_META" MODIFY ("SCHEDULE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table INTERFACE
		--------------------------------------------------------

		  ALTER TABLE "INTERFACE" MODIFY ("INTERFACE_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table INTERFACE_CH
		--------------------------------------------------------

		  ALTER TABLE "INTERFACE_CH" MODIFY ("INTERFACE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "INTERFACE_CH" MODIFY ("INTERFACE_CH_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table LINK_CMS
		--------------------------------------------------------

		  ALTER TABLE "LINK_CMS" MODIFY ("LINK_CMS_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "LINK_CMS" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table LINK_CMS_TC_XML
		--------------------------------------------------------

		  ALTER TABLE "LINK_CMS_TC_XML" MODIFY ("LINK_CMS_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "LINK_CMS_TC_XML" MODIFY ("SHOW_ORDER" NOT NULL ENABLE);
		 
		  ALTER TABLE "LINK_CMS_TC_XML" MODIFY ("LINK_CMS_TC_XML_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table LINK_XML_ERROR_LIST
		--------------------------------------------------------

		  ALTER TABLE "LINK_XML_ERROR_LIST" MODIFY ("LINK_XML_ERROR_LIST_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table LOG_SEQ_ID
		--------------------------------------------------------

		  ALTER TABLE "LOG_SEQ_ID" MODIFY ("TYPE" NOT NULL ENABLE);
		 
		  ALTER TABLE "LOG_SEQ_ID" MODIFY ("HEADER" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table MASTER_MONITOR
		--------------------------------------------------------

		  ALTER TABLE "MASTER_MONITOR" ADD CONSTRAINT "BC_MASTERROOM_MONITOR_PK" PRIMARY KEY ("REQUEST_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "MASTER_MONITOR" MODIFY ("MATERIAL_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "MASTER_MONITOR" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "MASTER_MONITOR" MODIFY ("REQUEST_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table MEMBER_EXTRA
		--------------------------------------------------------

		  ALTER TABLE "MEMBER_EXTRA" MODIFY ("MEMBER_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table NPS_WORK_LIST
		--------------------------------------------------------

		  ALTER TABLE "NPS_WORK_LIST" ADD CONSTRAINT "NPS_WORK_LIST_PK" PRIMARY KEY ("NPS_WORK_LIST_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "NPS_WORK_LIST" MODIFY ("NPS_WORK_LIST_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "NPS_WORK_LIST" MODIFY ("FROM_USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "NPS_WORK_LIST" MODIFY ("WORK_TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table PATH_MAPPING
		--------------------------------------------------------

		  ALTER TABLE "PATH_MAPPING" ADD CONSTRAINT "PATH_MAPPING_PK" PRIMARY KEY ("CATEGORY_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "PATH_MAPPING" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table PREVIEW_MAIN
		--------------------------------------------------------

		  ALTER TABLE "PREVIEW_MAIN" ADD CONSTRAINT "PREVIEW_PK" PRIMARY KEY ("NOTE_ID", "CONTENT_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "PREVIEW_MAIN" MODIFY ("NOTE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "PREVIEW_MAIN" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table PREVIEW_NOTE
		--------------------------------------------------------

		  ALTER TABLE "PREVIEW_NOTE" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "PREVIEW_NOTE" MODIFY ("NOTE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "PREVIEW_NOTE" MODIFY ("SORT" NOT NULL ENABLE);
		 
		  ALTER TABLE "PREVIEW_NOTE" MODIFY ("TYPE" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table REQUEST_CAPTION
		--------------------------------------------------------

		  ALTER TABLE "REQUEST_CAPTION" MODIFY ("REQUEST_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table REVIEW
		--------------------------------------------------------

		  ALTER TABLE "REVIEW" ADD CONSTRAINT "REVIEW_PK" PRIMARY KEY ("ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "REVIEW" MODIFY ("ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "REVIEW" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "REVIEW" MODIFY ("STATE" NOT NULL ENABLE);
		 
		  ALTER TABLE "REVIEW" MODIFY ("COMMENTS" NOT NULL ENABLE);
		 
		  ALTER TABLE "REVIEW" MODIFY ("REQUESTER" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table ROUGHCUT_PREVIEW_LIST
		--------------------------------------------------------

		  ALTER TABLE "ROUGHCUT_PREVIEW_LIST" MODIFY ("LIST_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "ROUGHCUT_PREVIEW_LIST" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table SCROLL_MONITOR
		--------------------------------------------------------

		  ALTER TABLE "SCROLL_MONITOR" MODIFY ("REQUEST_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table SGL_ARCHIVE
		--------------------------------------------------------

		  ALTER TABLE "SGL_ARCHIVE" ADD CONSTRAINT "SGL_ARCHIVE_PK" PRIMARY KEY ("TASK_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "SGL_ARCHIVE" MODIFY ("UNIQUE_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "SGL_ARCHIVE" MODIFY ("MEDIA_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "SGL_ARCHIVE" MODIFY ("TASK_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table SUBPROG_MAPPING
		--------------------------------------------------------

		  ALTER TABLE "SUBPROG_MAPPING" ADD CONSTRAINT "SUBPROG_MAPPING_PK" PRIMARY KEY ("CATEGORY_ID", "PROGCD", "SUBPROGCD", "FORMBASEYMD")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 131072 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		 
		  ALTER TABLE "SUBPROG_MAPPING" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "SUBPROG_MAPPING" MODIFY ("PARENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "SUBPROG_MAPPING" MODIFY ("PROGCD" NOT NULL ENABLE);
		 
		  ALTER TABLE "SUBPROG_MAPPING" MODIFY ("FORMBASEYMD" NOT NULL ENABLE);
		 
		  ALTER TABLE "SUBPROG_MAPPING" MODIFY ("SUBPROGCD" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table SYNC_PROGRAM
		--------------------------------------------------------

		  ALTER TABLE "SYNC_PROGRAM" ADD PRIMARY KEY ("PGM_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		--------------------------------------------------------
		--  Constraints for Table TB_ORD
		--------------------------------------------------------

		  ALTER TABLE "TB_ORD" MODIFY ("ORD_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "TB_ORD" MODIFY ("INPUT_DTM" NOT NULL ENABLE);
		 
		  ALTER TABLE "TB_ORD" ADD CONSTRAINT "TB_ORD_PK" PRIMARY KEY ("INPUT_DTM", "ORD_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 65536 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		--------------------------------------------------------
		--  Constraints for Table TB_ORD_FILE
		--------------------------------------------------------

		  ALTER TABLE "TB_ORD_FILE" MODIFY ("ORD_ID" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table TB_ORD_TRANSMISSION
		--------------------------------------------------------

		  ALTER TABLE "TB_ORD_TRANSMISSION" MODIFY ("ORD_TR_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "TB_ORD_TRANSMISSION" MODIFY ("CONTENT_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "TB_ORD_TRANSMISSION" MODIFY ("CREATE_TIME" NOT NULL ENABLE);
		 
		  ALTER TABLE "TB_ORD_TRANSMISSION" MODIFY ("CREATE_USER" NOT NULL ENABLE);
		--------------------------------------------------------
		--  Constraints for Table USER_MAPPING
		--------------------------------------------------------

		  ALTER TABLE "USER_MAPPING" MODIFY ("CATEGORY_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "USER_MAPPING" MODIFY ("USER_ID" NOT NULL ENABLE);
		 
		  ALTER TABLE "USER_MAPPING" ADD CONSTRAINT "USER_MAPPING_PK" PRIMARY KEY ("CATEGORY_ID", "USER_ID")
		  USING INDEX PCTFREE 10 INITRANS 2 MAXTRANS 255 COMPUTE STATISTICS 
		  STORAGE(INITIAL 7340032 NEXT 1048576 MINEXTENTS 1 MAXEXTENTS 2147483645
		  PCTINCREASE 0 FREELISTS 1 FREELIST GROUPS 1 BUFFER_POOL DEFAULT FLASH_CACHE DEFAULT CELL_FLASH_CACHE DEFAULT)
			ENABLE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_CATEGORY_GRANT
		--------------------------------------------------------

		  ALTER TABLE "BC_CATEGORY_GRANT" ADD CONSTRAINT "BC_CATEGORY_GRANT_BC_CATE_FK1" FOREIGN KEY ("CATEGORY_ID")
			  REFERENCES "BC_CATEGORY" ("CATEGORY_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_CATEGORY_GRANT" ADD CONSTRAINT "BC_CATEGORY_GRANT_BC_MEMB_FK1" FOREIGN KEY ("MEMBER_GROUP_ID")
			  REFERENCES "BC_MEMBER_GROUP" ("MEMBER_GROUP_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_CATEGORY_MAPPING
		--------------------------------------------------------

		  ALTER TABLE "BC_CATEGORY_MAPPING" ADD CONSTRAINT "BC_CATEGORY_MAPPING_BC_CA_FK1" FOREIGN KEY ("CATEGORY_ID")
			  REFERENCES "BC_CATEGORY" ("CATEGORY_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_CATEGORY_MAPPING" ADD CONSTRAINT "BC_CATEGORY_MAPPING_BC_UD_FK1" FOREIGN KEY ("UD_CONTENT_ID")
			  REFERENCES "BC_UD_CONTENT" ("UD_CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_CODE
		--------------------------------------------------------

		  ALTER TABLE "BC_CODE" ADD CONSTRAINT "BC_CODE_BC_CODE_TYPE_FK1" FOREIGN KEY ("CODE_TYPE_ID")
			  REFERENCES "BC_CODE_TYPE" ("ID") ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_CONTENT
		--------------------------------------------------------

		  ALTER TABLE "BC_CONTENT" ADD CONSTRAINT "BC_CONTENT_BC_BS_CONTENT_FK1" FOREIGN KEY ("BS_CONTENT_ID")
			  REFERENCES "BC_BS_CONTENT" ("BS_CONTENT_ID") ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_CONTENT" ADD CONSTRAINT "BC_CONTENT_BC_UD_CONTENT_FK1" FOREIGN KEY ("UD_CONTENT_ID")
			  REFERENCES "BC_UD_CONTENT" ("UD_CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_GRANT
		--------------------------------------------------------

		  ALTER TABLE "BC_GRANT" ADD CONSTRAINT "BC_GRANT_BC_CATEGORY_FK1" FOREIGN KEY ("CATEGORY_ID")
			  REFERENCES "BC_CATEGORY" ("CATEGORY_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_GRANT" ADD CONSTRAINT "BC_GRANT_BC_MEMBER_GROUP_FK1" FOREIGN KEY ("MEMBER_GROUP_ID")
			  REFERENCES "BC_MEMBER_GROUP" ("MEMBER_GROUP_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_GRANT" ADD CONSTRAINT "BC_GRANT_BC_UD_CONTENT_FK1" FOREIGN KEY ("UD_CONTENT_ID")
			  REFERENCES "BC_UD_CONTENT" ("UD_CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_MEDIA
		--------------------------------------------------------

		  ALTER TABLE "BC_MEDIA" ADD CONSTRAINT "BC_MEDIA_BC_CONTENT_FK1" FOREIGN KEY ("CONTENT_ID")
			  REFERENCES "BC_CONTENT" ("CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_MEMBER_GROUP_MEMBER
		--------------------------------------------------------

		  ALTER TABLE "BC_MEMBER_GROUP_MEMBER" ADD CONSTRAINT "BC_MEMBER_GROUP_MEMBER_BC_FK1" FOREIGN KEY ("MEMBER_GROUP_ID")
			  REFERENCES "BC_MEMBER_GROUP" ("MEMBER_GROUP_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_MEMBER_GROUP_MEMBER" ADD CONSTRAINT "BC_MEMBER_GROUP_MEMBER_BC_FK2" FOREIGN KEY ("MEMBER_ID")
			  REFERENCES "BC_MEMBER" ("MEMBER_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_PATH_AVAILABLE
		--------------------------------------------------------

		  ALTER TABLE "BC_PATH_AVAILABLE" ADD CONSTRAINT "BC_PATH_AVAILABLE_BC_MODU_FK1" FOREIGN KEY ("MODULE_INFO_ID")
			  REFERENCES "BC_MODULE_INFO" ("MODULE_INFO_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_SYS_META_FIELD
		--------------------------------------------------------

		  ALTER TABLE "BC_SYS_META_FIELD" ADD CONSTRAINT "BC_SYS_META_FIELD_BC_BS_C_FK1" FOREIGN KEY ("BS_CONTENT_ID")
			  REFERENCES "BC_BS_CONTENT" ("BS_CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_SYS_META_VALUE
		--------------------------------------------------------

		  ALTER TABLE "BC_SYS_META_VALUE" ADD CONSTRAINT "BC_SYS_META_VALUE_BC_CONT_FK1" FOREIGN KEY ("CONTENT_ID")
			  REFERENCES "BC_CONTENT" ("CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_TASK_LOG
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_LOG" ADD CONSTRAINT "BC_TASK_LOG_BC_TASK_FK1" FOREIGN KEY ("TASK_ID")
			  REFERENCES "BC_TASK" ("TASK_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_TASK_WORKFLOW_RULE
		--------------------------------------------------------

		  ALTER TABLE "BC_TASK_WORKFLOW_RULE" ADD CONSTRAINT "BC_TASK_WORKFLOW_RULE_BC__FK1" FOREIGN KEY ("TASK_WORKFLOW_ID")
			  REFERENCES "BC_TASK_WORKFLOW" ("TASK_WORKFLOW_ID") ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_UD_CONTENT
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT" ADD CONSTRAINT "BC_UD_CONTENT_BC_BS_CONTE_FK1" FOREIGN KEY ("BS_CONTENT_ID")
			  REFERENCES "BC_BS_CONTENT" ("BS_CONTENT_ID") ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_UD_CONTENT_DELETE_INFO
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT_DELETE_INFO" ADD CONSTRAINT "BC_UD_CONTENT_DELETE_INFO_FK1" FOREIGN KEY ("UD_CONTENT_ID")
			  REFERENCES "BC_UD_CONTENT" ("UD_CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_UD_CONTENT_STORAGE
		--------------------------------------------------------

		  ALTER TABLE "BC_UD_CONTENT_STORAGE" ADD CONSTRAINT "BC_UD_CONTENT_STORAGE_BC__FK1" FOREIGN KEY ("UD_CONTENT_ID")
			  REFERENCES "BC_UD_CONTENT" ("UD_CONTENT_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		 
		  ALTER TABLE "BC_UD_CONTENT_STORAGE" ADD CONSTRAINT "BC_UD_CONTENT_STORAGE_BC__FK2" FOREIGN KEY ("STORAGE_ID")
			  REFERENCES "BC_STORAGE" ("STORAGE_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table BC_USR_META_FIELD
		--------------------------------------------------------

		  ALTER TABLE "BC_USR_META_FIELD" ADD CONSTRAINT "BC_USR_META_FIELD_BC_UD_C_FK1" FOREIGN KEY ("UD_CONTENT_ID")
			  REFERENCES "BC_UD_CONTENT" ("UD_CONTENT_ID") ENABLE NOVALIDATE;
			--------------------------------------------------------
		--  Ref Constraints for Table CONTENT_CODE_INFO
		--------------------------------------------------------

		  ALTER TABLE "CONTENT_CODE_INFO" ADD CONSTRAINT "CONTENT_CODE_INFO_CONTENT_FK1" FOREIGN KEY ("CONTENT_ID")
			  REFERENCES "CONTENT_CODE_INFO" ("CONTENT_ID") ENABLE NOVALIDATE;
		--------------------------------------------------------
		--  Ref Constraints for Table IM_SCHEDULE_METADATA
		--------------------------------------------------------

		  ALTER TABLE "IM_SCHEDULE_METADATA" ADD CONSTRAINT "IM_SCHEDULE_METADATA_INGE_FK1" FOREIGN KEY ("SCHEDULE_ID")
			  REFERENCES "INGESTMANAGER_SCHEDULE" ("SCHEDULE_ID") ON DELETE CASCADE ENABLE NOVALIDATE;
		';
	$all_query_trg = '
		--------------------------------------------------------
		--  DDL for Trigger TRG_BC_CODE_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_BC_CODE_ID" 
			before insert on BC_CODE 
			for each row 
		 begin  
			if inserting then 
			   if :NEW.ID is null then 
				  select SEQ_BC_CODE_ID.nextval into :NEW.ID from dual; 
			   end if; 
			end if; 
		 end;



		--------------------------------------------------------
		--  DDL for Trigger BC_LOG_TRG
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "BC_LOG_TRG" 
		  before insert on BC_LOG    
			for each row begin     
			  if inserting 
			  then
				if :NEW.LOG_ID is null 
				then
				   select BC_LOG_SEQ.nextval into :NEW.LOG_ID from dual;       
				end if;
			  end if; 
		end;



		--------------------------------------------------------
		--  DDL for Trigger BC_LOG_ID_TRG
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "BC_LOG_ID_TRG" 
		before insert on "BC_LOG"
			for each row 
			begin
				if inserting then
					if :NEW."LOG_ID" is null then
						select BC_LOG_SEQ.nextval into :NEW."LOG_ID" from dual;    
					   end if;
				end if; 
			end;



		--------------------------------------------------------
		--  DDL for Trigger TRG_LOG_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_LOG_ID" before insert on 
		"BC_LOG"   for each row begin     if inserting then       if :NEW."LOG_ID" is null then
		select BC_LOG_SEQ.nextval into :NEW."LOG_ID" from dual;       end if;    end if; end;



		--------------------------------------------------------
		--  DDL for Trigger TRG_MEDIA_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_MEDIA_ID" before insert on 
		"BC_MEDIA"    for each row begin     if inserting then       if :NEW."MEDIA_ID" is null then
		select SEQ_MEDIA_ID.nextval into :NEW."MEDIA_ID" from dual;       end if;    end if; end;



		--------------------------------------------------------
		--  DDL for Trigger TRG_SYS_META_VALUE_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_SYS_META_VALUE_ID" 
			before insert on "BC_SYS_META_VALUE"    
			for each row 
				begin
					if inserting then
						if :NEW."SYS_META_VALUE_ID" is null then
							select SEQ_SYS_META_VALUE_ID.nextval into :NEW."SYS_META_VALUE_ID" from dual;
						end if;
					end if; 
				end;



		--------------------------------------------------------
		--  DDL for Trigger TASK_LOG_TRG
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TASK_LOG_TRG" 
			before insert on "BC_TASK_LOG"
			for each row 
			begin     
				if inserting then
					if :NEW."TASK_LOG_ID" is null then
						select TASK_LOG_SEQ.nextval into :NEW."TASK_LOG_ID" from dual;
						end if;    end if; end;



		--------------------------------------------------------
		--  DDL for Trigger TRG_TASK_WORKFLOW_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_TASK_WORKFLOW_ID" before insert on 
		"BC_TASK_WORKFLOW"    for each row begin     if inserting then       if :NEW."TASK_WORKFLOW_ID" is null then          select TASK_WORKFLOW_ID.nextval into :NEW."TASK_WORKFLOW_ID" from dual;       end if;    end if; end;



		--------------------------------------------------------
		--  DDL for Trigger TRG_WORKFLOW_RULE_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_WORKFLOW_RULE_ID" before insert on "BC_TASK_WORKFLOW_RULE"    for each row begin     if inserting then       if :NEW."WORKFLOW_RULE_ID" is null then          select TASK_WORKFLOW_RULE_ID_SEQ.nextval into :NEW."WORKFLOW_RULE_ID" from dual;       end if;    end 
		if; end;



		--------------------------------------------------------
		--  DDL for Trigger TRG_AD
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "TRG_AD" before insert on 
		"CT_AD_JOB"    for each row begin     if inserting then
		if :NEW."NO" is null then          select SEQ_AD.nextval into :NEW."NO" from dual;       end if;    end if; end;



		--------------------------------------------------------
		--  DDL for Trigger INGESTMANAGER_SCHEDULE_TRG
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "INGESTMANAGER_SCHEDULE_TRG" 
			before insert on "INGESTMANAGER_SCHEDULE"
			for each row 
		begin     
			if inserting then 
				if :NEW."SCHEDULE_ID" is null then
					select IM_SCHEDULE_SEQ.nextval into :NEW."SCHEDULE_ID" from dual;
				end if;
			end if;
		end;



		--------------------------------------------------------
		--  DDL for Trigger NOTE_ID
		--------------------------------------------------------

		  CREATE OR REPLACE TRIGGER "NOTE_ID" before insert on 
		"PREVIEW_MAIN"    for each row begin     
		if inserting then       if :NEW."NOTE_ID" is null then          select SEQ_NOTE_ID.nextval into :NEW."NOTE_ID" from dual;       end if;    end if; end;



		
		
		--------------------------------------------------------
		--  파일이 생성됨 - 금요일-2월-05-2016   
		--------------------------------------------------------
		--------------------------------------------------------
		--  DDL for Function FUNC_CODE
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_CODE" 
		(
		  CT IN VARCHAR2  
		, CD IN VARCHAR2  
		) RETURN VARCHAR2 AS 
		returnVal bc_code.name%TYPE;
		BEGIN
		  select c.name INTO returnVal from bc_code_type ct,bc_code c where ct.code=CT and c.code_type_id=ct.id  and c.code=CD and rownum=1;
		  RETURN returnVal;
		END FUNC_CODE;
		 

		--------------------------------------------------------
		--  DDL for Function FUNC_CODE_NAME
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_CODE_NAME" 
		(
		  PARAM_CODE_TYPE IN VARCHAR2  
		, PARAM_CODE_NAME IN VARCHAR2  
		) RETURN VARCHAR2 AS 
		returnVal bc_code.name%TYPE;
		BEGIN
		  select c.code INTO returnVal from bc_code_type ct,bc_code c where ct.code=PARAM_CODE_TYPE and c.code_type_id=ct.id  and c.name=PARAM_CODE_NAME and rownum=1;
		  RETURN returnVal;

		END FUNC_CODE_NAME;
		 

		--------------------------------------------------------
		--  DDL for Function FUNC_FILENAME
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_FILENAME" RETURN VARCHAR2 AS 
		v_val varchar2(200);
		BEGIN
		select \'P\'||TO_CHAR(SYSDATE, \'YYYYMMDD\') || LPAD(filename_seq.NEXTVAL,5,0)  into v_val from dual;
		  RETURN v_val;
		END FUNC_FILENAME;
		 

		--------------------------------------------------------
		--  DDL for Function FUNC_GET_USER_NAME
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_GET_USER_NAME" 
		(
		  P_USER_ID IN VARCHAR2 
		) RETURN VARCHAR2
		IS 
		returnVal BC_MEMBER.USER_NM%TYPE;
		BEGIN
		  returnVal := null;
		  select USER_NM into returnVal from BC_MEMBER where USER_ID=P_USER_ID;
		  RETURN returnVal;
		END FUNC_GET_USER_NAME;

		--------------------------------------------------------
		--  DDL for Function FUNC_PARSE_TASK_LOG
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_PARSE_TASK_LOG" 
		(
		  P_TASK_ID IN NUMBER
		) RETURN VARCHAR2 AS
		v_result varchar2(1000);
		BEGIN
			v_result := null;

			  SELECT CASE 
					 WHEN Instr(description, \'Cannot overwrite existing\') != 0 THEN \'<span style="color: red">중복</span>\' 
					 WHEN Instr(description, \'does not exists\') != 0 THEN \'<span style="color: red">원본없음</span>\'
					 ELSE \'<span style="color: red" ext:qtip="\' || description || \'">오류</span>\' END into v_result
				FROM bc_task_log 
			   WHERE task_id = p_task_id 
				 AND task_log_id = (SELECT Max(task_log_id) 
											FROM   bc_task_log 
											WHERE  task_id = p_task_id);
		  return v_result;
		 
		END FUNC_PARSE_TASK_LOG;

		--------------------------------------------------------
		--  DDL for Function FUNC_PROXY_URL
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_PROXY_URL" 
		(
		  BS_CONTENT_ID IN NUMBER  ,
		  ID IN NUMBER  
		) RETURN VARCHAR2 AS

		returnVal bc_media.path%TYPE;
		BEGIN
		  if( BS_CONTENT_ID =515) then  
			returnVal:=\'/img/audio_thumb.png\';
		  else 
			select m.path into returnVal from bc_media m where m.media_type=\'thumb\' and m.content_id=ID and rownum=1;
		  end if;
		  return returnVal;
		 
		END FUNC_PROXY_URL;
		 

		--------------------------------------------------------
		--  DDL for Function FUNC_TASK_TYPE_CODE
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_TASK_TYPE_CODE" 
		(
		  CT IN VARCHAR2  
		) RETURN VARCHAR2 AS 
		returnVal BC_TASK_TYPE.name%TYPE;
		BEGIN
		  select name INTO returnVal from BC_TASK_TYPE where type=CT and rownum=1;
		  RETURN returnVal;
		END FUNC_TASK_TYPE_CODE;
		 

		--------------------------------------------------------
		--  DDL for Function FUNC_TEST
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_TEST" 
		(
		  BS_CONTENT_ID IN NUMBER  
		, CONTENT_ID IN NUMBER  
		) 

		RETURN VARCHAR2
		AS
		 path bc_media.path%TYPE;
		BEGIN
		 if( bs_content_id = 506 ) then
		  select path INTO path from bc_media m where media_type=\'thumb\' and m.content_id=CONTENT_ID and rownum=1 order by m.media_id desc;
		else
		 path:=\'thumb.jpg\';
		end if;
		  RETURN path;
		END FUNC_TEST;
		 

		--------------------------------------------------------
		--  DDL for Function FUNC_USR_CODE
		--------------------------------------------------------

		  CREATE OR REPLACE FUNCTION "FUNC_USR_CODE" 
		(
		  USR_CODE IN VARCHAR2  ,
		  USR_KEY IN VARCHAR2  
		) RETURN VARCHAR2 AS 
		returnVal BC_USR_META_CODE.USR_CODE_VALUE%TYPE;
		BEGIN
		  returnVal := null;
		  select usr_code_value into returnVal from BC_USR_META_CODE where USR_META_FIELD_CODE=USR_CODE and USR_CODE_KEY=USR_KEY order by show_order;
		  RETURN returnVal;
		END FUNC_USR_CODE;
 
		';

	$all_query_otr = '	
			ALTER TRIGGER "BC_LOG_ID_TRG" ENABLE;
			ALTER TRIGGER "BC_LOG_TRG" ENABLE;
			ALTER TRIGGER "INGESTMANAGER_SCHEDULE_TRG" ENABLE;
			ALTER TRIGGER "NOTE_ID" ENABLE;
			ALTER TRIGGER "TASK_LOG_TRG" ENABLE;
			ALTER TRIGGER "TRG_AD" ENABLE;
			ALTER TRIGGER "TRG_BC_CODE_ID" ENABLE;
			ALTER TRIGGER "TRG_LOG_ID" ENABLE;
			ALTER TRIGGER "TRG_MEDIA_ID" ENABLE;
			ALTER TRIGGER "TRG_SYS_META_VALUE_ID" ENABLE;
			ALTER TRIGGER "TRG_TASK_WORKFLOW_ID" ENABLE;
			ALTER TRIGGER "TRG_WORKFLOW_RULE_ID" ENABLE;

		--------------------------------------------------------
		--  파일이 생성됨 - 화요일-2월-02-2016   
		--------------------------------------------------------
		--------------------------------------------------------
		--  DDL for View VIEW_CATEGORY
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_CATEGORY" ("CATEGORY_ID", "PARENT_ID", "CATEGORY_TITLE", "CODE", "SHOW_ORDER", "NO_CHILDREN", "EXTRA_ORDER", "PATH", "MEMBER_GROUP_ID", "STORAGE_GROUP", "USING_REVIEW", "UD_STORAGE_GROUP_ID") AS 
		  (  select c."CATEGORY_ID",c."PARENT_ID",c."CATEGORY_TITLE",c."CODE",c."SHOW_ORDER",c."NO_CHILDREN",c."EXTRA_ORDER",p.PATH,
		p.MEMBER_GROUP_ID,
		p.STORAGE_GROUP,
		p.USING_REVIEW,p.UD_STORAGE_GROUP_ID from bc_category c, path_mapping p where c.category_id=p.category_id(+) and c.parent_id=0 )
						union all
					   (  select c."CATEGORY_ID",c."PARENT_ID",c."CATEGORY_TITLE",c."CODE",c."SHOW_ORDER",c."NO_CHILDREN",c."EXTRA_ORDER",p.PATH,
		p.MEMBER_GROUP_ID,
		p.STORAGE_GROUP,
		p.USING_REVIEW,p.UD_STORAGE_GROUP_ID from bc_category c, path_mapping p where c.parent_id=p.category_id(+) and (c.parent_id!=0 or c.parent_id is null ) )
		 ;
		--------------------------------------------------------
		--  DDL for View VIEW_BC_CONTENT
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_BC_CONTENT" ("BS_CONTENT_TITLE", "BS_CONTENT_CODE", "UD_CONTENT_TITLE", "UD_CONTENT_CODE", "CATEGORY_TITLE", "IS_GROUP", "GROUP_COUNT", "STATE", "CATEGORY_ID", "CATEGORY_FULL_PATH", "BS_CONTENT_ID", "UD_CONTENT_ID", "CONTENT_ID", "TITLE", "IS_DELETED", "IS_HIDDEN", "REG_USER_ID", "EXPIRED_DATE", "LAST_MODIFIED_DATE", "CREATED_DATE", "STATUS", "READED", "LAST_ACCESSED_DATE", "PARENT_CONTENT_ID", "USER_NM", "MEMBER_ID", "CATEGORY_PATH", "CATEGORY_MEMBER_GROUP_ID", "PARENT_ID", "UD_SYSTEM_CODE") AS 
		  SELECT 
		BC.BS_CONTENT_TITLE BS_CONTENT_TITLE,
		BC.BS_CONTENT_CODE BS_CONTENT_CODE,
		UC.UD_CONTENT_TITLE UD_CONTENT_TITLE,
		UC.UD_CONTENT_CODE UD_CONTENT_CODE,
		CA.CATEGORY_TITLE,
		c.is_group, c.group_count, c.state,
		c.CATEGORY_ID,c.CATEGORY_FULL_PATH,c.BS_CONTENT_ID,c.UD_CONTENT_ID,
		c.CONTENT_ID,c.TITLE,c.IS_DELETED,c.IS_HIDDEN,c.REG_USER_ID,c.EXPIRED_DATE,
		c.LAST_MODIFIED_DATE,c.CREATED_DATE,c.STATUS,c.READED,c.LAST_ACCESSED_DATE,
		c.PARENT_CONTENT_ID,
		BM.USER_NM USER_NM, BM.MEMBER_ID MEMBER_ID, ca.path category_path,  ca.member_group_id category_member_group_id, ca.PARENT_ID PARENT_ID 
		,US.UD_SYSTEM_CODE UD_SYSTEM_CODE
		FROM
		BC_CONTENT C,
		BC_BS_CONTENT BC,
		BC_UD_CONTENT UC,
		BC_MEMBER BM,
		BC_UD_SYSTEM US,
		( select c.* from  VIEW_CATEGORY c  ) CA
		WHERE
		C.BS_CONTENT_ID=BC.BS_CONTENT_ID
		AND C.UD_CONTENT_ID=UC.UD_CONTENT_ID
		AND C.CATEGORY_ID=CA.CATEGORY_ID
		AND C.REG_USER_ID=BM.USER_ID(+)
		AND C.CONTENT_ID=US.CONTENT_ID(+);
		
		--------------------------------------------------------
		--  DDL for View VIEW_CONTENT
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_CONTENT" ("BS_CONTENT_TITLE", "UD_CONTENT_TITLE", "CATEGORY_TITLE", "CATEGORY_ID", "CATEGORY_FULL_PATH", "BS_CONTENT_ID", "UD_CONTENT_ID", "CONTENT_ID", "TITLE", "IS_DELETED", "IS_HIDDEN", "REG_USER_ID", "EXPIRED_DATE", "LAST_MODIFIED_DATE", "CREATED_DATE", "STATUS", "READED", "LAST_ACCESSED_DATE", "PARENT_CONTENT_ID", "USER_NM", "MEMBER_ID", "FILE_CREATED_DATE", "FORMBASEYMD", "PROGCD", "SUBPROGCD", "BRODYMD", "MEDCD", "ARCHIVE_STATUS", "CATEGORY_CODE", "CATEGORY_PATH", "CATEGORY_MEMBER_GROUP_ID", "PARENT_ID", "SUB_CATEGORY_ID", "MEDIA_STATUS", "USING_REVIEW", "UD_STORAGE_GROUP_ID", "STORAGE_GROUP", "REGISTER_TYPE") AS 
		  SELECT 
		BC.BS_CONTENT_TITLE BS_CONTENT_TITLE,
		UC.UD_CONTENT_TITLE UD_CONTENT_TITLE,
		CA.CATEGORY_TITLE,
		c."CATEGORY_ID",c."CATEGORY_FULL_PATH",c."BS_CONTENT_ID",c."UD_CONTENT_ID",c."CONTENT_ID",c."TITLE",c."IS_DELETED",c."IS_HIDDEN",c."REG_USER_ID",c."EXPIRED_DATE",c."LAST_MODIFIED_DATE",c."CREATED_DATE",c."STATUS",c."READED",c."LAST_ACCESSED_DATE",c."PARENT_CONTENT_ID",
		BM.USER_NM USER_NM, BM.MEMBER_ID MEMBER_ID,cc.file_created_date file_created_date,cc.formbaseymd, cc.progcd, cc.subprogcd,brodymd,cc.medcd, n.status archive_status, ca.code category_code , ca.path category_path,  ca.member_group_id category_member_group_id, ca.PARENT_ID PARENT_ID , ca.sub_category_id sub_category_id,
		m.status media_status,
		ca.using_review,ca.UD_STORAGE_GROUP_ID,ca.storage_group,cc.register_type
		FROM
		BC_CONTENT C,
		BC_BS_CONTENT BC,
		BC_UD_CONTENT UC,
		BC_MEMBER BM,
		( select c.* ,s.category_id sub_category_id  from  VIEW_CATEGORY c, SUBPROG_MAPPING s where  c.category_id=s.category_id(+)  ) CA,
		content_code_info cc,
		( select * from bc_media where media_type=\'original\' ) m,
		( select * from nps_work_list where work_type=\'archive\' ) n
		WHERE
		C.BS_CONTENT_ID=BC.BS_CONTENT_ID
		AND C.UD_CONTENT_ID=UC.UD_CONTENT_ID
		and cc.content_id(+)=c.content_id
		and n.content_id(+)=c.content_id
		and m.content_id(+)=c.content_id

		AND C.CATEGORY_ID=CA.CATEGORY_ID
		AND C.REG_USER_ID=BM.USER_ID(+)
		 ;
		--------------------------------------------------------
		--  DDL for View VIEW_INTERFACE
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_INTERFACE" ("INTERFACE_ID", "INTERFACE_TITLE", "INTERFACE_FROM_TYPE", "INTERFACE_FROM_ID", "INTERFACE_TARGET_TYPE", "INTERFACE_TARGET_ID", "INTERFACE_BASE_CONTENT_ID", "CREATE_DATE", "INTERFACE_CH_ID", "INTERFACE_CHANNEL", "INTERFACE_TYPE", "TARGET_ID", "TARGET_CONTENT_ID", "CH_CREATE_DATE") AS 
		  select i.INTERFACE_ID ,
		i.INTERFACE_TITLE,
		i.INTERFACE_FROM_TYPE,
		i.INTERFACE_FROM_ID,
		i.INTERFACE_TARGET_TYPE,
		i.INTERFACE_TARGET_ID,
		i.INTERFACE_BASE_CONTENT_ID,
		i.CREATE_DATE CREATE_DATE,

		ic.INTERFACE_CH_ID,
		ic.INTERFACE_CHANNEL,
		ic.INTERFACE_TYPE,
		ic.TARGET_ID,
		ic.TARGET_CONTENT_ID,
		ic.CREATE_DATE CH_CREATE_DATE
		from interface i,interface_ch ic where i.interface_id=ic.interface_id
		 ;
		
		
		--------------------------------------------------------
		--  DDL for View VIEW_PREVIEW
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_PREVIEW" ("NOTE_ID", "CONTENT_ID", "TALENT", "SHOTGU", "SHOTLO", "SHOTDATE", "SEASON", "SHOTMET", "SHOTOR", "SOURCE", "REGISTDATE", "SORT", "TYPE", "START_TC", "CONTENT", "VIGO") AS 
		  select
				pm.NOTE_ID,
				pm.CONTENT_ID,
				pm.TALENT,
				pm.SHOTGU,
				pm.SHOTLO,
				pm.SHOTDATE,
				pm.SEASON,
				pm.SHOTMET,
				pm.SHOTOR,
				pm.SOURCE,
				pm.REGISTDATE, 
				pn.SORT,
				pn.TYPE,
				pn.START_TC,
				pn.CONTENT,
				pn.VIGO
			from
				preview_main pm,
				preview_note pn ,
				nps_work_list n,
				( select  max(REGISTDATE) , content_id from preview_main group by  content_id ) maxc
			where
				pm.note_id=pn.note_id
			and
				pm.content_id=maxc.content_id
			and
				pm.content_id=n.content_id
			and
				n.work_type=\'preview\'
		 ;
		--------------------------------------------------------
		--  DDL for View VIEW_SYS_META
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_SYS_META" ("CONTENT_ID", "SYS_META_VALUE", "BS_CONTENT_ID", "SYS_META_FIELD_ID", "SYS_META_FIELD_TITLE", "FIELD_INPUT_TYPE", "SHOW_ORDER", "IS_VISIBLE", "DEFAULT_VALUE") AS 
		  SELECT 
		SMV.CONTENT_ID CONTENT_ID, SMV.SYS_META_VALUE  SYS_META_VALUE, SMF."BS_CONTENT_ID",SMF."SYS_META_FIELD_ID",SMF."SYS_META_FIELD_TITLE",SMF."FIELD_INPUT_TYPE",SMF."SHOW_ORDER",SMF."IS_VISIBLE",SMF."DEFAULT_VALUE"
		FROM
		BC_SYS_META_FIELD SMF,
		BC_SYS_META_VALUE SMV
		WHERE
		SMF.SYS_META_FIELD_ID=SMV.SYS_META_FIELD_ID
		 ;
		--------------------------------------------------------
		--  DDL for View VIEW_TASK
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_TASK" ("CONTENT_ID", "TYPE_NAME", "USER_TASK_NAME", "SRC_STORAGE_PATH", "TRG_STORAGE_PATH", "STATUS_NM", "MEDIA_ID", "TASK_ID", "TYPE", "SOURCE", "SOURCE_ID", "SOURCE_PW", "TARGET", "TARGET_ID", "TARGET_PW", "PARAMETER", "PROGRESS", "STATUS", "PRIORITY", "START_DATETIME", "COMPLETE_DATETIME", "CREATION_DATETIME", "DESTINATION", "TASK_WORKFLOW_ID", "JOB_PRIORITY", "TASK_RULE_ID", "ASSIGN_IP", "ROOT_TASK", "TASK_USER_ID", "WORKFLOW_RULE_ID", "SRC_CONTENT_ID", "SRC_MEDIA_ID", "SRC_STORAGE_ID", "TRG_STORAGE_ID", "TRG_MEDIA_ID") AS 
		  select 
		(select content_id from bc_media where media_id=t.media_id ) content_id,
		FUNC_TASK_TYPE_CODE( t.type) type_name,
		(select USER_TASK_NAME from BC_TASK_WORKFLOW where task_workflow_id=t.task_workflow_id ) USER_TASK_NAME,
		( select path from bc_storage where storage_id=t.src_storage_id ) src_storage_path,
		( select path from bc_storage where storage_id=t.trg_storage_id ) trg_storage_path,
		FUNC_CODE(\'TASK_STATUS\',t.status) status_nm,
		t."MEDIA_ID",t."TASK_ID",t."TYPE",t."SOURCE",t."SOURCE_ID",t."SOURCE_PW",t."TARGET",t."TARGET_ID",t."TARGET_PW",t."PARAMETER",t."PROGRESS",t."STATUS",t."PRIORITY",t."START_DATETIME",t."COMPLETE_DATETIME",t."CREATION_DATETIME",t."DESTINATION",t."TASK_WORKFLOW_ID",t."JOB_PRIORITY",t."TASK_RULE_ID",t."ASSIGN_IP",t."ROOT_TASK",t."TASK_USER_ID",t."WORKFLOW_RULE_ID",t."SRC_CONTENT_ID",t."SRC_MEDIA_ID",t."SRC_STORAGE_ID",t."TRG_STORAGE_ID",t."TRG_MEDIA_ID"
		from bc_task t
		 ;
		--------------------------------------------------------
		--  DDL for View VIEW_UD_STORAGE
		--------------------------------------------------------

		  CREATE OR REPLACE FORCE VIEW "VIEW_UD_STORAGE" ("STORAGE_ID", "TYPE", "LOGIN_ID", "LOGIN_PW", "PATH", "NAME", "GROUP_NAME", "MAC_ADDRESS", "AUTHORITY", "DESCRIBE", "DESCRIPTION", "VIRTUAL_PATH", "PATH_FOR_MAC", "UD_CONTENT_ID", "US_TYPE") AS 
		  SELECT 
		st."STORAGE_ID",st."TYPE",st."LOGIN_ID",st."LOGIN_PW",st."PATH",st."NAME",st."GROUP_NAME",st."MAC_ADDRESS",st."AUTHORITY",st."DESCRIBE",st."DESCRIPTION",st."VIRTUAL_PATH",st."PATH_FOR_MAC", UCS.UD_CONTENT_ID, UCS.US_TYPE
			FROM
			BC_STORAGE ST,
			BC_UD_CONTENT_STORAGE UCS
			WHERE
			ST.STORAGE_ID=UCS.STORAGE_ID
		 ;

		--------------------------------------------------------
		--  파일이 생성됨 - 화요일-2월-02-2016   
		--------------------------------------------------------
		
		Insert into BC_MEMBER (MEMBER_ID,USER_ID,PASSWORD,USER_NM,DEPT_NM,EMAIL,IS_DENIED,EXPIRED_DATE,LAST_LOGIN_DATE,IS_ADMIN,CREATED_DATE,EXTRA_VARS,OCCU_KIND,JOB_RANK,JOB_POSITION,JOB_DUTY,HIRED_DATE,DEP_TEL_NUM,BREAKE,RETIRE_DATE,MEMBER_NO,SESSION_ID,ORI_PASSWORD,PHONE,LOGIN_FAIL_CNT,PASSWORD_CHANGE_DATE,LANG) values (2,\'user1\',\'5821bc6ee005a12f61eb9c8068e9b013\',\'사용자1\',null,null,null,null,\'20160211145059\',null,\'20160211145059\',null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null);
		Insert into BC_MEMBER (MEMBER_ID,USER_ID,PASSWORD,USER_NM,DEPT_NM,EMAIL,IS_DENIED,EXPIRED_DATE,LAST_LOGIN_DATE,IS_ADMIN,CREATED_DATE,EXTRA_VARS,OCCU_KIND,JOB_RANK,JOB_POSITION,JOB_DUTY,HIRED_DATE,DEP_TEL_NUM,BREAKE,RETIRE_DATE,MEMBER_NO,SESSION_ID,ORI_PASSWORD,PHONE,LOGIN_FAIL_CNT,PASSWORD_CHANGE_DATE,LANG) values (1,\'admin\',\'5821bc6ee005a12f61eb9c8068e9b013\',\'관리자\',\'개발팀\',\'help@gemiso.com\',null,null,\'20160106113116\',\'Y\',\'20100825111111\',null,null,null,null,null,null,null,null,null,null,null,\'1\',\'02-857-1101\',0,\'20160106113110\',\'ko\');

		Insert into BC_MEMBER_GROUP (MEMBER_GROUP_ID,MEMBER_GROUP_NAME,IS_DEFAULT,IS_ADMIN,DESCRIPTION,CREATED_DATE,ALLOW_LOGIN) values (2,\'일반사용자\',\'N\',null,\'일반적으로 업무를 수행하는 사용자들의 그룹\',\'20160211144823\',null);
		Insert into BC_MEMBER_GROUP (MEMBER_GROUP_ID,MEMBER_GROUP_NAME,IS_DEFAULT,IS_ADMIN,DESCRIPTION,CREATED_DATE,ALLOW_LOGIN) values (3,\'기본권한\',\'Y\',null,\'그룹이 없는 사용자로 로그인시 부여되는 기본권한 그룹\',\'20101203155100\',null);
		Insert into BC_MEMBER_GROUP (MEMBER_GROUP_ID,MEMBER_GROUP_NAME,IS_DEFAULT,IS_ADMIN,DESCRIPTION,CREATED_DATE,ALLOW_LOGIN) values (1,\'관리자\',\'N\',\'Y\',\'관리자 그룹\',\'20110104172226\',\'Y\');

		Insert into BC_MEMBER_GROUP_MEMBER (MEMBER_ID,MEMBER_GROUP_ID) values (1,1);
		Insert into BC_MEMBER_GROUP_MEMBER (MEMBER_ID,MEMBER_GROUP_ID) values (2,2);
		
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (1,0,\'자료영상\',null,null,\'0\',null,0);
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (2,0,\'편집영상\',null,null,\'0\',null,0);
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (3,0,\'제작완료영상\',null,null,\'0\',null,0);
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (4,0,\'사운드\',null,null,\'0\',null,0);
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (5,0,\'이미지\',null,null,\'0\',null,0);
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (6,0,\'문서\',null,null,\'0\',null,0);
		Insert into BC_CATEGORY (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN,EXTRA_ORDER,IS_DELETED) values (0,null,\'Proxima\',\'1\',null,\'0\',null,0);
		

		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (506,\'동영상\',1,null,\'모든동영상\',null,\'MOVIE\');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (515,\'사운드\',2,null,\'모든음향파일\',null,\'SOUND\');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (518,\'이미지\',3,null,\'모든 사진\',null,\'IMAGE\');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (57057,\'문서\',6,null,\'모든 문서 파일\',null,\'DOCUMENT\');
		Insert into BC_BS_CONTENT (BS_CONTENT_ID,BS_CONTENT_TITLE,SHOW_ORDER,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,BS_CONTENT_CODE) values (57078,\'시퀀스\',7,null,\'모든 시퀀스 파일\',\'20120424205401\',\'SEQUENCE\');

		Insert into BC_UD_CONTENT (BS_CONTENT_ID,UD_CONTENT_ID,SHOW_ORDER,UD_CONTENT_TITLE,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,EXPIRE_DATE,SMEF_DM,EXPIRED_DATE,STORAGE_ID,CON_EXPIRE_DATE,UD_CONTENT_CODE) values (518,5,5,\'이미지\',\'.jpg,.png,.bmp,.zip\',\'이미지\',\'20160211153552\',null,null,\'-1\',null,null,\'IMAGE\');
		Insert into BC_UD_CONTENT (BS_CONTENT_ID,UD_CONTENT_ID,SHOW_ORDER,UD_CONTENT_TITLE,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,EXPIRE_DATE,SMEF_DM,EXPIRED_DATE,STORAGE_ID,CON_EXPIRE_DATE,UD_CONTENT_CODE) values (506,3,3,\'제작완료영상\',\'.mxf,.mov\',\'제작완료영상\',\'20160211153326\',null,null,\'-1\',null,null,\'MASTER\');
		Insert into BC_UD_CONTENT (BS_CONTENT_ID,UD_CONTENT_ID,SHOW_ORDER,UD_CONTENT_TITLE,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,EXPIRE_DATE,SMEF_DM,EXPIRED_DATE,STORAGE_ID,CON_EXPIRE_DATE,UD_CONTENT_CODE) values (506,1,1,\'자료영상\',\'.mov,.mxf,.mts,.mp4,.wmv\',\'자료영상\',\'20160211152440\',null,null,\'-1\',null,null,\'SOURCE\');
		Insert into BC_UD_CONTENT (BS_CONTENT_ID,UD_CONTENT_ID,SHOW_ORDER,UD_CONTENT_TITLE,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,EXPIRE_DATE,SMEF_DM,EXPIRED_DATE,STORAGE_ID,CON_EXPIRE_DATE,UD_CONTENT_CODE) values (506,2,2,\'편집영상\',\'.mxf,.mov\',\'편집영상\',\'20160211153156\',null,null,\'-1\',null,null,\'EDIT\');
		Insert into BC_UD_CONTENT (BS_CONTENT_ID,UD_CONTENT_ID,SHOW_ORDER,UD_CONTENT_TITLE,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,EXPIRE_DATE,SMEF_DM,EXPIRED_DATE,STORAGE_ID,CON_EXPIRE_DATE,UD_CONTENT_CODE) values (515,4,4,\'사운드\',\'.mp3,.mp2,.wav\',\'사운드\',\'20160211153434\',null,null,\'-1\',null,null,\'SOUND\');
		Insert into BC_UD_CONTENT (BS_CONTENT_ID,UD_CONTENT_ID,SHOW_ORDER,UD_CONTENT_TITLE,ALLOWED_EXTENSION,DESCRIPTION,CREATED_DATE,EXPIRE_DATE,SMEF_DM,EXPIRED_DATE,STORAGE_ID,CON_EXPIRE_DATE,UD_CONTENT_CODE) values (57057,6,6,\'문서\',\'.txt\',\'문서\',\'20160211153707\',null,null,\'-1\',null,null,\'DOCUMENT\');

		Insert into BC_CATEGORY_MAPPING (UD_CONTENT_ID,CATEGORY_ID) values (1,1);
		Insert into BC_CATEGORY_MAPPING (UD_CONTENT_ID,CATEGORY_ID) values (2,2);
		Insert into BC_CATEGORY_MAPPING (UD_CONTENT_ID,CATEGORY_ID) values (3,3);
		Insert into BC_CATEGORY_MAPPING (UD_CONTENT_ID,CATEGORY_ID) values (4,4);
		Insert into BC_CATEGORY_MAPPING (UD_CONTENT_ID,CATEGORY_ID) values (5,5);
		Insert into BC_CATEGORY_MAPPING (UD_CONTENT_ID,CATEGORY_ID) values (6,6);

		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (62,\'CONTENT_STATUS\',\'콘텐츠상태\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (238,\'CONTENT_STATUS_ENG\',\'Content status\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (132,\'ED_to_tapeless\',\'EDIUS에서 종편 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (170,\'FCP_to_subcontrol\',\'FCP에서 부조 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (130,\'FCP_to_tapeless\',\'FCP에서 종편 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (4,\'FLDDDT\',\'파일별 삭제기한\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (5,\'FLDLNM\',\'삭제기한 콘텐츠파일종류\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (91,\'MEDCD\',\'매체코드\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (90,\'NPS_WORK\',\'제작정보\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (150,\'NPS_to_subcontrol\',\'NPS에서 부조 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (131,\'NPS_to_tapeless\',\'NPS에서 종편 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (233,\'QUOTA_LIMIT\',\'쿼터 알림 설정값\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (241,\'REVIEW_STATUS\',\'심의 상태\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (190,\'SCTL_to_NPS\',\'부조에서 NPS 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (243,\'SOLR_USEABLE\',\'검색엔진 사용여부\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (230,\'STORAGE_ROOT\',\'스토리지 루트\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (210,\'TAPEL_to_NPS\',\'종편에서 NPS 전송\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (231,\'TASK_STATUS\',\'작업상태\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (236,\'TEST\',\'테스트\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (3,\'UCDDDT\',\'콘텐츠 별 삭제기한\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (2,\'UCSDDT\',\'사용자콘텐츠 삭제기한\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (171,\'XDS_IP_CHANNEL\',\'XDS 서버 정보\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (1,\'content_grant\',\'콘텐츠 권한\',\'BC_SYS_CODE 값과 연계하여 SYS_CODE값에 따라 권한 여부를 적용하기 위해 추가\');
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (244,\'grant_content\',\'콘텐츠 접근 권한\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (21,\'ingest_ip\',\'인제스트 서버\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (42,\'main_summary_field\',\'메인 그리드 섬네일,요약보기 대상 필드 코드\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (237,\'main_summary_field_eng\',\'메인 그리드 미리보기, 요약보기 대상 필드 코드 영문\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (83,\'sgl_active_check\',\'SGL ACTIVE CHECK\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (82,\'sgl_group_list\',\'SGL Group\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (110,\'storage_group\',\'스토리지 그룹\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (41,\'testingest\',\'192.168.3.151\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (234,\'ud_content_tab\',\'사용자 정의 콘텐츠 세부구분\',null);
		Insert into BC_CODE_TYPE (ID,CODE,NAME,REF1) values (235,\'ㄴㅇ\',\'ㄴㅇㄹ\',null);
		
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (418,\'1\',\'Thumbnail View\',237,null,0,\'(en)Thumbnail View\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (421,\'-2\',\'(E)등록중\',238,null,0,\'(en)(E)등록중\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (428,\'4\',\'(E)심의승인\',238,null,0,\'(en)(E)심의승인\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (430,\'6\',\'(E)심의조건부승인\',238,null,0,\'(en)(E)심의조건부승인\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (443,\'3\',\'심의대기\',241,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (445,\'5\',\'심의반려\',241,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (446,\'6\',\'심의조건부승인\',241,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (486,\'32\',\'FTP Transfer #1\',1,null,0,null,\'CONTEXT_GRANT\');
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (374,\'88\',\'스토리지 ID\',230,null,0,\'(en)스토리지 ID\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (378,\'error\',\'실패\',231,null,0,\'(en)실패\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (380,\'canceled\',\'작업취소\',231,null,0,\'(en)작업취소\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (386,\'usr_materialid\',\'mtrl_id\',232,null,0,\'(en)mtrl_id\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (387,\'usr_progid\',\'pgm_id\',232,null,0,\'(en)pgm_id\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (388,\'usr_turn\',\'epsd_id\',232,null,0,\'(en)epsd_id\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (389,\'usr_program\',\'pgm_nm\',232,null,0,\'(en)pgm_nm\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (391,\'ori_path\',\'path\',232,null,0,\'(en)path\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (383,\'transfer\',\'전송\',90,null,0,\'(en)전송\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (390,\'10\',\'쿼터 알림 제한값\',233,null,0,\'(en)쿼터 알림 제한값\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (173,\'2\',\'AD\',110,null,0,\'(en)AD\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (61,\'1\',\'미리 보기\',42,null,0,\'Thumbnail View\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (62,\'2\',\'요약 보기\',42,null,0,\'Summary view\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (84,\'3\',\'심의대기\',62,null,0,\'(en)심의대기\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (88,\'-5\',\'반려\',62,null,0,\'(en)반려\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (42,\'30\',\'30일\',3,null,0,\'(en)30일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (45,\'192.168.3.151\',\'인제스트1\',21,null,0,\'(en)인제스트1\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (41,\'30\',\'30일\',2,null,0,\'(en)30일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (44,\'30\',\'30일\',4,null,0,\'(en)30일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (20,\'pfr\',\'구간추출파일\',5,null,0,\'(en)구간추출파일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (19,\'original\',\'원본파일\',5,null,0,\'(en)원본파일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (47,\'60\',\'60일\',3,null,0,\'(en)60일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (15,\'-1\',\'영구보존\',2,null,0,\'(en)영구보존\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (17,\'-1\',\'영구보존\',3,null,0,\'(en)영구보존\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (18,\'-1\',\'영구보존\',4,null,0,\'(en)영구보존\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (21,\'capture\',\'캡쳐이미지파일\',5,null,0,\'(en)캡쳐이미지파일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (48,\'60\',\'60일\',4,null,0,\'(en)60일\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (81,\'0\',\'대기\',62,null,0,\'(en)대기\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (82,\'2\',\'승인\',62,null,0,\'(en)승인\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (83,\'-2\',\'등록중\',62,null,0,\'(en)등록중\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (85,\'4\',\'심의승인\',62,null,0,\'(en)심의승인\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (86,\'5\',\'심의반려\',62,null,0,\'(en)심의반려\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (87,\'6\',\'심의조건부승인\',62,null,0,\'(en)심의조건부승인\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (89,\'-6\',\'재승인요청\',62,null,0,\'(en)재승인요청\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (132,\'-7\',\'삭제요청\',62,null,0,\'(en)삭제요청\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (92,\'info\',\'제작정보\',90,null,0,\'(en)제작정보\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (130,\'archive\',\'아카이브\',90,null,0,\'(en)아카이브\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (90,\'preview\',\'프리뷰노트\',90,null,0,\'(en)프리뷰노트\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (91,\'review\',\'심의\',90,null,0,\'(en)심의\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (375,\'queue\',\'작업대기\',231,null,0,\'(en)작업대기\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (376,\'processing\',\'작업중\',231,null,0,\'(en)작업중\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (377,\'complete\',\'작업성공\',231,null,0,\'(en)작업성공\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (379,\'assigning\',\'작업할당중\',231,null,0,\'(en)작업할당중\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (381,\'pending\',\'작업대기중\',231,null,0,\'(en)작업대기중\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (382,\'regist\',\'등록\',90,null,0,\'(en)등록\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (392,\'program\',\'프로그램\',234,null,0,\'(en)프로그램\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (393,\'topic\',\'토픽\',234,null,0,\'(en)토픽\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (415,\'4\',\'상세 보기\',42,null,0,\'List View\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (417,\'ㄴㅇㄹ\',\'ㄴㅇㄹ\',235,null,0,\'(en)ㄴㅇㄹ\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (416,\'TEST\',\'테스트\',236,null,0,\'(en)테스트\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (420,\'4\',\'List View\',237,null,0,\'(en)List View\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (444,\'4\',\'심의승인\',241,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (419,\'2\',\'Summary View\',237,null,0,\'(en)Summary View\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (119,\'N\',\'Active Code\',83,null,0,\'(en)Active Code\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (423,\'-6\',\'(E)재승인요청\',238,null,0,\'(en)(E)재승인요청\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (424,\'-7\',\'(E)삭제요청\',238,null,0,\'(en)(E)삭제요청\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (425,\'0\',\'(E)대기\',238,null,0,\'(en)(E)대기\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (426,\'2\',\'(E)승인\',238,null,0,\'(en)(E)승인\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (427,\'3\',\'(E)심의대기\',238,null,0,\'(en)(E)심의대기\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (429,\'5\',\'(E)심의반려\',238,null,0,\'(en)(E)심의반려\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (448,\'SGLDSK2\',\'SGLDSK2\',82,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (447,\'SGLDSK1\',\'SGLDSK1\',82,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (451,\'0\',\'1: 사용 0:미사용\',243,null,0,null,null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (463,\'1\',\'읽기\',244,null,0,\'Read\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (464,\'2\',\'수정\',244,null,0,\'Edit\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (465,\'4\',\'등록\',244,null,0,\'Register\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (466,\'8\',\'다운로드\',244,null,0,\'Download\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (467,\'16\',\'카테고리 수정\',244,null,0,\'Category Manage\',null);
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (468,\'1\',\'작업흐름보기\',1,null,0,\'Show Workflow\',\'BASE_GRANT\');
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (469,\'2\',\'관심콘텐츠\',1,null,0,\'Favorate\',\'BASE_GRANT\');
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (470,\'4\',\'삭제\',1,null,0,\'Delete\',\'BASE_GRANT\');
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (471,\'8\',\'아카이브\',1,null,0,\'Archive\',\'INTERWORK_FLASHNET\');
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (472,\'16\',\'리스토어\',1,null,0,\'Restore\',\'INTERWORK_FLASHNET\');
		Insert into BC_CODE (ID,CODE,NAME,CODE_TYPE_ID,SORT,HIDDEN,ENAME,REF1) values (422,\'-5\',\'(E)반려\',238,null,0,\'(en)(E)반려\',null);

		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (1,1,\'grant_content\',31,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (2,1,\'grant_content\',31,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (3,1,\'grant_content\',31,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (4,1,\'grant_content\',31,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (5,1,\'grant_content\',31,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (6,1,\'grant_content\',31,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (3,2,\'grant_content\',1,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (1,2,\'grant_content\',1,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (2,2,\'grant_content\',1,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (4,2,\'grant_content\',1,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (5,2,\'grant_content\',1,0,\'/0\');
		Insert into BC_GRANT_ACCESS (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (6,2,\'grant_content\',1,0,\'/0\');
		
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (2,1,\'content_grant\',31,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (3,1,\'content_grant\',31,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (4,1,\'content_grant\',31,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (5,1,\'content_grant\',31,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (6,1,\'content_grant\',31,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (1,1,\'content_grant\',31,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (3,2,\'content_grant\',1,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (4,2,\'content_grant\',1,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (5,2,\'content_grant\',1,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (6,2,\'content_grant\',1,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (1,2,\'content_grant\',1,0,\'/0\');
		Insert into BC_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,GRANT_TYPE,GROUP_GRANT,CATEGORY_ID,CATEGORY_FULL_PATH) values (2,2,\'content_grant\',1,0,\'/0\');
		
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (10,\'INTERWORK_ZODIAC\',\'ZODIAC 연계 여부\',1,1,\'N\',\'ZODIAC 연계 적용 여부 의뢰, 보도정보 상단 메뉴 등 처리\',null,null,null,null,null,\'20151208000000\',0,\'20151208000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (1,\'HRDK_DTL_PANEL\',\'한국산업인력공단 분류체계 등록 패널 사용여부\',1,1,\'N\',\'한국산업인력공단 분류체계 등록 패널 사용여부\',null,null,null,null,null,\'20150713000000\',0,\'20150713000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (7,\'CHECK_PASSWORD_YN\',\'비밀번호 체크 사용여부\',1,1,\'N\',\'비밀번호 변경, 추가시 비밀번호 형식 체크 사용여부\',null,null,null,null,null,\'20150826000000\',0,\'20150826000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (8,\'USE_MENU_ACCEPT\',\'콘텍스트메뉴에서 승인메뉴 사용여부\',1,1,\'N\',\'검색화면 콘텍스트메뉴에서 승인 메뉴 사용여부\',null,null,null,null,null,\'20150826000000\',0,\'20150826000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (6,\'HRDK_YN\',\'한국산업인력공단 적용여부\',1,1,\'N\',\'한국산업인력공단 소스 적용여부\',null,null,null,null,null,\'20150816000000\',0,\'20150816000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (2,\'CUESHEET_USE_YN\',\'큐시트 사용여부\',1,1,\'N\',\'큐시트 사용여부\',null,null,null,null,null,\'20150716000000\',0,\'20150716000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (3,\'MONITERING_USE_YN\',\'모니터링 메뉴 사용여부\',1,1,\'N\',\'모니터링 메뉴 사용여부\',null,null,null,null,null,\'20150721000000\',0,\'20150721000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (4,\'QUARTER_PASS_CHANGE_YN\',\'분기마다 비밀번호 변경 기능 적용여부\',1,1,\'N\',\'분기마다 비밀번호 변경 기능 적용여부\',null,null,null,null,null,\'20150721000000\',0,\'20150721000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (9,\'DUPLICATE_LOGIN\',\'중복로그인 방지\',1,1,\'N\',\'중복로그인이 될 경우 먼저 로그인된 세션은 로그아웃 처리됨\',null,null,null,null,null,\'20150831000000\',0,\'20150831000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (11,\'INTERWORK_FLASHNET\',\'FlashNet 연계 여부\',1,1,\'N\',\'FlashNet 연계 적용 여부 시스템에서 FlashNet 설정 메뉴 추가, 미디어검색 Archive Status 필터링 기능, 우클릭 Archive 메뉴 추가 등 처리\',\'archive\',null,null,null,null,\'20160111000000\',null,\'20160111000000\',null);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (12,\'COMMENT_YN\',\'Comment option\',1,1,\'Y\',\'Comment function\',null,null,null,null,null,\'20160111000000\',0,\'20160111000000\',0);
		Insert into BC_SYS_CODE (ID,CODE,CODE_NM,TYPE_ID,SORT,USE_YN,MEMO,REF1,REF2,REF3,REF4,REF5,CREATED_DATE,CREATED_USER,UPDATED_DATE,UPDATED_USER) values (13,\'HISTORY_EDIT_METADATA\',\'History edit metadata option\',1,1,\'Y\',\'History edit metadata option\',null,null,null,null,null,\'20160111000000\',0,\'20160111000000\',0);

		
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,6014280,\'파일사이즈\',\'textfield\',2,null,null,null,\'0\',null,null,null,\'FILESIZE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,6073034,\'파일위치,파일명\',\'textfield\',3,\'1\',\'0\',\'1\',\'0\',null,null,null,\'FILENAME\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (515,57054,\'오디오 비트레이트\',\'textfield\',1,\'1\',\'0\',\'1\',\'1\',null,null,null,\'AUDIO_BITRATE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,507,\'영상길이\',\'textfield\',1,\'1\',\'0\',\'1\',\'1\',\'00:00:00:00\',\'영상길이\',null,\'VIDEO_RT\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,508,\'비디오 비트레이트\',\'textfield\',6,\'0\',\'0\',\'1\',\'1\',null,\'Video Bitrate\',null,\'VIDEO_BITRATE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (518,57056,\'이미지 포맷\',\'textfield\',1,\'1\',\'0\',\'1\',\'1\',null,null,null,\'IMAGE_FORMAT\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (518,57055,\'해상도\',\'textfield\',1,\'1\',\'0\',\'1\',\'1\',null,null,null,\'RESOLUTION\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (515,57052,\'재생길이\',\'textfield\',1,\'1\',\'1\',\'1\',\'1\',null,null,null,\'DURATION\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,615,\'해상도\',\'textfield\',7,\'0\',\'0\',\'1\',\'1\',null,\'해상도\',null,\'DISPLAY_SIZE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,616,\'프레임 비트레이트\',\'textfield\',8,\'1\',\'0\',\'1\',\'1\',null,\'Frame Rate\',null,\'FRAME_RATE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,58172,\'비디오 코덱\',\'textfield\',5,\'1\',\'0\',\'1\',\'1\',null,\'비디오 코덱\',null,\'VIDEO_CODEC\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,58173,\'오디오 코덱\',\'textfield\',9,\'1\',\'0\',\'1\',\'1\',null,\'오디오 코덱\',null,\'AUDIO_CODEC\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,58192,\'오디오 비트레이트\',\'textfield\',10,\'1\',\'0\',\'1\',\'1\',null,\'Audio Bitrate\',null,\'AUDIO_BITRATE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (515,58210,\'오디오 코덱\',\'textfield\',1,\'1\',\'0\',\'1\',\'1\',null,null,null,\'AUDIO_CODEC\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,4164343,\'스토리지 드라이브명\',\'textfield\',4,\'1\',\'0\',\'1\',\'0\',null,null,null,\'STORAGE\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (57078,4802298,\'해상도\',\'textfield\',1,null,null,null,\'1\',null,null,null,\'RESOLUTION\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (57078,4802299,\'이미지 포맷\',\'textfield\',1,null,null,null,\'1\',null,null,null,\'IMAGE_FORMAT\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (506,250608,\'오디오 채널\',\'textfield\',11,null,null,null,\'1\',null,\'오디오 채널\',null,\'AUDIO_CHANNEL\');
		Insert into BC_SYS_META_FIELD (BS_CONTENT_ID,SYS_META_FIELD_ID,SYS_META_FIELD_TITLE,FIELD_INPUT_TYPE,SHOW_ORDER,IS_REQUIRED,EDITABLE,IS_SHOW,IS_VISIBLE,DEFAULT_VALUE,DESCRIPTION,SUMMARY_FIELD_CD,SYS_META_FIELD_CODE) values (57057,1710654,\'문서포맷\',\'textfield\',1,null,null,null,\'1\',null,null,null,\'DOCUMENT_FORMAT\');


		Insert into BC_MODULE_INFO (NAME,ACTIVE,MAIN_IP,SUB_IP,DESCRIPTION,MODULE_INFO_ID,LAST_ACCESS) values (\'Agent #1\',\'1\',\'127.0.0.1\',\'127.0.0.1\',\'Agent #1\',1,\'20151221170333\');

		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'thumb_box_margin\',\'5px 5px 0px 5px\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'thumb_box_width\',\'160px\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'thumb_box_height\',\'130px\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'thumb_width\',\'150\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'thumb_height\',\'110\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'list_width\',\'70\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'list_height\',\'50\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'list_box_width\',\'310px\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'list_box_height\',\'65px\');
		Insert into BC_HTML_CONFIG (TYPE,VALUE) values (\'list_box_margin\',\'5px 5px 5px 5px\');
		
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (16,\'15\',\'Quality Checker(QC)\',16);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (7,\'70\',\'Transcoding (Audio)\',12);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (3,\'60\',\'Transfer (FS)\',1);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (4,\'80\',\'Transfer (FTP)\',2);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (5,\'22\',\'Transcoding (Image)\',4);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (6,\'100\',\'Delete\',7);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (8,\'11\',\'Thumbnail Creator\',5);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (12,\'120\',\'Open Directory\',11);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (11,\'31\',\'Rewrapping(MXF<->MOV)\',10);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (1,\'10\',\'Cataloging\',6);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (2,\'20\',\'Transcoding (Video)\',3);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (15,\'130\',\'Media Information\',15);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (13,\'30\',\'Partial File Restore (Gemiso)\',14);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (21,\'110\',\'Archive\',21);
		Insert into BC_TASK_TYPE (TASK_TYPE_ID,TYPE,NAME,SHOW_ORDER) values (22,\'160\',\'Restore\',22);
		
		Insert into BC_STORAGE (STORAGE_ID,TYPE,LOGIN_ID,LOGIN_PW,PATH,NAME,GROUP_NAME,MAC_ADDRESS,AUTHORITY,DESCRIBE,DESCRIPTION,VIRTUAL_PATH,PATH_FOR_MAC,PATH_FOR_UNIX) values (108,\'FTP\',\'ftp1\',\'ftp1\',\'127.0.0.1\',\'FTP Destination 1\',null,null,null,null,\'Sample FTP server connection info\',null,null,null);
		Insert into BC_STORAGE (STORAGE_ID,TYPE,LOGIN_ID,LOGIN_PW,PATH,NAME,GROUP_NAME,MAC_ADDRESS,AUTHORITY,DESCRIBE,DESCRIPTION,VIRTUAL_PATH,PATH_FOR_MAC,PATH_FOR_UNIX) values (104,\'NAS\',null,null,\'Z:/Storage/highres\',\'Highres Storage\',null,null,null,null,\'For store highres(original) file\',null,\'/Volumes/storage/Storage\',\'/Volumes/onlmg/highres\');
		Insert into BC_STORAGE (STORAGE_ID,TYPE,LOGIN_ID,LOGIN_PW,PATH,NAME,GROUP_NAME,MAC_ADDRESS,AUTHORITY,DESCRIBE,DESCRIPTION,VIRTUAL_PATH,PATH_FOR_MAC,PATH_FOR_UNIX) values (105,\'NAS\',null,null,\'Z:/Storage/lowres\',\'Lowres Storage\',null,null,null,null,\'For store lowres(proxy) file\',\'/data\',null,null);
		Insert into BC_STORAGE (STORAGE_ID,TYPE,LOGIN_ID,LOGIN_PW,PATH,NAME,GROUP_NAME,MAC_ADDRESS,AUTHORITY,DESCRIBE,DESCRIPTION,VIRTUAL_PATH,PATH_FOR_MAC,PATH_FOR_UNIX) values (106,\'NAS\',null,null,\'Z:/Storage/upload\',\'Upload Storage\',null,null,null,null,\'For store temp file from NLE, FileIngest\',null,null,null);		
		
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (3,\'Transcoding\',\'"h.264" "AAC" "480" "270" "700" "128"\',\'104\',\'105\',2,\'media=original\',\'media=proxy&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID/Proxy&filename=$SRC_FILE&ext=mp4\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (12,\'FS Transfer(Ingest)\',\'"rename" "move"\',\'106\',\'104\',3,\'filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (13,\'Transcoding\',\'"h.264" "AAC" "480" "270" "700" "128"\',\'104\',\'105\',2,\'media=original\',\'media=proxy&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID/Proxy&filename=$SRC_FILE&ext=mp4\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (14,\'Thumbnail\',\'"4" "1" "JPG" "150" "85"\',\'105\',\'105\',8,\'media=proxy\',\'media=thumb&dir=$PROXY_DIR/Thumbnail&filename=thumb_$CONTENT_ID_$HH$mm$SS&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (15,\'Cataloging\',\'"2" "10" "JPG" "320" "240"\',\'105\',\'105\',1,\'media=proxy\',\'dir=$PROXY_DIR/Catalog\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (16,\'FS Transfer(Ingest)\',\'"rename" "move"\',\'106\',\'104\',3,\'filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (17,\'Transcoding\',\'"h.264" "AAC" "480" "270" "700" "128"\',\'104\',\'105\',2,\'media=original\',\'media=proxy&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID/Proxy&filename=$SRC_FILE&ext=mp4\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (18,\'Thumbnail\',\'"4" "1" "JPG" "150" "85"\',\'105\',\'105\',8,\'media=proxy\',\'media=thumb&dir=$PROXY_DIR/Thumbnail&filename=thumb_$CONTENT_ID_$HH$mm$SS&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (19,\'Cataloging\',\'"2" "10" "JPG" "320" "240"\',\'105\',\'105\',1,\'media=proxy\',\'dir=$PROXY_DIR/Catalog\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (20,\'FS Transfer(Ingest)\',\'"rename" "move"\',\'106\',\'104\',3,\'filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (21,\'Transcoding(Audio)\',\'"MP3" "128" "48000"\',\'104\',\'105\',7,\'media=original\',\'media=proxy&dir=$SRC_DIR&filename=Proxy_$YYYY$MM$DD_$HH$mm$SS$CONTENT_ID&ext=mp3\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (22,\'Media Information(Audio)\',\'audio\',\'104\',null,15,\'media=original\',\'null\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (26,\'FS Transfer(Ingest)\',\'"rename" "move"\',\'106\',\'104\',3,\'filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (27,\'Media Information(Document)\',\'video\',\'104\',null,15,\'media=original\',\'null\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (32,\'FTP Transfer\',\'"target"\',\'104\',\'108\',4,\'media=original\',\'filename=$ORI_FILE&ext=$ORI_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (1,\'Cataloging\',\'"2" "10" "JPG" "320" "240"\',\'105\',\'105\',1,\'media=proxy\',\'dir=$PROXY_DIR/Catalog\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (2,\'Thumbnail\',\'"4" "1" "JPG" "150" "85"\',\'105\',\'105\',8,\'media=proxy\',\'media=thumb&dir=$PROXY_DIR/Thumbnail&filename=thumb_$CONTENT_ID_$HH$mm$SS&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (4,\'Partial File Restore\',\'"C" "15555" "X"\',\'104\',\'104\',13,\'dir=$OUT_SRC_DIR&filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (5,\'FS Transfer(Ingest)\',\'"rename" "move"\',\'106\',\'104\',3,\'filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (6,\'FTP Transfer\',\'"target"\',\'104\',\'108\',4,\'media=original\',\'filename=$ORI_FILE&ext=$ORI_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (7,\'Transcoding(Audio)\',\'"MP3" "128" "48000"\',\'104\',\'105\',7,\'media=original\',\'media=proxy&dir=$SRC_DIR&filename=Proxy_$YYYY$MM$DD_$HH$mm$SS$CONTENT_ID&ext=mp3\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (8,\'Media Information(Audio)\',\'audio\',\'104\',null,15,\'media=original\',\'null\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (9,\'Media Information(Document)\',\'video\',\'104\',null,15,\'media=original\',\'null\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (10,\'Transcoding(Image)\',\'"400" "400"\',\'104\',\'105\',5,\'media=original\',\'media=proxy&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID/Proxy&filename=Proxy_$YYYY$MM$DD_$HH$mm$SS$CONTENT_ID&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (11,\'Thumbnail(Image)\',\'"150" "85"\',\'105\',\'105\',5,\'media=proxy\',\'media=thumb&dir=$PROXY_DIR/Thumbnail&filename=thumb_$CONTENT_ID_$HH$mm$SS&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (23,\'FS Transfer(Ingest)\',\'"rename" "move"\',\'106\',\'104\',3,\'filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (24,\'Transcoding(Image)\',\'"400" "400"\',\'104\',\'105\',5,\'media=original\',\'media=proxy&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID/Proxy&filename=Proxy_$YYYY$MM$DD_$HH$mm$SS$CONTENT_ID&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (25,\'Thumbnail(Image)\',\'"150" "85"\',\'105\',\'105\',5,\'media=proxy\',\'media=thumb&dir=$PROXY_DIR/Thumbnail&filename=thumb_$CONTENT_ID_$HH$mm$SS&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (28,\'Partial File Restore\',\'"C" "15555" "X"\',\'104\',\'104\',13,\'dir=$OUT_SRC_DIR&filename=$OUT_SRC_FILE&ext=$OUT_SRC_EXT\',\'media=original&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID&filename=$CONTENT_ID&ext=$OUT_SRC_EXT\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (29,\'Transcoding\',\'"h.264" "AAC" "480" "270" "700" "128"\',\'104\',\'105\',2,\'media=original\',\'media=proxy&dir=$YYYY/$MM/$DD/$HH/$CONTENT_ID/Proxy&filename=$SRC_FILE&ext=mp4\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (30,\'Thumbnail\',\'"4" "1" "JPG" "150" "85"\',\'105\',\'105\',8,\'media=proxy\',\'media=thumb&dir=$PROXY_DIR/Thumbnail&filename=thumb_$CONTENT_ID_$HH$mm$SS&ext=jpg\');
		Insert into BC_TASK_RULE (TASK_RULE_ID,JOB_NAME,PARAMETER,SOURCE_PATH,TARGET_PATH,TASK_TYPE_ID,SOURCE_OPT,TARGET_OPT) values (31,\'Cataloging\',\'"2" "10" "JPG" "320" "240"\',\'105\',\'105\',1,\'media=proxy\',\'dir=$PROXY_DIR/Catalog\');

		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (5,\'FileIngest(Document)\',\'filer_doc\',\'1\',\'Document register from FileIngest\',null,\'p\',null,\'ingest\');
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (6,\'PFR(Geminisoft)\',\'PFR_MXF\',\'1\',\'PFR(Partial File Restore) from MXF file by Geminisoft\',null,\'p\',null,\'ingest\');
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (8,\'NLE(Premiere)\',\'premiere\',\'1\',\'Register by NLE(Premiere)\',null,\'i\',null,null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (9,\'FileIngest(Video)\',\'filer_movie\',\'1\',\'Register by FileIngest(Video)\',null,\'i\',null,null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (10,\'FileIngest(Audio)\',\'filer_audio\',\'1\',\'Register by FileIngest(Audio)\',null,\'i\',null,null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (12,\'FileIngest(Document)\',\'filer_doc\',\'1\',\'Register by FileIngest(Document)\',null,\'i\',null,null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (14,\'FTP Transfer #1\',\'ftp_transfer\',\'1\',\'FTP Transfer sample 1\',null,\'c\',\'/led-icons/icon.png\',null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (3,\'FileIngest(Audio)\',\'filer_audio\',\'1\',\'Audio register from FileIngest\',null,\'p\',null,\'ingest\');
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (4,\'FileIngest(Image)\',\'filer_image\',\'1\',\'Image register from FileIngest\',null,\'p\',null,\'ingest\');
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (7,\'FTP Transfer\',\'ftp_transfer\',\'1\',\'Transfer by FTP\',null,\'p\',null,\'outgest\');
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (11,\'FileIngest(Image)\',\'filer_image\',\'1\',\'Register by FileIngest(Image)\',null,\'i\',null,null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (13,\'PFR(Geminisoft)\',\'PFR_MXF\',\'1\',\'PFR(Partial File Restore) from MXF file by Geminisoft\',null,\'i\',null,null);
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (1,\'NLE(Premiere)\',\'premiere\',\'1\',\'Register from Premiere NLE\',null,\'p\',null,\'ingest\');
		Insert into BC_TASK_WORKFLOW (TASK_WORKFLOW_ID,USER_TASK_NAME,REGISTER,ACTIVITY,DESCRIPTION,CONTENT_STATUS,TYPE,ICON_URL,PRESET_TYPE) values (2,\'FileIngest(Video)\',\'filer_movie\',\'1\',\'Video register from FileIngest\',null,\'p\',null,\'ingest\');

		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (1,3,2,2,\'null\',5,null,104,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (4,5,1,13,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (1,1,3,4,\'null\',3,null,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (1,2,3,5,\'null\',3,2,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (4,11,3,17,\'null\',10,2,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (6,4,1,18,\'null\',0,null,104,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (6,3,2,19,\'null\',4,null,104,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (6,2,3,21,\'null\',3,null,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (7,6,1,22,\'null\',0,null,104,108,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (8,12,1,23,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (8,13,2,24,\'null\',12,null,104,105,null,23);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (8,14,3,25,\'null\',13,2,105,105,null,24);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (8,15,3,26,\'null\',13,null,105,105,null,24);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (9,16,1,27,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (9,17,2,28,\'null\',16,null,104,105,null,27);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (9,18,3,29,\'null\',17,2,105,105,null,28);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (9,19,3,30,\'null\',17,null,105,105,null,28);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (10,20,1,31,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (10,21,2,32,\'null\',20,2,104,105,null,31);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (10,22,3,33,\'null\',21,null,104,null,null,32);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (12,26,1,37,\'null\',0,2,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (12,27,2,38,\'null\',26,null,104,null,null,37);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (14,32,1,43,\'null\',0,null,104,108,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (1,5,1,1,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (2,5,1,6,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (2,3,2,7,\'null\',5,null,104,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (2,2,3,8,\'null\',3,2,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (2,1,3,9,\'null\',3,null,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (3,5,1,10,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (3,7,2,11,\'null\',5,2,104,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (3,8,3,12,\'null\',7,null,104,null,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (5,5,1,14,\'null\',0,2,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (5,9,2,15,\'null\',5,null,104,null,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (4,10,2,16,\'null\',5,null,104,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (6,1,3,20,\'null\',3,2,105,105,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (11,23,1,34,\'null\',0,null,106,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (11,24,2,35,\'null\',23,null,104,105,null,34);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (11,25,3,36,\'null\',24,2,105,105,null,35);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (13,28,1,39,\'null\',0,null,104,104,null,0);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (13,29,2,40,\'null\',28,null,104,105,null,39);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (13,30,3,41,\'null\',29,null,105,105,null,40);
		Insert into BC_TASK_WORKFLOW_RULE (TASK_WORKFLOW_ID,TASK_RULE_ID,JOB_PRIORITY,WORKFLOW_RULE_ID,CONDITION,TASK_RULE_PARANT_ID,CONTENT_STATUS,SOURCE_PATH_ID,TARGET_PATH_ID,STORAGE_GROUP,WORKFLOW_RULE_PARENT_ID) values (13,31,3,42,\'null\',29,null,105,105,null,40);


		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,12);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,13);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,14);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,15);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,16);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,17);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,18);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,19);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,20);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,21);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,22);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,26);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,27);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,32);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,23);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,24);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,25);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,28);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,29);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,30);
		Insert into BC_TASK_AVAILABLE (MODULE_INFO_ID,TASK_RULE_ID) values (1,31);

		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (1,1,1,\'기본항목\',\'container\',\'1\',\'1\',\'1\',\'1\',null,1,0,null,null,null,0,null);
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (2,2,1,\'기본항목\',\'container\',\'1\',\'1\',\'1\',\'1\',null,2,0,null,null,null,0,null);
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (3,3,1,\'기본항목\',\'container\',\'1\',\'1\',\'1\',\'1\',null,3,0,null,null,null,0,null);
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (4,4,1,\'기본항목\',\'container\',\'1\',\'1\',\'1\',\'1\',null,4,0,null,null,null,0,null);
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (5,5,1,\'기본항목\',\'container\',\'1\',\'1\',\'1\',\'1\',null,5,0,null,null,null,0,null);
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (6,6,1,\'기본항목\',\'container\',\'1\',\'1\',\'1\',\'1\',null,6,0,null,null,null,0,null);
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (1,11,2,\'제목\',\'textfield\',\'1\',\'1\',\'1\',\'1\',null,1,1,null,null,null,0,\'TITLE\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (1,12,3,\'내용\',\'textarea\',\'0\',\'1\',\'1\',\'1\',null,1,1,null,null,null,0,\'CONTENTS\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (2,21,2,\'제목\',\'textfield\',\'1\',\'1\',\'1\',\'1\',null,2,1,null,null,null,0,\'TITLE\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (2,22,3,\'내용\',\'textarea\',\'0\',\'1\',\'1\',\'1\',null,2,1,null,null,null,0,\'CONTENTS\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (3,31,2,\'제목\',\'textfield\',\'1\',\'1\',\'1\',\'1\',null,3,1,null,null,null,0,\'TITLE\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (3,32,3,\'내용\',\'textarea\',\'0\',\'1\',\'1\',\'1\',null,3,1,null,null,null,0,\'CONTENTS\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (4,41,2,\'제목\',\'textfield\',\'1\',\'1\',\'1\',\'1\',null,4,1,null,null,null,0,\'TITLE\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (4,42,3,\'내용\',\'textarea\',\'0\',\'1\',\'1\',\'1\',null,4,1,null,null,null,0,\'CONTENTS\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (5,51,2,\'제목\',\'textfield\',\'1\',\'1\',\'1\',\'1\',null,5,1,null,null,null,0,\'TITLE\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (5,52,3,\'내용\',\'textarea\',\'0\',\'1\',\'1\',\'1\',null,5,1,null,null,null,0,\'CONTENTS\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (6,61,2,\'제목\',\'textfield\',\'1\',\'1\',\'1\',\'1\',null,6,1,null,null,null,0,\'TITLE\');
		Insert into BC_USR_META_FIELD (UD_CONTENT_ID,USR_META_FIELD_ID,SHOW_ORDER,USR_META_FIELD_TITLE,USR_META_FIELD_TYPE,IS_REQUIRED,IS_EDITABLE,IS_SHOW,IS_SEARCH_REG,DEFAULT_VALUE,CONTAINER_ID,DEPTH,META_GROUP_TYPE,DESCIPTION,SMEF_DM_CODE,SUMMARY_FIELD_CD,USR_META_FIELD_CODE) values (6,62,3,\'내용\',\'textarea\',\'0\',\'1\',\'1\',\'1\',null,6,1,null,null,null,0,\'CONTENTS\');
		
		Insert into BC_NOTICE (NOTICE_ID,NOTICE_TITLE,NOTICE_CONTENT,CREATED_DATE,NOTICE_TYPE,FROM_USER_ID,TO_USER_ID,MEMBER_GROUP_ID,DEPCD,FST_ORDER) values (42,\'Proxima notice\',\'Thanks for using Proxima V3!\',\'20151230200201\',\'all\',\'admin\',null,null,null,1);
		
		Insert into BC_CATEGORY_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,CATEGORY_ID,GROUP_GRANT,CATEGORY_FULL_PATH) values (1,1,0,3,\'/0\');
		Insert into BC_CATEGORY_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,CATEGORY_ID,GROUP_GRANT,CATEGORY_FULL_PATH) values (2,1,0,3,\'/0\');
		Insert into BC_CATEGORY_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,CATEGORY_ID,GROUP_GRANT,CATEGORY_FULL_PATH) values (3,1,0,3,\'/0\');
		Insert into BC_CATEGORY_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,CATEGORY_ID,GROUP_GRANT,CATEGORY_FULL_PATH) values (4,1,0,3,\'/0\');
		Insert into BC_CATEGORY_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,CATEGORY_ID,GROUP_GRANT,CATEGORY_FULL_PATH) values (5,1,0,3,\'/0\');
		Insert into BC_CATEGORY_GRANT (UD_CONTENT_ID,MEMBER_GROUP_ID,CATEGORY_ID,GROUP_GRANT,CATEGORY_FULL_PATH) values (6,1,0,3,\'/0\');

		Insert into BC_UD_CONTENT_DELETE_INFO (UD_CONTENT_ID,TYPE_CODE,DATE_CODE,CODE_TYPE) values (1,\'-1\',\'-1\',\'UCSDDT\');
		Insert into BC_UD_CONTENT_DELETE_INFO (UD_CONTENT_ID,TYPE_CODE,DATE_CODE,CODE_TYPE) values (2,\'-1\',\'-1\',\'UCSDDT\');
		Insert into BC_UD_CONTENT_DELETE_INFO (UD_CONTENT_ID,TYPE_CODE,DATE_CODE,CODE_TYPE) values (3,\'-1\',\'-1\',\'UCSDDT\');
		Insert into BC_UD_CONTENT_DELETE_INFO (UD_CONTENT_ID,TYPE_CODE,DATE_CODE,CODE_TYPE) values (4,\'-1\',\'-1\',\'UCSDDT\');
		Insert into BC_UD_CONTENT_DELETE_INFO (UD_CONTENT_ID,TYPE_CODE,DATE_CODE,CODE_TYPE) values (5,\'-1\',\'-1\',\'UCSDDT\');
		Insert into BC_UD_CONTENT_DELETE_INFO (UD_CONTENT_ID,TYPE_CODE,DATE_CODE,CODE_TYPE) values (6,\'-1\',\'-1\',\'UCSDDT\');

		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (1,104,\'highres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (1,105,\'lowres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (1,106,\'upload\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (2,104,\'highres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (2,105,\'lowres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (2,106,\'upload\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (3,104,\'highres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (3,105,\'lowres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (3,106,\'upload\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (4,104,\'highres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (4,105,\'lowres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (4,106,\'upload\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (5,104,\'highres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (5,105,\'lowres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (5,106,\'upload\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (6,104,\'highres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (6,105,\'lowres\');
		Insert into BC_UD_CONTENT_STORAGE (UD_CONTENT_ID,STORAGE_ID,US_TYPE) values (6,106,\'upload\');

		Insert into CONTEXT_MENU (CODE_ID,WORKFLOW_ID) values (32,14);
	';


//	$all_query = '';
//	$all_query_trg = '';
//	$all_query_otr = '';

	//SEQUENCE, TABLE part
	$arr_query = explode(';', $all_query);
	foreach($arr_query as $one_query) {
		$one_query = trim($one_query);
		if($one_query == '') continue;
		$stid = oci_parse($db, $one_query);
		if (!$stid) {
			$err = oci_error($db);
			$err_msg = $err['message'].'('.$err['sqltext'].')';
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n"."oci_parse", FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$one_query, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$err_msg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".print_r($err, true), FILE_APPEND);
			return;
		}

		$ret = oci_execute($stid);	
		if (!$ret) {
			$err = oci_error($stid);
			$err_msg = $err['message'].'('.$err['sqltext'].')';
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n"."oci_execute", FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$one_query, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$err_msg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".print_r($err, true), FILE_APPEND);
			return;
		}
	}
	
	//TRIGGER, FUNCTION part
	$arr_query_trg = explode('CREATE', $all_query_trg);
	$arr_query_trg[0] = '';
	foreach($arr_query_trg as $one_query_trg) {
		$one_query_trg = trim($one_query_trg);
		if($one_query_trg == '') continue;
		$one_query_trg = 'CREATE '.$one_query_trg;
		$stid = oci_parse($db, $one_query_trg);
		if (!$stid) {
			$err = oci_error($db);
			$err_msg = $err['message'].'('.$err['sqltext'].')';
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n"."oci_parse", FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$one_query_trg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$err_msg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".print_r($err, true), FILE_APPEND);
			return;
		}

		$ret = oci_execute($stid);	
		if (!$ret) {
			$err = oci_error($stid);
			$err_msg = $err['message'].'('.$err['sqltext'].')';
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n"."oci_execute", FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$one_query_trg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$err_msg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".print_r($err, true), FILE_APPEND);
			return;
		}
	}

	//OTHER, DATA part
	$arr_query_otr = explode(';', $all_query_otr);
	foreach($arr_query_otr as $one_query_otr) {
		$one_query_otr = trim($one_query_otr);
		if($one_query_otr == '') continue;
		$stid = oci_parse($db, $one_query_otr);
		if (!$stid) {
			$err = oci_error($db);
			$err_msg = $err['message'].'('.$err['sqltext'].')';
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n"."oci_parse", FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$one_query_otr, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$err_msg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".print_r($err, true), FILE_APPEND);
			return;
		}

		$ret = oci_execute($stid);	
		if (!$ret) {
			$err = oci_error($stid);
			$err_msg = $err['message'].'('.$err['sqltext'].')';
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n"."oci_execute", FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$one_query_otr, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".$err_msg, FILE_APPEND);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".print_r($err, true), FILE_APPEND);
			return;
		}
	}

	

	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/111sstest0202.html', "\r\n".date('Y-m-d H:i:s')." Install database table and data FINISH.", FILE_APPEND);

	oci_close($db);
	$result = true;
	return $result;
}