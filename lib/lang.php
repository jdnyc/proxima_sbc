<?php
function _text($str_code)
{
	if(!defined('LANG')) {

		if( empty($_SESSION['user']) || empty( $_SESSION['user']['lang'] ) ){
			if( empty($_REQUEST['lang']) ) {
				$defined_lang = 'ko';
			} else {
				$defined_lang = $_REQUEST['lang'];
			}
		}else{
			$defined_lang = $_SESSION['user']['lang'];
		}

		define('LANG', $defined_lang);
	}


	if(!defined('ROOT')) {
        define('ROOT', __DIR__);
	}
	if (strstr($str_code, 'MN'))
	{
		$doc = simplexml_load_file(ROOT.'/lang/lang_cmd.xml');

		if (LANG == 'en')
		{
			$__lang = $doc->xpath("/root/menu[mcode='$str_code']");
			$lang = (Array)$__lang[0]->emenu;
		}
		else if (LANG == 'ko')
		{
			$__lang = $doc->xpath("/root/menu[mcode='$str_code']");
			$lang = (Array)$__lang[0]->hmenu;
		}
		else if (LANG == 'ja')
		{
			$__lang = $doc->xpath("/root/menu[mcode='$str_code']");
			$lang = (Array)$__lang[0]->jmenu;
        }
        else if (LANG == 'zh_CN')
		{
			$__lang = $doc->xpath("/root/menu[mcode='$str_code']");
			$lang = (Array)$__lang[0]->cmenu;
		}
		else
		{
			throw new Exception('국가가 정의 되어있지 않습니다.');
		}
	}
	else if (strstr($str_code, 'MSG'))
	{
		$doc = simplexml_load_file(ROOT.'/lang/lang_msg.xml');

		if (LANG == 'en')
		{
			//$lang = $doc->xpath("/root/items[msgcode='$str_code']/msgeng");
			$__lang = $doc->xpath("/root/items[msgcode='$str_code']");
			$lang = (Array)$__lang[0]->msgeng;
		}
		else if (LANG == 'ko')
		{
			//$lang = $doc->xpath("/root/items[msgcode='$str_code']/msgkor");
			$__lang = $doc->xpath("/root/items[msgcode='$str_code']");

			$lang = (Array)$__lang[0]->msgkor;
		}
		else if (LANG == 'ja')
		{
			//$lang = $doc->xpath("/root/items[msgcode='$str_code']/msgeng");
			$__lang = $doc->xpath("/root/items[msgcode='$str_code']");
			$lang = (Array)$__lang[0]->msgeng;
        }
        else if (LANG == 'zh_CN')
		{
			//$lang = $doc->xpath("/root/items[msgcode='$str_code']/msgeng");
			$__lang = $doc->xpath("/root/items[msgcode='$str_code']");
			$lang = (Array)$__lang[0]->msgchi;
        }
		else
		{
			throw new Exception('국가가 정의 되어있지 않습니다.');
		}
	}
	else if (strstr($str_code, 'IMG'))
	{
		$doc = simplexml_load_file(ROOT.'/lang/image_path.xml');
		if (LANG == 'en')
		{
			//$lang = $doc->xpath("/root/items[imgcode='$str_code']/imgeng");
			$__lang = $doc->xpath("/root/items[imgcode='$str_code']");
			$lang = (Array)$__lang[0]->imgeng;
		}
		else if (LANG == 'ko')
		{
			//$lang = $doc->xpath("/root/items[imgcode='$str_code']/imgkor");
			$__lang = $doc->xpath("/root/items[imgcode='$str_code']");
			$lang = (Array)$__lang[0]->imgkor;
		}
		else if (LANG == 'ja')
		{
			$__lang = $doc->xpath("/root/items[imgcode='$str_code']");
			$lang = (Array)$__lang[0]->imgeng;
        }
        else if (LANG == 'zh_CN')
		{
			$__lang = $doc->xpath("/root/items[imgcode='$str_code']");
			$lang = (Array)$__lang[0]->imgeng;
		}
		else
		{
			throw new Exception('국가가 정의 되어있지 않습니다.');
		}
	}
	else
	{
		throw new Exception('존재하지 않는 언어코드입니다.');
	}


	$lang = $lang[0];

	$text = str_replace("'", "\\'", $lang);

	// 언어 텍스트를 오버라이딩 하기 위한 구문
	if(\Proxima\core\CustomHelper::customClassExists('\ProximaCustom\core\CustomLang')) {
		$text = \ProximaCustom\core\CustomLang::getText($text, LANG, $str_code);
    }
    
    if (empty($text))
	{
		return 'Undefined text';
	}
	
	return $text;
}
?>