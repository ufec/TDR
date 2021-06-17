<?php
// 应用公共文件

/**
 * 密码加密
 * @param string $password
 * @return string
 */
function crypto_password(string $password):string
{
    return md5($password . sha1(config('daily.secret')));
}

/**
 * 生成随机字符串
 * @param int $num
 * @return string
 */
function get_rand_str(int $num = 5):string
{
    $str = str_shuffle("1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ"); // 随机打乱字符串
    $length = strlen($str); // 获取字符串长度
    $min = $length - $num; // 计算剩余最小部分
    $start = rand(0, $min); // 生成随即开始截取数目
    return substr($str, $start, $num);
}

/**
 * cUrl方法
 * @param string $url 请求的地址
 * @param string $method 请求的方法
 * @param array $params 请求的参数
 * @param false $getinfo 是否需要获取Response
 * @return bool|mixed|string
 */
function curl(string $url, string $method='GET', array $params=array(), bool $getinfo=false)
{
    $user_agent = (!isset($params["ua"]) || empty($params["ua"])) ? false : $params["ua"] ;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (isset($params['header'])){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);
    }
    if(isset($params["ref"])){
        curl_setopt($ch, CURLOPT_REFERER, $params["ref"]);
    }
    if (isset($params['responseHeader']) && $params['responseHeader']) {
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }else {
        curl_setopt($ch, CURLOPT_NOBODY, false);
    }
    curl_setopt($ch, CURLOPT_USERAGENT,$user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    if($method == 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        $postData = !is_array($params['data']) ?: json_encode($params['data']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    $res = curl_exec($ch);
    if ($getinfo) {
        $data = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
    }else {
        $data = $res;
    }
    curl_close($ch);
    return $data;
}