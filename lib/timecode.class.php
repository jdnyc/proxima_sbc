<?php

class timecode
{
	public $_start;
	public $_end;
	static public $_errmsg;
	public $_startSec;
	public $_endSec;

	const FRAMERATE = 29.97;

	function __construct( $start = null, $end = null )
	{
		$this->_start = $start;
		$this->_end = $end;
		$this->_startSec = $this->getConvSec($start);
		$this->_endSec = $this->getConvSec($end);
	}

	function setStart($time)
	{
		$this->_start = $time;
		$this->_startSec = $this->getConvSec($time);
		return true;
	}

	function setEnd($time)
	{
		$this->_end = $time;
		$this->_endSec = $this->getConvSec($time);
		return true;
	}

	function setStartSec($time)
	{
		$this->_start = $time;
		$this->_startSec = $this->getConvSec($time);
		return true;
	}

	function getStartSec()
	{
		return $this->_startSec;
	}

	function getEndSec()
	{
		return $this->_endSec;
	}


	function getLength()
	{
		if( empty( $this->_end ) )
		{//인자가 불충분할때
			$this->errmsg = 'not exist ending timecode';
			return false;
		}

		$start = $this->_startSec;
		$end = $this->_endSec;

		if($start > $end)
		{
			return $start - $end;
		}
		else
		{
			return $end - $start;
		}
	}

	static function getConvSec($tc)
	{
		$tc = trim($tc);
		if( strstr( $tc, ':' ) ) // 00:00:00 형식 00:00:00:00 -> sec
		{
			$time_array = explode(':', $tc);
			$tc = (int)($time_array[0])*3600 + (int)($time_array[1])*60 + (int)($time_array[2]);

			return $tc;
		}
		else
		{
			//$this->errmsg = 'timecode type error (ex. 00:00:00 or 00:00:00:00)';
			return false;
		}
	}

	static function getConvSecFrame($tc, $frame_rate)
	{
		$tc = trim($tc);
		if( strstr( $tc, ':' ) ) // 00:00:00 형식 00:00:00:00 -> sec
		{
			$time_array = explode(':', $tc);
			$tc = (int)($time_array[0])*3600 + (int)($time_array[1])*60 + (int)($time_array[2]) + ((int)($time_array[3]))/$frame_rate;

			return $tc;
		}
		else
		{
			//$this->errmsg = 'timecode type error (ex. 00:00:00 or 00:00:00:00)';
			return false;
		}
	}

	static function getConvSecFrameToTimecode($sec, $frame_rate)
	{
		$sec = trim($sec);
		if( !is_numeric($sec) )
		{
            timecode::$_errmsg = 'is not number';
			return false;
		}

		$h = (int)($sec / 3600);
		$i = (int)(($sec % 3600) / 60) ;
		$s = (int)(($sec % 3600) % 60) ;
		$f = round(($sec-(int)$sec)*$frame_rate);

		$h = str_pad($h,2,'0', STR_PAD_LEFT);
		$i = str_pad($i,2,'0', STR_PAD_LEFT);
		$s = str_pad($s,2,'0', STR_PAD_LEFT);
		$f = str_pad($f,2,'0', STR_PAD_LEFT);

		$value = $h.':'.$i.':'.$s.':'.$f;

		return $value;
	}

	static function getConvFrameToTimecode($frame, $frame_rate)
	{
		$frame = trim($frame);
		if( !is_numeric($frame) )
		{
            timecode::$_errmsg = 'is not number';
			return false;
		}
		$sec = (int)($frame / $frame_rate);
		$h = (int)($sec / 3600);
		$i = (int)(($sec % 3600) / 60) ;
		$s = (int)(($sec % 3600) % 60) ;
		$f = $frame-(int)($sec*$frame_rate);

		$h = str_pad($h,2,'0', STR_PAD_LEFT);
		$i = str_pad($i,2,'0', STR_PAD_LEFT);
		$s = str_pad($s,2,'0', STR_PAD_LEFT);
		$f = str_pad($f,2,'0', STR_PAD_LEFT);

		$value = $h.':'.$i.':'.$s.':'.$f;

		return $value;
	}

	static function getConvFrame($tc)
	{
		$rate = self::FRAMERATE;
		if( strstr( $tc, ':' ) ){
			$time_array = explode(':', $tc);
			$tc_sec = (int)($time_array[0])*3600 + (int)($time_array[1])*60 + (int)($time_array[2]);

			$tc = (int)(( $tc_sec * $rate ) + $time_array[3]);

			return $tc;
		}else{
			//$this->errmsg = 'timecode type error (ex. 00:00:00 or 00:00:00:00)';
			return false;
		}
	}

	static function getConvTimecode($sec)
	{
		$sec = trim($sec);
		if( !is_numeric($sec) )
		{
            timecode::$_errmsg = 'is not number';
			return false;
		}

		$h = (int)($sec / 3600);
		$i = (int)(($sec % 3600) / 60) ;
		$s = (int)(($sec % 3600) % 60) ;

		$h = str_pad($h,2,'0', STR_PAD_LEFT);
		$i = str_pad($i,2,'0', STR_PAD_LEFT);
		$s = str_pad($s,2,'0', STR_PAD_LEFT);

		$value = $h.':'.$i.':'.$s;

		return $value;
	}

	static function addFrame($tc)
	{
		return $tc.':00';
	}

	function getSum()
	{
		$start = $this->_startSec;
		$end = $this->_endSec;

		return $start + $end;
	}



//		function TimeCodeToFrame(ATC: String; AFrameRate: Double){
//			var
//			$HH, $MI, $SS, $FF;
//			$RatePerFrame;
//			$DropFrame;
//			begin
//			Result := 0;
//			if Length(ATC) < 11 then exit;
//
//			DropFrame := (AFrameRate = FrameRate23_98) or
//			(AFrameRate = FrameRate29_97) or
//			(AFrameRate = FrameRate59_94);
//
//			RatePerFrame := Round(AFrameRate);
//			if RatePerFrame <= 0 then RatePerFrame := 1;
//
//			try
//			HH := StrToInt(Copy(ATC, 1, 2));
//			MI := StrToInt(Copy(ATC, 4, 2));
//			SS := StrToInt(Copy(ATC, 7, 2));
//			FF := StrToInt(Copy(ATC, 10, 2));
//
//			if (DropFrame) then
//			begin
//			{ if (AFrameRate = FrameRate23_98) and (FF = 0) and (SS = 0) and ((MI mod 5) <> 0) then FF := 1
//			else if (AFrameRate = FrameRate23_98) and (HH <> 0) and (MI <> 0) and (FF = 0) and (SS = 0) and ((MI mod 5) = 0) then FF := 2
//			else if (AFrameRate = FrameRate29_97) and (FF = 0) and (SS = 0) and ((MI mod 10) <> 0) then FF := 2
//			else if (AFrameRate = FrameRate59_94) and (FF = 0) and (SS = 0) and ((MI mod 10) <> 0) then FF := 4; }
//
//			if (AFrameRate = FrameRate23_98) then
//			begin
//			if (FF = 0) and (SS = 0) and ((MI mod 5) <> 0) then FF := 1
//			else if (MI <> 0) and (FF in [0..1]) and (SS = 0) and ((MI mod 5) = 0) then FF := 2
//			else if FF >= 24 then FF := 23;
//			Result := (HH * 86328) + ((MI * 1440) - ((MI - (MI div 5)) + (MI div 5) * 2)) + (SS * 30) + FF + 1;
//			end
//			else if (AFrameRate = FrameRate29_97) then
//			begin
//			if (FF in [0..1]) and (SS = 0) and ((MI mod 10) <> 0) then FF := 2
//			else if FF >= 30 then FF := 29;
//			Result := (HH * 107892) + ((MI * 1800) - ((MI - (MI div 10)) * 2)) + (SS * 30) + FF + 1;
//			end
//			else if (AFrameRate = FrameRate59_94) then
//			begin
//			if (FF in [0..3]) and (SS = 0) and ((MI mod 10) <> 0) then FF := 4
//			else if FF >= 60 then FF := 59;
//			Result := (HH * 215784) + ((MI * 3600) - ((MI - (MI div 10)) * 4)) + (SS * 60) + FF + 1;
//			end;
//			end
//			else
//			begin
//			if FF >= RatePerFrame then FF := RatePerFrame - 1;
//			Result := ((HH * 3600) + (MI * 60) + SS) * RatePerFrame + FF + 1;//Format('%.2d:%.2d:%.2d:%.2d', [HH, MI, SS, FF]);
//			end;
//			except
//			Result := 0;
//			end;
//			end;


}

/*
$time = new timecode('00:10:00', '00:01:00' );

$length =  $time->getLength();
$timecode =  timecode::getConvTimecode($length);
$sec = 	timecode::getConvSec($timecode);

echo $length.'_'.$timecode.'_'.$sec;
*/
?>