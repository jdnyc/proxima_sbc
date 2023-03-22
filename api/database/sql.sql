--솔트키 적용후 admin계정 패스워드 1111 유지를 위한 기본 값 인서트
UPDATE proxima_v3.bc_member SET password = '3ccbe0ad5db20d39aa91853790348f387116ce32a30133cbd3e08628fbc1cc45960b9f7692054b1891a0d0d997f45b1150c6d62dbf7ca05249df37e6c521038d', salt_key='gemiso' WHERE user_id='admin';

-- 사용자 생성,로그인시 비밀번호 솔트기능 추가
ALTER TABLE proxima_v3.bc_member ADD salt_key varchar(100) NULL;
COMMENT ON COLUMN proxima_v3.bc_member.salt_key IS 'pw생성시고유솔트키';

-- PFR 사용여부
INSERT INTO proxima_v3.bc_sys_code
(id, code, code_nm, type_id, sort, use_yn, memo, ref1, ref2, ref3, ref4, ref5, created_date, created_user, updated_date, updated_user, code_nm_english)
VALUES(37, 'PFR_USE_YN', 'PFR 사용유무', 1, 1, 'Y', '', '', '', '', '', '', NULL, NULL, NULL, NULL, 'PFR user YN');
-- 콘텐츠 원본파일명
ALTER TABLE bc_sysmeta_image ADD sys_ori_filename varchar(4000);
ALTER TABLE bc_sysmeta_movie ADD sys_ori_filename varchar(4000);
-- 메타수정이력
ALTER TABLE bc_log_detail RENAME COLUMN usr_meta_field_id TO usr_meta_field_code;
-- 첨부파일 원본파일명
ALTER TABLE bc_media ADD memo varchar(500) NULL;
-- FTP 동시접속제한
ALTER TABLE bc_storage ADD limit_session numeric null default 0;
comment on column "bc_storage"."limit_session" is 'FTP connection limit';

/*
2018.05.03 hkkim 카테고리 권한 테이블 아이디, 코멘트 추가하여 재생성
*/

DROP TABLE proxima_v3.bc_category_grant ;
CREATE TABLE proxima_v3.bc_category_grant (
  id SERIAL,
  category_id numeric NOT NULL,
  category_full_path varchar(500) NULL,
	member_group_id numeric NOT NULL,	
	group_grant numeric NOT NULL DEFAULT 0,	
	CONSTRAINT bc_category_grant_bc_cate_fk1 FOREIGN KEY (category_id) REFERENCES proxima_v3.bc_category(category_id) ON DELETE CASCADE,
	CONSTRAINT bc_category_grant_bc_memb_fk1 FOREIGN KEY (member_group_id) REFERENCES proxima_v3.bc_member_group(member_group_id) ON DELETE CASCADE
)
WITH (
	OIDS=FALSE
) ;
COMMENT ON TABLE proxima_v3.bc_category_grant IS '카테고리 권한' ;
COMMENT ON COLUMN proxima_v3.bc_category_grant.id IS 'PK' ;
COMMENT ON COLUMN proxima_v3.bc_category_grant.category_id IS '카테고리 아이디' ;
COMMENT ON COLUMN proxima_v3.bc_category_grant.category_full_path IS '카테고리 전체 경로' ;
COMMENT ON COLUMN proxima_v3.bc_category_grant.member_group_id IS '그룹 아이디' ;
COMMENT ON COLUMN proxima_v3.bc_category_grant.group_grant IS '권한' ;


/*
2018.02.21 사용자 정의 검색 테이블
*/
--테이블
CREATE TABLE proxima_v3.bc_custom_search (
	id SERIAL,
	user_id varchar(20) NOT NULL,
	name varchar(255) NOT NULL,	
	filters text NOT NULL,
	show_order numeric NOT NULL,
  PRIMARY KEY ("id")
)
WITH (
	OIDS=FALSE
) ;
COMMENT ON TABLE proxima_v3.bc_custom_search IS '사용자별 검색 설정' ;
COMMENT ON COLUMN proxima_v3.bc_custom_search.id IS 'PK' ;
COMMENT ON COLUMN proxima_v3.bc_custom_search.user_id IS '사용자 아이디(?common은 공통)' ;
COMMENT ON COLUMN proxima_v3.bc_custom_search.name IS '이름' ;
COMMENT ON COLUMN proxima_v3.bc_custom_search.filters IS '검색조건' ;
COMMENT ON COLUMN proxima_v3.bc_custom_search.show_order IS '순서' ;

--기본 데이터
INSERT INTO bc_custom_search (user_id, name, filters, show_order) 
	VALUES ('?common', '등록대기', '{"category_id": null,"search_keyword": null,"content_status": 0,"created_date": null}', 1);
INSERT INTO bc_custom_search (user_id, name, filters, show_order) 
	VALUES ('?common', '등록완료', '{"category_id": null,"search_keyword": null,"content_status": 2,"created_date": null}', 2);
INSERT INTO bc_custom_search (user_id, name, filters, show_order) 
	VALUES ('?common', '심의대기', '{"category_id": null,"search_keyword": null,"content_status": 3,"created_date": null}', 3);
INSERT INTO bc_custom_search (user_id, name, filters, show_order) 
	VALUES ('?common', '승인', '{"category_id": null,"search_keyword": null,"content_status": 4,"created_date": null}', 4);
INSERT INTO bc_custom_search (user_id, name, filters, show_order) 
	VALUES ('?common', '반려', '{"category_id": null,"search_keyword": null,"content_status": 5,"created_date": null}', 5);
INSERT INTO bc_custom_search (user_id, name, filters, show_order) 
	VALUES ('?common', '최근등록영상', '{"category_id": null,"search_keyword": null,"content_status": null,"created_date": 0}', 6);


/*
sslee 2017-12-28 Top메뉴 관련 컬럼 추가함
*/
alter table bc_top_menu add is_show character(1) default 'N';
alter table bc_top_menu add menu_code varchar(20);
alter table bc_top_menu add menu_icon varchar(20);

17.10.18
    /*add filter table by Can*/

    CREATE TABLE bc_user_filters
    (
    id numeric NOT NULL,
    title character(100),
    user_id character(50),
    use_yn character(1), -- Y/N
    code character(50),
    CONSTRAINT bc_user_filters_pkey PRIMARY KEY (id)
    )
    WITH (
    OIDS=FALSE
    );
    ALTER TABLE bc_user_filters
    OWNER TO proxima_v3;
    COMMENT ON COLUMN bc_user_filters.use_yn IS 'Y/N';

    /*add filter duration value field by Can*/
    ALTER TABLE bc_sysmeta_movie ADD sys_video_duration integer;
    COMMENT ON COLUMN bc_sysmeta_movie.sys_video_duration IS 'sys_video_duration int';
    
    ALTER TABLE bc_sysmeta_sound ADD sys_audio_duration integer;
    COMMENT ON COLUMN bc_sysmeta_sound.sys_audio_duration IS 'sys_audio_duration int';


-- 사용자 테이블에 등록자 멤버아이디와 수정자 멤버아이디 수정일시 추가
-- 컬럼 사이즈 조정
ALTER TABLE PROXIMA_V3.BC_MEMBER ADD CREATOR_ID NUMBER;
ALTER TABLE PROXIMA_V3.BC_MEMBER ADD UPDATED_DATE VARCHAR2(14);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY CREATED_DATE VARCHAR2(14);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY EXPIRED_DATE VARCHAR2(14);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY HIRED_DATE VARCHAR2(14);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY RETIRE_DATE VARCHAR2(14);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY LAST_LOGIN_DATE VARCHAR2(14);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY USER_NM VARCHAR2(100);
ALTER TABLE PROXIMA_V3.BC_MEMBER MODIFY USER_ID VARCHAR2(320);
COMMENT ON COLUMN PROXIMA_V3.BC_MEMBER.CREATOR_ID IS '생성자 아이디';
ALTER TABLE PROXIMA_V3.BC_MEMBER ADD UPDATER_ID NUMBER;
COMMENT ON COLUMN PROXIMA_V3.BC_MEMBER.UPDATED_DATE IS '수정일시';
COMMENT ON COLUMN PROXIMA_V3.BC_MEMBER.UPDATER_ID IS '수정자 아이디';


CREATE TABLE BIS_SCPGMMST (
	"PGM_ID" VARCHAR2(15) NOT NULL ENABLE, 
	"PGM_NM" VARCHAR2(300), 
	"PGM_ONM" VARCHAR2(100), 
	"PGM_TYP" VARCHAR2(10), 
	"BRD_RUN" NUMBER(5,0), 
	"TOT_CNT" NUMBER(5,0), 
	"PGM_CLF1" VARCHAR2(10), 
	"PGM_CLF2" VARCHAR2(10), 
	"JENR_CLF" VARCHAR2(10), 
	"TARGET" VARCHAR2(10), 
	"DELIB_GRD" VARCHAR2(10), 
	"INFO_GRD" VARCHAR2(10), 
	"FRGN_CLF" CHAR(1), 
	"GAME_CLF" VARCHAR2(10), 
	"MAIN_ROLE" VARCHAR2(200), 
	"SUPP_ROLE" VARCHAR2(200), 
	"DIRECTOR" VARCHAR2(200), 
	"MC_NM" VARCHAR2(100), 
	"AWARD_INFO" VARCHAR2(200), 
	"PRD_CNTRY1" VARCHAR2(10), 
	"PRD_CNTRY2" VARCHAR2(10), 
	"PRD_CO_CD" VARCHAR2(10), 
	"PRD_CO_NM" VARCHAR2(100), 
	"PRD_YM" VARCHAR2(8), 
	"FLASH_YN" CHAR(1), 
	"EMERG_YN" CHAR(1), 
	"NEWS_YN" CHAR(1), 
	"PILOT_YN" CHAR(1), 
	"PGM_INFO" VARCHAR2(500), 
	"PGM_ICON" VARCHAR2(50), 
	"REGR" VARCHAR2(15), 
	"REG_DT" CHAR(14), 
	"MODR" VARCHAR2(15), 
	"MOD_DT" CHAR(14), 
	"DEL_YN" CHAR(1), 
	"DELR" VARCHAR2(15), 
	"DEL_DT" CHAR(14), 
	"DVS_YN" VARCHAR2(1), 
	"PGM_OID" VARCHAR2(15), 
	"SCRIPTOR_CNTRY" VARCHAR2(10), 
	"DIRECTOR_CNTRY" VARCHAR2(10), 
	"PRD_CNTRY" VARCHAR2(10), 
	"ACTOR_CNTRY" VARCHAR2(10), 
	"BRD_FORM" VARCHAR2(10), 
	"WEB_CATEGORY_CD" VARCHAR2(10), 
	"WEB_PRD_CLF" VARCHAR2(10), 
	"WEB_BRD_CLF" VARCHAR2(10), 
	"WEB_NEWS_PRD_CLF" VARCHAR2(10), 
	"DVS_YM" CHAR(8), 
	 PRIMARY KEY ("PGM_ID")
);

