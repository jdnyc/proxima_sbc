{"result":"true","total":"1","data":[{"user_nm":"\uae40\uae30\ubc31","dept":"\uc720\uc544\uc5b4\ub9b0\uc774\ud2b9\uc784\ubd80","is_copyright":"0","archive_id":"E-PT12090461","medcd":"TV","creatno":"NA457113","loanseq":"2746680","datagu":"2","datanm1":"\uadf9\ud55c\uc9c1\uc5c5","datanm2":"\uc0b0\ub9bc\ud56d\uacf5\ubcf8\ubd80 - \uc81c2\ubd80","datanm3":"20120830000000","datshap":"","empno":"96656","korname":"\uae40\uae30\ubc31","depcd":"3062","kordepnm":"\uc720\uc544\uc5b4\ub9b0\uc774\ud2b9\uc784\ubd80","jjcd":"","jjnm":"\ud30c\uacac\uc9c1","jgcd":"H0","jgnm":"","jycd":"","jynm":"","jmcd":"J","jmnm":"","loanymd":"20130206","retscheymd":"20130206","retrnymd":"20121123","pdempno":"","pdkorname":"","loangu":"2","outyn":"Y","reqserno":"1345740","loanrsn":"","realloanempno":"","content_id":"23694182","is_not_das_mam":"","is_download":"1","dwnymd":"20121123","mdb2rn":"1"}],"datagu":null,"query":"select *   from(\n\t\t\t\t\t\tselect\n\t\t\t\t\t\t(select name from member where user_id   = a.empno) user_nm,\n\t\t\t\t\t\t(select dept_nm from member where user_id = a.empno) dept,\n\t\t\t\t\t\t(select count(*) from tbblb_copyright where req_no = b.req_no) is_copyright,\n\t\t\t\t\t\t(select archive_id from content_code_info where content_id = a.content_id) archive_id,\n\t\t\t\t\t\t(select cdnm from tbbxx01 where cd = ci.medcd and cdgu = 'A01') medcd,\n\t\t\t\t\t\ta.*\n\t\t\t\t\t\tfrom tbblb03 a , content_code_info ci,\n\t\t\t\t\t\t\t Tbblb_Download B\n\t\t\t\t\t\t\t where\n\t\t\t\t\t\t\t ci.content_id(+) = a.content_id and\n\t\t\t\t\t\t\t a.loangu = 2 and a.is_download ='1'  and a.reqserno = b.req_no) a  where  a.loangu = 2 and a.is_download ='1' AND( a.realloanempno = '96656' or (a.empno = '96656'  )) and a.dwnymd is not null and a.retscheymd >= 20130206 and a.retrnymd <= 20130206 "}<?

// UTF-8 한글 체크
?>