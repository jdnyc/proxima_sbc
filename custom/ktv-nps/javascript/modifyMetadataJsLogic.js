// 제목 글자 길이 처리
if (getByteLength(formValues.k_title) > 60) {
  Ext.Msg.alert('알림', '제목은 60바이트(띄어쓰기 포함 한글 최대 30자) 이내로 입력해야 합니다.');
  return;
}

// 사용기간
if (formValues.usr_expire_period_from !== undefined &&
  formValues.usr_expire_period_to !== undefined) {
  if (Ext.isEmpty(formValues.usr_expire_period_from) || Ext.isEmpty(formValues.usr_expire_period_to)) {
    Ext.Msg.alert('알림', '사용기간 설정 후 저장해주세요.');
    return;
  }
  formValues.usr_expire_period = formValues.usr_expire_period_from + '~' + formValues.usr_expire_period_to;
}

// 채널코드
if (formValues.usr_channel_code !== undefined) {
  var channelCodeCombo = Ext.getCmp('usr_meta__channel_code');
  formValues.usr_channel_code = channelCodeCombo.getChannelCombo().getValue();
  if ((Ext.isEmpty(formValues.usr_channel_code) || formValues.usr_channel_code === 'none') &&
    channelCodeCombo.required) {
    Ext.Msg.alert('알림', '제작채널 설정 후 저장해주세요.');
    return;
  }

  // 방송 채널의 경우 pgm코드를 필수항목으로 체크한다
  if (formValues.usr_channel_code === 'CJOL' ||
    formValues.usr_channel_code === 'CJOP' ||
    formValues.usr_channel_code === 'CJSL') {
    if (Ext.isEmpty(formValues.usr_pgm_code)) {
      Ext.Msg.alert('알림', '프로그램 코드 설정 후 저장해주세요.');
      return;
    }
  }
}

// 상품목록
var itemListPanel = Ext.getCmp('usr_meta__item_list');
if (itemListPanel !== undefined) {

  // 정합성 체크
  if (!itemListPanel.validate()) {
    return;
  }

  formValues.usr_item_list = Ext.encode(itemListPanel.getValue());
  if (Ext.isEmpty(formValues.usr_item_list) && itemListPanel.required) {
    Ext.Msg.alert('알림', '상품목록 설정 후 저장해주세요.');
    return;
  }
}

// 동영상 키워드
var keywordField = Ext.getCmp('usr_meta__keyword');
if (keywordField !== undefined) {
  formValues.usr_keyword = Ext.encode(keywordField.getValue());
  if (Ext.isEmpty(formValues.usr_keyword) && keywordField.required) {
    Ext.Msg.alert('알림', '동영상 키워드 설정 후 저장해주세요.');
    return;
  }
}

// 콘텐츠유형 콤보박스 3개에 대한 처리
var keys = Object.keys(formValues);
var arr_usr_content_type = [];
Ext.each(keys, function (key) {
  var value = formValues[key];
  // 콘텐츠유형 콤보박스 3개에 대한 처리
  if (value === '선택없음' || value === '선택') {
    value = null;
  }
  if (key.indexOf("k_content_type_") === 0) {
    arr_usr_content_type.push(value);
  }

  if (key == 'k_content_type_1' && Ext.isEmpty(value)) {
    Ext.Msg.alert('알림', '콘텐츠 유형의 유형1은 필수로 입력해야 합니다.');
  }
});
if (!Ext.isEmpty(arr_usr_content_type)) {
  formValues['usr_content_type'] = arr_usr_content_type.join(',');
}