<?php
/**
 * 페이징 함수
 *
 * @author Woo,Yeon-geun <cjdma@cjdma.com>
 *
 * @param int $total; 86
 * @param int $now;
 * @param int $page; 10
 * @param int $scale; 2
 * @param int $block_scale; 10
 *
 * @return int  $prev  이전 페이지
 * @return int  $page  현재 페이지
 * @return int  $next  다음페이지
 * @return int  $first 시작할 때의 글번호
 * @return array $range 출력하려는 페이지들                     
 */
function paging($param)
{
	$total			= $param['total'];
    $page			= $param['page']        ? $param['page']		:  1;
    $scale			= $param['scale']		? $param['scale']		: 20;
    $block_scale	= $param['block_scale']	? $param['block_scale'] : 10;

	  $pages = ceil($total / $scale); // 전체 페이지 수    86

    $begin = floor(($page - 1) / $block_scale) * $block_scale; // 첫번째블럭번호, 마지막블럭번호 (5,6,7,8,9 <- 여기서 $begin=5, $end=9)
	  $end   = $begin + $block_scale;
    $begin++; // 내부적으로 시작값이 '0'인 변수를 '1'로 맞추기...

    if($end > $pages) $end = $pages; // 블럭내 끝  record($end)가 전체값($total)을 넘을 수 없으므로...
    if($end == 0) $end = 1;

		$range = range($begin, $end); // prev 5.6.7.8.9 next - 이 경우에 array(5,6,7,8,9)  1 10

		if( $page != 1) $prev = $page - 1; // prev(이전페이지)출력 - $page가 1이 아닐 때
    if( $begin <= $page || $begin != $page ) $prev_set = $begin - 1; // prev_set(이전 10 페이지)출력

		if($page != $pages && $end != 1) $next = $page + 1; // next(다음페이지)출력 - $page가 $end가 아닐 때
    if( $end != $pages ) $next_set = $end + 1; // next_set(다음 10 페이지)출력

    $return['prev']				= $prev;
    $return['prev_set']			= $prev_set;
    $return['page']				= $page;
    $return['next']				= $next;
    $return['next_set']			= $next_set;
    $return['range']			= $range;
	$return['last']				= $pages;
	$return['total']			= $total;

    return $return;
}

function strcut_utf8($str, $len, $checkmb=false, $tail='...') {
preg_match_all('/[\xEA-\xED][\x80-\xFF]{2}|./', $str, $match);

$m = $match[0];
$slen = strlen($str); // length of source string
$tlen = strlen($tail); // length of tail string
$mlen = count($m); // length of matched characters

if ($slen <= $len) return $str;
if (!$checkmb && $mlen <= $len) return $str;

$ret = array();
$count = 0;

for ($i=0; $i < $len; $i++) {
$count += ($checkmb && strlen($m[$i]) > 1)?2:1;

if ($count + $tlen > $len) break;
$ret[] = $m[$i];
}

return join('', $ret).$tail;
}


function writePaging($paging, $url,  $query){


	if( $paging['total'] ){

		echo "<li id=\"pagination\" class=\"r hdr_pagination\">\n";
		if ( $paging["prev_set"] )	echo "<a href=\"{$url}?page={$paging["prev_set"]}$query\" class=\"btn prevset\" title=\"Previous set of 10\">Previous set of 10</a>\n";
		if ( $paging["prev"] )		echo "<a href=\"{$url}?page={$paging["prev"]}$query\" class=\"btn prevpg\" title=\"Previous page\">Previous page</a>\n";
		if ( $paging["prev_set"] )	echo "<a href=\"{$url}?page=1$query\">1</a><span>.....</span>\n";
		
		if( $paging["range"] )
		{
			foreach ( $paging["range"] as $key => $value ) 
			{
				if ( $value != $paging['page'] )
					echo "<a href=\"{$url}?page=$value$query\">$value</a>\n";
				else
					echo "<span	class=\"pgon\">$value</span>\n";
			}
		}

		if ( $paging["next_set"] )	echo "<span>.....</span><a href=\"{$url}?page={$paging["last"]}$query\">{$paging["last"]}</a>\n";
		if ( $paging["next"] )		echo "<a href=\"{$url}?page={$paging["next"]}$query\" class=\"btn nextpg\" title=\"Next page\">Next page</a>\n";
		if ( $paging["next_set"] )	echo "<a href=\"{$url}?page={$paging["next_set"]}$query\"	class=\"btn nextset\"	title=\"Next set	of 10\">Next set of 10</a>\n";
		echo "</li>\n";
	}
}

function getCategory() {

}

function fDown($file,$name,$downview,$speed,$limit)// 경로, 원파일명, 다운 0/보임 1, 다운속도, 속도제한여부

{

    //do something on download abort/finish

    //register_shutdown_function( 'function_name'  );

    if(!file_exists($file))

        die('File not exist!');

    $size = filesize($file);

    $name = rawurldecode($name);



    if (ereg('Opera(/| )([0-9].[0-9]{1,2})', $_SERVER['HTTP_USER_AGENT']))

        $UserBrowser = "Opera";

    elseif (ereg('MSIE ([0-9].[0-9]{1,2})', $_SERVER['HTTP_USER_AGENT']))

        $UserBrowser = "IE";

    else

        $UserBrowser = '';



    $downview = ($downview) ? "attachment" : "inline"; 



    /// important for download im most browser

    $mime_type = ($UserBrowser == 'IE' || $UserBrowser == 'Opera')? 'application/octetstream' : 'application/octet-stream';

    @ob_end_clean(); /// decrease cpu usage extreme

    Header('Content-Type: ' . $mime_type);

    Header('Content-Disposition: '.$downview.'; filename="'.$name.'"');

    Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    Header('Accept-Ranges: bytes');

    Header("Cache-control: private");

    Header('Pragma: private');



    /////  multipart-download and resume-download

    if(isset($_SERVER['HTTP_RANGE']))

    {

        list($a, $range) = explode("=",$_SERVER['HTTP_RANGE']);

        str_replace($range, "-", $range);

        $size2 = $size-1;

        $new_length = $size-$range;

        Header("HTTP/1.1 206 Partial Content");

        Header("Content-Length: $new_length");

        Header("Content-Range: bytes $range$size2/$size");

    } else {

        $size2=$size-1;

        Header("Content-Length: ".$size);

    }

    $chunksize = 1*(1024*$speed);

    $this->bytes_send = 0;

    if ($file = fopen($file, 'rb'))

    {

        if(isset($_SERVER['HTTP_RANGE']))

            fseek($file, $range);

        while(!feof($file) and (connection_status()==0))

        {

            $buffer = fread($file, $chunksize);

            print($buffer);//echo($buffer); // is also possible

            flush();

            $this->bytes_send += strlen($buffer);

            if($limit) sleep(1); // 다운로드 속도제한

        }

        fclose($file);

    } else

        die('Error can not open file!!');

    if(isset($new_length))

        $size = $new_length;

    die();

    Header("Connection: close");

}

function roundsize($size){
    $i=0;
    $iec = array("B", "Kb", "Mb", "Gb", "Tb");
    while (($size/1024)>1){

        $size=$size/1024;
        $i++;
	}
    return (round($size,1)." ".$iec[$i]);
}



function formatBytes($b, $p=null) {
    $units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
    $c=0;
    if(!$p && $p !== 0) {
        foreach($units as $k => $u) {
            if(($b / pow(1024,$k)) >= 1) {
                $r["bytes"] = $b / pow(1024,$k);
                $r["units"] = $u;
                $c++;
            }
        }
        return number_format($r["bytes"],2) . " " . $r["units"];
    } else {
        return number_format($b / pow(1024,$p)) . " " . $units[$p];
    }
   
}

function db_get_one($query){
	global $db_conn;
	
	$result = $mdb->queryRow(($query), 0, 0);
		
	if($result === false){

		echo $query.': '.mysql_error();
		exit;
	}

	return $result;
}
/*
function db_get_one($query){
	global $db_conn;
	
	$result = mysql_result(mysql_query($query), 0, 0);
		
	if($result === false){

		echo $query.': '.mysql_error();
		exit;
	}

	return $result;
}
*/
function db_get_all_assoc($query){
	global $db_conn;
	
	$result = mysql_query($query);
	if($result === false){

		echo $query.': '.mysql_error();
		exit;
	}
	
	while( $row = mysql_fetch_assoc($result) ){

		$s[] = $row;
	}

return $s;
}

function db_get_all_array($query){
	global $db_conn;
	
	$result = mysql_query($query);		
	if($result === false){

		echo $query.': '.mysql_error();
		exit;
	}
	
	while( $row = mysql_fetch_array($result) ){

		$s[] = $row[0];
	}

return $s;
}

function alert_back($msg){

	echo "<script>alert('$msg');history.back();</script>";
}
function alert($msg){

	echo "<script>alert('$msg')</script>";
}
function get_original_file($id){

	global $db_conn;

	$query = "select vcharOriginalPath from base where uid='$id'";
	$result = mysql_result(mysql_query($query), 0, 0);
	$result = str_replace("\\", "/", $result);
	
	return ROOT.$result;
}
function check_id($id){
	global $mdb;

	return $mdb->queryOne("select count(*) from base where id='$id'");
}
function get_reg_user($id){
	global $mdb;
	
	return $mdb->queryOne("select create_user from base where id='$id'");
}

function cut_title($title, $num){

	if ( mb_strlen($title) > $num	)
	{
		return mb_substr($title, 1,	$num). '...';
	}

	return $title;
}
function get_basename( $path )
{
	$path = explode('\\', $path);
	for($i=0; $i<count($path)-1; $i++)
	{
		$output .= $path[$i].'\\';
	}

	return $output;
}

function getFileSize($file) {
/*
		$size = filesize($file);
        if ($size < 0)
            if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'))
                $size = trim(`stat -c%s $file`);
            else{
                $fsobj = new COM("Scripting.FileSystemObject");
                $f = $fsobj->GetFile($file);
                $size = $file->Size;
            }
*/
	//$size = sprintf("%u", filesize($file));
	//$size = filesize($file);
	if( file_exists($file) )
	{
		$fsobj = new COM("Scripting.FileSystemObject");
		$f = $fsobj->GetFile($file);
		$size = $f->Size;
		//echo $size;
		return $size;
	}
	else
	{
		return 0;
	}
}

function fetch_category( $categories_id )
{
	global $mdb;

	$c1 = $mdb->queryOne("select title from categories where id={$categories_id}");

	return array(array($ex_category[0], $c1), array($ex_category[1], $c2), array($ex_category[2], $c3));
}

function get_category_name($categories_id)
{
	global $mdb;
	
	$result = '';
	
	$tmp = explode('_', $categories_id);	
	for ($i=0; $i<count($tmp); $i++) { 
		if($tmp[$i] == 0) continue;
		
		$result[] = $mdb->queryOne("select title from categories where id=".$tmp[$i]);
	}
	
	if($result){
		$category_name = implode(' > ', $result);
	}
	
	return $category_name;
}

function getCurDateTime(){
	return date('YmdHis');
}

function build_target_path($root, $filename=null){

	return $root.'/'.date('Y/m/d/H/is').'/'.$filename;
}
?>