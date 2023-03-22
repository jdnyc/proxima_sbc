-- 비디오 코드 테이블 시작
CREATE TABLE proxima_v3.video_codes
(
	id serial NOT NULL,
	--pk
	code varchar(7) NOT NULL,
	--비디오 코드
	CONSTRAINT video_codes_pk PRIMARY KEY (id)
)
WITH (
	OIDS=FALSE
) ;

COMMENT ON TABLE proxima_v3.video_codes IS '비디오 코드';
COMMENT ON COLUMN proxima_v3.video_codes.id IS 'PK';
COMMENT ON COLUMN proxima_v3.video_codes.code IS '비디오 코드';
-- 비디오 코드 테이블 끝

-- 수정자 아이디 추가 시작
ALTER TABLE proxima_v3.bc_content ADD updater_id varchar(25) NULL;
COMMENT ON COLUMN proxima_v3.bc_content.updater_id IS '수정자 아이디';
-- 수정자 아이디 추가 끝

-- 상품 테이블 시작
CREATE TABLE proxima_v3.items
(
	id serial NOT NULL,
	--pk
	code varchar(10) NOT NULL,
	--상품 코드
	name varchar(50) NOT NULL,
	--상품명
	content_id numeric NOT NULL,
	CONSTRAINT items_pk PRIMARY KEY (id)
)
WITH (
	OIDS=FALSE
) ;

COMMENT ON TABLE proxima_v3.items IS '상품';
COMMENT ON COLUMN proxima_v3.items.id IS 'PK';
COMMENT ON COLUMN proxima_v3.items.code IS '상품코드';
COMMENT ON COLUMN proxima_v3.items.name IS '상품명';
COMMENT ON COLUMN proxima_v3.items.content_id IS '콘텐츠 아이디';

-- 상품 테이블 끝

-- 동영상 키워드 테이블 시작
CREATE TABLE proxima_v3.video_keywords
(
	id serial NOT NULL,
	--pk
	keyword varchar(50) NOT NULL,
	--키워드
	CONSTRAINT video_keywords_pk PRIMARY KEY (id)
)
WITH (
	OIDS=FALSE
) ;

COMMENT ON TABLE proxima_v3.video_keywords IS '동영상 키워드';
COMMENT ON COLUMN proxima_v3.video_keywords.id IS 'PK';
COMMENT ON COLUMN proxima_v3.video_keywords.keyword IS '키워드';

-- index
CREATE INDEX video_kewords_idx ON video_keywords (keyword);

-- 동영상 키워드 테이블 끝

-- 미디어 테이블에 URL 필드 추가
ALTER TABLE proxima_v3.bc_media ADD url varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.bc_media.url IS 'URL 경로';
ALTER TABLE proxima_v3.bc_media ADD width int NULL;
COMMENT ON COLUMN proxima_v3.bc_media.width IS '화면너비';
ALTER TABLE proxima_v3.bc_media ADD height int NULL;
COMMENT ON COLUMN proxima_v3.bc_media.height IS '화면높이';
ALTER TABLE proxima_v3.bc_media ADD cfs_path varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.bc_media.cfs_path IS 'CFS 경로';
ALTER TABLE proxima_v3.bc_media ADD cfs_filename varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.bc_media.cfs_filename IS 'CFS 파일명';


-- scene 테이블에 URL 필드 추가
ALTER TABLE proxima_v3.bc_scene ADD url varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.bc_scene.url IS 'URL 경로';
ALTER TABLE proxima_v3.bc_scene ADD cfs_path varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.bc_scene.cfs_path IS 'CFS 경로';
ALTER TABLE proxima_v3.bc_scene ADD cfs_filename varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.bc_scene.cfs_filename IS 'CFS 파일명';

-- log detail 컬럼명 변경
ALTER TABLE proxima_v3.bc_log_detail RENAME COLUMN usr_meta_field_id TO usr_meta_field_code;

-- items data json 필드 추가
ALTER TABLE proxima_v3.items ADD rep_item_yn varchar(1) NOT NULL DEFAULT 'N';
COMMENT ON COLUMN proxima_v3.items.rep_item_yn IS '대표상품 여부';
ALTER TABLE proxima_v3.items ADD chn_cd varchar(20) NULL;
COMMENT ON COLUMN proxima_v3.items.chn_cd IS '채널코드';
ALTER TABLE proxima_v3.items ADD sl_cls varchar NULL;
COMMENT ON COLUMN proxima_v3.items.sl_cls IS '상품상태(A:정상, D:영구중단, I:판매중단, S:매진)';
ALTER TABLE proxima_v3.items ADD disp_order numeric NULL DEFAULT 99;
COMMENT ON COLUMN proxima_v3.items.disp_order IS '전시순서';
ALTER TABLE proxima_v3.items ADD disp_yn varchar(1) NOT NULL DEFAULT 'N';
COMMENT ON COLUMN proxima_v3.items.disp_yn IS '전시여부';
ALTER TABLE proxima_v3.items ADD mod_nm varchar(50) NULL;
COMMENT ON COLUMN proxima_v3.items.mod_nm IS '수정자';
ALTER TABLE proxima_v3.items ADD mod_dtm varchar(20) NULL;
COMMENT ON COLUMN proxima_v3.items.mod_dtm IS '수정일시';
ALTER TABLE proxima_v3.items ADD image_url varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.items.image_url IS '이미지경로';


ALTER TABLE proxima_v3.items ADD dp_cate_id varchar(255) NULL;
COMMENT ON COLUMN proxima_v3.items.dp_cate_id IS '전시 카테고리 아이디';
ALTER TABLE proxima_v3.items ADD dp_cate_nm varchar(50) NULL;
COMMENT ON COLUMN proxima_v3.items.dp_cate_nm IS '전시 카테고리 명';