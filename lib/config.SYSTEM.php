<?php
//일부 define 정보를 xml로 변경

$doc = simplexml_load_file(ROOT.'/config.SYSTEM.xml');

$items = $doc->xpath("/root/items");
if(!empty($items)) {
	foreach($items as $item) {
		foreach($item as $key => $val) {
			define($key, (string)$val );
		}
	}
}
