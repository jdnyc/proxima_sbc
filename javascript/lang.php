<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
	session_start();
	$lang = $_SESSION['user']['lang'];
	$version = env("APP_VER", "3.0.0");
?>

var LANG = '<?=$lang?>';
var _lang_cmd_xmlDoc;
var _lang_msg_xmlDoc;
var APP_VER = '<?=$version?>';

function _load_xml(file)
{
	if (window.XMLHttpRequest)
	{
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else
	{
		// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.open("GET","/lib/lang/"+file+"?v="+APP_VER, false);
	xmlhttp.send();
	lang_xmlDoc=xmlhttp.responseXML;

	return lang_xmlDoc;
}

function _load_lang(file)
{
	if (file == 'lang_cmd.xml')
	{
		if (!_lang_cmd_xmlDoc)
		{
			_lang_cmd_xmlDoc = _load_xml(file);
		}

		return _lang_cmd_xmlDoc;
	}
	else if (file == 'lang_msg.xml')
	{
		if (!_lang_msg_xmlDoc)
		{
			_lang_msg_xmlDoc = _load_xml(file);
		}

		return _lang_msg_xmlDoc;
	}
}

function _findLang(items, code, code_tag, lang_tag)
{
	for (var i=0; i<items.length; i++)
	{
		_code = items[i].getElementsByTagName(code_tag)[0].childNodes[0].nodeValue;
		if (_code == code)
		{
			return  items[i].getElementsByTagName(lang_tag)[0].childNodes[0].nodeValue;
		}
	}

	return 'undefined lang';
}

function _text(code)
{
	var xmlDoc, items, lang;

	if (code.indexOf("MN") > -1)
	{
		xmlDoc = _load_lang('lang_cmd.xml');

		if(xmlDoc) {
			if (LANG == 'ko')
			{
				items = xmlDoc.getElementsByTagName("menu");
				lang = _findLang(items, code, 'mcode', 'hmenu');
			}
			else if (LANG == 'en')
			{
				items = xmlDoc.getElementsByTagName("menu");
				lang = _findLang(items, code, 'mcode', 'emenu');
			}
			else if (LANG == 'ja')
			{
				items = xmlDoc.getElementsByTagName("menu");
				lang = _findLang(items, code, 'mcode', 'emenu');
            }
            else if (LANG == 'zh_CN')
			{
				items = xmlDoc.getElementsByTagName("menu");
				lang = _findLang(items, code, 'mcode', 'cmenu');
            }
			else
			{
				//alert('no');
			}
		}
	}
	else if (code.indexOf('MSG') > -1)
	{
		xmlDoc = _load_lang('lang_msg.xml');

		if(xmlDoc) {
			if (LANG == 'ko')
			{
				items = xmlDoc.getElementsByTagName("items");
				lang = _findLang(items, code, 'msgcode', 'msgkor');
			}
			else if (LANG == 'en')
			{
				items = xmlDoc.getElementsByTagName("items");
				lang = _findLang(items, code, 'msgcode', 'msgeng');
			}
			else if (LANG == 'ja')
			{
				items = xmlDoc.getElementsByTagName("items");
				lang = _findLang(items, code, 'msgcode', 'msgeng');
            }
            else if (LANG == 'zh_CN')
			{
				items = xmlDoc.getElementsByTagName("items");
				lang = _findLang(items, code, 'msgcode', 'msgchi');
            }
			else
			{
				//alert('no');
			}
		}
	}
	else
	{
		alert('존재하지 않는 언어코드입니다.');
	}

	//alert(lang);

	return lang;
}
