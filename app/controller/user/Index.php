<?php
declare(strict_types=1);

namespace app\controller\user;


use app\util\ReturnCode;
use app\model\User;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\Response;

class Index extends Base
{
    /**
     * 修改用户昵称
     * @return Response
     */
    public function changeUserName(): Response
    {
        $param = request()->param();
        try {
            validate([
                "nick_name" => ["require", "chsAlpha"],
                "id" => ["require", "number"],
                "password" => ["alphaNum"],
            ], [
                "nick_name.require" => "姓名不得为空！",
                "nick_name.chsAlpha" => "姓名只能为汉字或字母！",
                "id.require" => "未知身份！",
                "id.number"  => "未知身份！",
                "password.alphaNum" => "密码只能是字母或数字！"
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $user = (new User())->where('id', $param['id'])->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (null == $user) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "用户数据为空！");
        }
        $user->nick_name = $param['nick_name'];
        if (isset($param['password'])) {
            $user->password = crypto_password($param['password']);
        }
        if ($user->save()) {
            return $this->actionSuccess();
        }else{
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "保存出错！");
        }
    }

    /**
     * 获取小程序码
     * @return Response
     */
    public function getQRCode(): Response
    {
        $param = request()->param();
        if (!isset($param['uuid']) || strlen($param['uuid']) != 32){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "无效的uuid");
        }
        $uuid = $param['uuid'];
        $cache = Cache::get($uuid);
        if ($cache != false){
            return $this->actionSuccess(['data' => $cache]);
        }
        try {
            $AccessToken = $this->getAccessToken();
        } catch (Exception $e) {
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, $e->getMessage());
        }
        $api = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$AccessToken";
        $param = [
            'data' => [
                "scene" => $param['uuid'],
                "page" => "pages/scanLogin/scanLogin",
                "width" => 430,
                "auto_color" => true
            ]
        ];
        $res = curl($api, "POST", $param);
        $baseImg = base64_encode($res);
        Cache::set($uuid, $baseImg, 300);
        return $this->actionSuccess(['img' => $baseImg]);
    }

    /**
     * 校验UUID，并为当前用户设置缓存openid
     * @return Response
     */
    public function checkUUID(): Response
    {
        $param = request()->param();
        // 校验参数
        try {
            validate([
                "uuid" => ["require", "length:32"],
            ], [
                "uuid.require" => "参数有误！",
                "uuid.length" => "参数有误！",
            ])->check($param);
        } catch (ValidateException $e){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $uuid = $param['uuid'];
        // 校验uuid是否在有效期内
        $cache = Cache::get($uuid);
        if (!$cache){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "二维码已过期，请刷新页面重新生成！");
        }
        try {
            $userInfo = json_decode(base64_decode(request()->jwt['userInfo']->getValue()), true);
        } catch (Exception $e){
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, "用户信息有误！");
        }
        if (empty($userInfo) || !isset($userInfo['open_id'])){
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, "用户信息有误！");
        }
        // 为uuid生成token ，token为用户的openid
        if (Cache::set($uuid."_token", $userInfo['open_id'], 300)){
            return $this->actionSuccess([], "登陆成功！");
        }
        Cache::delete($param['uuid']."_token");
        return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, "系统出错！");
    }
}