<?php
// 公共函数文件

//定义常量
define('CMS_ADMIN', '/fladmin/');  // 后台模块，首字母最好大写

function dataList($modelname, $map = '', $orderby = '', $field = '*', $listRows = 15)
{
	return db($modelname)->where($map)->field($field)->order($orderby)->limit($listRows)->select();
}

//pc前台栏目、标签、内容页面地址生成
function get_front_url($param='')
{
	$url = '';
	
    if($param['type'] == 'list')
    {
        //列表页
        $url .= '/cat'.$param['catid'];
    }
    else if($param['type'] == 'content')
    {
        //内容页
        $url .= '/p/'.$param['id'];
    }
    else if($param['type'] == 'tags')
    {
        //tags页面
        $url .= '/tag'.$param['tagid'];
    }
    else if($param['type'] == 'page')
    {
        //单页面
        $url .= '/page/'.$param['pagename'].'.html';
    }
    
    return $url;
}

//wap前台栏目、标签、内容页面地址生成
function murl(array $param)
{
    $url = '';
    
    if($param['type'] == 'list')
    {
        //列表页
        $url .= '/cat'.$param['catid'];
    }
    else if($param['type'] == 'content')
    {
        //内容页
        $url .= '/p/'.$param['id'];
    }
    else if($param['type'] == 'tags')
    {
        //tags页面
        $url .= '/tag'.$param['tagid'];
    }
    else if($param['type'] == 'page')
    {
        //单页面
        $url .= '/'.$param['pagename'].'.html';
    }
    
    return $url;
}

/**
 * 获取文章列表
 * @param int $tuijian=0 推荐等级
 * @param int $typeid=0 分类
 * @param int $image=1 是否存在图片
 * @param int $row=10 需要返回的数量
 * @param string $orderby='id desc' 排序，默认id降序，随机rand()
 * @param string $limit='0,10' 如果存在$row，$limit就无效
 * @return string
 */
function arclist(array $param)
{
    $map=array();
    $Artlist = '';
    
    if(isset($param['where'])){$map=$param['where'];}
	if(isset($param['tuijian'])){$map['tuijian']=$param['tuijian'];}
	if(isset($param['typeid'])){$map['typeid']=$param['typeid'];}
	if(isset($param['image'])){$map['litpic']=array('NEQ','');}
	if(isset($param['limit'])){$limit=$param['limit'];}else{if(isset($param['row'])){$limit="0,".$param['row'];}else{$limit='0,'.CMS_PAGESIZE;}}
	if(isset($param['orderby'])){$orderby=$param['orderby'];}else{$orderby='id desc';}
	$article = db("article");
    if(isset($param['field'])){$article = $article->field($param['field']);}else{$article = $article->field('body',true);}
    $article = $article->limit($limit);
    
	if(isset($param['sql']))
	{
		$Artlist = $article->where($param['sql'])->order($orderby)->select();
	}
	else
	{
        $Artlist = $article->where($map)->order($orderby)->select();
	}
    
    if($Artlist==''){$Artlist = $article->order("rand()")->select();}
	
	return $Artlist;
}

/**
 * 获取tag标签列表
 * @param int $row=10 需要返回的数量，如果存在$limit,$row就无效
 * @param string $orderby='id desc' 排序，默认id降序，随机rand()
 * @param string $limit='0,10'
 * @return string
 */
function tagslist($param="")
{
    $orderby=$limit="";
	if(isset($param['limit'])){$limit=$param['limit'];}else{if(isset($param['row'])){$limit=$param['row'];}}
	if(isset($param['orderby'])){$orderby=$param['orderby'];}else{$orderby='id desc';}
	
	return db("tagindex")->field('content',true)->order($orderby)->select();
}

/**
 * 获取友情链接
 * @param string $orderby='id desc' 排序，默认id降序，随机rand()
 * @param int||string $limit='0,10'
 * @return string
 */
function flinklist($param="")
{
	if(isset($param['row'])){$limit=$param['row'];}else{$limit="";}
	if(isset($param['orderby'])){$orderby=$param['orderby'];}else{$orderby='id desc';}
	
	return db("friendlink")->order($orderby)->limit($limit)->select();
}

/**
 * 获取文章上一篇，下一篇id
 * @param $param['aid'] 当前文章id
 * @param $param['typeid'] 当前文章typeid
 * @param string $type 获取类型
 *       pre:上一篇 next:下一篇
 * @return array
 */
function get_article_prenext(array $param)
{
    $sql = $typeid = $res = '';
    $sql='id='.$param["aid"];
    
    if(!empty($param["typeid"]))
    {
        $typeid = $param["typeid"];
    }
    else
    {
        $Article = db("article")->field('typeid')->where($sql)->find();
        $typeid = $Article["typeid"];
    }
    
    if($param["type"]=='pre')
    {
        $sql='id<'.$param['aid'].' and typeid='.$typeid;
        $res = db("article")->field('id,typeid,title')->where($sql)->order('id desc')->find();
    }
    else if($param["type"]=='next')
    {
        $sql='id>'.$param['aid'].' and typeid='.$typeid;
        $res = db("article")->field('id,typeid,title')->where($sql)->order('id asc')->find();
    }
    
    return $res;
}

/**
 * 获取列表分页
 * @param $param['pagenow'] 当前第几页
 * @param $param['counts'] 总条数
 * @param $param['pagesize'] 每页显示数量
 * @param $param['catid'] 栏目id
 * @param $param['offset'] 偏移量
 * @return array
 */
function get_listnav(array $param)
{
	$catid=$param["catid"];
	$pagenow=$param["pagenow"];
	$prepage = $nextpage = '';
    $prepagenum = $pagenow-1;
    $nextpagenum = $pagenow+1;
	
	$counts=$param["counts"];
	$totalpage=get_totalpage(array("counts"=>$counts,"pagesize"=>$param["pagesize"]));
	
	if($totalpage<=1 && $counts>0)
	{
		return "<li><span class=\"pageinfo\">共1页/".$counts."条记录</span></li>";
	}
	if($counts == 0)
	{
		return "<li><span class=\"pageinfo\">共0页/".$counts."条记录</span></li>";
	}
	$maininfo = "<li><span class=\"pageinfo\">共".$totalpage."页".$counts."条</span></li>";
    
	if(!empty($param["urltype"]))
    {
        $urltype = $param["urltype"];
    }
	else
	{
		$urltype = 'cat';
	}
	
	//获得上一页和下一页的链接
	if($pagenow != 1)
	{
		if($pagenow == 2)
		{
			$prepage.="<li><a href='/".$urltype.$catid."'>上一页</a></li>";
		}
		else
		{
			$prepage.="<li><a href='/".$urltype.$catid."/$prepagenum'>上一页</a></li>";
		}
		
		$indexpage="<li><a href='/".$urltype.$catid."'>首页</a></li>";
	}
	else
	{
		$indexpage="<li>首页</li>";
	}
	if($pagenow!=$totalpage && $totalpage>1)
	{
		$nextpage.="<li><a href='/".$urltype.$catid."/$nextpagenum'>下一页</a></li>";
		$endpage="<li><a href='/".$urltype.$catid."/$totalpage'>末页</a></li>";
	}
	else
	{
		$endpage="<li><a>末页</a></li>";
	}
	
	//获得数字链接
	$listdd="";
	if(!empty($param["offset"])){$offset=$param["offset"];}else{$offset=2;}
	
	$minnum=$pagenow-$offset;
	$maxnum=$pagenow+$offset;
	
	if($minnum<1){$minnum=1;}
	if($maxnum>$totalpage){$maxnum=$totalpage;}
	
	for($minnum;$minnum<=$maxnum;$minnum++)
	{
		if($minnum==$pagenow)
		{
			$listdd.= "<li class=\"thisclass\">$minnum</li>";
		}
		else
		{
			if($minnum==1)
			{
				$listdd.="<li><a href='/".$urltype.$catid."'>$minnum</a></li>";
			}
			else
			{
				$listdd.="<li><a href='/".$urltype.$catid."/$minnum'>$minnum</a></li>";
			}
		}
	}
    
    $plist = '';
	$plist .= $indexpage; //首页链接
	$plist .= $prepage; //上一页链接
	$plist .= $listdd; //数字链接
	$plist .= $nextpage; //下一页链接
	$plist .= $endpage; //末页链接
	$plist .= $maininfo;
	
	return $plist;
}

/**
 * 获取列表上一页、下一页
 * @param $param['pagenow'] 当前第几页
 * @param $param['counts'] 总条数
 * @param $param['pagesize'] 每页显示数量
 * @param $param['catid'] 栏目id
 * @return array
 */
function get_prenext(array $param)
{
	$counts=$param['counts'];
	$pagenow=$param["pagenow"];
	$prepage = $nextpage = '';
	$prepagenum = $pagenow-1;
    $nextpagenum = $pagenow+1;
	$cat=$param['catid'];
    
	if(!empty($param["urltype"]))
    {
        $urltype = $param["urltype"];
    }
	else
	{
		$urltype = 'cat';
	}
    
	$totalpage=get_totalpage(array("counts"=>$counts,"pagesize"=>$param["pagesize"]));
	
	//获取上一页
	if($pagenow == 1)
	{
		
	}
	elseif($pagenow==2)
	{
		$prepage='<a class="prep" href="/'.$urltype.$cat.'.html">上一页</a> &nbsp; ';
	}
	else
	{
		$prepage='<a class="prep" href="/'.$urltype.$cat.'/'.$prepagenum.'.html">上一页</a> &nbsp; ';
	}
	
	//获取下一页
	if($pagenow<$totalpage && $totalpage>1)
	{
		$nextpage='<a class="nextp" href="/'.$urltype.$cat.'/'.$nextpagenum.'.html">下一页</a>';
	}
	
	$plist = '';
	$plist .= $indexpage; //首页链接
	$plist .= $prepage; //上一页链接
	$plist .= $nextpage; //下一页链接
	
	return $plist;
}
/**
 * 获取分页列表
 * @access    public
 * @param     string  $list_len  列表宽度
 * @param     string  $list_len  列表样式
 * @return    string
 */
function pagenav(array $param)
{
    $prepage = $nextpage = '';
    $prepagenum = $param["pagenow"]-1;
    $nextpagenum = $param["pagenow"]+1;
    
	if(!empty($param['tuijian'])){$map['tuijian']=$param['tuijian'];}
	if(!empty($param['typeid'])){$map['typeid']=$param['typeid'];}
	if(!empty($param['image'])){$map['litpic']=array('NEQ','');}
	if(!empty($param['row'])){$limit="0,".$param['row'];}else{if(!empty($param['limit'])){$limit=$param['limit'];}else{$limit='0,8';}}
	if(!empty($param['orderby'])){$orderby=$param['orderby'];}else{$orderby='id desc';}
	
    return db("article")->field('body',true)->where($map)->order($orderby)->limit($limit)->select();
}

//根据总数与每页条数，获取总页数
function get_totalpage(array $param)
{
	if(!empty($param['pagesize'] || $param['pagesize']==0)){$pagesize=$param["pagesize"];}else{$pagesize=CMS_PAGESIZE;}
	$counts=$param["counts"];
	
	//取总数据量除以每页数的余数
    if($counts % $pagesize)
	{
		$totalpage = intval($counts/$pagesize) + 1; //如果有余数，则页数等于总数据量除以每页数的结果取整再加一,如果没有余数，则页数等于总数据量除以每页数的结果
	}
	else
	{
		$totalpage = $counts/$pagesize;
	}
	
	return $totalpage;
}

/**
 * 获得当前的页面文件的url
 * @access public
 * @return string
 */
function GetCurUrl()
{
    if(!empty($_SERVER['REQUEST_URI']))
    {
        $nowurl = $_SERVER['REQUEST_URI'];
        $nowurls = explode('?', $nowurl);
        $nowurl = $nowurls[0];
    }
    else
    {
        $nowurl = $_SERVER['PHP_SELF'];
    }
    return $nowurl;
}

/**
 * 获取单页列表
 * @param int $row=8 需要返回的数量
 * @param string $orderby='id desc' 排序，默认id降序，随机rand()
 * @param string $limit='0,8' 如果存在$row，$limit就无效
 * @return string
 */
function pagelist($param="")
{
	if(!empty($param['row'])){$limit="0,".$param['row'];}else{if(!empty($param['limit'])){$limit=$param['limit'];}else{$limit='0,8';}}
	if(!empty($param['orderby'])){$orderby=$param['orderby'];}else{$orderby='id desc';}
	
    return db("page")->field('body',true)->order($orderby)->limit($limit)->select();
}

/**
 * 截取中文字符串
 * @param string $string 中文字符串
 * @param int $sublen 截取长度
 * @param int $start 开始长度 默认0
 * @param string $code 编码方式 默认UTF-8
 * @param string $omitted 末尾省略符 默认...
 * @return string
 */
function cut_str($string, $sublen=250, $omitted = '', $start=0, $code='UTF-8')
{
	$string = strip_tags($string);
	$string = str_replace("　","",$string);
	$string = mb_strcut($string,$start,$sublen,$code);
	$string.= $omitted;
	return $string;
}

//PhpAnalysis获取中文分词
function get_keywords($keyword)
{
	Vendor('phpAnalysis.phpAnalysis');
	//import("Vendor.phpAnalysis.phpAnalysis");
	//初始化类
	PhpAnalysis::$loadInit = false;
    $pa = new PhpAnalysis('utf-8', 'utf-8', false);
	//载入词典
	$pa->LoadDict();
	//执行分词
    $pa->SetSource($keyword);
    $pa->StartAnalysis( false );
    $keywords = $pa->GetFinallyResult(',');
	
    return ltrim($keywords, ",");
}

//获取二维码
function get_erweima($url,$size=6)
{
	Vendor('phpqrcode.qrlib');
	ob_end_clean();
	return 'data:image/png;base64,'.base64_encode(\QRcode::png($url, false, "H", $size));
}

//根据栏目id获取栏目信息
function typeinfo($typeid)
{
    return db("arctype")->where("id=$typeid")->find();
}

//根据栏目id获取该栏目下文章/商品的数量
function catarcnum($typeid,$modelname='article')
{
    $map['typeid']=$typeid;
    return db($modelname)->where($map)->count('id');
}

//根据Tag id获取该Tag标签下文章的数量
function tagarcnum($tagid)
{
    if(!empty($tagid)){$map['tid']=$tagid;}
    return db("taglist")->where($map)->count();
}

//判断是否是图片格式，是返回true
function imgmatch($url)
{
    $info = pathinfo($url);
    if (isset($info['extension']))
    {
        if (($info['extension'] == 'jpg') || ($info['extension'] == 'jpeg') || ($info['extension'] == 'gif') || ($info['extension'] == 'png'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}

//将栏目列表生成数组
function get_category($modelname,$parent_id=0,$pad=0)
{
    $arr=array();
    
    $cats = db($modelname)->where("parent_id=$parent_id")->order('id asc')->select();
    
    if($cats)
    {
        foreach($cats as $row)//循环数组
        {
            $row['deep'] = $pad;
            if($child = get_category($modelname,$row["id"],$pad+1))//如果子级不为空
            {
                $row['child'] = $child;
            }
            $arr[] = $row;
        }
        
        return $arr;
    }
}

function tree($list,$parent_id=0)
{
    global $temp;
    if(!empty($list))
    {
        foreach($list as $v)
        {
            $temp[] = array("id"=>$v['id'],"deep"=>$v['deep'],"name"=>$v['name'],"parent_id"=>$v['parent_id'],"typedir"=>$v['typedir'],"addtime"=>$v['addtime']);
            //echo $v['id'];
            if(array_key_exists("child",$v))
            {
                tree($v['child'],$v['parent_id']);
            }
        }
    }
    
    return $temp;
}

//递归获取面包屑导航
function get_cat_path($cat)
{
    global $temp;
    
    $row = db("arctype")->field('name,parent_id,id')->where("id=$cat")->find();
    
    $temp = '<a href="'.get_front_url(array("type"=>"list","catid"=>$row["id"])).'">'.$row["name"]."</a> > ".$temp;
    
    if($row["parent_id"]<>0)
    {
        get_cat_path($row["parent_id"]);
    }
    
    return $temp;
}

//根据文章id获得tag，$id表示文章id，$tagid表示要排除的标签id
function taglist($id,$tagid=0)
{
    $tags="";
    if($tagid!=0)
    {
        $Taglist = db("taglist")->where("aid=$id and tid<>$tagid")->select();
    }
    else
    {
        $Taglist = db("taglist")->where("aid=$id")->select();
    }
    
    foreach($Taglist as $row)
    {
        if($tags==""){$tags='id='.$row['tid'];}else{$tags=$tags.' or id='.$row['tid'];}
    }
	
    if($tags!=""){return db("tagindex")->where($tags)->select();}
}

//获取https的get请求结果
function get_curl_data($c_url,$data='')
{
	$curl = curl_init(); // 启动一个CURL会话
	curl_setopt($curl, CURLOPT_URL, $c_url); // 要访问的地址
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
	curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
	
	if($data)
	{
		curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
	}
	
	curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
	curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	
	$tmpInfo = curl_exec($curl); // 执行操作
	
	if (curl_errno($curl))
	{
		echo 'Errno'.curl_error($curl);//捕抓异常
	}
	
	curl_close($curl); // 关闭CURL会话
	
	return $tmpInfo; // 返回数据
}

//通过file_get_content获取远程数据
function http_request_post($url,$data,$type='POST')
{
	$content = http_build_query($data);
	$content_length = strlen($content);
	$options = array(
		'http' => array(
			'method' => $type,
			'header' =>
			"Content-type: application/x-www-form-urlencoded\r\n" .
			"Content-length: $content_length\r\n",
			'content' => $content
		)
	);
	
	$result = file_get_contents($url,false,stream_context_create($options));
	
	return $result;
}

function imageResize($url, $width, $height)
{
	header('Content-type: image/jpeg');
	
	list($width_orig, $height_orig) = getimagesize($url);
	$ratio_orig = $width_orig/$height_orig;
	
	if($width/$height > $ratio_orig)
	{
		$width = $height*$ratio_orig;
	}
	else
	{
		$height = $width/$ratio_orig;
	}
	
	// This resamples the image
	$image_p = imagecreatetruecolor($width, $height);
	$image = imagecreatefromjpeg($url);
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	// Output the image
	imagejpeg($image_p, null, 100);
}

/**
 * 为文章内容添加内敛, 排除alt title <a></a>直接的字符替换
 *
 * @param string $body
 * @return string
 */
function ReplaceKeyword($body)
{
	$karr = $kaarr = array();
    
	//暂时屏蔽超链接
	$body = preg_replace("#(<a(.*))(>)(.*)(<)(\/a>)#isU", '\\1-]-\\4-[-\\6', $body);
	
	if(cache("keywordlist")){$posts=cache("keywordlist");}else{$posts = db("Keyword")->select();cache("keywordlist",$posts,2592000);}
    
	foreach($posts as $row)
	{
		$keyword = trim($row['keyword']);
		$key_url=trim($row['rpurl']);
		$karr[] = $keyword;
		$kaarr[] = "<a href='$key_url' target='_blank'><u>$keyword</u></a>";
	}
	
	asort($karr);
    
    $body = str_replace('\"', '"', $body);
    
	foreach ($karr as $key => $word)
	{
		$body = preg_replace("#".preg_quote($word)."#isU", $kaarr[$key], $body, 1);
	}
    
	//恢复超链接
	return preg_replace("#(<a(.*))-\]-(.*)-\[-(\/a>)#isU", '\\1>\\3<\\4', $body);
}

/**
 * 删除非站内链接
 *
 * @access    public
 * @param     string  $body  内容
 * @param     array  $allow_urls  允许的超链接
 * @return    string
 */
function replacelinks($body, $allow_urls=array())
{
    $host_rule = join('|', $allow_urls);
    $host_rule = preg_replace("#[\n\r]#", '', $host_rule);
    $host_rule = str_replace('.', "\\.", $host_rule);
    $host_rule = str_replace('/', "\\/", $host_rule);
    $arr = '';
	
    preg_match_all("#<a([^>]*)>(.*)<\/a>#iU", $body, $arr);
	
    if( is_array($arr[0]) )
    {
        $rparr = array();
        $tgarr = array();
		
        foreach($arr[0] as $i=>$v)
        {
            if( $host_rule != '' && preg_match('#'.$host_rule.'#i', $arr[1][$i]) )
            {
                continue;
            }
			else
			{
                $rparr[] = $v;
                $tgarr[] = $arr[2][$i];
            }
        }
		
        if( !empty($rparr) )
        {
            $body = str_replace($rparr, $tgarr, $body);
        }
    }
    $arr = $rparr = $tgarr = '';
    return $body;
}

/**
 * 获取文本中首张图片地址
 * @param  [type] $content
 * @return [type]
 */
function getfirstpic($content)
{
    if(preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $content, $matches))
	{
        $file=$_SERVER['DOCUMENT_ROOT'].$matches[3][0];
		
		if(file_exists($file))
		{
			return $matches[3][0];
		}
    }
	else
	{
		return false;
	}
}

/**
 * 更新配置文件 / 更新系统缓存
 */
function updateconfig()
{
	$str_tmp="<?php\r\n"; //得到php的起始符。$str_tmp将累加
    $str_end="?>"; //php结束符
    $str_tmp.="//全站配置文件\r\n";
    
	$param = db("sysconfig")->select();
    foreach($param as $row)
    {
        $str_tmp .= 'define("'.$row['varname'].'","'.$row['value'].'"); // '.$row['info']."\r\n";
    }
	
    $str_tmp .= $str_end; //加入结束符
    //保存文件
    $sf = APP_PATH."common.inc.php"; //文件名
    $fp = fopen($sf,"w"); //写方式打开文件
    fwrite($fp,$str_tmp); //存入内容
    fclose($fp); //关闭文件
    return $sf;
}

//清空文件夹
function dir_delete($dir)
{
    //$dir = dir_path($dir);
    if (!is_dir($dir)) return FALSE; 
    $handle = opendir($dir); //打开目录
    
    while(($file = readdir($handle)) !== false)
    {
        if($file == '.' || $file == '..')continue;
        $d = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($d) ? dir_delete($d) : @unlink($d);
    }
    
    closedir($handle);
    return @rmdir($dir);
}

//读取动态配置
function sysconfig($varname='')
{
	$sysconfig = cache('sysconfig');
	$res = '';
	
	if(empty($sysconfig))
	{
		cache('sysconfig', NULL);
        
		$sysconfig = db('sysconfig')->field('varname,value')->select();
		
		cache('sysconfig',$sysconfig,86400);
	}
	
	if($varname != '')
	{
		foreach($sysconfig as $row)
		{
			if($varname == $row['varname'])
			{
				$res = $row['value'];
			}
		}
	}
	else
	{
		$res = $sysconfig;
	}
	
	return $res;
}

if (! function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd($args)
    {
        echo '<pre>';
        foreach ($args as $x)
        {
            //var_dump($x);
            print_r($x);
        }
        
        die(1);
    }
}

//获取http(s)://+域名
function http_host($flag=true)
{
    $res = '';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    if($flag)
    {
        $res = "$protocol$_SERVER[HTTP_HOST]";
    }
    else
    {
        $res = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; //完整网址
    }
    
    return $res;
}

/**
 * 获取数据属性
 * @param $dataModel 数据模型
 * @param $data 数据
 * @return array
 */
function getDataAttr($dataModel,$data = [])
{
    if(empty($dataModel) || empty($data))
    {
        return false;
    }
    
    foreach($data as $k=>$v)
    {
        $_method_str=ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $k));
        
        $_method = 'get' . $_method_str . 'Attr';
        
        if(method_exists($dataModel, $_method))
        {
            $data[$k.'_text'] = $dataModel->$_method($data);
        }
    }
    
    return $data;
}

//根据当前网站获取上一页下一页网址
function get_pagination_url($http_host,$query_string,$page=0)
{
    $res = '';
    foreach(explode("&",$query_string) as $row)
    {
        if($row)
        {
            $canshu = explode("=",$row);
            $res[$canshu[0]] = $canshu[1];
        }
    }
    
    if(isset($res['page']))
    {
        unset($res['page']);
    }
    
    if($page==1 || $page==0){}else{$res['page'] = $page;}
    
    if($res){$res = $http_host.'?'.http_build_query($res);}
    
    return $res;
}

//输出json
function echo_json($data)
{
    header("content-type:application/json");
    exit(json_encode($data));
}