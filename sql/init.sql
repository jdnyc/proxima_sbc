   --인화요청

    delete from print_request;

    delete from bc_member_request;

    --즐겨찾기
    delete from bc_favorite;
    delete from bc_favorite_category;

    --공지사항 초기화
    delete from bc_notice_files;
    delete from bc_notice;
    SELECT setval('proxima_v3.seq_bc_notice_files', 1, true);
    SELECT setval('proxima_v3.notice_seq', 1, true);

    --로그
    delete from bc_log;
    delete from bc_log_detail;

    --BC_LOG_SEQ
    SELECT setval('proxima_v3.BC_LOG_SEQ', 1000000, true);
    SELECT setval('proxima_v3.seq_bc_log_detail_id', 1000000, true);

    delete from bc_task_log;
    delete from bc_task;
    --TASK_SEQ
    SELECT setval('proxima_v3.TASK_SEQ', 1000000, true);
    --TASK_LOG_SEQ
    SELECT setval('proxima_v3.TASK_LOG_SEQ', 1000000, true);

    delete from interface;
    delete from interface_ch;

    --SEQ_INTERFACE_ID
    SELECT setval('proxima_v3.SEQ_INTERFACE_ID', 1000000, true);
    --SEQ_INTERFACE_CH_ID
    SELECT setval('proxima_v3.SEQ_INTERFACE_CH_ID', 1000000, true);

    --미디어 파일 확인
    delete from bc_media;
    delete from bc_media_quality;
    delete from bc_media_quality_info;

    --SEQ_MEDIA_ID
    SELECT setval('proxima_v3.SEQ_MEDIA_ID', 1000000, true);

    --콘텐츠

    delete from bc_scene;
    delete from bc_shot_list;
    delete from bc_social_transfer;
    delete from bc_story_board;
    delete from bc_restore_ing;
    delete from bc_comments;
    delete from bc_mark;

    delete from bc_content_status;
    delete from bc_delete_content;

    delete from bc_tag;
    delete from bc_tag_category;

    delete from bc_sysmeta_document;
    delete from bc_sysmeta_image;
    delete from bc_sysmeta_movie;
    delete from bc_sysmeta_sequence;
    delete from bc_sysmeta_sound;

    delete from bc_usrmeta_clean;
    delete from bc_usrmeta_image;
    delete from bc_usrmeta_master;
    delete from bc_usrmeta_source;

    delete from bc_content;

    --SEQ_CONTENT_ID
    SELECT setval('proxima_v3.SEQ_CONTENT_ID', 1000000, true);

    delete from bc_category;
    --SEQ_BC_CATEGORY_ID
    SELECT setval('proxima_v3.SEQ_BC_CATEGORY_ID', 10000, true);

    insert into bc_category (category_id,category_title,code,show_order,no_children,extra_order,is_deleted ) values (0,'Proxima','1',1,'0',0,0);
    insert into bc_category (category_id,parent_id,category_title,code,show_order,no_children,extra_order,is_deleted ) values (100,0,'영상','2',1,'0',0,0);
    insert into bc_category (category_id,parent_id,category_title,code,show_order,no_children,extra_order,is_deleted ) values (110,0,'사진','3',2,'0',0,0);
    insert into bc_category (category_id,parent_id,category_title,code,show_order,no_children,extra_order,is_deleted ) values (120,0,'오디오','4',3,'0',0,0);
    insert into bc_category (category_id,parent_id,category_title,code,show_order,no_children,extra_order,is_deleted ) values (130,0,'문서','4',3,'0',0,0);

    insert into bc_category_mapping (ud_content_id, category_id ) values (1,100);
    insert into bc_category_mapping (ud_content_id, category_id ) values (2,100);
    insert into bc_category_mapping (ud_content_id, category_id ) values (3,100);
    insert into bc_category_mapping (ud_content_id, category_id ) values (4,120);
    insert into bc_category_mapping (ud_content_id, category_id ) values (5,110);
    insert into bc_category_mapping (ud_content_id, category_id ) values (6,130);

