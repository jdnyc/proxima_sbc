ArielApp = {
	path: {
		vcr: "c:\\program files\\geminisoft\\geminiActiveX\\VCRListWrite.exe",
		filer: "C:\\Program Files\\Ariel Filer\\ArielFiler.exe"
	}
};


function runArielAppFiler(params) {
	var o = document.getElementById('GeminiAxCtrl');
	if (o && o.IsInstalled(ArielApp.path.filer) == 'true') {
		o.ProcessStart(ArielApp.path.filer, params);
	} else {
		alert('파일러 프로그램이 설치 되어있지 않습니다.');
	}
}

function runArielApp(program) {
	var run = chooseApp(program);

	var o = document.getElementById('GeminiAxCtrl');
	if (o && o.IsInstalled(run) == 'true') {
		var _p = '"'+mode+'" '+'"'+user_id+'"';
		o.ProcessStart(run);
	} else {
		alert('설치 요망');
	}
}
function runArielAppVCR(f, mode, user_id) {
	var run = chooseApp(f);

	var o = document.getElementById('GeminiAxCtrl');
	if (o && o.IsInstalled(run) == 'true') {
		var _p = '"'+mode+'" '+'"'+user_id+'"';
		o.ProcessStart(run, _p);
	} else {
		alert('VCR리스트 작성 프로그램이 설치 되어있지 않습니다.');
	}
}

function runArielAppVCRList(prog, args) {
	var run = chooseApp(prog);

	var o = document.getElementById('GeminiAxCtrl');
	if (o && o.IsInstalled(run) == 'true') 
	{
		o.ProcessStart(run, args);
		return true;
	}
	else 
	{
		alert('VCR리스트 작성 프로그램이 설치 되어있지 않습니다.');
	}
}

function chooseApp(prog, mode) {
	var _t, _p;
	switch (prog) {
		case 'vcr':
			_t = ArielApp.path.vcr;
			//_p = getParamsArielAppVCR(mode)
		break;

		default:
			alert('선탣하신 프로그램이 목록에 존재하지 않습니다.');
		break;
	}

	return _t;
}

function getParamsArielAppVCR(mode){

}