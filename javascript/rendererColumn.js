function renderContentStatus(v){
	var r ;
	switch (parseInt(v)) {
		case 0:
			r = '등록대기';
		break;

		case 1:
			r = '등록요청';
		break;

		case 2:
			r = '심의비대상(등록완료)';
		break;

		case 3:
			r = '심의대기중';
		break;

		case 4:
			r = '승인';
		break;

		case 5:
			r = '반려';
		break;

		case 6:
			r = '조건부승인';
		break;
	}

	return r;
}
