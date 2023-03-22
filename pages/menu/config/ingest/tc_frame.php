<?php
//$tc_store = $_POST['tc_store'];
//$tc_store_array = json_decode($tc_store);
$out_data = $_POST['out_data'];
$in_data = $_POST['in_data'];

$in_hh = substr($in_data,0,2);
$in_mm = substr($in_data,3,2);
$in_ss = substr($in_data,6,2);
$in_fr = substr($in_data,9,2);
$out_hh = substr($out_data,0,2);
$out_mm = substr($out_data,3,2);
$out_ss = substr($out_data,6,2);
$out_fr = substr($out_data,9,2);

if(($in_mm % 10 == 0)&&($in_ss == '00'))
{
	$in_data = $in_hh.':'.$in_mm.':'.$in_ss.':'.$in_fr;
}else if(($in_ss == '00')&&($in_fr == '00' || $in_fr == '01'))
{
	$in_data = $in_hh.':'.$in_mm.':'.$in_ss.':02';
}

if(($out_mm % 10 == 0)&&($out_ss == '00'))
{
	$out_data = $out_hh.':'.$out_mm.':'.$out_ss.':'.$out_fr;
}else if(($out_ss == '00')&&($out_fr == '00' || $out_fr == '01'))
{
	$out_data = $out_hh.':'.$out_mm.':'.$out_ss.':02';
}
echo "{success: true, in_data:'$in_data', out_data:'$out_data'}";
/*

   String hh, mm, ss, fr;

    hh = TimeCode.SubString(1,2);
    mm = TimeCode.SubString(4,2);
    ss = TimeCode.SubString(7,2);
    fr = TimeCode.SubString(10,2);

	if( (StrToInt(mm) % 10 == 0 ) &&(ss == "00") && (fr == "00" || fr == "01"))
    {
        Memo1->Lines->Add(hh+mm+ss+fr+ " second ");
        return "hh:mm:ss:02";
    }
    else
    {
        return "else";
    }*/
?>