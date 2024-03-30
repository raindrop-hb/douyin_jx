<?php
/*
抖音视频图集解析，支持短链接及分享口令
请求方式post
如：4.30 复制打开抖音，看看【汤宇浩（Tom ）的作品】# 感觉到好炽热刚好是你经过 # 被迫营业 ... https://v.douyin.com/iFKH2men/ 12/18 g@O.KJ use:/ 
或者https://www.douyin.com/video/7351336588875484431
Author  : raindrop
Email   : 196329640@qq.com
Github  : https://github.com/raindrop-hb/
Give me money(狗头) :http://dy.tom14.top/ds.html
*/

$url=$_POST['url'];

//对接收的url进行处理
function handle($url){
	if(strpos($url,"douyin.com/video/")!==false || strpos($url,"douyin.com/note")!==false){
	    if(strpos($url,"?previous_page=app_code_link")!==false){
	        $do_url=str_replace("?previous_page=app_code_link","",$url);
	    }
	    else{
	        $do_url=$url;
	    }
	    $result=get_aweme($do_url);
	}
	else if(strpos($url,"https://")==false){
		$result=array("code"=>500,"message"=>array());
	}
	else{
		preg_match_all("/https:\/\/[A-Za-z0-9_.\/]+(\s?)/",$url,$do_url);
		$do_url=$do_url[0][0];
		$do_url=lang_url($do_url);
		$result=get_aweme($do_url);
	}
	return $result;
}

//获取ttwid
function get_ttwid(){
    $ch = curl_init();
    $url="https://ttwid.bytedance.com/ttwid/union/register/";
    curl_setopt($ch, CURLOPT_URL, $url);  // 设置URL
    curl_setopt($ch, CURLOPT_POST, 1);  // 设置为POST请求
    $data = array("region" => "cn","aid" => 1768,"needFid" => "false","service" => "www.ixigua.com","migrate_info" => array("ticket" => "","source" => "node"),"cbUrlProtocol" => "https","union" => "true");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  // 设置POST数据
    $this_header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
    curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $result = curl_exec($ch);
    preg_match('/Set-Cookie:(.*);/iU',$result,$str);
    $cookie = $str[1];
    preg_match('/ ttwid=(.*)/',$cookie,$str);
    $cookie = $str[1];
    curl_close($ch);
    return $cookie;
}

//解析长链接
function lang_url($url){
    for ($i=1; $i<=2; $i++){
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//设置请求地址
        $header = ["referer:https://www.douyin.com/","user-agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.5359.125 Safari/537.36","cookie:ttwid=".get_ttwid()]; //设置一个你的浏览器agent的header
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回结果为字符串
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置请求头部不输出
        curl_exec($ch);//执行curl请求
        $url = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
        curl_close($ch);//关闭curl
    }
    $url=str_replace("?previous_page=app_code_link","",$url);
    return $url;
}

function get_aweme($url){
    $header = ["referer:https://www.douyin.com/","user-agent:Mozilla/5.0 (Linux; Android 12; 2210132C Build/SP1A.210812.016) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.104 Mobile Safari/537.36","Cookie:ttwid=".get_ttwid()];
    if(strpos($url,"douyin.com/note/")!==false){
        $aweme_id=str_replace("https://www.douyin.com/note/","",$url);
        $url="https://www.iesdouyin.com/share/note/".$aweme_id;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//设置请求地址
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回结果为字符串
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置请求头部不输出
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $html = curl_exec($ch);//执行curl请求
        curl_close($ch);//关闭curl
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath=new DOMXPath($dom);
        $result=$xpath->query('/html/body/script[@id="RENDER_DATA"]');
        $result=$result[0]->textContent;
        $result=urldecode($result);
        $arr=json_decode($result,true);
        $desc=$arr["app"]["videoInfoRes"]["item_list"][0]["statistics"];
        unset($desc["play_count"]);
        $desc = array_merge($desc, ['url' => []]);
        $url=array();
        $desc = array_merge($desc, ['nickname' => $arr["app"]["videoInfoRes"]["item_list"][0]["author"]["nickname"],'type'=>"note"]);
        foreach ($arr["app"]["videoInfoRes"]["item_list"][0]["images"] as $i){
            array_push($desc["url"], end($i["url_list"]));
        }
        $result=array("code"=>200,"message"=>$desc);
    }
    else{
        $aweme_id=str_replace("https://www.douyin.com/video/","",$url);
        $url="https://www.douyin.com/aweme/v1/web/aweme/detail/?aweme_id=".$aweme_id;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//设置请求地址
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回结果为字符串
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置请求头部不输出
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $html = curl_exec($ch);//执行curl请求
        curl_close($ch);//关闭curl
        $result=urldecode($html);
        $arr=json_decode($result,true);
        $desc=$arr["aweme_detail"]["statistics"];
        unset($desc["play_count"]);
        unset($desc["admire_count"]);
        $desc = array_merge($desc, ['nickname' => $arr["aweme_detail"]["author"]["nickname"],'type'=>"video"]);
        $video_url=$arr["aweme_detail"]["video"]["play_addr_h264"]["uri"];
        $video_url="https://aweme.snssdk.com/aweme/v1/play/?video_id=".$video_url."&ratio=1080p&line=0";
        $desc = array_merge($desc, ['url' => [$video_url]]);
        $result=array("code"=>200,"message"=>$desc);
    }
    return $result;
}

$a=handle($url);
$json = json_encode($a);
echo $json;

?>