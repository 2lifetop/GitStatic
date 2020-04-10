<?php
//https://test.9st.top/git/demo/upload/xxx.jpg?time=150000
include dirname(__FILE__) . "/GitHelper.php";
include dirname(__FILE__) . "/config.php";
header("Access-control-Allow-Origin:*");
$url_parse = parse_url($_SERVER['REQUEST_URI']);
/*
*array(2) {
  * ["path"]=> string(24) "/git/demo/upload/xxx.jpg"
  * ["query"]=> string(11) "time=150000"
  * }
*/
$url_array = explode("/", substr($url_parse["path"], 1));
//包括router
/*array(4) {
  * [0]=> string(3) "git"
  * [1]=> string(4) "demo"
  * [2]=> string(6) "upload" 
  * [3]=> string(7) "xxx.jpg"
  * }
*/
$real_url_p= strstr(trim($_SERVER['REQUEST_URI'], '/'), "/");
//有参数
//string(32) "/demo/upload/xxx.jpg?time=150000"

$real_url_n = parse_url($real_url_p) ["path"];
//无参数
//string(20) "/demo/upload/xxx.jpg"
$query_method = isset($GET["method"]) ? $GET["method"] : "default";
$_config=isset($config["github"][$url_array[0]]) ?$config["github"][$url_array[0]] : _die(10001); //非目录中断
$extension_webp = array("png" => "png", "gif" => "gif", "jpeg" => "jpeg", "jpg" => "jpeg");
//初始化webp处理后辍
function _die($error) {
    echo "不存在";
    die;
  }
  ignore_user_abort(true);
  set_time_limit(0);
  function_exists("method_" . $query_method) ? call_user_func("method_" . $query_method) : _die(10002);
  function method_default() {
      //默认操作函数
      $CacheFile = dirname(__FILE__) . "/hash/" . md5($GLOBALS["_Config"]["Parameter"] ? $GLOBALS["real_url_p"] : $GLOBALS["real_url_n"]) . ".json";
      $url_info_file = pathinfo($GLOBALS["real_url_n"]);
      /*array(4) { 
        *["dirname"]=> string(12) "/demo/upload" 
        *["basename"]=> string(7) "xxx.jpg"
        *["extension"]=> string(3) "jpg" 
        *["filename"]=> string(3) "xxx" 
        * }
      */
      if (($GLOBALS["_config"]["CacheTime"] == 0 or time() - @filemtime($CacheFile) < $GLOBALS["_config"]["CacheTime"]) && file_exists($CacheFile)) 
      {
        //缓存时间内
        $Cache_json = json_decode(file_get_contents($CacheFile));
        if ($Cache_json->webp) {
          $temp_path = $url_info_file["dirname"] == "/" ?$url_info_file["dirname"] . $url_info_file["filename"] . ".webp" : $url_info_file["dirname"] . "/" . $url_info_file["filename"] . ".webp";
        } else {
          $temp_path = $GLOBALS["real_url_p"];
        }
        //已缓存 立即导航jsdelivr
        //如果设置跳转缓存更加高效
        header('Location: https://cdn.jsdelivr.net/gh/' . $GLOBALS["_config"]["username"] . "/" . $GLOBALS["_config"]["repos"] . $GLOBALS["_config"]["path"] . $temp_path);
        die;
      }
      header('Location: ' . $GLOBALS["_config"]["site"] .$GLOBALS["_config"]["path"]. $GLOBALS["real_url_n"]);
      fastcgi_finish_request();
      $if_webp=isset($GLOBALS["extension_webp"][$url_info_file["extension"]]) && $GLOBALS["_config"]["webp"];
      $Cache_json = array("webp" => $if_webp);
      if($if_webp) { 
        $temp_func = "imagecreatefrom" . $GLOBALS["extension_webp"][$url_info_file["extension"]];
        $temp_path = $url_info_file["dirname"] == "/" ? $url_info_file["dirname"] . $url_info_file["filename"] . ".webp" : $url_info_file["dirname"] . "/" . $url_info_file["filename"] . ".webp";
        //目录
        $img_file = fopen('img/' . $url_info_file["basename"], "w+");
        fwrite($img_file, file_get_contents($GLOBALS["_config"]["site"] . $GLOBALS["_config"]["path"] . $GLOBALS["real_url_p"]));
        fclose($img_file);
        //获取到远程图片
        $temp_img = $temp_func('img/' . $url_info_file["basename"]);
        imagewebp($temp_img, 'img/' . $url_info_file["filename"] . ".webp");
        imagedestroy($temp_img);
        //写出webp图片
        $file_data = file_get_contents('img/' . $url_info_file["filename"] . ".webp");
        if (file_exists('img/' . $url_info_file["filename"] . ".webp")) @unlink('img/' . $url_info_file["filename"] . ".webp");
        if (file_exists('img/' . $url_info_file["basename"])) @unlink('img/' . $url_info_file["basename"]);
        //清理写出缓存
      } else { 
        $temp_path = $GLOBALS["real_url_n"];
        $file_data = file_get_contents($GLOBALS["_config"]["site"] . $GLOBALS["_config"]["path"] . $GLOBALS["real_url_p"]);
      }
      $temp_ret="";
      $result = files_upload($GLOBALS["_config"]["username"], $GLOBALS["_config"]["token"], $GLOBALS["_config"]["repos"], $temp_path, base64_encode($file_data),$temp_ret);
      if (!$result) $result = files_updata($GLOBALS["_config"]["username"], $GLOBALS["_config"]["token"], $GLOBALS["_config"]["repos"], $temp_path, base64_encode($file_data), get_sha($GLOBALS["_config"]["username"], $GLOBALS["_config"]["repos"], $temp_path),$temp_ret);
      //再次出错建议写出日志
      $Cache_json["result"] = $result;
      if (!$result){ 
        file_put_contents("log/".time().".txt","\n上传目录:".$temp_path."\n".$result);
      }else{
        file_put_contents($CacheFile, json_encode($Cache_json));
        //正常写入
      }
      //写出记录
    }