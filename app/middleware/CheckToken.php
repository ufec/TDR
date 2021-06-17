<?php
declare (strict_types = 1);

namespace app\middleware;

use thans\jwt\facade\JWTAuth;
use thans\jwt\exception\JWTException;
use think\Response;
use app\util\ReturnCode;

/**
 * Token检测中间件
 * Class CheckToken
 * @package app\middleware
 */
class CheckToken
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(\think\Request $request, \Closure $next): Response
    {
        try {
            $data = JWTAuth::auth();
            $request->jwt = $data;
        }catch (JWTException $e){
            return json([
                'code' => ReturnCode::JWT_CHECK_TOKEN_ERROR,
                'msg'  => $e->getMessage(),
                'data' => [],
            ]);
        }
        return $next($request);
    }
}
