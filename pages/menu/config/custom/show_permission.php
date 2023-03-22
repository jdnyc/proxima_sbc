<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$action = $_POST['action'];
// $type = $_POST['type'];
$title = $_POST['title'];
$button = $_POST['button'];
$url = $_POST['url'];

?>


(function(){

   
    var selected_permission = Ext.getCmp('main_grid').getSelectionModel().getSelected();
    var permission_id = Ext.isEmpty(selected_permission) ? '' : selected_permission.data.id;

      var win_permission = new Ext.Window({
          id: 'permission_add_win',
          title: _text('<?=$title?>'),
          width: 500,
          height: 500,
          modal: true,
          layout: 'fit',
          buttonAlign: 'center',
          items: [{
              id: 'permission_edit_form',
              cls: 'change_background_panel',
              xtype: 'form',
              url: '/pages/menu/config/custom/<?=$action?>_permission.php',
              frame: true,
              defaults: {
                  anchor : '100%'
              },
            items: [{
            xtype: 'textfield',
            maskRe: /[a-zA-Z0-9_.]/,
            name: 'code',
            fieldLabel: _text('MN02030'),
          },{
            xtype: 'textfield',
            name: 'code_path',
            maskRe: /[a-zA-Z0-9_.]/,
            fieldLabel: '코드 경로'
          },{
            xtype: 'textarea',
            name: 'description',
            fieldLabel: '설명'
          }
   
          , {
              xtype: 'fieldset',
              id: 'member_group_id',
              title: _text('MN00111'),
              style: {
                  background: 'white',
              },
              flex: 1,
              labelWidth: 30,
              height: 180,
              layout: 'fit',
              items: [{
                  xtype: 'checkboxgroup',
                  id: 'group',
                  autoScroll: true,
                  cls: 'x-check-group-alt',
                  columns: 3,
                  frame: true,
                  name:'groups',
                  items: [
                  

                    <?php
                    $member_group_info = $db->queryAll("select * from BC_MEMBER_GROUP order by member_group_id");
                    while ( $member_group = current($member_group_info) )
                    {
                        
                        echo "{boxLabel: '{$member_group['member_group_name']}', name: '{$member_group['member_group_id']}', inputValue: '{$member_group['member_group_id']}'}\n";
                     
                      
                        if (next($member_group_info))
                        {
                            echo ',';
                        }
                    }
                    ?>
                    ]
              }]
          },{
             xtype: 'textfield',
             name: 'parent_id',
             maskRe: /[0-9]/,
             fieldLabel: '상위 ID'
          },
            {
             xtype: 'textfield',
             name: 'p_depth',
             maskRe: /[0-9]/,
             fieldLabel: '경로 레벨(p_depth)'
            }
          ,{
            xtype: 'checkbox',
            name: 'use',
            status: 'use',
            fieldLabel: '사용 여부'
          }
          ],
          listeners : {
              afterrender : function(self){
                if('<?=$action?>' == 'edit'){
                var sm = Ext.getCmp('main_grid').getSelectionModel();
                var rec = sm.getSelected();
                self.getForm().loadRecord(rec);
              }
              }
          }
          }],
          buttons: [{
                <?php if($button == 'MN00033'){ ?>
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
				<?php }else{ ?>
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
				<?php } ?>
				scale: 'medium',
                handler: function(){
                    var form_type = Ext.getCmp('permission_edit_form').getForm();
                    var infos = form_type.getFieldValues();
                    console.log(infos);
                    var array_groups = [] ;
                    Ext.each(infos.groups, function(r) {
                        array_groups.push(r.inputValue);
                    });
                    if(Ext.isEmpty(infos.code)){
						Ext.Msg.alert(_text('MN00024'), _text('MSG00125'));
						return;
					}else{
                        Ext.Msg.show({
                            title : _text('MN00024'),
                            msg: _text('<?=$button?>')+' : '+_text('MSG02039'),
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnId, text, opts) {
                                if(btnId == 'ok'){
                                    Ext.Ajax.request({
                                        url: '/pages/menu/config/custom/<?=$action?>_permission.php',
                                        params: {
                                                permission_id : permission_id,
                                                code : infos.code,
                                                code_path : infos.code_path,
                                                description : infos.description,
                                                use : infos.use,
                                                p_depth : infos.p_depth,
                                                parent_id : infos.parent_id,
                                                groups : Ext.encode(array_groups)
                                        },
                                        callback: function(opt, success, response){
                                            try{
                                                var r = Ext.decode(response.responseText);
                                                if(r.success) {
                                                    Ext.getCmp('main_grid').getStore().reload();
                                                    Ext.Msg.alert('알림','등록되었습니다.');
                                                } else {
                                                    Ext.Msg.alert( _text('MN01039'), _text('MSG02002'));//코드 또는 코드명이 이미 등록되어 있습니다.
                                                }
                                            }catch(e) {
                                                Ext.Msg.alert( _text('MN01039'), _text('MSG00024'));//오류
                                            }
                                        }
                                    });
                                    win_permission.close();
                                }
                            }
                        });
                    }
                }
                
      }, {
                text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function(){
					Ext.getCmp('permission_add_win').close();
				}
        }],
                listeners: {
                    afterrender : function(self) {
                        if( '<?=$action?>' == 'edit'){
                            var selected_data = new Ext.data.Record(selected_permission.data);

                        }
                    }
                }
      });




   
    return win_permission;
    
})()