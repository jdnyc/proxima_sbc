<?PHP
//작성일 : 2011.12.16
//작성자 : 김형기
//내용 : source_option이나 target_option의 데이터를 파싱하는 클래스
use ProximaCustom\services\ContentService as ContentService;

class TaskOptionParser
{
    private $taskManager    = null;
	private $db			= null;
	private $type		= '';   //source, target
	private $channel	= '';

	private $media_type = '';
	private $media_id	= '';
	private $dir_path	= '';
	private $filename	= '';
	private $ext		= '';
	private $full_path	= '';

    private $src_media_id = '';
    private $src_media_type = '';
    private $trg_media_type = '';

	//2012-3-26 by 이성용
	//원본미디어생성이나 첨부파일 미디어생성시 media 생성전에 소스 및 타겟 파일분석이 필요

	private $vr_start	= '';
	private $vr_end	= '';
	private $source_path	= '';
	private $source_dir_path	= '';
	private $source_filename	= '';
	private $source_ext	= '';
	private $target_path	= '';

	private $source_root	= '';
    private $target_root	= '';
    
    private $src_storage_id	= null;
    private $trg_storage_id	= null;

    private $content_info	= array();
    
	private $metadata_info	= array();
	private $media_info	= array();

	private $get_jobs	= '';

	function getContentInfo ( $content_id , $force = true ){
        if($force){
            return $this->db->queryRow("select * from view_bc_content where content_id=$content_id");
        }else{
            if( empty($this->content_info) ){
                $this->content_info =$this->db->queryRow("select * from view_bc_content where content_id=$content_id");
            }
        }
        return $this->content_info;
    }
    
    function getMetadataInfo( $content_id  ){
        if( empty($this->metadata_info) ){
			$contentInfo = $this->getContentInfo($content_id , false);
            $this->metadata_info = MetaDataClass::getValueInfo('usr', $contentInfo['ud_content_id'] , $content_id );
		}
        return $this->metadata_info;
    }

	function getMediaInfo ( $content_id , $type , $media_id = null ){
		if( empty($this->media_info) ){
			$this->media_info = $this->db->queryAll("select * from bc_media where content_id=$content_id order by media_id desc ");
		}

		if( empty($type) && empty($media_id) ){
			return $this->media_info;
		}

		foreach($this->media_info as $m_info ){
			if( !empty($media_id) && ($m_info['media_id'] == $media_id) ){
				return $m_info;
			}
		}
		foreach($this->media_info as $m_info ){
			if( !empty($type) && ( $m_info['media_type'] == $type ) ){
				return $m_info;
			}
		}
		return array();
	}

    /**
     * 생성자
     *
     * @param [type] $taskManager
     * @param [type] $type
     * @param [type] $channel
     * @param [type] $arr_param_info
     * @param [type] $rule_options
     * @param [type] $get_jobs
     */
	public function __construct($taskManager, $type, $channel=null , $arr_param_info = null, $rule_options = null, $get_jobs = null)
	{
        //작업 클래스를 넘기도록 수정
        $this->taskManager = $taskManager;
		$this->db = $taskManager->getDB();
		$this->type = $type;
		$this->channel = $channel;
		$this->vr_start = 0;
		$this->vr_end = 0;
		$this->rule_options = $rule_options;
		$this->get_jobs = $get_jobs;
		if ( ! empty($arr_param_info) ) {

			// pfr 작업시 미디어정보에 인아웃 값 업데이트
			if( ! is_null($arr_param_info[0]['value']) && !is_null($arr_param_info[1]['value']) ) {
				$this->vr_start = $arr_param_info[0]['value'];
				$this->vr_end = $arr_param_info[1]['value'];
			}

			if ( ! is_null( $arr_param_info[0]['source_path'])) {
				$this->source_path = $arr_param_info[0]['source_path'];
				$temp_path_array = explode('/', $arr_param_info[0]['source_path']);
				$temp_filename = array_pop($temp_path_array);
				$filename_array = explode('.', $temp_filename );

				if( count( $filename_array ) > 1 ) {
					$this->source_ext = array_pop( $filename_array );
					$this->source_filename = implode('.', $filename_array );
				} else {

					//확장자가 없다는건데..그럼 폴더?
					$this->source_filename = array_pop( $filename_array );
				}

				$this->source_dir_path	= implode('/', $temp_path_array);

				$this->setFileName($this->source_filename);
				$this->setFileExt($this->source_ext);
			}

			if ( ! is_null( $arr_param_info[0]['target_path'])) {
				$this->target_path = $arr_param_info[0]['target_path'];
			}

            $this->root_task_id = $arr_param_info[0]['root_task'];
            $this->pre_task_id = $arr_param_info[0]['pre_task'];
		}
	}

	//모든 정보를 뭉텅이로 받을 때...
	public function getTaskOption()
	{
		$task_opt['media_type'] = $this->media_type;
		$task_opt['media_id'] = $this->media_id;
		$task_opt['dir_path'] = $this->dir_path;
		$task_opt['filename'] = $this->filename;
		$task_opt['ext'] = $this->ext;
		$task_opt['full_path'] = $this->full_path;
		return $task_opt;
	}

	public function getMediaType()
	{
		return $this->media_type;
	}

	public function getDirPath()
	{
		return $this->dir_path;
	}

	public function setDirPath($dir_path)
	{
		$this->dir_path = $dir_path;
	}

	public function setSourceRoot($source_root)
	{
		$this->source_root = $source_root;
	}

	public function getSourceRoot()
	{
		return $this->source_root;
	}

	public function setTargetRoot($target_root)
	{
		$this->target_root = $target_root;
	}

	public function getTargetRoot()
	{
		return $this->target_root;
	}

	public function getFileName()
	{
		return $this->filename;
	}

	public function setFileName($filename)
	{
		$this->filename = $filename;
	}

	public function getFileExt()
	{
		return $this->ext;
	}

	public function setFileExt($ext)
	{
		$this->ext = $ext;
    }    
    
	public function getMediaId()
	{
		return $this->media_id;
    }
    
    
	public function getSrcStorageId()
	{
		return $this->src_storage_id;
    }
    
	public function getTrgStorageId()
	{
		return $this->trg_storage_id;
	}
	public function getFullPath()
	{
		if( !empty($this->filename) && !empty($this->ext) ){
			$filenameext = $this->filename.'.'.$this->ext ;
		}

		if( !empty($this->dir_path) && !empty($filenameext) ){
			$this->full_path = $this->dir_path.'/'.$filenameext;
		}else if( !empty($this->dir_path) ){
			$this->full_path = $this->dir_path;
		}else{
			$this->full_path = $filenameext;
		}

		return $this->full_path;
	}

	public function setFullPath($full_path)
	{
		//full path를 입력하면 폴더명, 파일명, 확장자명 분리하여 모두 입력한다.
		$this->full_path = $full_path;
		$arr_full_path = explode('/', $full_path);

		if($arr_full_path[0] == $full_path)
		{
			//파일만 있는경우
			$arr_filename = explode('.', $arr_full_path[0]);
			if($arr_filename[0] == $arr_full_path[0])
			{
				//파일명만 있는경우
				$this->filename = $arr_filename[0];
			}
			else
			{
				//확장자 까지 있는경우
				$this->ext = array_pop($arr_filename);
				$this->filename = implode('.', $arr_filename);
			}
		}
		else
		{
			//디렉터리 까지 있는 경우
			$filename = array_pop($arr_full_path);
			$this->dir_path = implode('/', $arr_full_path);
			$arr_filename = explode('.', $filename);
			$this->ext = array_pop($arr_filename);
			$this->filename = implode('.', $arr_filename);
		}
	}

	//2012-01-29 원본 폴더 중복 체크 함수 추가
	private function exists_original_dir( $media_type )
	{
		$query = "select content_id, path from bc_media ".
					"where media_type = '$media_type' and path like '{$this->dir_path}%'";
		$rows = $this->db->queryAll($query);
		if($rows <= 0)
			return false;

		$content_id = '';

		foreach($rows as $row)
		{
			if($content_id == '')
				$content_id = $row['content_id'];

			//같은 경로가 여러개 나와도 같은 콘텐츠면 관계 없다...하지만 다른 콘텐츠라면 폴더를 새로 만들어야 겠지?

			if($media_type == 'original')
			{
				if($content_id != $row['content_id'])
				{
					return true;
				}
			}

			//만약 경로가 파일명까지 모두 동일하다면 역시 새로 만들어야 겠지?
			if($this->full_path == $row['path'])
			{
				return true;
			}
		}


		return false;
	}

	//작업 옵션을 분석한다.
	public function parseTaskOption($content_id, $option, $src_media_type= null , $src_media_id = null )
	{

        $this->src_media_id = empty($src_media_id) ? null : $src_media_id;
        $this->src_media_type = empty($src_media_type) ? null : $src_media_type;

        if (empty($option) || $option == 'null') {
            $this->media_id = empty($src_media_id) ? null : $src_media_id;
            return;
        }
		$arr_opt = explode('&', $option);

		$exists_dir_path = false;
		$exists_filename = false;
		$exists_ext = false;
		foreach ($arr_opt as $opt) {

			list($key, $value) = explode('=', $opt);
			if ($key == 'media') {

                $this->media_type = $value;
                
                if( $this->type == 'target' ){
                    $this->trg_media_type = $value;
                }

				if( $this->type == 'target' && $this->media_type == 'proxy_out' ){
					//미디어 생성
					$this->media_id = getSequence('SEQ_MEDIA_ID');

				}else if( $this->trg_media_type == 'source'  ){
                    //소스 참조
                    if( $src_media_id ){
                        $query = "select * from bc_media where media_id={$src_media_id} order by media_id desc";
                    }else if($src_media_type){
                        $query = "select * from bc_media where content_id={$content_id} and media_type={$src_media_type} order by media_id desc";                        
                    }else{
                        $query = "select * from bc_media where content_id = {$content_id} and media_type = '{$value}' order by media_id desc";
                    }
                    $row = $this->db->queryRow($query);
                    if (!empty($row['media_id'])) {
                        $this->media_id = $row['media_id'];
                        $this->media_type = $row['media_type'];
                        $this->src_storage_id = $row['storage_id'];
                        $this->src_media_type = $row['media_type'];
                    }
                }else{
                    if( $this->media_type == 'dynamic' && !empty( $this->taskManager->getSrcMediaId() ) ){
                        $this->src_media_id = $this->taskManager->getSrcMediaId();
                        $query = "select * from bc_media where media_id={$this->src_media_id} order by media_id desc";
                        $row = $this->db->queryRow($query);
                        if (! empty($row['media_id'])) {
                            $this->media_type = $row['media_type'];
                            $this->src_storage_id = $row['storage_id'];
                            $this->src_media_type = $row['media_type'];
                        }
                    }else{
                        $query = "select * from bc_media where content_id = {$content_id} and media_type = '{$value}' order by media_id desc";
                        $row = $this->db->queryRow($query);                      
                    }

					if ( !empty($row['media_id'])) {
                        $this->media_type = $row['media_type'];
                        $this->media_id = $row['media_id'];
                        
                        $pathInfo           = new \Api\Core\FilePath($row['path']);
                        $this->dir_path     = $pathInfo->getFilePath();
                        $this->ext          = $pathInfo->getFileExt(); 
                        $this->filename     = $pathInfo->getFilename();        

						// $arr_path = explode('/', $row['path']);
						// $filename = array_pop($arr_path);
						// $this->dir_path = implode('/', $arr_path);
						// $arr_filename = explode('.', $filename);
						// $this->ext = array_pop($arr_filename);
						// $filename = implode('.', $arr_filename);
                        // $this->filename = $filename;                        
                        
					}
				}
			} else if($key == 'dir') {
				$exists_dir_path = true;
				if($value == '$SELF')
					$dir = $this->dir_path;
				else
					$dir = $this->getDirPathFromOption($content_id, $value, $this->src_media_type);
			} else if($key == 'filename') {
				$exists_filename = true;
				if($value == '$SELF')
					$file_nanme = $this->file_nanme;
				else
					$file_nanme = $this->getFileNameFromOption($content_id, $value, $this->src_media_type);
			} else if($key == 'ext') {
				$exists_ext = true;
				if($value == '$SELF')
					$ext = $this->ext;
				else{
                    $ext = $this->getFileNameFromOption($content_id, $value, $this->src_media_type);
                }
			} else if($key == 'media_ext') {
				if($this->media_type != "null")
					$media_type = $this->media_type;

				$query = "select * from bc_media where content_id = {$content_id} and media_type = 'original' and path like '%{$value}'";
				//echo '미디어 타입 쿼리 : '.$query.'<br/>';
				$row = $this->db->queryRow($query);
				if(!empty($row['media_id']))
				{
					$this->media_id = $row['media_id'];
					$arr_path = explode('/', $row['path']);
					$filename = array_pop($arr_path);
					$this->dir_path = implode('/', $arr_path);
					$arr_filename = explode('.', $filename);
					$this->ext = array_pop($arr_filename);
					$filename = implode('.', $arr_filename);
					$this->filename = $filename;
					//if($this->type == 'source')
					//{
					//	echo '요기 : '.$query.'<br/>';
					//	echo '요기2 : '.$this->dir_path.'<br/>';
					//}
				}
			}else if($key == 'before_media'){
				$bf_task_info = $this->getBeforeTaskInfo( $this->root_task_id, $this->get_jobs );

				if( $bf_task_info && $value == 'source'){
					$this->media_id = $bf_task_info['src_media_id'];
					if(!empty($this->media_id)){
						$bmedia = $this->db->queryRow("select * from bc_media where media_id=".$this->media_id);
						$this->media_type = $bmedia['media_type'];
						$this->setFullPath($bmedia['path']);
					}
				}else if( $bf_task_info &&  $value == 'target' ){
					$this->media_id = $bf_task_info['trg_media_id'];
					if(!empty($this->media_id)){
						$bmedia = $this->db->queryRow("select * from bc_media where media_id=".$this->media_id);
						$this->media_type = $bmedia['media_type'];
						$this->setFullPath($bmedia['path']);
					}
				}else{
					throw new Exception('not found media');
				}
			}else if($key == 'ref_src_storage_id'){
                //media의 스토리지ID가 있는 경우 워크플로우의 소스루트 스토리지ID 보다 우선한다.
                if( !empty($value)){
                    if( !empty($this->media_id) ){
                        $query = "select * from bc_media where media_id={$this->media_id} order by media_id desc";
                        $row = $this->db->queryRow($query);
                        if (!empty($row['storage_id'])) {
                            $this->src_storage_id = $row['storage_id'];
                        }
                    }
                }
            }
		}

		if($exists_dir_path || $exists_filename || $exists_ext)
		{
			$this->dir_path = '';
			$this->filename = '';
			$this->ext = '';

			if($exists_dir_path)
				$this->dir_path = $dir;

			if($exists_filename)
				$this->filename = $file_nanme;

			if($exists_ext)
				$this->ext = $ext;

		}

		$this->full_path = $this->dir_path;

		if($this->filename != '')
		{
			$this->full_path .= '/'.$this->filename;
		}
		if($this->ext != '')
		{
			$this->full_path .= '.'.$this->ext;
        }
        
		//echo $this->type.'<br/>';
		//echo 'before:'.$this->full_path.'<br/>';

		if($this->type == 'target')
		{
			//타겟이고 미디어 타입이 있을 때 미디어 테이블에 신규로 등록
			if( $this->media_type != '')
			{
                // $this->taskManager->_log('', __LINE__.':media_type', $this->media_type );
                // $this->taskManager->_log('', __LINE__.':src_storage_id', $this->src_storage_id );
                // $this->taskManager->_log('', __LINE__.':src_media_type', $this->src_media_type );


                if( $this->trg_media_type == 'source' ) return;

				$created_datetime = date('YmdHis');
				$content_info = $this->getContentInfo( $content_id );
				$ud_content_id = $content_info['ud_content_id'];
				//$expired_date = '99981231000000';
				$expired_date = check_media_expire_date($ud_content_id, $this->media_type, $created_datetime);
				//오리지널 중복 제거 주석 $this->media_type == 'original'
				if( $this->media_type == 'attach' )
				{//첨부화일도 중복패스체크
					$i=0;
					while( $this->exists_original_dir( $this->media_type ) )
					{
						//이미 패스가 등록되어 있으므로 디렉터리 뒤에 "_ + 숫자 카운팅"으로 한다.
						$arr_dir_path = explode('_', $this->dir_path);
						$pre_dir = $arr_dir_path[0];
						$dir_count = (int)$arr_dir_path[1];

						$arr_dir_path = array();
						if(empty($dir_count))
						{
							$dir_count = 1;
						}
						else
						{
							$dir_count++;
						}
						array_push($arr_dir_path, $pre_dir);
						$dir_count = (string)$dir_count;
						array_push($arr_dir_path, $dir_count);

						$new_dir = implode('_', $arr_dir_path);
						$new_full_path = $new_dir.'/'.$this->filename.'.'.$this->ext;

						$this->setFullPath($new_full_path);

						if($i > 1000)
							break;

						$i++;
					}
				}

				$fullpath = $this->db->escape($this->full_path);

				// 미디어타입생성 없이 전송작업만 해야될때
				if ($this->media_type == 'out') {
					$this->media_id = 0;
				}else if( $this->media_type == 'proxy_out'  ){
					$storage_id = $this->rule_options['target_path'];
					if (empty($storage_id)) {
						$storage_id = 0;
					}
					$query = "insert into bc_media (content_id,media_id, media_type, storage_id, status, path, reg_type, created_date, expired_date)
						values ({$content_id},'{$this->media_id}', '{$this->media_type}', $storage_id, '0', '{$fullpath}', '{$this->channel}', '{$created_datetime}', '{$expired_date}')";
					$this->db->exec($query);
				}else if( strstr(strtolower($this->media_type),'pfr') ) {
                    //pfr타입은 이미 있더라도 무조건 insert해서 동시에 여러 파일이 관리될 수 있도록
                    //파일명은 in/out이 포함되어 워크플로우 시작 전에 param으로 넘어오는 경우가 있으므로 fullpath대신 target_path를 사용
                    $target_path = $this->target_path;
                    $storage_id = $this->rule_options['target_path'];
                    if (empty($storage_id)) {
                        $storage_id = 0;
                    }
                    $query = "insert into bc_media (content_id, media_type, storage_id, status, path, reg_type, created_date, expired_date)
                                values ({$content_id}, '{$this->media_type}', $storage_id, '0', '{$target_path}', '{$this->channel}', '{$created_datetime}', '{$expired_date}')";
                    $this->db->exec($query);

                    //넣고 나서 바로 구하고...음...동일한 미디어 타입이 있을때를 대비해서 시간까지 체크, 삭제안된놈으로...
                    $query = "select media_id from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' and created_date = '$created_datetime' and delete_date is null order by created_date desc";
                    $this->media_id = $this->db->queryOne($query);
                }else  {
					//타입이 이미 있을때

					$query = "select media_id from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' order by created_date desc";
					$check_media_id = $this->db->queryOne($query);

					if ( empty($check_media_id) ) {
						$storage_id = $this->rule_options['target_path'];
						if (empty($storage_id)) {
							$storage_id = 0;
						}
						$query = "insert into bc_media (content_id, media_type, storage_id, status, path, reg_type, created_date, expired_date)
								  values ({$content_id}, '{$this->media_type}', $storage_id, '0', '{$fullpath}', '{$this->channel}', '{$created_datetime}', '{$expired_date}')";
						$this->db->exec($query);

						//넣고 나서 바로 구하고...음...동일한 미디어 타입이 있을때를 대비해서 시간까지 체크, 삭제안된놈으로...
						$query = "select media_id from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' and created_date = '$created_datetime' and delete_date is null order by created_date desc";
						$this->media_id = $this->db->queryOne($query);
					}else {
                        $storage_id = $this->rule_options['target_path'];
						if (empty($storage_id)) {
							$storage_id = 0;
						}
                        $this->taskManager->_log('', __LINE__.':update ', "update bc_media set path='{$fullpath}' ,storage_id='{$storage_id}',status='0', reg_type='{$this->channel}', created_date='{$created_datetime}', expired_date='{$expired_date}' where media_id=$check_media_id " );
						$this->db->exec("update bc_media set path='{$fullpath}' , reg_type='{$this->channel}',storage_id='{$storage_id}',status='0',delete_date=null,flag=null,filesize=0, created_date='{$created_datetime}', expired_date='{$expired_date}' where media_id=$check_media_id ");
						$this->media_id =$check_media_id;
					}

					//echo '미디어 아이디 쿼리:'.$query.'<br/>';
					//echo '미디어 아이디:'.$this->media_id.'<br/>';
				}
			}
			else //미디어 타입이 없으면 원본의 미디어 아이디를 미디어 아이디로...
			{
                $this->media_id = $this->src_media_id;
				if(!$content_id)
				{
					return;
				}
			}
		}
		else
		{
			if(empty($this->full_path))
			{
				if(empty($this->media_id)) {
					$this->media_id = 'null';
				}
				$query = "select path from bc_media where media_id = {$this->media_id}";

				//echo '소스 쿼리 : '.$query.'<br/>';
				//echo '소스 쿼리(미디어 아이디) : '.$this->media_id.'<br/>';
				$this->full_path = $this->db->queryOne($query);
				//echo 'after:'.$this->full_path;
			}

			if ($this->media_type == 'xml') {
				$created_datetime = date('YmdHis');
				$content_info = $this->getContentInfo( $content_id );
				$ud_content_id = $content_info['ud_content_id'];
				//$expired_date = '99981231000000';
				$expired_date = check_media_expire_date($ud_content_id, $this->media_type, $created_datetime);
				$fullpath = $this->db->escape($this->full_path);

				//is exists check
				$query = "select media_id from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' and delete_date is null order by created_date desc";
				$check_media_id = $this->db->queryOne($query);

				if (empty($check_media_id)) {
					$storage_id = $this->rule_options['source_path'];
					if (empty($storage_id)) {
						$storage_id = 0;
					}
					$query = "insert into bc_media (content_id, media_type, storage_id, status, path, reg_type, created_date, expired_date)
							  values ({$content_id}, '{$this->media_type}', $storage_id, '0', '{$fullpath}', '{$this->channel}', '{$created_datetime}', '{$expired_date}')";
					$this->db->exec($query);

					//넣고 나서 바로 구하고...음...동일한 미디어 타입이 있을때를 대비해서 시간까지 체크, 삭제안된놈으로...
					$query = "select media_id from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' and created_date = '$created_datetime' and delete_date is null order by created_date desc";
					$this->media_id = $this->db->queryOne($query);
				} else {
					$this->db->exec("update bc_media set path='{$fullpath}' , reg_type='{$this->channel}', created_date='{$created_datetime}', expired_date='{$expired_date}' where media_id=$check_media_id ");
					$this->media_id =$check_media_id;
				}
			}
		}
	}

	//디렉터리 경로를 얻는다
	private function getDirPathFromOption($content_id, $dir_opt, $src_media_type='')
	{
		$dir_path = '';
		$arr_dir = explode('/', $dir_opt);
		for($i=0; $i<count($arr_dir); $i++)
		{
			$this->checkConstantValue($content_id, $arr_dir[$i], $src_media_type);
		}
		$dir_path = implode('/', $arr_dir);
		//echo 'dir_path : '.$dir_path.'<br/>';
		return $dir_path;
	}

	private function getFileNameFromOption($content_id, $file_opt, $src_media_type='')
	{
		$file_name = '';
		$arr_file_name = explode('/', $file_opt);
		for($i=0; $i<count($arr_file_name); $i++)
		{
			$this->checkConstantValue($content_id, $arr_file_name[$i], $src_media_type);
        }	
		$file_name = implode('/', $arr_file_name);
		//echo 'file_name : '.$file_name.'<br/>';
		return $file_name;
	}

	//옵션 상수를 확인한다.
	//옵션 상수에 해당되지 않으면 받은값 그대로 리턴
	private function checkConstantValue($content_id, &$value, $src_media_type=null)
	{
		$tokens = array(
					'$SRC_DIR', '$SRC_FILE','$ORI_FILE', '$ORI_DIR','$PROXY_DIR','$PROXY_FILE',
					'$ORI_DFILE','$SRC_DFILE',
					'$ORI_EXT', '$SRC_EXT', '$VR_ID', '$YYYY', '$MM', '$DD', '$HH', '$mm', '$SS',
					'$USER_ID', '$CNT', '$BD_DATE', '$CONTENT_ID', '$PARENT_CONTENT_ID', '$TITLE', '$UD_CONTENT_ID', '$UD_CONTENT_CODE' ,
					'$VR_START', '$VR_END' , '$MEDIA_ID' , '$UD_PATH', '$OUT_SRC_DIR', '$OUT_SRC_FILE', '$OUT_SRC_EXT',
					'$UD_PROG_PATH','$FILE_ID','$USR_MATERIALID','$USR_PROGID','$USR_TURN','$MTRL_ID','$ORD_TR_ID','$ORD_TR_DEL_ID',
                    '$HARRIS_XID',
                    '$PRE_SRC_DIR', '$PRE_SRC_FILE','PRE_SRC_EXT','$PRE_TRG_DIR', '$PRE_TRG_FILE','PRE_TRG_EXT',
                    '$CJO_SHOP_ID','$CJO_SL_ID','$CJO_HV_ID','$CJO_KTH_ID','$CJO_TB_ID','$CJO_TB_DIR','$CJO_TITLE',
                    '$KTV_ORI_FILENAME','$KTV_MIG_FILENAME','$KTV_PATH','$MEDIA_TYPE','$CREATE_YMD'
				);

		foreach($tokens as $token) {
			$arr_val = explode($token, $value);

			//토큰을 찾지 못하면 배열 첫번째 값이 원래 값과 같다...
			if($arr_val[0] == $value){
				continue;
			}

			//해당 토큰에 대한 값을 구하고
			switch($token)
			{
				case '$SRC_DIR' :	//소스의 디렉터리와 동일한 디렉터리 구조
					if(empty($content_id) || $content_id==''){
						$val = '';
						break;
					}
					$mediainfo = $this->getMediaInfo ( $content_id , $src_media_type , $this->src_media_id );
					$src_path = $mediainfo['path'];
					//소스 경로가 폴더/파일 구성이라고 생각하고 파일명 떼어낸다
					$val = implode('/', explode('/', $src_path, -1));


					//echo '소스 경로 : '.$val.'<br/>';
					break;
				case '$SRC_FILE':	//소스의 동일한 파일명
					if(empty($content_id) || $content_id=='')
					{
						$val = '';
						break;
					}
					$mediainfo = $this->getMediaInfo ( $content_id , $src_media_type , $this->src_media_id );
					$src_path = $mediainfo['path'];
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					$arr_val = explode('.', $val);
					array_pop($arr_val);
					$val = join('.' , $arr_val);
					//echo '소스 파일 : '.$val.'<br/>';
					break;
				case '$SRC_EXT':	//소스의 확장자
                    if (empty($content_id) || $content_id=='') {
                        $val = '';
                        break;
                    }                    
                    $mediainfo = $this->getMediaInfo ( $content_id , $src_media_type , $this->src_media_id );
					$src_path = $mediainfo['path'];
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					$arr_val = explode('.', $val);
					$val = array_pop($arr_val);
					break;
				case '$ORI_DIR':	//원본소스의 경로
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$mediainfo = $this->getMediaInfo ( $content_id , 'original' );
						$src_path = $mediainfo['path'];
						$val = implode('/', explode('/', $src_path, -1));
					}
					break;
				case '$PROXY_DIR':	//원본소스의 경로
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$mediainfo = $this->getMediaInfo ( $content_id , 'proxy' );
                        $src_path = $mediainfo['path'];
                        $pathArray = explode('/', $src_path, -1);
                        $lastPath = array_pop($pathArray);
                        if( $lastPath == 'Proxy'){
                            $val = implode('/', explode('/', $src_path, -2));//파일명과 프록시 폴더 제거
                        }else{
                            $val = implode('/', explode('/', $src_path, -1));//파일명과 프록시 폴더 제거
                        }
					}
				break;

				case '$PROXY_FILE':	//원본소스의 경로
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$mediainfo = $this->getMediaInfo ( $content_id , 'proxy' );
						$src_path = $mediainfo['path'];
						//PHP5.4에서 지원안됨. $filename = array_pop( explode('/', $src_path) ); //파일명추출
						$src_path_arr = explode('/', $src_path);
						$filename = array_pop( $src_path_arr ); //파일명추출
						$filename_array = explode('.', $filename); //파일명 배열로 변환
						$file_ext = array_pop($filename_array); //확장자 추출
						$val = join('.', $filename_array); //확장자 제거한 파일명 배열 변환
					}
				break;

				case '$ORI_FILE':	//원본소스의 경로
				if(empty($content_id) || $content_id==''){
					$val = '';
				}else{
					$mediainfo = $this->getMediaInfo ( $content_id , 'original' );
						$src_path = $mediainfo['path'];
					//PHP5.4에서 지원안됨. $filename = array_pop( explode('/', $src_path) ); //파일명추출
					$src_path_arr = explode('/', $src_path);
					$filename = array_pop( $src_path_arr ); //파일명추출
					$filename_array = explode('.', $filename); //파일명 배열로 변환
					$file_ext = array_pop($filename_array); //확장자 추출
					$val = join('.', $filename_array); //확장자 제거한 파일명 배열 변환
				}
				break;

				case '$ORI_EXT':	//원본소스의 파일 확장자
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$mediainfo = $this->getMediaInfo ( $content_id , 'original' );
						$src_path = $mediainfo['path'];
						$arr_src_path = explode('/', $src_path);
						$val = array_pop($arr_src_path);
						$arr_val = explode('.', $val);
						$val = array_pop($arr_val);
					}
					break;

				case '$YYYY' :		//현재시간의 년도 4자리
					$val = date('Y');
					break;
				case '$MM' :		//현재시간의 월 2자리
					$val = date('m');
					break;
				case '$DD' :		//현재시간의 일 2자리
					$val = date('d');
					break;
				case '$HH' :		//현재시간의 시간 2자리(24시간 체계)
					$val = date('H');
					break;
				case '$mm' :		//현재시간의 분 2자리
					$val = date('i');
					break;
				case '$SS' :		//현재시간의 초 2자리
					$val = date('s');
                    break;
                    
                case '$CREATE_YMD':
                    $row = $this->getContentInfo ( $content_id );
                    $toTime =  strtotime($row['created_date']);
                    if( $toTime ){
                        $val = date('Y/m/d', $toTime);
                    }else{
                        $val = date('Y/m/d');
                    }
                break;

				case '$CNT' : //미디어 개수만큼 카운팅한 숫자
					if(!empty($this->media_type)){
						//미디어 타입이 있으면...
						$query = "select count(*) from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' and delete_date is null";
					}else{
						//미디어 타입이 없으면...
						$query = "select count(*) from bc_media where content_id = {$content_id}";
					}
					$val = $this->db->queryOne($query);
					break;
				case '$CONTENT_ID':
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$val = $content_id;
					}
					break;
				case '$PARENT_CONTENT_ID':
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$content_info = $this->db->queryRow("select * from bc_content
							where content_id=".$content_id);
						if($content_info['parent_content_id'] != '') {
							$val = $content_info['parent_content_id'];
						} else {
							$val = $content_id;
						}
					}
					break;
				case '$TITLE':
					if(empty($content_id) || $content_id==''){
						$val = 'empty';
					}else{
						$row = $this->getContentInfo ( $content_id );
                        $title = $row['title'];
                        
                        $title = $this->getFilenameformat($title);

                        $title = utf8_strcut($title,50);
						$val = $title;
					}
                    break;
                case '$CJO_TITLE':
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$row = $this->db->queryRow("select * from bc_content where content_id=".$content_id);
						$title = $row['title'];
						$val = $title;
					}
					break;
				case '$USER_ID':
					if(empty($content_id) || $content_id==''){
						$val = '';
					}else{
						$row = $this->getContentInfo ( $content_id );
						$val = $row['reg_user_id'];
					}
					break;
				case '$UD_CONTENT_CODE':
						$contentInfo = $this->getContentInfo ( $content_id );
						$val = $contentInfo['ud_content_code'];
					break;

				case '$VR_START':
						$val = $this->vr_start;
					break;
				case '$VR_END':
						$val = $this->vr_end;
					break;
				case '$MEDIA_ID':
					if( empty($this->media_id) ){
							$query = "select media_id from bc_media where content_id = {$content_id} and media_type = '{$this->media_type}' and delete_date is null order by media_id desc";
							$row = $this->db->queryRow($query);
							$val = $row['media_id'];
					}else{
						$val = $this->media_id;
					}
					break;
				case '$UD_PATH':
					//NPS에서 카테고리 매핑에 따라 패스 설정
						$row = $this->getContentInfo ( $content_id );
						$val = $this->getCategoryDirPath($row, $row['category_id'], true );
					break;

				case '$UD_PROG_PATH':
					//프로그램 폴더에서 다른 프로그램 폴더로 바로 이동할수 없기에 거치는 중간패스
						$row = $this->getContentInfo ( $content_id );
						$val = $this->getCategoryDirPath($row, $row['category_id'] , true );

					break;
				case '$OUT_SRC_DIR':
						$val = $this->source_dir_path;
					break;
				case '$OUT_SRC_FILE':
						$val = $this->source_filename;
					break;
				case '$OUT_SRC_EXT':
						$val = $this->source_ext;
					break;
				case '$FILE_ID':
					$val = buildFileID();
					break;
				//ZODIAC 송출
				case '$ORD_TR_ID':
					$query = "
						SELECT	ORD_TR_ID
						FROM		TB_ORD_TRANSMISSION
						WHERE	CONTENT_ID = ".$content_id."
							AND	TASK_ID IS NULL
					";
					$val = $this->db->queryOne($query);
				break;
				case '$ORD_TR_DEL_ID':
					$query = "
						SELECT	ORD_TR_ID
						FROM		TB_ORD_TRANSMISSION
						WHERE	CONTENT_ID = ".$content_id."
							AND	DELETE_TASK_ID IS NULL
					";
					$val = $this->db->queryOne($query);
				break;
				case '$ORI_DFILE':	//When original source is folder
					if(empty($content_id) || $content_id=='')
					{
						$val = '';
						break;
					}
					$query = "select path from bc_media where content_id = $content_id and media_type = 'original' and delete_date is null  order by media_id desc";
					$src_path = $this->db->queryOne($query);
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					break;
				case '$SRC_DFILE':	//When source is folder
					if(empty($content_id) || $content_id=='')
					{
						$val = '';
						break;
					}
					$query = "select path from bc_media where content_id = $content_id and media_type = '".$src_media_type."' and delete_date is null  order by media_id desc";
					$src_path = $this->db->queryOne($query);
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					break;
				case '$HARRIS_XID':	
					if(empty($content_id) || $content_id=='')
					{
						$val = '';
						break;
					}
					$query = "SELECT XID FROM HARRIS_XID_MAP WHERE CONTENT_ID=".$content_id;
					$val = $this->db->queryOne($query);
					if($val == '') {
						$val = 'GP'.date('Ymd').'_C'.$content_id;//GP20160829_C43483
						$this->db->exec("INSERT INTO HARRIS_XID_MAP (XID, CONTENT_ID) VALUES ('".$val."',".$content_id.")");
					}

                    break;
                case '$PRE_SRC_DIR' :	//이전작업 source의 dir
                    $pre_task_id = $this->pre_task_id;
					if(empty($content_id) || empty($pre_task_id)){ 
                        $val = '';
                        break;
                    }
					$pre_task = $this->db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$pre_task_id);
					$src_path = $pre_task['source'];
					//소스 경로가 폴더/파일 구성이라고 생각하고 파일명 떼어낸다
					$val = implode('/', explode('/', $src_path, -1));
					break;
                case '$PRE_SRC_FILE':	//이전작업 source의 file
                    $pre_task_id = $this->pre_task_id;
                    if(empty($content_id) || empty($pre_task_id)){ 
                        $val = '';
                        break;
                    }
                    $pre_task = $this->db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$pre_task_id);
					$src_path = $pre_task['source'];
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					$arr_val = explode('.', $val);
					array_pop($arr_val);
					$val = join('.' , $arr_val);
					break;
                case '$PRE_SRC_EXT':	//이전작업 source의 ext
                    $pre_task_id = $this->pre_task_id;
                    if(empty($content_id) || empty($pre_task_id)){ 
                        $val = '';
                        break;
                    }
					$pre_task = $this->db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$pre_task_id);
					$src_path = $pre_task['source'];
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					$arr_val = explode('.', $val);
					$val = array_pop($arr_val);
                    break;
                case '$PRE_TRG_DIR' :	//이전작업 target의 dir
                    $pre_task_id = $this->pre_task_id;
					if(empty($content_id) || empty($pre_task_id)){ 
                        $val = '';
                        break;
                    }
					$pre_task = $this->db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$pre_task_id);
					$src_path = $pre_task['target'];
					//소스 경로가 폴더/파일 구성이라고 생각하고 파일명 떼어낸다
					$val = implode('/', explode('/', $src_path, -1));
					break;
                case '$PRE_TRG_FILE':	//이전작업 target의 file
                    $pre_task_id = $this->pre_task_id;
                    if(empty($content_id) || empty($pre_task_id)){ 
                        $val = '';
                        break;
                    }
                    $pre_task = $this->db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$pre_task_id);
					$src_path = $pre_task['target'];
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					$arr_val = explode('.', $val);
					array_pop($arr_val);
					$val = join('.' , $arr_val);
					break;
                case '$PRE_TRG_EXT':	//이전작업 target의 ext
                    $pre_task_id = $this->pre_task_id;
                    if(empty($content_id) || empty($pre_task_id)){ 
                        $val = '';
                        break;
                    }
					$pre_task = $this->db->queryRow("SELECT * FROM BC_TASK WHERE TASK_ID=".$pre_task_id);
					$src_path = $pre_task['target'];
					$arr_src_path = explode('/', $src_path);
					$val = array_pop($arr_src_path);
					$arr_val = explode('.', $val);
					$val = array_pop($arr_val);
                    break;
                case '$CJO_SHOP_ID':	//CJO, Shop전송시 사용하는 ID
                    if(empty($content_id)){ 
                        $val = '';
                        break;
                    }
					$get_id = $this->db->queryOne("SELECT CJMALL_ID FROM CJMALL_ID_MAPPING WHERE CONTENT_ID=".$content_id." ORDER BY CJMALL_ID DESC");
					$val = $get_id;
                    break;
                case '$CJO_SL_ID':	//CJO, Skylife전송시 사용하는 ID
                    if(empty($content_id)){ 
                        $val = '';
                        break;
                    }
                    $get_id = $this->db->queryOne("SELECT TCOM_ID FROM TCOM_ID_MAPPING WHERE CONTENT_ID=".$content_id." AND TCOM_CH_CD = '1' ORDER BY TCOM_ID DESC");
					$val = $get_id."V";
                    break;
                case '$CJO_HV_ID':	//CJO, Hellovision전송시 사용하는 ID
                    if(empty($content_id)){ 
                        $val = '';
                        break;
                    }
                    $get_id = $this->db->queryOne("SELECT TCOM_ID FROM TCOM_ID_MAPPING WHERE CONTENT_ID=".$content_id." AND TCOM_CH_CD = '2' ORDER BY TCOM_ID DESC");
					$val = $get_id;
                    break;
                case '$CJO_KTH_ID':	//CJO, KTH전송시 사용하는 ID
                    if(empty($content_id)){ 
                        $val = '';
                        break;
                    }
                    $get_id = $this->db->queryOne("SELECT TCOM_ID FROM TCOM_ID_MAPPING WHERE CONTENT_ID=".$content_id." AND TCOM_CH_CD = '3' ORDER BY TCOM_ID DESC");
					$val = $get_id;
                    break;
                case '$CJO_TB_ID':	//CJO, T-Broad전송시 사용하는 ID
                    if(empty($content_id)){ 
                        $val = '';
                        break;
                    }
                    $get_id = $this->db->queryOne("SELECT LEFT(TCOM_ID, 8) AS TCOM_ID FROM TCOM_ID_MAPPING WHERE CONTENT_ID=".$content_id." AND TCOM_CH_CD = '4' ORDER BY TCOM_ID DESC");
					$val = $get_id;
					break;
				case '$CJO_TB_DIR':	//CJO, T-Broad전송시 사용하는 카테고리 경로
                    if(empty($content_id)){ 
                        $val = '';
                        break;
                    }
                    $get_id = $this->db->queryOne("SELECT TCOM_ID FROM TCOM_ID_MAPPING WHERE CONTENT_ID=".$content_id." AND TCOM_CH_CD = '4' ORDER BY TCOM_ID DESC");
					$val = $get_id;
                    break;
                    
                case '$KTV_ORI_FILENAME':
                    $contentInfo = $this->getContentInfo ( $content_id , false );
                    $metaInfo = $this->getMetadataInfo($content_id);
                    $mediainfo = $this->getMediaInfo ( $content_id , $src_media_type , $this->src_media_id );
                    $src_path = $mediainfo['path'];
                    $arr_src_path = explode('/', $src_path);
                    $arr_src_path_last = array_pop($arr_src_path);
                    $arr_val = explode('.', $arr_src_path_last);
                    $ext = array_pop($arr_val);

                    $val = ContentService::getFileName($metaInfo['media_id'],$contentInfo['ud_content_id'] , $metaInfo);
                break;

                case '$KTV_MIG_FILENAME':
                    $contentInfo = $this->getContentInfo ( $content_id , false );
                    $metaInfo = $this->getMetadataInfo($content_id);
                    $mediainfo = $this->getMediaInfo ( $content_id , $src_media_type , $this->src_media_id );
                    $src_path = $mediainfo['path'];
                    $arr_src_path = explode('/', $src_path);
                    $arr_src_path_last = array_pop($arr_src_path);
                    $arr_val = explode('.', $arr_src_path_last);
                    //$ext = array_pop($arr_val);
                    $cid = 'R';
                    $archiveId = $metaInfo['media_id'];
                    $newArchiveId = substr($archiveId, 0,8).$cid.substr($archiveId, 9,5);

                    $val = ContentService::getFileName($newArchiveId,$contentInfo['ud_content_id'] , $metaInfo);
                break;

                case '$MEDIA_TYPE':
                    $val = strtolower($this->media_type);
                break;

                case '$KTV_PATH':
                        $row = $this->getContentInfo( $content_id , false );                        
                        $val = ContentService::getFolderPath($row['category_id']);
                    break;
				default :
				break;
			}
			//하나 끝날때마다 리플레이스
			$value = str_replace($token, $val, $value);
		}
	}


	//카테고리와 매핑된 디렉터리 경로를 얻는다
	public function getCategoryDirPath( $content , $category_id , $is_prog = false )
	{
		$returnPath = '';

//아래 방식은 사용안함 2015-11-24 in Zodiac

//		$category = $this->db->queryRow("select * from bc_category where category_id='$category_id'");
//
//		$is_AD_storage_group_path = false;
//
//		//스토리지그룹이 AD일때 프로그램패스 / ingest / 부제 이런식 2013-02-05 이성용
//		//define('UD_INGEST', 4000282 );//사용자 정의 - 인제스트
//		//define('UD_DASDOWN', 4000284 );//사용자 정의 - DAS다운로드
//		//define('UD_FINALEDIT', 4000345 );//사용자 정의 - 편집마스터
//		//define('UD_FINALBROD', 4000346 );//사용자 정의 - 방송마스터
//
//
//		$parent_id = $category['parent_id'];
//
//		if( $parent_id == '0' ) //제작프로그램
//		{
//			$info =  $this->db->queryRow("select c.*, pm.path from path_mapping pm, bc_category c where pm.category_id=c.category_id and c.category_id='$category_id'");
//
//			if( empty($info) ) return 'tmp';
//
//			$returnPath = $info['path'];
//
//		}
//		else//부제일때?
//		{
//			$sub_path = $category['category_title'];//부제패스 명명
//
//			$info =  $this->db->queryRow("select c.*, pm.path from path_mapping pm, bc_category c where pm.category_id=c.category_id and c.category_id='$parent_id'");
//
//			$prog_path = $info['path'];
//
//			//부제카테고리여도 프로그램패스를 얻어야할때
//			if($is_prog){
//
//
//				//프로그램패스
//				$returnPath = $prog_path;
//
//			}else{
//
//				//$subinfo =  $this->db->queryRow("select c.*, sb.subprogcd, sb.progcd from subprog_mapping sb, bc_category c where pm.category_id=c.category_id and c.category_id='$category_id'");
//
//				if( empty($info) ) return '';
//
//				//프로그램패스
//				$returnPath = $prog_path;
//
//				if($is_AD_storage_group_path){
//					$returnPath = $returnPath.'/'.$is_AD_storage_group_path.'/'.$sub_path;
//				}else{
//					$returnPath = $returnPath.'/'.$sub_path;
//				}
//			}
//		}

		if(!empty($content['ud_content_code'])){
			//$returnPath = $returnPath.'/'.$content['ud_content_code'];
			$returnPath = $content['ud_content_code'];
		}

		return $returnPath;
	}

	private function getBeforeTaskInfo( $root_task_id, $get_jobs ){
		$workflow_rule_parent_id = $get_jobs['workflow_rule_parent_id'];
		if( empty($root_task_id)  ||  empty($workflow_rule_parent_id) ) return false;

		$bf_workflow_rule = $this->db->queryRow("select * from BC_TASK_WORKFLOW_RULE wr where workflow_rule_id=$workflow_rule_parent_id ");
		$bf_workflow_rule_id = $bf_workflow_rule['workflow_rule_id'];
		if( empty($bf_workflow_rule_id) ) return false;

		$bc_task_info = $this->db->queryRow("select * from bc_task where root_task=$root_task_id and WORKFLOW_RULE_ID=$bf_workflow_rule_id ");

		return $bc_task_info;
    }
    
    /**
     * 파일명용 문자열 필터링
     */
    public function getFilenameformat($value){
            //아래 패턴만 허용
            $p_array = array(		
            '[\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]',//한글
            '[a-zA-Z]',//영문
            '[0-9]',//숫자
            '[()_-]'//그외 허용할 특수문자
        );
        //공백 _ 치환
        $value = preg_replace("/[\s]/i", "_", $value );

        $pattern = '/'.join('|', $p_array).'+/u';
        preg_match_all($pattern, $value, $match);
        $return = implode('', $match[0]);	

        if( is_null($return) ){
            $return = 'empty';
        }
        return $return;
    }
}
