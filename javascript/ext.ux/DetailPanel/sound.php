<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');

$content_id = $_REQUEST['content_id'];
$user_id = $_SESSION['user']['user_id'];
$mode = $_REQUEST['mode'];
$request_id = $_REQUEST['request_id'];

$record = json_decode( $_POST['record'], true);

$content = $db->queryRow("select c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
$ud_content_id = $content['ud_content_id'];
$is_group = $content['is_group'];
$bs_content_id = $content['bs_content_id'];


$columns = [];
$usr_meta_field_id = '';

$is_sound = true;
$start_sec=0;


$stream_file = addslashes($stream_file);
//$flashVars = '"mp3:'.$stream_file.'"';
$flashVars = 'data/'.$stream_file;
$switch = 'false';



$medias = \Api\Models\Media::where('content_id',$content_id)
->with('storage')->get();

foreach($medias as $media){
    if( $media->media_type == 'proxy' ){
        if( $media->status != 1 && $media->storage ){
            $proxyPath = rtrim($media->storage->virtual_path,'/') .'/'. ltrim($media->path,'/') ;
        };
    }
    if( $media->media_type == 'thumb' ){
        if( $media->status != 1 && $media->storage ){
            $thumbPath = rtrim($media->storage->virtual_path,'/') .'/'. ltrim($media->path,'/') ;
        };
    }
    if( $media->media_type == 'original' ){
        if( $media->status != 1 && $media->storage ){
            $originalPath = rtrim($media->storage->virtual_path,'/').'/'. ltrim($media->path,'/') ;
        };
    }
}
$flashVars = $proxyPath ?? $originalPath;

function getListViewForm($columns, $usr_meta_field_id)
{
    $asciiA = 65;
    $columns = explode(';', $columns);
    $columnCount = count($columns);

    $check_datanm = false;

    foreach ($columns as $v)
    {
        if($v=='순번' )
        {
            $result[] = "{ readOnly: true , fieldLabel: '$v', anchor:'90%' , name: 'column".chr($asciiA++)."'}";
        }
        else if( $v=='내용'|| $v== '비고' )
        {
            $result[] = "{ xtype:'textarea', fieldLabel: '$v', anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
        }
        else if($v == '길이' )
        {
            $result[] = "{ fieldLabel: '$v', anchor:'90%'  , name: 'column".chr($asciiA++)."'}";

        }
        else if( $v == '카테고리' )
        {
            if($usr_meta_field_id == '11879136')
            {
                $root_category = findCategoryRoot(CLEAN); //메타테이블아이디를 루트로 설정 by 이성용
                if( $root_category )
                {
                    $root_category_id = $root_category['id'];
                    $root_category_text = $root_category['title'];
                    $category_path = substr(getCategoryPath('3936212').'/'.'3936212', 11);
                //	$category_path ='3936425/3936571';

                }

                if(empty($category_path)) $category_path = '0';


            $result[] = 	"{
                    xtype: 'treecombo',
                    id: 'tc_category',
                    fieldLabel: '카테고리',
                    treeWidth: '300',
                    anchor:'90%',
                    name: 'column".chr($asciiA++)."',
                    autoScroll: true,
                    pathSeparator: ' -> ',
                    rootVisible: false,
                    value: '$category_path',
                    loader: new Ext.tree.TreeLoader({
                        url: '/store/get_categories.php',
                        baseParams: {
                            action: 'get-folders',
                            path: '$category_path'
                        },
                        listeners: {
                            load: function(self, node, response){

                                var path = self.baseParams.path;

                                if(!Ext.isEmpty(path) && path != '0'){
                                    path = path.split('/');

                                    var id = path.shift();
                                    self.baseParams.path = path.join('/');

                                    var n = node.findChild('id', id);
                                    if(!Ext.isEmpty(n))
                                    {
                                        if(n && n.isExpandable()){
                                            n.expand();
                                        }else{
                                            n.select();
                                            Ext.getCmp('tc_category').setValue(n.id);
                                        }
                                    }
                                }else{
                                    node.select();
                                    Ext.getCmp('tc_category').setValue(node.id);
                                }
                            }
                        }
                    }),
                    root: new Ext.tree.AsyncTreeNode({
                        id: $root_category_id,
                    //	text: '$root_category_text',
                        expanded: true
                    })
                }";
            }
        }
        else if($v=='방송일자' || $v =='촬영일자')
        {
            $result[] = "{xtype:'datefield', fieldLabel: '$v',format: 'Y-m-d',
                                                altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
                                                editable: true, anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
        }
        else if($v=='자료명')
        {
            $check_datanm = true;
            $datanm_field = "{ hidden: true, anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
        }
        else if( $v == '계절' )
        {
            $result[] = "{
                            xtype:'combo',
                            fieldLabel: '$v',
                            mode: 'local',
                            triggerAction: 'all',
                            typeAhead: true,
                            editable: true,
                            anchor:'90%' ,
                            store:  [
                                ['봄','봄'],
                                ['여름','여름'],
                                ['가을','가을'],
                                ['겨울','겨울']
                            ],
                            name: 'column".chr($asciiA++)."'
                        }";
        }
        else if( $v == '언어' )
        {
            $result[] = "{
                            xtype:'combo',
                            fieldLabel: '$v',
                            mode: 'local',
                            triggerAction: 'all',
                            typeAhead: true,
                            editable: true,
                            anchor:'90%' ,
                            store:  [
                                ['독일어','독일어'],
                                ['라틴어','라틴어'],
                                ['러시아어','러시아어'],
                                ['스페인어','스페인어'],
                                ['슬라브어','슬라브어'],
                                ['영어','영어'],
                                ['이태리어','이태리어'],
                                ['일본어','일본어'],
                                ['중국어','중국어'],
                                ['프랑스어','프랑스어'],
                                ['한국어','한국어'],
                                ['헝가리어','헝가리어'],
                                ['기타','기타']
                            ],
                            name: 'column".chr($asciiA++)."'
                        }";
        }
        else if( $v == '촬영구분' )
        {
            $result[] = "{
                            xtype:'combo',
                            fieldLabel: '$v',
                            mode: 'local',
                            triggerAction: 'all',
                            typeAhead: true,
                            editable: true,
                            anchor:'90%' ,
                            store:  [
                                ['국내','국내'],
                                ['해외','해외']
                            ],
                            name: 'column".chr($asciiA++)."'
                        }";
        }
        else if( $v == '소재구분' )
        {
            $result[] = "{
                            xtype:'combo',
                            fieldLabel: '$v',
                            mode: 'local',
                            triggerAction: 'all',
                            typeAhead: true,
                            editable: true,
                            anchor:'90%' ,
                            store:  [
                                ['1:1 편집본','1:1 편집본'],
                                ['2:1 편집본','2:1 편집본'],
                                ['완성편집본','완성편집본'],
                                ['VCR 편집본','VCR 편집본'],
                                ['방송본','방송본'],
                                ['스튜디오촬영본','스튜디오촬영본'],
                                ['촬영본','촬영본'],
                                ['6MM 촬영본','6MM 촬영본'],
                                ['COPY본','COPY본'],
                                ['코너자료','코너자료'],
                                ['인서트모음','인서트모음'],
                                ['FILLER','FILLER']
                            ],
                            name: 'column".chr($asciiA++)."'
                        }";
        }
        else if( $v == '촬영기법' )
        {
            $result[] = "{
                            xtype:'combo',
                            fieldLabel: '$v',
                            mode: 'local',
                            triggerAction: 'all',
                            typeAhead: true,
                            editable: true,
                            anchor:'90%' ,
                            store:  [
                                ['일반','일반'],
                                ['항공','항공'],
                                ['특수','특수'],
                                ['수중','수중']
                            ],
                            name: 'column".chr($asciiA++)."'
                        }";
        }
        else
        {
            $result[] = "{ fieldLabel: '$v', anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
        }
    }
    if( $check_datanm )
    {
        $result[]  =	$datanm_field ;
    }
    $result[] = "{anchor:'90%'  , name: 'meta_value_id', hidden: true }";

    return array(
        'columnHeight' => ($columnCount * 45 + 20),
        'columns' => join(",\n", $result)
    );
}


if(is_array($record)) {
    $highres_web_root = $record['highres_web_root'];
    $lowres_web_root = 	$record['lowres_web_root'];
    $ori_path =  $record['ori_path'];
    $album_path =  $record['album'];
}

if( !empty($thumbPath) ){
    $image_path = $thumbPath;
}else{
    if ($album_path == null || $album_path == '/img/incoming.jpg' || $album_path == ''){
        $image_path = '/img/audio_spectrum.gif';
    } else {
        $image_path = $lowres_web_root.'/'.$album_path;
    }
}

if(empty($image_path )) {
    $image_path = '/img/audio_spectrum.gif';
}

$html_text = 'background-color:black;background-image:url("'.addslashes($image_path).'");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;';
// if($image_path = '/img/audio_spectrum.gif'){
//     $html_text = 'background-color:black;background-image:url("'.addslashes($image_path).'");background-position:center center;background-repeat:no-repeat; text-align:center;';
// }else{
//     $html_text = 'background-color:black;background-image:url("'.addslashes($image_path).'");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;';
// }

// if(is_file($image_path)){
// 	$html_text = 'background-color:black;background-image:url("'.addslashes($image_path).'");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;';
// }else{
// 	$image_path = '/img/incoming_proxy.png';
// 	$html_text = 'background-color:black;background-image:url("'.addslashes($image_path).'");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;';
// }
$protocol = isset($_SERVER['HTTPS']) && (strcasecmp('off', $_SERVER['HTTPS']) !== 0);
$hostname = $_SERVER['SERVER_ADDR'];
$port = $_SERVER['SERVER_PORT'];

$image_full_path = $protocol.$hostname.':'.$port.'/'.$image_path;

list($width, $height) = @getimagesize($image_full_path);
//list($width, $height) = @getimagesize('http://'.convertIP('').'/'.$image_path);

//if (($width/300) > ($height/500))
    //{
    //	$_width = $width;
    //	$_height = 500;
    //}
//else
    //{
    //	$_width = 300;
    //	$_height = $height;
    //}
if($width > $height) {
    $_width = '100%';
    $_height = '0%';
    $_autoW = 'false';
    $_autoH = 'true';
} else if($width < $height) {
    $_width = '0%';
    $_height = '100%';
    $_autoW = 'true';
    $_autoH = 'false';
} else {
    $_width = '500';
    $_height = '0%';
    $_autoW = 'false';
    $_autoH = 'true';
}

?>
(function(){
    Ext.ns('Ariel');

    var that = this;
    var lastSeekPos = 0;
    function SeekAudio(pos){
        var playerObj = document.getElementById('player2');
        if(pos>0){
            var curPos = playerObj.currentTime;
            var toPos = curPos + pos;
            playerObj.currentTime = toPos;
        }else{
            var curPos = playerObj.currentTime;
            var toPos = curPos + pos;
            playerObj.currentTime = toPos;
        }
    }
    function SeekFlowPlayer(pos)
    {
        if(pos > 0)
        {
            //pos초 앞으로
            var curPos = parseInt($f('player2').getTime());
            var toPos = curPos + pos;


            if(lastSeekPos == toPos)
            {
                toPos = toPos + 1;
            }
            var clip = $f('player2').getClip();



            if( toPos > clip.duration )
            {
                toPos = clip.duration;
            }

            lastSeekPos = toPos;
            $f('player2').seek(toPos);
        }
        else
        {
            //pos초 뒤로
            var curPos = parseInt($f('player2').getTime());
            var toPos = curPos + pos;
            if(lastSeekPos == toPos)
            {
                toPos = toPos - 1;
            }
            if( toPos < 0 )
            {
                toPos = 0;
            }
            lastSeekPos = toPos;
            $f('player2').seek(toPos);
        }
    }

    Ariel.DetailWindow = Ext.extend(Ext.Window, {
        id: 'winDetail',
        title: _text('MN00137')+' [<?=addslashes($content['title'])?>]',
        //등록대기와 dcart에서 사용하기 위해 주석처리 by 이성용 2011-1-20
        //'<?=$content['meta_type_name']?> 상세보기 [<?=addslashes($content['title'])?>]',
        editing: <?=$editing ? 'true,' : 'false,'?>
        //width: '95%',
        //top: 50,
        //height: 700,
        //minWidth:  900,
        //width:  1050,
        //minHeight: 500,
        modal: true,
        draggable : false,//prevent move
        layout: 'fit',
        //maximizable: true,
        //maximized: true,
        width: Ext.getBody().getViewSize().width*0.9,
        height: Ext.getBody().getViewSize().height*0.9,
        listeners: {
            render: function(self){
                // Ext.getCmp('grid_thumb_slider').hide();
                // Ext.getCmp('grid_summary_slider').hide();
                self.mask.applyStyles({
                    "opacity": "0.5",
                    "background-color": "#000000"
                });
                //self.setSize(1150,680);
                //self.setPosition('150','100');
                var width_side = Ext.getBody().getViewSize().width*0.9/2-8;
                Ext.getCmp('left_side_panel').setWidth(width_side);
            },
            move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
                var pos = self.getPosition();
                if(pos[0] < 0)
                {
                    self.setPosition(0,pos[1]);
                }
                else if(pos[1] < 0)
                {
                    self.setPosition(pos[0],0);
                }
            },

            close: function(self){
                //CANPN 20160225 reload store of ActiveTab
                Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
                // Ext.getCmp('grid_thumb_slider').show();
                // Ext.getCmp('grid_summary_slider').show();
                /*
                var p = Ext.getCmp('detail_panel').checkModified();
                if (p == false)
                {
                    return false;
                }
                */
            },
            show : function(win) {
                document.onkeyup = function(evt) {
                    evt = evt || window.event;
                    if (evt.keyCode == 27) {
                        win.close();
                    }
                };
            }
        },
        initComponent: function(config){
            Ext.apply(this, config || {});

            var group_child_store = new Ext.data.JsonStore({
                url: '/store/group/get_child_list.php',
                autoLoad: false,
                root: 'data',
                fields: [
                    'content_id', 'title', 'bs_content_id', 'thumb', 'sys_ori_filename', 'ori_path', 'album_path'
                ],
                baseParams: {
                    content_id: <?=$content_id?>,
                    bs_content_id: <?=$bs_content_id?>
                }
            });

            function groupChildThumb(v, m, r) {
                var content_id = r.get('content_id');
                var bs_content_id = r.get('bs_content_id');

                if(bs_content_id == 515) {
                    var img = '<img id="thumb-group-child-' + content_id + '" onload="resizeImg(this, {w:45, h:30})" src="/data/' + v + '" />';
                } else {
                    var img = '<img id="thumb-group-child-' + content_id + '" onload="resizeImg(this, {w:45, h:30})" src="/data/' + v + '" />';
                }

                return img;
            }

            var group_list_panel = {
                    xtype: 'grid',
                    id: 'group_list_panel',
                    title: _text('MN01001'),
                    cls: 'proxima_customize_grid_for_group proxima_grid_header group_sound_content',
                    //bodyStyle:'border-top: 1px solid #d0d0d0',
                    height: 300,
                    border: false,
                    //frame: true,
                    enableDragDrop: false,
                    ddGroup: 'ContentDD',
                    stripeRows: true,
                    dragZone: null,
                    enableColumnMove: false,
                    store: group_child_store,
                    viewConfig: {
                        forceFit: true
                    },
                    colModel: new Ext.grid.ColumnModel({
                        columns: [
                            //new Ext.grid.RowNumberer({width: 30}),
                            {header: '', dataIndex: 'thumb', width: 30, renderer: groupChildThumb, align: 'center'},
                            {header: '<center>'+_text('MN00370')+'</center>', dataIndex: 'sys_ori_filename'}//원본파일명
                        ]
                    }),
                    sm: new Ext.grid.RowSelectionModel({
                        singleSelect: false,
                        listeners: {
                            selectionchange: function(self) {
                                if (Ext.isAir) {
                                    airFunRemoveFilePath('all');

                                    if (self.getSelections()) {
                                        Ext.each(self.getSelections(), function(record){
                                            var root_path ='//Volumes/onlmg/highres/';
                                            var file = root_path +'/'+ record.get('ori_path');

                                            airFunAddFilePath(file);
                                        });
                                    }
                                } // END Ext.isAir
                            },
                            rowselect: function(selModel, rowIndex, e) {
                                var self = selModel.grid;
                                var record = selModel.getSelected();
                                var content_id = record.get('content_id');
                                var bs_content_id = record.get('bs_content_id');
                                //Preview image change
                                if(!Ext.isEmpty(document.getElementById('image_sound'))) {
                                    var div_count = document.getElementById('image_sound').getElementsByTagName('div').length;
                                    var target_div = document.getElementById('image_sound').getElementsByTagName('div')[div_count-1];
                                    
                                    var img = new Image();
                                    img.src = "/data/"+ record.get('album_path');
                                    
                                    if(img.height != 0){
                                        target_div.style.backgroundImage = "url('/data/"+ record.get('album_path') + "')";
                                    }else{
                                        target_div.style.backgroundImage = "url('/img/incoming_proxy.png')";
                                    }
                                    
                                }
                                // 스트리밍 영상 변경
                                new_stream = 'mp3:' + record.json.proxy_path + '?tm=0';
                                url_stream = 'data/'+record.json.proxy_path;
                                var playerObj = document.getElementById('player2');
                                var source = document.getElementById('player2_source');
                                source.src = url_stream;
                                
                                playerObj.load();
                                playerObj.play();
                                
                                //$f('player2').stop();
                                //$f('player2').setClip({ url: new_stream });



                                // 미디어 정보내의 시스템 메타를 업데이트
                                Ext.Ajax.request({
                                    url: '/store/group/get_sysmeta.php',
                                    params: {
                                        content_id: content_id,
                                        bs_content_id: bs_content_id
                                    },
                                    callback: function(opts, success, response) {
                                        if(success) {
                                            try {
                                                var r = Ext.decode(response.responseText);
                                                if(r.success === false) {
                                                    Ext.Msg.alert( _text('MN00022'), r.msg);
                                                } else {
                                                    var media_info = Ext.getCmp('detail_panel').find('name', 'media_info_tab');
                                                    if(!Ext.isEmpty(media_info[0].items.get(0))) {
                                                        media_info[0].items.get(0).getForm().loadRecord(r);
                                                    }
                                                }
                                            } catch(e) {
                                                Ext.Msg.alert(e['name'], e['message']);
                                            }
                                        }else{
                                            Ext.Msg.alert( _text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
                                        }
                                    }
                                });
                                // 미디어 정보내의 미디어파일리스트는 store를 load
                                var media_list = Ext.getCmp('media_list');
                                //2015-11-19 수정
                                if(media_list){
                                    media_list.getStore().load({
                                        params: {
                                            content_id: content_id
                                        }
                                    });
                                }

                                // QC탭도 store를 load(단 있을 경우에만)
                                var qc_list = Ext.getCmp('detail_panel').find('name', 'qc_info_tab');
                                if(!Ext.isEmpty(qc_list)) {
                                    // QC 목록
                                    Ext.getCmp('qc_grid').getStore().load({
                                        params: {
                                            content_id: content_id
                                        }
                                    });
                                    // QC 검토의견
                                    Ext.Ajax.request({
                                        url: '/store/media_quality_store.php',
                                        params: {
                                            content_id: content_id,
                                            action: 'get_cmt'
                                        },
                                        callback: function(opts, success, response){
                                            if (success) {
                                                try {
                                                    var r = Ext.decode(response.responseText);
                                                    if (r.success) {
                                                        Ext.getCmp('qc_review_cmt').setValue(r.comment);
                                                    } else {
                                                        Ext.Msg.alert('확인', r.msg);
                                                    }
                                                } catch(e) {
                                                    Ext.Msg.alert('오류', response.responseText);
                                                }
                                            } else {
                                                Ext.Msg.alert('서버 통신 오류', response.statusText);
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    }),
                    listeners: {
                        render: function(grid) {
                            grid.getStore().on('load', function() {
                                grid.getSelectionModel().selectRow(0);
                            });
                            grid.getStore().load();
                        }
                    }
            };


            var listview_panel = {
                xtype: 'panel',
                id: 'tc_panel',
                cls: 'change_background_panel',
                border: false,
                height: 300,
                //frame: true,
                //hidden: true,
                layout: 'fit',
                items: {
                    xtype: 'form',
                    padding: 5,
                    frame: true,
                    autoScroll: true,
                    defaultType: 'textfield',

                    items: [
                        <?=$columns['columns']?>
                    ],

                    listeners: {
                        afterrender: function(self){
                            //self.get(0).focus(false, 250);
                        }
                    }
                },
                buttonAlign: 'left',
                buttons: [{
                    hidden: true,
                    text: '구간 추출 생성',
                    handler: function(b, e){
                        $f('player2').pause();

                        Ext.Msg.show({
                            title: '확인',
                            msg: '구간 추출 클립을 생성 하시겠습니까?',
                            icon: Ext.Msg.QUESTION,
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnId){
                                if ( btnId == 'ok')
                                {

                                    var setInSec, setOutSec;

                                    setInTC = Ext.getCmp('secIn').getValue();
                                    setOutTC = Ext.getCmp('secOut').getValue();

                                    setInSec = timecodeToSec(Ext.getCmp('secIn').getValue());
                                    setOutSec = timecodeToSec(Ext.getCmp('secOut').getValue());

                                    var form = Ext.getCmp('tc_panel').get(0).getForm();
                                    var values = form.getFieldValues();

                                    if( Ext.isEmpty( values ) )
                                    {
                                        Ext.Msg.alert('정보', '생성될 정보가 없습니다.');
                                        return;
                                    }

                                    var txt = checkInOut(setInSec, setOutSec);
                                    if ( !Ext.isEmpty(txt) )
                                    {
                                        Ext.Msg.alert('정보', txt);
                                        return;
                                    }

                                    var duration = secToTimecode(setOutSec - setInSec);

                                    var wait_msg = Ext.Msg.wait('등록중입니다.', '요청');
                                    Ext.Ajax.request({
                                        url: '/store/create_pfr.php',
                                        params: {
                                            content_id: <?=$content_id?>,
                                            //vr_meta: Ext.encode(values),
                                            start: setInSec,
                                            end: setOutSec
                                        },
                                        callback: function(opts, success, response){
                                            wait_msg.hide();
                                            if (success)
                                            {
                                                try
                                                {
                                                    var r = Ext.decode(response.responseText);
                                                    if (r.success)
                                                    {

                                                        Ext.Msg.show({
                                                            title: '확인',
                                                            msg: '구간 추출 클립이 등록되었습니다.<br />창을 닫으시겠습니까?',
                                                            icon: Ext.Msg.QUESTION,
                                                            buttons: Ext.Msg.OKCANCEL,
                                                            fn: function(btnId){
                                                                if (btnId == 'ok')
                                                                {
                                                                    Ext.getCmp('winDetail').close();
                                                                }
                                                            }
                                                        });
                                                    }
                                                    else
                                                    {
                                                        Ext.Msg.alert('확인', r.msg);
                                                    }
                                                }
                                                catch(e)
                                                {
                                                    Ext.Msg.alert('오류', response.responseText);
                                                }
                                            }
                                            else
                                            {
                                                Ext.Msg.alert('서버 통신 오류', response.statusText);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                },{
                    xtype: 'tbfill'
                },{
                    <?php
                    if ( ! checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT)) {
                        //echo "hidden: true,";
                    }
                    ?>
                    hidden: true,
                    text: _text('MN00003'),
                    icon: '/led-icons/application_edit.png',
                    handler: function(b, e){

                        var parent = b.ownerCt.ownerCt;
                        var msg = 'Do it?';
                        if( parent.title == _text('MN00033') )//add //!!추가
                        {
                            msg = _text('MSG00174');
                        }
                        else //edit
                        {
                            msg = _text('MSG00175');
                        }

                        Ext.Msg.show({
                            title: _text('MN00003'),
                            msg: msg,
                            icon: Ext.Msg.QUESTION,
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnId){
                                if ( btnId == 'ok')
                                {
                                    var form = parent.get(0).getForm();
                                    var values = form.getFieldValues();

                                    var list = Ext.getCmp('list<?=$usr_meta_field_id?>');
                                    var list_store = list.store;

                                    if( parent.title == _text('MN00033') )
                                    {
                                        var start_tc_sec = timecodeToSec( values.columnB ) ;
                                        var end_tc_sec = timecodeToSec( values.columnC ) ;
                                    }
                                    else
                                    {
                                        var start_tc_sec = timecodeToSec( values.columnB );
                                        var end_tc_sec = timecodeToSec( values.columnC );
                                    }

                                    values.columnB = secToTimecode( start_tc_sec );
                                    values.columnC = secToTimecode( end_tc_sec );

                                    var new_record = new list_store.recordType( values );
                                    if ( parent.title == _text('MN00033') )
                                    {
                                        list_store.add( new_record );
                                    }
                                    else
                                    {
                                        var old_record = list.getSelectedRecords()[0];
                                        var idx = list_store.indexOf( old_record );

                                        list_store.remove( old_record );
                                        list_store.insert( idx, new_record );
                                    }
                                    var outer = list.ownerCt;
                                    outer.submit(outer, parent, parent.title);
                                }
                            }
                        });
                    }
                },{
                    hidden: true,
                    //!!text: '취소',
                    text: _text('MN00004'),
                    icon: '/led-icons/cancel.png',
                    handler: function(b, e){

                        b.ownerCt.ownerCt.setVisible(false);
                    }
                }],

                listeners: {
                    hide: function(self){
                        self.get(0).getForm().reset();
                        if ( ! Ext.isEmpty(Ext.getCmp('tc_category'))) {
                            Ext.getCmp('tc_category').setRawValue();
                        }
                    }
                }
            };

            var review_panel = {
                title: '심 의',
                margins : '5 5 5 5',
                xtype: 'form',
                items: [
                {
                    xtype: 'hidden',
                    name: 'k_content_id',
                    value: '<?=$content_id?>'
                },{
                    xtype: 'hidden',
                    name: 'k_request_type',
                    value: 'review'
                },{
                    anchor: '90%',
                    fieldLabel: '심의 결과',
                    xtype : 'combo',
                    typeAhead: true,
                    triggerAction: 'all',
                    mode : 'local',
                    editable : false,
                    value: 'accept',
                    valueField: 'value',
                    displayField: 'name',
                    name: 'review_status',
                    hiddenName: 'review_status',
                    store: new Ext.data.SimpleStore({
                        fields: [
                            'value','name'
                        ],
                        data: [
                            ['accept', '승인'],
                            ['refuse', '반려'],
                            ['progressing', '진행중']
                        ]
                    })
                },{
                    anchor: '90% 40%',
                    fieldLabel: '지적사항 및<br /> 조치내용',
                    name: 'review_point_out',
                    emptyText: '',
                    xtype: 'textarea'
                },{
                    anchor: '90% 40%',
                    fieldLabel: '심의 의견',
                    name: 'review_comments',
                    emptyText: '',
                    xtype: 'textarea'
                }
                ],

                listeners: {
                    render: function(self){
                        Ext.Ajax.request({
                            url: '/store/nps_work/get_review_detail_list.php',
                            params: {
                                type: 'detail',
                                content_id: <?=$content_id?>
                            },
                            callback: function(opts, success, response){
                                if (success)
                                {
                                    try
                                    {
                                        var r = Ext.decode(response.responseText);
                                        if (r.success)
                                        {

                                            var data = new Ext.data.Record(r.data);

                                            self.getForm().loadRecord(data);
                                            //if( r.data.review_status == 'accept' || r.data.review_status == 'refuse'){
                                            //	self.buttons[1].setDisabled(true);
                                            //}
                                        }
                                        else
                                        {
                                            Ext.Msg.alert('확인', r.msg);
                                        }
                                    }
                                    catch(e)
                                    {
                                        Ext.Msg.alert('오류', response.responseText);
                                    }
                                }
                                else
                                {
                                    Ext.Msg.alert('서버 통신 오류', response.statusText);
                                }
                            }
                        });
                    }
                },
                buttonAlign: 'center',
                buttons: [{

                    scale: 'medium',
                    text: '저해상도 다운로드',
                    icon: '/led-icons/download_sicon.jpg',
                    handler: function(b, e){
                        var content_id = this.ownerCt.ownerCt.getForm().findField('k_content_id').getValue();

                        var rs = [];

                        rs.push(content_id);

                        Ext.Ajax.request({
                            url: '/store/download_use_air.php',
                            params: {
                                flag : 'media',
                                media_type : 'proxy',
                                content_id_list : Ext.encode(rs)
                            },
                            callback: function(opts, success, response){
                                var air_badge = response.responseText;
                                new Ext.Window({
                                    title: '다운로더',
                                    modal: true,
                                    layout: 'fit',
                                    width: 200,
                                    height: 110,
                                    padding: 10,

                                    items: {
                                        html: '<div align="center"><h3>다운로더를 실행합니다.<br>' + air_badge + '</div>'
                                    }
                                }).show();
                            }
                        });
                    }
                },{
                    text: '저장',
                    scale: 'medium',
                    icon: '/led-icons/accept.png',
                    handler: function(b, e){
                        var form = this.ownerCt.ownerCt.getForm();
                        var values = form.getValues();

                        form.submit({
                            clientValidation: true,
                            url: '/store/nps_work/review_update.php',
                            //params: values,
                            success: function(form, action) {
                                Ext.Msg.show({
                                    title: '확인',
                                    msg: action.result.msg+'<br />'+'창을 닫으시겠습니까?',
                                    icon: Ext.Msg.QUESTION,
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function(btnId){
                                        if ( btnId == 'ok')
                                        {
                                            Ext.getCmp('review_list_grid').getStore().reload();
                                            Ext.getCmp('winDetail').close();
                                        }
                                    }
                                });

                            },
                            failure: function(form, action) {
                                switch (action.failureType) {
                                    case Ext.form.Action.CLIENT_INVALID:
                                        Ext.Msg.alert('실패', 'Form fields may not be submitted with invalid values');
                                        break;
                                    case Ext.form.Action.CONNECT_FAILURE:
                                        Ext.Msg.alert('실패', 'Ajax communication failed');
                                        break;
                                    case Ext.form.Action.SERVER_INVALID:
                                    Ext.Msg.alert('실패', action.result.msg);
                            }
                            }
                        });

                    }
                },{
                    //!!text: '취소',
                    scale: 'medium',
                    text: _text('MN00004'),
                    icon: '/led-icons/cancel.png',
                    handler: function(b, e){
                        //b.ownerCt.ownerCt.setVisible(false);
                    }
                }]
            };

            this.items = {
                border: false,
                layout: 'border',

                items: [{
                    layout: 'border',
                    region: 'center',
                    border: false,
                    items: [{
                        region: 'center',
                        //bodyStyle:'background-color:black;background-image:url("<?=addslashes($image_path)?>");background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;',
                        bodyStyle:'<?=$html_text?>',
                        id : 'image_sound',
                        border: false,
                        xtype : 'panel',
                        width: 900,
                        height: '50%',
                        minWidth: 480,
                        minHeight: 300
                        //html: '<div style="display: table;"><div style="display: table-cell; text-align: center; vertical-align: middle; width: 100%;" ><img id="preview" src="<?=addslashes($image_path)?>" style="width:100%;"></div></div>'
                    },{
                        region: 'south',
                        //width: '50%',
                        height:320,
                        split: true,
                        layout: {
                            type: 'vbox',
                            align: 'stretch'
                        },

                        items: [{
                            id: 'audio_player_warp',
                            // xtype: 'panel',
                            //id:'player_warp',
                            height: 28,
                            width: '100%',
                            html:'<div id="audio_player_warp"></div>',
                            listeners:{
                                render:function(){
                                    var v_result = '';
        
                                    v_result += '<div class="audio green-audio-player">';
                                    v_result += '<div class="loading_t">';
                                    v_result += '<div class="spinner_t"></div>';
                                    v_result += '</div>';
                                    v_result += '<div class="play-pause-btn_t">  ';
                                    v_result += '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 24">';
                                    v_result += '<path fill="#FFFFFF" fill-rule="evenodd" d="M18 12L0 24V0" class="play-pause-icon_t" id="playPause_t"/>';
                                    v_result += '</svg>';
                                    v_result += '</div>';
                                    v_result += '<div class="controls_t">';
                                    v_result += '<div id="controls_slider_t"class="slider_t" data-direction="horizontal">';
                                    v_result += '<span class="current-time_t" style="margin-top: -7px;float: left;">0:00</span>';
                                    v_result += '<div class="progress_t" >';
                                    v_result += '<div class="pin_t" id="progress-pin_t" data-method="rewind"></div>';
                                    v_result += '</div>';
                                    v_result += '</div>';
                                    v_result += '<span class="total-time_t">0:00</span>';
                                    v_result += '</div>';
                                    v_result += '<div class="volume_t">';
                                    v_result += '<div class="volume-btn_t">';
                                    v_result += '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">';
                                    v_result += '<path fill="#FFFFFF" fill-rule="evenodd" d="M14.667 0v2.747c3.853 1.146 6.666 4.72 6.666 8.946 0 4.227-2.813 7.787-6.666 8.934v2.76C20 22.173 24 17.4 24 11.693 24 5.987 20 1.213 14.667 0zM18 11.693c0-2.36-1.333-4.386-3.333-5.373v10.707c2-.947 3.333-2.987 3.333-5.334zm-18-4v8h5.333L12 22.36V1.027L5.333 7.693H0z" id="speaker_t"/>';
                                    v_result += '</svg>';
                                    v_result += '</div>';
                                    v_result += '<div class="volume-controls_t" style="bottom:10px;left:20px;">';
                                    // v_result += '<span class="tooltip"></span>';
                                    v_result += '<div id="slider_t" class="slider_t" data-direction="horizontal">';
                                    v_result += '<div class="progress_t">';
                                    v_result += '<div class="pin_t" id="volume-pin_t" data-method="changeVolume"></div>';
                                    v_result += '</div>';
                                    v_result += '</div>';
                                    v_result += '</div>';
                                    v_result += '</div>';
                                    v_result += '<audio id="player2" preload="auto"  crossorigin>';
                                    //v_result += '<source src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/355309/Swing_Jazz_Drum.mp3" type="audio/mpeg">';
                                    v_result += '<source src="<?=$flashVars?>" type="audio/mpeg">';
                                    v_result += '</audio>';
                                    v_result += '</div>';
                                        //src="<?=$flashVars?>"
                                    document.getElementById('audio_player_warp').innerHTML = v_result;

                                    // console.log('testttttt::::',<?=$flashVars?>);
                                    var audioPlayer = document.querySelector('.green-audio-player');
                                    var playPause = audioPlayer.querySelector('#playPause_t');
                                    var playpauseBtn = audioPlayer.querySelector('.play-pause-btn_t');
                                    var loading = audioPlayer.querySelector('.loading_t');
                                    var progress = audioPlayer.querySelector('.progress_t');
                                    var sliders = audioPlayer.querySelectorAll('.slider_t');
                                    var controls_slider = audioPlayer.querySelectorAll('#controls_slider_t');
                                    var volumeBtn = audioPlayer.querySelector('.volume-btn_t');
                                    var volumeControls = audioPlayer.querySelector('.volume-controls_t');
                                    var volumeProgress = volumeControls.querySelector('.slider_t .progress_t');
                                    var player = audioPlayer.querySelector('audio');
                                    var currentTime = audioPlayer.querySelector('.current-time_t');
                                    var totalTime = audioPlayer.querySelector('.total-time_t');
                                    var speaker = audioPlayer.querySelector('#speaker_t');

                                    var draggableClasses = ['pin_t'];
                                    var currentlyDragged = null;
                                    
                                    window.addEventListener('mousedown', function(event) {
                                    
                                    if(!isDraggable(event.target)) return false;
                                    
                                    currentlyDragged = event.target;
                                    let handleMethod = currentlyDragged.dataset.method;
                                    
                                    this.addEventListener('mousemove', window[handleMethod], false);

                                    window.addEventListener('mouseup', function() {
                                        currentlyDragged = false;
                                        window.removeEventListener('mousemove', window[handleMethod], false);
                                    }, false);  
                                    });
                                    // sliders.addEventListener('click', rewind);
                                    playpauseBtn.addEventListener('click', togglePlay);
                                    
                                    player.addEventListener('timeupdate', updateProgress);
                                    // player.addEventListener('volumechange', test);
                                    player.addEventListener('volumechange', updateVolume);
                                    player.addEventListener('loadedmetadata', function() {

                                    totalTime.textContent = formatTime(player.duration);

                                    });
                                    player.addEventListener('canplay', makePlay);
                                    player.addEventListener('ended', function(){
                                        
                                    playPause.attributes.d.value = "M18 12L0 24V0";
                                    player.currentTime = 0;
                                    });

                                    window.addEventListener('resize', directionAware);

                                    //controls_slider.forEach(slider => {
                                    //    let pin = slider.querySelector('#controls_slider_t');
                                    //    slider.addEventListener('click', rewind);
                                    //});
                                    Ext.each(controls_slider, function(slider) {
                                        let pin = slider.querySelector('#controls_slider_t');
                                        slider.addEventListener('click', rewind);
                                    });

                                    directionAware();
                                
                                    function isDraggable(el) {
                                        let canDrag = false;
                                        let classes = Array.from(el.classList);
                                        //draggableClasses.forEach(draggable => {
                                        //    if(classes.indexOf(draggable) !== -1)
                                        //    canDrag = true;
                                        //});
                                        Ext.each(draggableClasses, function(draggable){
                                            if(classes.indexOf(draggable) !== -1)
                                            canDrag = true;
                                        });
                                        return canDrag;
                                    }

                                    function inRange(event) {
                                
                                    let rangeBox = getRangeBox(event);
                                    let rect = rangeBox.getBoundingClientRect();
                                    let direction = rangeBox.dataset.direction;
                                    if(direction == 'horizontal') {
                                        var min = rangeBox.offsetLeft;
                                        var max = min + rangeBox.offsetWidth; 
                                        if(event.clientX < min || event.clientX > max) return false;
                                    } 
                                    
                                    return true;
                                    }

                                    function updateProgress() {
                                    var current = player.currentTime;
                                    var percent = (current / player.duration) * 100;
                                    progress.style.width = percent + '%';
                                    
                                    currentTime.textContent = formatTime(current);
                                    }
                                    $("#slider_t").slider({
                                        value : 100,
                                        width : 100,
                                        step  : 1,
                                        range : 'min',
                                        min   : 0,
                                        max   : 100,
                                        slide : function(){
                                            var value = $("#slider_t").slider("value");
                                            document.getElementById("player2").volume = (value / 100);
                                        }
                                    });
                                    
                                    function updateVolume() {
                                    volumeProgress.style.width = player.volume * 100 + '%';
                                    if(player.volume >= 0.5) {
                                        speaker.attributes.d.value = 'M14.667 0v2.747c3.853 1.146 6.666 4.72 6.666 8.946 0 4.227-2.813 7.787-6.666 8.934v2.76C20 22.173 24 17.4 24 11.693 24 5.987 20 1.213 14.667 0zM18 11.693c0-2.36-1.333-4.386-3.333-5.373v10.707c2-.947 3.333-2.987 3.333-5.334zm-18-4v8h5.333L12 22.36V1.027L5.333 7.693H0z';  
                                    } else if(player.volume < 0.5 && player.volume > 0.05) {
                                        speaker.attributes.d.value = 'M0 7.667v8h5.333L12 22.333V1L5.333 7.667M17.333 11.373C17.333 9.013 16 6.987 14 6v10.707c2-.947 3.333-2.987 3.333-5.334z';
                                    } else if(player.volume <= 0.05) {
                                        speaker.attributes.d.value = 'M0 7.667v8h5.333L12 22.333V1L5.333 7.667';
                                    }
                                    }

                                    function getRangeBox(event) {
                                    let rangeBox = event.target;
                                    let el = currentlyDragged;
                                    if(event.type == 'click' && isDraggable(event.target)) {
                                        rangeBox = event.target.parentElement.parentElement;
                                    }
                                    if(event.type == 'mousemove') {
                                        rangeBox = el.parentElement.parentElement;
                                    }
                                    return rangeBox;
                                    }

                                    function getCoefficient(event) {
                                    let slider = getRangeBox(event);
                                    let rect = slider.getBoundingClientRect();
                                    let K = 0;
                                    if(slider.dataset.direction == 'horizontal') {
                                    
                                        let offsetX = event.clientX - slider.offsetLeft -80;
                                        let width = slider.clientWidth;
                                        
                                        K = offsetX / width; 
                                        
                                    } 
                                    
                                    return K;
                                    }

                                    function rewind(event) {
                                    if(inRange(event)) {
                                        player.currentTime = player.duration * getCoefficient(event);
                                    }
                                    }

                                    function changeVolume(event) {
                                        
                                    if(inRange(event)) {
                                        player.volume = getCoefficient(event);
                                    }
                                    }

                                    function formatTime(time) {
                                    var min = Math.floor(time / 60);
                                    var sec = Math.floor(time % 60);
                                    return min + ':' + ((sec<10) ? ('0' + sec) : sec);
                                    }

                                    function togglePlay(event) {
                                    
                                    if(player.paused) {
                                        playPause.attributes.d.value = "M0 0h6v24H0zM12 0h6v24h-6z";
                                        player.play();
                                    } else {
                                        playPause.attributes.d.value = "M18 12L0 24V0";
                                        player.pause();
                                    }  
                                    }

                                    function makePlay() {
                                    playpauseBtn.style.display = 'block';
                                    loading.style.display = 'none';
                                    }

                                    function directionAware() {
                                    // if(window.innerHeight < 250) {
                                    //   volumeControls.style.bottom = '-54px';
                                    //   volumeControls.style.left = '54px';
                                    // } else if(audioPlayer.offsetTop < 154) {
                                    //   volumeControls.style.bottom = '-164px';
                                    //   volumeControls.style.left = '-3px';
                                    // } else {
                                    //   volumeControls.style.bottom = '52px';
                                    //   volumeControls.style.left = '-3px';
                                    // }
                                    }
                                }
                            }
                        },{
                            hidden: true,
                            xtype: 'toolbar',
                            <?php
                                if($is_sound || !checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CREATE)) {
                                    echo "hidden: true,";
                                }
                            ?>
                            id: 'tc_toolbar',
                            items: [{
                                xtype: 'label',
                                text: _text('MN00149')
                                //!!text: '구간 입력'
                            },'-',{
                                text: 'Set In',
                                iconAlign: 'right',
                                icon: '/led-icons/set_in.png',
                                handler: function(b, e){
                                    var _s = $f('player2').getTime() + <?=$start_sec?>;

                                    var H = parseInt( _s / 3600 );
                                    var i = parseInt( (_s % 3600) / 60 );
                                    var s = parseInt( (_s % 3600) % 60 );

                                    H = String.leftPad(H, 2, '0');
                                    i = String.leftPad(i, 2, '0');
                                    s = String.leftPad(s, 2, '0');

                                    Ext.getCmp('secIn').setValue( H+':'+i+':'+s );
                                }
                            },{
                                xtype: 'textfield',
                                id: 'secIn',
                                width: 65,
                                value: '00:00:00',
                                invalidText : '00:00:00 ~ 23:59:59',
                                regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/,
                                plugins: [new Ext.ux.InputTextMask('99:99:99')]
                            },'-',{
                                text: 'Set Out',
                                iconAlign: 'right',
                                icon: '/led-icons/set_out.png',
                                handler: function(b, e){
                                    var _s = $f('player2').getTime() + <?=$start_sec?>;

                                    var H = parseInt( _s / 3600 );
                                    var i = parseInt( (_s % 3600) / 60 );
                                    var s = parseInt( (_s % 3600) % 60 );

                                    H = String.leftPad(H, 2, '0');
                                    i = String.leftPad(i, 2, '0');
                                    s = String.leftPad(s, 2, '0');

                                    Ext.getCmp('secOut').setValue( H+':'+i+':'+s );
                                }
                            }, {
                                xtype: 'textfield',
                                id: 'secOut',
                                width: 65,
                                value: '00:00:00',
                                invalidText : '00:00:00 ~ 23:59:59',
                                regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/,
                                            plugins: [new Ext.ux.InputTextMask('99:99:99')]
                            },'-', {
                                text: _text('MN02141'),//'바로 이동'
                                id: 'btnGoto',
                                handler: function(){
                                    var _seek = timecodeToSec(Ext.getCmp('goto').getValue());
                                    if (Ext.isEmpty(_seek)) {
                                        Ext.getCmp('goto').setValue(0);
                                        _seek = 0;
                                    }
                                    $f('player2').seek(_seek);
                                }
                            }, {
                                id: 'goto',
                                xtype: 'textfield',
                                enableKeyEvents: true,
                                value: '00:00:00',
                                invalidText : '00:00:00 ~ 23:59:59',
                                regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/,
                                            plugins: [new Ext.ux.InputTextMask('99:99:99')],
                                width: 65,
                                listeners: {
                                    specialKey: function(self, e){
                                        if (e.getKey() == e.ENTER) {
                                            Ext.getCmp('btnGoto').handler();
                                        }
                                    }
                                }
                            }, '-', {
                                hidden: true,
                                icon: '/led-icons/sort_date.png',
                                text: _text('MN00391'),
                                handler: function(b, e){
                                    $f('player2').pause();

                                    var setInSec, setOutSec;
                                    setInTC = Ext.getCmp('secIn').getValue();
                                    setOutTC = Ext.getCmp('secOut').getValue();
                                    setInSec = timecodeToSec(Ext.getCmp('secIn').getValue());
                                    setOutSec = timecodeToSec(Ext.getCmp('secOut').getValue());

                                    var txt = checkInOut(setInSec, setOutSec);
                                    if ( ! Ext.isEmpty(txt)) {
                                        Ext.Msg.alert(_text('MN00023'), txt);
                                        return;
                                    }

                                    Ext.Msg.show({
                                        title: '확인',
                                        msg: '구간 추출 클립을 생성 하시겠습니까?',
                                        icon: Ext.Msg.QUESTION,
                                        buttons: Ext.Msg.OKCANCEL,
                                        fn: function(btnId) {
                                            if (btnId == 'ok') {
                                                var wait_msg = Ext.Msg.wait('등록중입니다.', '요청');
                                                Ext.Ajax.request({
                                                    url: '/store/create_pfr.php',
                                                    params: {
                                                        content_id: <?=$content_id?>,
                                                        //vr_meta: Ext.encode(values),
                                                        start: setInSec,
                                                        end: setOutSec
                                                    },
                                                    callback: function(opts, success, response){
                                                        wait_msg.hide();
                                                        if (success) {
                                                            try {
                                                                var r = Ext.decode(response.responseText);
                                                                if (r.success) {

                                                                    Ext.Msg.show({
                                                                        title: '확인',
                                                                        msg: '구간 추출 클립이 등록되었습니다.<br />창을 닫으시겠습니까?',
                                                                        icon: Ext.Msg.QUESTION,
                                                                        buttons: Ext.Msg.OKCANCEL,
                                                                        fn: function(btnId){
                                                                            if (btnId == 'ok') {
                                                                                Ext.getCmp('winDetail').close();
                                                                            }
                                                                        }
                                                                    });
                                                                } else {
                                                                    Ext.Msg.alert('확인', r.msg);
                                                                }
                                                            } catch(e) {
                                                                Ext.Msg.alert('오류', response.responseText);
                                                            }
                                                        } else {
                                                            Ext.Msg.alert('서버 통신 오류', response.statusText);
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    });
                                }
                            }, {
                                text: '<span style="position:relative;top:1px;"><i class="fa fa-external-link" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02140'),//'신규 콘텐츠 생성'
                                //icon: '/led-icons/doc_convert.png',
                                handler: function(b, e){
                                    $f('player2').pause();

                                    if('<?=$pfr_err_msg?>' != '') {
                                        Ext.Msg.alert( _text('MN00023'), '<?=$pfr_err_msg?>');//알림
                                        return;
                                    }

                                    var setInSec, setOutSec;

                                    setInTC = Ext.getCmp('secIn').getValue();
                                    setOutTC = Ext.getCmp('secOut').getValue();

                                    setInSec = timecodeToSec(Ext.getCmp('secIn').getValue());
                                    setOutSec = timecodeToSec(Ext.getCmp('secOut').getValue());

                                    var txt = checkInOut(setInSec, setOutSec);
                                    if ( ! Ext.isEmpty(txt)) {
                                        Ext.Msg.alert(_text('MN00023'), txt);
                                        return;
                                    }

                                    //	Ext.Msg.alert(_text('MN00023'), '구현중입니다.');
                                    //	return;

                                    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
                                    var title = sm.getSelected().get('title');

                                    //원본파일이 있을 경우
                                    if ('<?=$flag_ori?>' == 'Y' ){
                                        new Ext.Window({
                                            layout: 'fit',
                                            height: 120,
                                            width: 600,
                                            modal: true,

                                            items: [{
                                                xtype: 'form',
                                                frame: true,
                                                padding: 5,
                                                labelWidth: 50,

                                                items: [{
                                                    xtype: 'textfield',
                                                    anchor: '100%',
                                                    fieldLabel: _text('MN00249'),//'제목'
                                                    name: 'title',
                                                    value: title
                                                }]

                                            }],

                                            buttons: [{
                                                text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00003'),//'확인'
                                                handler: function(btn) {
                                                    var win = btn.ownerCt.ownerCt;
                                                    var form = win.get(0).getForm();

                                                    var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));//('등록중입니다.', '요청');
                                                    Ext.Ajax.request({
                                                        url: '/store/create_new_content.php',
                                                        params: {
                                                            content_id: <?=$content_id?>,
                                                            title: form.getValues().title,
                                                            //vr_meta: Ext.encode(values),
                                                            start: setInSec,
                                                            end: setOutSec
                                                        },
                                                        callback: function(opts, success, response){
                                                            wait_msg.hide();
                                                            if (success) {
                                                                try {
                                                                    var r = Ext.decode(response.responseText);
                                                                    if (r.success) {
                                                                        win.close();
                                                                        Ext.Msg.show({
                                                                            title: _text('MN00003'),//'확인'
                                                                            msg: _text('MSG02037')+'</br>'+_text('MSG00190'),//'등록되었습니다.<br />창을 닫으시겠습니까?'
                                                                            icon: Ext.Msg.QUESTION,
                                                                            buttons: Ext.Msg.OKCANCEL,
                                                                            fn: function(btnId){
                                                                                if (btnId == 'ok') {
                                                                                    Ext.getCmp('winDetail').close();
                                                                                }
                                                                            }
                                                                        });
                                                                    } else {
                                                                        Ext.Msg.alert( _text('MN00003'), r.msg);//'확인'
                                                                    }
                                                                } catch(e) {
                                                                    Ext.Msg.alert( _text('MN01039'), response.responseText);//'오류'
                                                                }
                                                            } else {
                                                                Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                                                            }
                                                        }
                                                    });
                                                }
                                            },{
                                                text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//'취소'
                                                handler: function(btn) {
                                                    btn.ownerCt.ownerCt.close();
                                                }
                                            }]
                                        }).show();
                                        return;
                                    } else if ( '<?=$flag_ori?>' == 'N' ){
                                        Ext.Msg.show({
                                            title: _text('MN00024'),//MN00024 '확인',
                                            msg: _text('MSG01043'),//MSG01043 'PFR작업을 요청합니다.';
                                            icon: Ext.Msg.QUESTION,
                                            buttons: Ext.Msg.OKCANCEL,
                                            fn: function(btnId){
                                                if (btnId == 'ok')
                                                {
                                                    var pfr_list = [];
                                                    pfr_list.push({
                                                        'in': setInSec,
                                                        'out': setOutSec,
                                                        'tc_in': setInTC,
                                                        'tc_out': setOutTC
                                                    });
                                                    var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));//('등록중입니다.', '요청');
                                                    Ext.Ajax.request({
                                                        url: '/store/tc_edit.php',
                                                        params: {
                                                            mode: 'pfr_request',
                                                            content_id: '<?=$content_id?>',
                                                            pfr_list: Ext.encode(pfr_list)
                                                        },
                                                        callback: function(opt, success, res) {
                                                            wait_msg.hide();
                                                            var r = Ext.decode(res.responseText);
                                                            if(!r.success) {
                                                                Ext.Msg.alert(_text('MN00023'),r.msg);
                                                            } else {
                                                                Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
                                                            }
                                                        }
                                                    });
                                                }
                                            }
                                        });
                                    }

                                    //Ext.Msg.show({
                                        //title: '확인',
                                        //msg: '신규 콘텐츠를 생성하시겠습니까?',
                                        //icon: Ext.Msg.QUESTION,
                                        //buttons: Ext.Msg.OKCANCEL,
                                        //fn: function(btnId){
                                            //if ( btnId == 'ok')
                                            //{
                                                //var wait_msg = Ext.Msg.wait('등록중입니다.', '요청');
                                                //Ext.Ajax.request({
                                                    //url: '/store/create_new_content.php',
                                                    //params: {
                                                        //content_id: <?=$content_id?>,
                                                        ////vr_meta: Ext.encode(values),
                                                        //start: setInSec,
                                                        //end: setOutSec
                                                    //},
                                                    //callback: function(opts, success, response){
                                                        //wait_msg.hide();
                                                        //if (success)
                                                        //{
                                                            //try
                                                            //{
                                                                //var r = Ext.decode(response.responseText);
                                                                //if (r.success)
                                                                //{

                                                                    //Ext.Msg.show({
                                                                        //title: '확인',
                                                                        //msg: '등록되었습니다.<br />창을 닫으시겠습니까?',
                                                                        //icon: Ext.Msg.QUESTION,
                                                                        //buttons: Ext.Msg.OKCANCEL,
                                                                        //fn: function(btnId){
                                                                            //if (btnId == 'ok')
                                                                            //{
                                                                                //Ext.getCmp('winDetail').close();
                                                                            //}
                                                                        //}
                                                                    //});
                                                                //}
                                                                //else
                                                                //{
                                                                    //Ext.Msg.alert('확인', r.msg);
                                                                //}
                                                            //}
                                                            //catch(e)
                                                            //{
                                                                //Ext.Msg.alert('오류', response.responseText);
                                                            //}
                                                        //}
                                                        //else
                                                        //{
                                                            //Ext.Msg.alert('서버 통신 오류', response.statusText);
                                                        //}
                                                    //}
                                                //});
                                            //}
                                        //}
                                    //});
                                }
                            }
                            ,{
                                hidden: true,
                                icon: '/led-icons/add.png',
                                //!!text: '입력',
                                text: _text('MN00036'),
                                handler: function(b, e){

                                    $f('player2').pause();

                                    var setInSec, setOutSec;

                                    setInTC = Ext.getCmp('secIn').getValue();
                                    setOutTC = Ext.getCmp('secOut').getValue();

                                    setInSec = timecodeToSec(Ext.getCmp('secIn').getValue());
                                    setOutSec = timecodeToSec(Ext.getCmp('secOut').getValue());

                                    var txt = checkInOut(setInSec, setOutSec);
                                    if ( !Ext.isEmpty(txt) )
                                    {
                                        Ext.Msg.alert(_text('MN00023'), txt);
                                        return;
                                    }

                                    var duration = secToTimecode(setOutSec - setInSec);

                                    b.ownerCt.ownerCt.get(1).get(0).get(0).getForm().items.get(1).setValue(setInTC);
                                    b.ownerCt.ownerCt.get(1).get(0).get(0).getForm().items.get(2).setValue(setOutTC);
                                    b.ownerCt.ownerCt.get(1).get(0).get(0).getForm().items.get(3).setValue(duration);

                                }
                            }, {
                                hidden: true,
                                icon: '/led-icons/bin_closed.png',
                                //!!text: '삭제',
                                text: _text('MN00034'),
                                handler: function(b, e){
                                }
                            }]
                        }, {
                            xtype: 'toolbar',
                            id: 'sound_search_toolbar',
                            items: [ '<span style="color:white;">'+_text('MN01050')+'</span>',//'탐색'
                                '-', {
                                cls: 'proxima_btn_customize proxima_btn_customize_new',
                                width: 30,
                                text: '<span style="position:relative;top:1px;" title="'+_text('MN01052')+'"><i class="fa fa-fast-backward" style="font-size:13px;"></i></span>',
                                handler: function(){
                                    //SeekFlowPlayer(-10);
                                    SeekAudio(-10);
                                }
                            },{
                                cls: 'proxima_btn_customize proxima_btn_customize_new',
                                width: 30,
                                text: '<span style="position:relative;top:1px;" title="'+_text('MN01051')+'"><i class="fa fa-step-backward" style="font-size:13px;"></i></span>',
                                handler: function(){
                                    //SeekFlowPlayer(-1);
                                    SeekAudio(-1);
                                }
                            },{
                                cls: 'proxima_btn_customize proxima_btn_customize_new',
                                width: 30,
                                text: '<span style="position:relative;top:1px;" title="'+_text('MN01053')+'"><i class="fa fa-step-forward" style="font-size:13px;"></i></span>',
                                handler: function(){
                                    //SeekFlowPlayer(1);
                                    SeekAudio(1);
                                }
                            },{
                                cls: 'proxima_btn_customize proxima_btn_customize_new',
                                width: 30,
                                text: '<span style="position:relative;top:1px;" title="'+_text('MN01054')+'"><i class="fa fa-fast-forward" style="font-size:13px;"></i></span>',
                                handler: function(){
                                    //SeekFlowPlayer(10);
                                    SeekAudio(10);
                                }
                            }]
                        },
                            <?php
                                if ($is_group == 'G') {
                                    // echo "group_list_panel";
                                    echo "	{
                                                flex: 1,
                                                xtype: 'tabpanel',
                                                id: 'sound_tabpanel',
                                                activeTab: 0,
                                                cls:'proxima_tabpanel_customize proxima_media_tabpanel',
                                                split: true,
                                                region: 'south',
                                                height:320,
                                                border: false,
                                                items: [
                                                    group_list_panel											
                                                ]
    
                                            }";
                                } else if ($_REQUEST['mode'] == 'review') {
                                    echo "review_panel";
                                } else {
                                    echo "listview_panel";
                                }
                            ?>
                        ]
                    }]
                },{
                    region: 'east',
                    xtype: 'panel',
                    layout: 'border',
                    id: 'left_side_panel',
                    border: false,
                    //bodyStyle: 'border-left:1px solid #d0d0d0;',
                    width: 520,
                    split: true,
                    items: 
                    [
                    {
                        region: 'south',
                        xtype: 'form',
                        height: 35,
                        id: 'tag_list_in_content',
                        hidden: true,
                        bodyStyle: 'background: #eaeaea;padding-top:3px;',
                        items: [],
                        listeners :{
                            render: function(){
                                var tag_list_in_content_form = Ext.getCmp('tag_list_in_content');
                            
                                Ext.Ajax.request({
                                        url: '/store/tag/tag_action.php',
                                        params: {
                                        action: 'get_tag_list_of_content',
                                        content_id: <?=$content_id?>
                                        },
                                        callback: function(opt, success, response){
                                            if(success) {
                                                var result = Ext.decode(response.responseText);
                                                var result_data = result.data;

                                                tag_list_in_content_form.add({
                                                xtype : 'button',
                                                cls: 'proxima_button_customize',
                                                    width: 30,
                                                text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02240')+'\"><i class=\"fa fa-eraser\" style=\"font-size:13px;color:white;\"></i></span>',
                                                style: {
                                                    float: 'left',
                                                    marginRight: '2px'
                                                },
                                                listeners: {
                                                    render: function(c){
                                                        c.getEl().on('click', function(){
                                                            var content_id_array_2 = [];
                                                            content_id_array_2.push({
                                                                    content_id: <?=$content_id ?>
                                                                });
    
                                                            Ext.Ajax.request({
                                                                url: '/store/tag/tag_action.php',
                                                                params: {
                                                                    content_id: Ext.encode(content_id_array_2),
                                                                    action: "clear_tag_for_content"
                                                                },
                                                                callback: function(opts, success, response) {
                                                                    Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
                                                                }
                                                            });					
                                                        }, c);
                                                    }
                                                }
                        
                                                });
                                                
                                                tag_list_in_content_form.add({
                                                xtype : 'button',
                                                cls: 'proxima_button_customize',
                                                    width: 30,
                                                text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02239')+'\"><i class=\"fa fa-cog\" style=\"font-size:13px;color:white;\"></i></span>',
                                                style: {
                                                    float: 'left',
                                                    marginRight: '5px'
                                                },
                                                listeners: {
                                                    render: function(c){
                                                        c.getEl().on('click', function(){
                                                            tag_management_windown('detail_content');					
                                                        }, c);
                                                    }
                                                }
                        
                                                });
                                                for(i = 0; i < result_data.length; i++){
                                                    if(i<10){
                                                        if(result_data[i].is_checked == '1'){
                                                            tag_list_in_content_form.add({
                                                                xtype: 'label',
                                                                html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:5px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
                                                            });
                                                            
                                                        }else{
                                                            tag_list_in_content_form.add({
                                                                xtype: 'label',
                                                                html: '<div tag_id_data =\"'+result_data[i].tag_category_id+'\" style=\"position: relative;float:left;height:1px;width:18px\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-right: 5px;margin-top:7px;color:'+result_data[i].tag_category_color+';padding-right: 4px;\" title=\"'+result_data[i].tag_category_title+'\"></i><i class=\"fa fa-check\" style=\"position: absolute;font-size:16px;margin-top:1px; display:none;\"></i></div>',
                                                                listeners: {
                                                                    render: function(c){
                                                                        var tag_category_id = c.getEl().dom.children[0].getAttribute('tag_id_data');
                                                                        c.getEl().on('click', function(){
                                                                            var content_id_array_2 = [];
                                                                            content_id_array_2.push({
                                                                                    content_id: <?=$content_id ?>
                                                                            });
                                                                            change_tag_content('change_tag_content', content_id_array_2, tag_category_id,'no_reload_data');
                                                                        }, c);
                                                                    }
                                                                }
                                                            });
                                                        }
                                                        
                                                }else{
                                                        if(result_data[i].is_checked == '1'){
                                                            tag_list_in_content_form.add({
                                                                xtype: 'label',
                                                                html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:2px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
                                                            });
                                                            
                                                        }
                                                }
                                                }
                                                if(result_data.length > 10){
                                                tag_list_in_content_form.add({
                                                    xtype : 'button',
                                                    cls: 'proxima_button_customize',
                                                        width: 30,
                                                    text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02288')+'\"><i class=\"fa fa-ellipsis-h\" style=\"font-size:13px;color:white;\"></i></span>',
                                                    style: {
                                                        float: 'left',
                                                        marginRight: '5px'
                                                    },
                                                    listeners: {
                                                        render: function(c){
                                                            c.getEl().on('click', function(event){
                                                            tag_list_windown(<?=$content_id ?>);
                                                            }, c);
                                                        }
                                                    }
                                                });
                                                }
                                                tag_list_in_content_form.doLayout();
                                            }
                                        }
                                    });
                            }
                        },
                        reset_list_of_tag_form: function(){
                            Ext.getCmp('tag_list_in_content').removeAll();
                            var tag_list_in_content_form = Ext.getCmp('tag_list_in_content');
                            Ext.Ajax.request({
                                    url: '/store/tag/tag_action.php',
                                    params: {
                                    action: 'get_tag_list_of_content',
                                    content_id: <?=$content_id?>
                                    },
                                    callback: function(opt, success, response){
                                        if(success) {
                                            var result = Ext.decode(response.responseText);
                                            var result_data = result.data;
                                            
                                            tag_list_in_content_form.add({
                                            xtype : 'button',
                                            cls: 'proxima_button_customize',
                                                width: 30,
                                            text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02240')+'\"><i class=\"fa fa-eraser\" style=\"font-size:13px;color:white;\"></i></span>',
                                            style: {
                                                float: 'left',
                                                marginRight: '2px'
                                            },
                                            listeners: {
                                                render: function(c){
                                                    c.getEl().on('click', function(){
                                                        var content_id_array_2 = [];
                                                        content_id_array_2.push({
                                                                content_id: <?=$content_id ?>
                                                            });

                                                        Ext.Ajax.request({
                                                            url: '/store/tag/tag_action.php',
                                                            params: {
                                                                content_id: Ext.encode(content_id_array_2),
                                                                action: "clear_tag_for_content"
                                                            },
                                                            callback: function(opts, success, response) {
                                                                Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
                                                            }
                                                        });					
                                                    }, c);
                                                }
                                            }
                    
                                            });
                                            
                                            tag_list_in_content_form.add({
                                            xtype : 'button',
                                            cls: 'proxima_button_customize',
                                                width: 30,
                                            text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02239')+'\"><i class=\"fa fa-cog\" style=\"font-size:13px;color:white;\"></i></span>',
                                            style: {
                                                float: 'left',
                                                marginRight: '5px'
                                            },
                                            listeners: {
                                                render: function(c){
                                                    c.getEl().on('click', function(){
                                                        tag_management_windown('detail_content');					
                                                    }, c);
                                                }
                                            }
                    
                                            });
                                            for(i = 0; i < result_data.length; i++){
                                                if(i<10){
                                                    if(result_data[i].is_checked == '1'){
                                                        tag_list_in_content_form.add({
                                                            xtype: 'label',
                                                            html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:5px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
                                                        });
                                                        
                                                    }else{
                                                        tag_list_in_content_form.add({
                                                            xtype: 'label',
                                                            html: '<div tag_id_data =\"'+result_data[i].tag_category_id+'\" style=\"position: relative;float:left;height:1px;width:18px\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-right: 5px;margin-top:7px;color:'+result_data[i].tag_category_color+';padding-right: 4px;\" title=\"'+result_data[i].tag_category_title+'\"></i><i class=\"fa fa-check\" style=\"position: absolute;font-size:16px;margin-top:1px; display:none;\"></i></div>',
                                                            listeners: {
                                                                render: function(c){
                                                                    var tag_category_id = c.getEl().dom.children[0].getAttribute('tag_id_data');
                                                                    c.getEl().on('click', function(){
                                                                        var content_id_array_2 = [];
                                                                        content_id_array_2.push({
                                                                                content_id: <?=$content_id ?>
                                                                        });
                                                                        change_tag_content('change_tag_content', content_id_array_2, tag_category_id,'no_reload_data');
                                                                    }, c);
                                                                }
                                                            }
                                                        });
                                                    }
                                                    
                                            }else{
                                                    if(result_data[i].is_checked == '1'){
                                                        tag_list_in_content_form.add({
                                                            xtype: 'label',
                                                            html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:2px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
                                                        });
                                                        
                                                    }
                                            }
                                            }
                                            if(result_data.length > 10){
                                            tag_list_in_content_form.add({
                                                xtype : 'button',
                                                cls: 'proxima_button_customize',
                                                    width: 30,
                                                text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02288')+'\"><i class=\"fa fa-ellipsis-h\" style=\"font-size:13px;color:white;\"></i></span>',
                                                style: {
                                                    float: 'left',
                                                    marginRight: '5px'
                                                },
                                                listeners: {
                                                    render: function(c){
                                                        c.getEl().on('click', function(event){
                                                        tag_list_windown(<?=$content_id ?>);
                                                        }, c);
                                                    }
                                                }
                                            });
                                            }

                                            tag_list_in_content_form.doLayout();
                                        }
                                    }
                                });
                        }
                    },
                    {
                        region: 'center',
                        xtype: 'tabpanel',
                        id: 'detail_panel',
                        //!!title: '메타데이터',
                        title: _text('MN00164'),
                        enableTabScroll:true,
                        border: false,
                        split: true,
                        width: 520,
                        checkModified: function(self, n, c){
                            var _isDirty = false;
    
                            if (c && c.xtype == 'form' && c.getForm().isDirty()) {
                                var items = c.getForm().items;
                                items.each(function(i){
                                    if (i.xtype != 'treecombo' && i.isDirty()) {
                                        _isDirty = true;
                                        return false;
                                    }
                                });
                            }
    
                            if (_isDirty) {
                                Ext.Msg.show({
                                    animEl: self.el,
                                    //!!title: '확인',
                                    //!!msg: '수정된 내용이 있습니다. 적용 하시겠습니까?',
                                    title: _text('MN00003'),
                                    msg: _text('MSG00173'),
                                    icons: Ext.Msg.QUESTION,
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function(btnId){
                                        if (btnId == 'ok')
                                        {
                                            c.getFooterToolbar().items.each(function(b){
                                                if (b.text == _text('MN00043'))
                                                {
                                                    b.fireEvent('click', b, true);
                                                }
                                            });
                                        }
                                        else
                                        {
                                            c.getForm().reset();
                                            if ( n )
                                            {
                                                self.setActiveTab(n);
                                            }
                                        }
                                    }
                                });
    
                                return false;
                            }
    
                            return true;
                        },
                        <?php
                        $activeTab = 0;
                        if (in_array(REVIEW_GROUP, $_SESSION['user']['groups']))
                        {
                            $activeTab = 2;
                        }
    
                        ?>
    
                        listeners: {
                            render: function(self){
                                var myMask = new Ext.LoadMask(Ext.getBody(), {msg:_text('MSG00143')});
                                myMask.show();
                                Ext.Ajax.request({
                                    url: '/store/get_detail_metadata.php',
                                    params: {
                                        mode: '<?=$mode?>',
                                        content_id: <?=$content_id?>
                                        <?php
                                            if ( ! is_null($request_id)) {
                                                echo ',request_id: '.$request_id;
                                            }
                                        ?>
    
                                    },
                                    callback: function(opts, success, response){
                                        myMask.hide();
                                        if (success) {
                                            try {
                                                var r = Ext.decode(response.responseText);
    
                                                // 사용자화
                                                Ext.each(r[0].items, function (i, idx) {
                                                    // 날짜를 선택하면 자동으로 요일표시
                                                    if (i.fieldLabel == '방송일자') {
                                                        Ext.apply(i, {
                                                            listeners: {
                                                                select: function (self, dt) {
                                                                    var weekday = self.ownerCt.find('fieldLabel', '방송요일');
                                                                    if (weekday.length == 1) {
                                                                        weekday[0].setValue(dt.format('l')+'요일');
                                                                    }
                                                                }
                                                            }
                                                        });
                                                    }
                                                }); // 사용자화 끝
    
                                                self.add(r);
                                                self.doLayout();
                                                self.activate(0);
                                            }
                                            catch(e) {
                                                Ext.Msg.alert(e['name'], e['message']);
                                            }
                                        }
                                        else {
                                            Ext.Msg.alert(_text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
                                        }
                                    }
                                });
                            },
                            beforetabchange: function(self, n, c){
                                var tc_toolbar = Ext.getCmp('tc_toolbar');
                                if (n.title == 'TC정보') {
    
                                    // tc list 추가
                                    tc_toolbar.get(13).setVisible(true);
                                } else {
    
                                    tc_toolbar.get(13).setVisible(false);
                                }
    
                                /*
                                if( !Ext.isEmpty( self.getActiveTab() ) )//탭 변경시 tc_목록입력창 하이드
                                {
                                    if( self.getActiveTab().getId() != 'user_metadata_4002610' )
                                    {
                                        Ext.getCmp('tc_panel').setVisible(false);
                                    }
                                }
    
    
                                return self.checkModified(self, n, c);
                                */
                            }
                        }
                    }
                    ]
                }]
            };

            Ariel.DetailWindow.superclass.initComponent.call(this);
        },

        taskSend: function(type){

            $f('player2').pause();

            Ext.Msg.show({
                icon: Ext.Msg.QUESTION,
                title: '확인',
                msg: '추가하신 일괄 구간 추출 작업을 등록하시겠습니까?',
                buttons: Ext.Msg.OKCANCEL,
                fn: function(btnID){
                    if (btnID == 'ok')
                    {
                        var w = Ext.Msg.wait('일괄 구간 추출 작업 등록중...', '정보');

                        var pfr_list = [];
                        Ext.getCmp('user_pfr_list').store.each(function(i){
                            pfr_list.push({
                                'in': i.get('setInSec'),
                                'out': i.get('setOutSec')
                            });
                        });

                        Ext.Ajax.request({
                            url: '/store/add_task_pfr.php',
                            params: {
                                content_id: <?=$content_id?>,
                                type: type,
                                pfr_list: Ext.encode(pfr_list)
                            },
                            callback: function(opts, success, resp){
                                if (success)
                                {
                                    try
                                    {
                                        w.hide();

                                        var r = Ext.decode(resp.responseText);
                                        if (r.success)
                                        {
                                            Ext.Msg.alert('정보', '일괄 구간 추출 작업이 정상적으로 등록되었습니다.');
                                            Ext.getCmp('user_pfr_list').store.removeAll();
                                        }
                                        else
                                        {
                                            Ext.Msg.alert('오류', r.msg);
                                        }
                                    }
                                    catch (e)
                                    {
                                        Ext.Msg.alert(e['name'], e['message']);
                                    }
                                }
                                else
                                {
                                    Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
                                }
                            }
                        })
                    }
                }
            });
        }
    });


    new Ariel.DetailWindow().show();
})()