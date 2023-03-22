<?php
$doc = simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml');
$items = $doc->xpath("/root/items");
if(!empty($items)){
	foreach($items as $item){
		foreach($item as $key => $val)
		{
			define($key, (string)$val );
		}
	}
}
?>

<!DOCTYPE html>
<html>

	<head>
		<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
		<link href="bootstrap/css/bootstrap-wizard.css" rel="stylesheet" />
		<link href="bootstrap/css/bootstrap-font.css" rel="stylesheet" />
		
		<style type="text/css">
			
		
	        .wizard-modal p {
	        	margin: 0 0 0px;
	        	padding: 0;
	        }
	        
			#wizard-ns-detail-servers, .wizard-additional-servers {
				font-size:12px;
				margin-top:10px;
				margin-left:15px;
			}
			#wizard-ns-detail-servers > li, .wizard-additional-servers li {
				line-height:10px;
				list-style-type:none;
			}
			#wizard-ns-detail-servers > li > img {
				padding-right:5px;
			}
			
			.wizard-modal .chzn-container .chzn-results {
				max-height:150px;
			}
			.wizard-addl-subsection {
				margin-bottom:40px;
			}

			.control-group input {
				
			}

			.wizard-input-section {
				margin-bottom: 10px;
			}

			.allow_icon
			{
				background:red;color:#fff;
			}

			.not_allow_icon
			{
				background:red;color:#fff;
			}

			.btn.not_allow_icon:hover{
				 cursor:default !important;
			}

			.color-blue
			{
				color:blue;
			}

			.db_mig_bar_txt
			{
				color:#000;font-weight:bold;font-size:15px;width:100%;position:absolute;text-align:center;
			}

			.db_info
			{
				width:120px;
			}
			.label2
			{
				line-height: 17px;float: left; padding-right: 10px;
			}

			.glyphicon-refresh-animate {
				-animation: spin .7s infinite linear;
				-webkit-animation: spin2 .7s infinite linear;
			}

			@-webkit-keyframes spin2 {
				from { -webkit-transform: rotate(0deg);}
				to { -webkit-transform: rotate(360deg);}
			}

			@keyframes spin {
				from { transform: scale(1) rotate(0deg);}
				to { transform: scale(1) rotate(360deg);}
			}
		</style>
	</head>
		
	<body style="padding:30px;">

		<div class="wizard" id="wizard-demo">

			<h1>Proxima V3 Web Configuration</h1>
		
			<div class="wizard-card" data-onValidated="setServerResource" data-cardname="name">
				<h3>Storage Information</h3>
		
				<div class="wizard-input-section">
					<p>
						Please enter the path for proxy folder.
						<br />( For PROXY, THUMBNAIL, CATALOG images )
                        <br />Example: <b>Z:/Storage/Proxy</b>
					</p>
		
					<div class="control-group">
						<input id="web_path_local_value" type="text" value="<?=WEB_PATH_LOCAL_VALUE?>"
							placeholder="" data-validate="" required/>
					</div>
                    <button class="btn wizard-back " type="button" onclick='fn_check_path_upload_folder("check")'><span class=''>Check path</span></button>
                    <!-- <button class="btn wizard-back " type="button" onclick='fn_check_path_upload_folder("set")'><span class=''>Set path</span></button>-->
				</div>

			</div>

			<div class="wizard-card" data-onValidated="setWebServer" data-cardname="name">
				<h3>Web server Information</h3>
		
				<div class="wizard-input-section">
					<p>
						Server IP
					</p>
					<div class="control-group">
						<input id="server_ip" type="text" value="<?=SERVER_IP?>"
							placeholder="" data-validate="" required />
					</div>
				</div>
				<!--
				<div class="wizard-input-section">
					<p>
						Server Port
					</p>
					<div class="control-group">
						<input id="server_port" type="text" value="<?=SERVER_PORT?>"
							placeholder="" data-validate="" required />
					</div>
				</div>
				-->
				<div class="wizard-input-section">
					<p>
						Streaming Server IP
					</p>
					<div class="control-group">
						<input id="stream_server_ip" type="text" value="<?=STREAM_SERVER_IP?>"
							placeholder="" data-validate="" required />
					</div>
				</div>
				<!--
				<div class="wizard-input-section">
					<p>
						Streaming Server Port
					</p>
					<div class="control-group">
						<input id="stream_server_port" type="text" value="<?=STREAM_SERVER_PORT?>"
							placeholder="" data-validate="" required readonly/>
					</div>
				</div>
				-->
			</div>
            
			<div class="wizard-card" data-onValidated="setDatabase" data-cardname="name">
				<h3>Database Information</h3>

				<p>
					Information for Database connection.
				</p>

				<div class="wizard-input-section">
					<p>
						Database driver
					</p>
					<div class="">
						<input type="radio" name="db_driver" value="0" checked>PostgresSQL
						<span style="padding-right:10px;"></span>
						<input type="radio" name="db_driver" value="1" >Oracle
					</div>
				</div>

				<div class="wizard-input-section">
					<p>
						Database Host
					</p>
					<div class="control-group">
						<input id="db_host" type="text" value="<?=DB_HOST?>"
							placeholder="" data-validate="" required />
					</div>
				</div>

				<div class="wizard-input-section">
					<p>
						Database port
					</p>
					<div class="control-group">
						<input id="db_port" type="text" value="<?=DB_PORT?>"
							placeholder="" data-validate="" required />
					</div>
				</div>
				
				<div id="db_service_id_div" class="wizard-input-section" style="display:none;">
					<p>
						Service id
					</p>
					<div class="control-group">
						<input id="db_service_id" type="text" value="<?=DB_SID?>"
							placeholder="" data-validate="" required />
					</div>
				</div>

				<div id="db_name_div" class="wizard-input-section">
					<p>
						Database Name
					</p>
					<div class="control-group">
						<input id="db_name" type="text" value="<?=DB_NAME?>"
							placeholder="" data-validate="" required />
					</div>
				</div>
		
				<div class="wizard-input-section">
					<p>Database user ID</p>
					<div class="control-group">
						<input id="db_user" type="text" value="<?=DB_USER?>"
							placeholder="" data-validate="" required/>
					</div>
				</div>
				<div class="wizard-input-section">
					<p>
						Database user Password
					</p>
					<div class="control-group">
						<input id="db_user_pw" type="text" value="<?=DB_USER_PW?>"
							placeholder="" data-validate="" required />
					</div>
				</div>

				<div>
					
					<button class="btn wizard-back " type="button" onclick='connection_db_test(this);'><span style="position:relative;top:2px;left:-3px;"></span><span class=''>DB Connection Test</span></button>
					<br />
					<button type="button" style="margin-top: 10px;" class="btn wizard-back" onclick="fn_show_popup();">Create New Database User</button>
                    <br />
					<button type="button" style="margin-top: 10px;" class="btn wizard-back" onclick="fn_install_db_table_data();">Install database table and data</button>
                    
                    
             
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" style="display: none;">
                      <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel">Create New Database User</h4>
                          </div>
                          <div class="modal-body">
                            <form role="form">
                              <div class="form-group">
                                <p id="host_popup" style="display: none;"></p>
                                <p id="db_port_popup" style="display: none;"></p>
                                <p id="db_name_popup" style="display: none;"></p>
                              </div>
								<!--FOR Postgres -->
                              <div class="form-group">
                                <label for="super_user" id="label_super_user">Super Username</label>
                               	<input id="super_user" type="text" value="postgres" />
                              </div>
								<!--FOR Postgres -->
                              <div class="form-group">
                                <label for="super_user_pw" id="label_super_user_pw">Super Username Password</label>
                               	<input id="super_user_pw" type="text" value="" placeholder="Password input"/>
                              </div>

								<!--FOR Oracle -->
							  <div class="form-group">
                                <label for="system_user" id="label_system_user">System Username</label>
                               	<input id="system_user" type="text" value="system" />
                              </div>
								<!--FOR Oracle -->
                              <div class="form-group">
                                <label for="system_user_pw" id="label_system_user_pw">System Username Password</label>
                               	<input id="system_user_pw" type="text" value="" placeholder="Password input"/>
                              </div>
								

                              <div class="form-group">
                                <label for="new_user">New User ID</label>
                                <input id="new_user" type="text"/>
                              </div>
                              
                              <div class="form-group">
                                <label for="new_user_pw">New User Password</label>
                                <input id="new_user_pw" type="text"/>
                              </div>
                              
                              <button type="button" class="btn wizard-back" onclick="fn_create_new_db_user();">Create New User</button>
                            </form>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-default" onclick="fn_hide_popup();">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
					
				</div>
			</div>
		
			<div class="wizard-error">
				<div class="alert alert-error">
					<strong>There was a problem</strong> with your submission.
					Please correct the errors and re-submit.
				</div>
			</div>
		
			<div class="wizard-failure">
				<div class="alert alert-error">
					<strong>There was a problem</strong> submitting the form.
					Please try again in a minute.
				</div>
			</div>
		
			<div class="wizard-success">
				<div class="alert alert-success">
					<span class="create-server-name"></span>
					was created <strong>successfully.</strong>
				</div>
		
				<a class="btn create-another-server">Create another server</a>
				<span style="padding:0 10px">or</span>
				<a class="btn im-done">Done</a>
			</div>
		
		</div>
			
	
			
		<script src="bootstrap/js/jquery.min.js"></script>
		<script src="bootstrap/js/bootstrap.min.js"></script>
		<script src="bootstrap/js/bootstrap-wizard.js"></script>
		
		
<script type="text/javascript">
var select_db_driver;
select_db_driver = $('input[name=db_driver]:checked').val();
$('input[name=db_driver]').on('change', function() {
	select_db_driver = $('input[name=db_driver]:checked').val();
	
	// 0: PostgresSQL || 1: Oracle
	if(select_db_driver == '1'){
		$('#db_service_id_div').show();
		$('#db_name_div').hide();
		fn_set_default_for_oracle_view();
		fn_show_postgres_user_form();
	}else{
		$('#db_service_id_div').hide();
		$('#db_name_div').show();
		fn_set_default_for_postgres_view();
		fn_show_oracle_user_form();
	}
});

$('#system_user').hide();
$('#system_user_pw').hide();
$('#label_system_user').hide();
$('#label_system_user_pw').hide();


var connection_flag = false;
var wizard = null;
var v_check_path = false;
var v_check_db_connection = false;

var db_mig_timer = null;
var db_mig_percent = 0;
var db_mig_flag = false;
var db_mig_complete_flag = false;
var db_service = "";

function fn_set_default_for_postgres_view(){
	$('#db_port').val('5432');
    $('#db_name').val('<?=DB_NAME?>');
    $('#db_user').val('<?=DB_USER?>');
    $('#db_user_pw').val('<?=DB_USER_PW?>');
	
}

function fn_set_default_for_oracle_view(){
	$('#db_port').val('1521');
    $('#db_name').val('<?=DB_NAME?>');
    $('#db_user').val('<?=DB_USER?>');
    $('#db_user_pw').val('<?=DB_USER_PW?>');
}

function fn_show_postgres_user_form(){
	$('#super_user').hide();
	$('#super_user_pw').hide();
	$('#label_super_user').hide();
	$('#label_super_user_pw').hide();
	$('#system_user').show();
	$('#system_user_pw').show();
	$('#label_system_user').show();
	$('#label_system_user_pw').show();
}

function fn_show_oracle_user_form(){
	$('#super_user').show();
	$('#super_user_pw').show();
	$('#label_super_user').show();
	$('#label_super_user_pw').show();
	$('#system_user').hide();
	$('#system_user_pw').hide();
	$('#label_system_user').hide();
	$('#label_system_user_pw').hide();
}

function fn_check_path_upload_folder(av_mode){
	
    var v_path_upload_folder = $('#web_path_local_value').val();
    
    if(v_path_upload_folder.length <= '0'){
        alert('Please input the path of upload folder');
    }else{
		
        $.ajax({
    		url:'includes/set_path_folder.php',
    		type:'post',
    		data:{
                path_folder : v_path_upload_folder,
                mode: av_mode 
    		},
    		dataType: 'json',
    		success:function(data){				
    		  if(data.success == true){
					if(av_mode == 'check'){
						alert(data.result);
					}
					v_check_path = true;
					
    		  }else{
				  if(av_mode == 'check'){
					alert(data.result);
				  }
					
    		  }
    		  
    		},
    		failure:function(data){
    		  
    		}
	   });
        
    }
	
}

function fn_show_popup(){
    var db_host = $('#db_host').val();
    var db_port = $('#db_port').val();
    var db_name = $('#db_name').val();
    var db_new_user = $('#db_user').val();
    var db_new_user_pw = $('#db_user_pw').val();
	var db_driver = $('input[name=db_driver]:checked').val();
	//alert(db_driver);
	
	// 0: PostgresSQL || 1: Oracle
	if(db_driver == '0'){
		$('#super_user').val('postgres');
		$('#host_popup').text(db_host);
		$('#db_port_popup').text(db_port);
		$('#db_name_popup').text(db_name);
		$('#new_user').val(db_new_user);
		$('#new_user_pw').val(db_new_user_pw);
		
		$('#myModal').modal('show');
	}else{
		$('#super_user').val('proxima');
		$('#host_popup').text(db_host);
		$('#db_port_popup').text(db_port);
		$('#db_name_popup').text(db_name);
		$('#new_user').val(db_new_user);
		$('#new_user_pw').val(db_new_user_pw);
		
		$('#myModal').modal('show');
	}
    
    
}
function fn_hide_popup(){
    $('#myModal').modal('hide');
}

function connection_db_test(that){
    
    var db_host = $('#db_host').val();
    var db_port = $('#db_port').val();
    var db_name = $('#db_name').val();
    var db_user = $('#db_user').val();
    var db_user_pw = $('#db_user_pw').val();
	var db_driver = $('input[name=db_driver]:checked').val();
	var db_service_id = $('#db_service_id').val();
    
    if(db_host.length <= '0' || db_port.length <= '0' || db_name.length <= '0' || db_user.length <= '0' || db_user_pw.length <= '0'){
        alert('Please check your input information');
    }else{
        $.ajax({
    		url:'includes/check_db_connection.php',
    		type:'post',
    		data:{
                db_host : db_host,
                db_port : db_port,
                db_name : db_name,
                db_user : db_user,
                db_user_pw : db_user_pw,
				db_driver: db_driver,
				db_service_id: db_service_id
    		},
    		dataType: 'json',
    		success:function(data){
    		  
                if(data.success == true){
                    alert(data.result);
					v_check_db_connection = true;
                }else{
                    alert(data.result);
                }
    		},
    		failure:function(data){
    		  
    		}
	   });
        
    }
    
}

function fn_create_new_db_user(){
    var host_popup = $('#host_popup').text();
    var db_port_popup = $('#db_port_popup').text();
    var db_name_popup = $('#db_name_popup').text();
    
    var super_user = $('#super_user').val();
    var super_user_pw = $('#super_user_pw').val();
    var new_user = $('#new_user').val();
    var new_user_pw = $('#new_user_pw').val();
	var db_driver = $('input[name=db_driver]:checked').val();
	var db_service_id = $('#db_service_id').val();

	var system_user = $('#system_user').val();
	var system_user_pw = $('#system_user_pw').val();
	
	// 0: Postgres || 1: Oracle
	if(db_driver == '0') {
		if(super_user.length <= '0' || super_user_pw.length <= '0' || new_user.length <= '0' || new_user_pw.length <= '0'){
			alert('Please fill full the input text');
		}else{
			$.ajax({
				url:'includes/create_new_user_and_schema.php',
				type:'post',
				data:{
					db_host : host_popup,
					db_port : db_port_popup,
					db_name : db_name_popup,
					db_user : super_user,
					db_user_pw : super_user_pw,
					db_new_user: new_user,
					db_new_user_pw:new_user_pw,
					db_driver: db_driver,
					db_service_id: db_service_id
				},
				dataType: 'json',
				success:function(data){
					if(data.success == true){
						alert(data.result);
						$('#db_user').val(new_user);
						$('#db_user_pw').val(new_user_pw);
						fn_hide_popup();    
					}else{
						alert(data.result);
					}
				},
				failure:function(data){
				  
				}
		   });
			
		}
	} else {
		if(system_user.length <= '0' || system_user_pw.length <= '0' || new_user.length <= '0' || new_user_pw.length <= '0'){
        alert('Please fill full the input text');
		}else{
			$.ajax({
				url:'includes/create_new_user_and_schema.php',
				type:'post',
				data:{
					db_host : host_popup,
					db_port : db_port_popup,
					db_name : db_name_popup,
					db_user : system_user,
					db_user_pw : system_user_pw,
					db_new_user: new_user,
					db_new_user_pw:new_user_pw,
					db_driver: db_driver,
					db_service_id: db_service_id
				},
				dataType: 'json',
				success:function(data){
					if(data.success == true){
						alert(data.result);
						$('#db_user').val(new_user);
						$('#db_user_pw').val(new_user_pw);
						fn_hide_popup();    
					}else{
						alert(data.result);
					}
				},
				failure:function(data){
				  
				}
		   });
			
		}
	}
    
}
//TODO
function fn_install_db_table_data(){
    
    var db_host = $('#db_host').val();
    var db_port = $('#db_port').val();
    var db_name = $('#db_name').val();
    var db_user = $('#db_user').val();
    var db_user_pw = $('#db_user_pw').val();
	var db_driver = $('input[name=db_driver]:checked').val();
	var db_service_id = $('#db_service_id').val();
    
    if(db_host.length <= '0' || db_port.length <= '0' || db_name.length <= '0' || db_user.length <= '0' || db_user_pw.length <= '0'){
        alert('Please check your input information');
    }else{
        $.ajax({
            url:'includes/install_db_table_and_data.php',
            type:'post',
            data:{
                db_host : db_host,
                db_port : db_port,
                db_name : db_name,
                db_user : db_user,
                db_user_pw : db_user_pw,
				db_driver : db_driver,
				db_service_id:db_service_id
            },
            dataType: 'json',
            success:function(data){
            
                if(data.success == true){
                    alert(data.result);    
                }else{
                    alert(data.result);
                }
            },
            failure:function(data){
            
            }
        });
    
    }
}

function fn_restart_service_finish(){
	var db_host = $('#db_host').val();
    var db_port = $('#db_port').val();
    var db_name = $('#db_name').val();
    var db_user = $('#db_user').val();
    var db_user_pw = $('#db_user_pw').val();
	var db_driver = $('input[name=db_driver]:checked').val();
	var db_service_id = $('#db_service_id').val();
	setTimeout("goIndex()",1000*10);
	$.ajax({
		url:'includes/restart_server_and_finish.php',
		type:'post',
		data:{
			type: "restart",
			db_host : db_host,
			db_port : db_port,
			db_name : db_name,
			db_user : db_user,
			db_user_pw : db_user_pw,
			db_driver : db_driver,
			db_service_id:db_service_id
		},
		dataType: 'json',
		success:function(data){
		
		},
		failure:function(data){
		
		}
	});
	var confirm_result = confirm(" Web service will be restarted.\n It may take a few minute.\n After restarting service, go to http://<?=SERVER_IP?>:<?=SERVER_PORT?> for login.\n Please login with account: admin / gemiso.com");

}

function goIndex(){
	window.location.href = 'http://<?=SERVER_IP?>:<?=SERVER_PORT?>/index.php';
}

function setServerResource(card) {
//	var displayName = 'Geminisoft';
//
//	card.wizard.setSubtitle(displayName);
//	card.wizard.el.find(".create-server-name").text(displayName);
}

function setWebServer(card) {
	var server_ip = $('#server_ip').val();
	var stream_server_ip = $('#stream_server_ip').val();
	var server_port = $('#server_port').val();
	var stream_server_port = $('#stream_server_port').val();
	$.ajax({
		url:'includes/set_xml_info.php',
		type:'post',
		data:{
			server_ip: server_ip,
			stream_server_ip: stream_server_ip,
			server_port: server_port,
			stream_server_port: stream_server_port
		},
		dataType: 'json',
		success:function(data){

		},
		failure:function(data){
		
		}
	});
}

$(function() {
	$.fn.wizard.logging = false;
	
	wizard = $("#wizard-demo").wizard();

	wizard._submit_confirm_flag = true;
	wizard._submit_confirm_flag2 = true;

	wizard.el.find(".wizard-ns-select").change(function() {
		wizard.el.find(".wizard-ns-detail").show();
	});

	wizard.el.find(".create-server-service-list").change(function() {
		var noOption = $(this).find("option:selected").length == 0;
		wizard.getCard(this).toggleAlert(null, noOption);
	});

	wizard.cards["name"].on("validated", function(card) {
		var db_host = card.el.find("#web_path_local_value").val();
	});

	wizard.on("submit", function(wizard) {
		fn_restart_service_finish();
	});

	wizard.on("reset", function(wizard) {
		wizard.setSubtitle("");
		wizard.el.find("#web_path_local_value").val("");

	});

	wizard.el.find(".wizard-success .im-done").click(function() {
		wizard.reset().close();
	});

	wizard.el.find(".wizard-success .create-another-server").click(function() {
		wizard.reset();
	});
	
	$(".wizard-group-list").click(function() {
		alert("Disabled for demo.");
	});


	wizard.show();
});

</script>
		
		
	
	</body>
</html>