<?php

function findCategoryRoot($meta_table_id , $category_full_path = null)  //카테고리 콘텐츠 유형별로 루트설정을 위한 함수 2011-02-25 by 이성용
{
	global $dbDas;

	$roots = $dbDas->queryAll("
	select c.id , c.title
	from categories c, category_mapping cm
	where c.id=cm.category_id
	and cm.meta_table_id='$meta_table_id'");

	if (PEAR::isError($roots) || empty($roots) )
	{
		return '0';
	}

	if( count($roots) > 1 )
	{
		foreach($roots as $row)
		{
			if( !empty($category_full_path) && strstr( $category_full_path, $row['id']) )
			{
				$result = $row;
			}
		}
	}
	else if(count($roots) == 1)
	{
		$result = $roots[0];
	}
	else
	{
		return '0';
	}

	return $result;
}

function getCategoryList( $parent_id )
{
	global $dbDas;

	if( empty( $parent_id ) )
	{
		return array();
	}

	$childen = $dbDas->queryAll("select * from CATEGORIES where PARENT_ID='$parent_id' order by sort ");

	if( MDB2::isError($childen) || empty($childen) )
	{
		return array();
	}
	else
	{
		$data = array();
		foreach($childen as $child)
		{
			array_push($data ,
				array(
					$child['id'],
					$child['title']
				)
			);
		}
	}

	return $data;
}

function getCategoryListText( $root_category_id, $category_id )
{
	$categoryList1 ='';
	$categoryList2 ='';
	$categoryList3 ='';
	$category_path_array = getCategoryFullPathInfo($category_id);
	$categoryListArray =  getCategoryList( $root_category_id );

	foreach($categoryListArray as $key => $list)
	{
		if( ( count($categoryListArray)- 1 ) == $key)
		{
			$categoryList1 .= '['.$list[0].",'".$list[1]."'".']';
		}
		else
		{
			$categoryList1 .= '['.$list[0].",'".$list[1]."'".'],';
		}
	}

	if(!empty($category_path_array))
	{
		$category_path = array_pop($category_path_array);
		$category_path1 = $category_path['id'];
		$category_pathName1  = $category_path['title'];

		$categoryListArray =  getCategoryList( $category_path1 );
		$categoryList2 ='';

		foreach($categoryListArray as $key => $list)
		{
			if( ( count($categoryListArray)- 1 ) == $key)
			{
				$categoryList2 .= '['.$list[0].",'".$list[1]."'".']';
			}
			else
			{
				$categoryList2 .= '['.$list[0].",'".$list[1]."'".'],';
			}
		}

	}

	if(!empty($category_path_array))
	{
		$category_path = array_pop($category_path_array);
		$category_path2 = $category_path['id'];
		$category_pathName2  = $category_path['title'];

		$categoryListArray =  getCategoryList( $category_path2 );
		$categoryList3 ='';

		foreach($categoryListArray as $key => $list)
		{
			if( ( count($categoryListArray) - 1 ) == $key)
			{
				$categoryList3 .= '['.$list[0].",'".$list[1]."'".']';
			}
			else
			{
				$categoryList3 .= '['.$list[0].",'".$list[1]."'".'],';
			}
		}

	}

	if(!empty($category_path_array))
	{
		$category_path = array_pop($category_path_array);
		$category_path3 = $category_path['id'];
		$category_pathName3  = $category_path['title'];
	}

	$categoryList1 = '['.$categoryList1.']';
	$categoryList2 = '['.$categoryList2.']';
	$categoryList3 = '['.$categoryList3.']';

	$result = array();

	array_push($result, array(
		'id' => $category_path1,
		'list' => $categoryList1
	));

	array_push($result, array(
		'id' => $category_path2,
		'list' => $categoryList2
	));

	array_push($result, array(
		'id' => $category_path3,
		'list' => $categoryList3
	));

	return $result;
}

function getCategoryPathDas($id){
	global $dbDas;

	$parent_id = $dbDas->queryOne("select parent_id from categories where id=".$id);
	if(!empty($parent_id)){
		$r = getCategoryPathDas($parent_id);
	}

	return $r.'/'.$parent_id;
}


function getListViewDataFields($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		$result[] = "{name: 'column".chr($asciiA++)."'}";
	}
	$result[] = "{name: 'meta_value_id'}";

	return join(",\n", $result);
}

function getListViewColumns2($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		$result[] = "column".chr($asciiA++)."";
	}
	

	return $result;
}

function getListViewColumns($columns, $meta_field_id) //소재영상쪽 컬럼 히든처리를 위해 필드아이디 포함
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		if($meta_field_id == '11879136' )
		{
			if( $v == '순번' )
			{
				$result[] = "{width: .1, header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
			}
			else if( $v =='Start TC' || $v == 'End TC' )
			{
				$result[] = "{width: .15, header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
			}
			else if( $v == '내용' )
			{
				$result[] = "{header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
			}
			else
			{
				$asciiA++;
			}
		}
		else
		{
			$result[] = "{header: '$v', dataIndex: 'column".chr($asciiA++)."'}";
		}
	}

//	$result[] = "{header: 'meta_value_id', dataIndex: 'meta_value_id', hidden: true }";

	return join(",\n", $result);
}

function getListViewForms($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	$columnCount = count($columns);

	foreach ($columns as $v)
	{
		if($v=='내용')
		{
			$result[] = "{xtype:'textarea', fieldLabel: '$v',width:400 , name: 'column".chr($asciiA++)."'}";
		}
		else
		{
			$result[] = "{fieldLabel: '$v',width:400 , name: 'column".chr($asciiA++)."'}";
		}
	}

	return array(
		'columnHeight' => ($columnCount * 45 + 20),
		'columns' => join(",\n", $result)
	);
}


?>