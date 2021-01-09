<?php

declare(strict_types=1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;

use Firebase\JWT\JWT;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        $this->jwtValidate();
    }

    private function jwtValidate()
    {
        if ($_SERVER["REQUEST_METHOD"] == "OPTIONS" && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] == "access-token") {
            exit;
        }

        $jwt = isset($_SERVER['HTTP_ACCESS_TOKEN']) ? $_SERVER['HTTP_ACCESS_TOKEN'] : '';

        if (empty($jwt)) {
            header('HTTP/1.1 401 Unauthorized');
            header('status: 401 Unauthorized');
            exit;
        }

        try {
            JWT::$leeway = 60;
            $decoded = JWT::decode($jwt, 'U2FsdGVkX1/JWgZqBvRZmEuyNLdUy3L8xMGS', ['HS256']);
            $arr = (array) $decoded;
            if ($arr['exp'] < time()) {
                header('HTTP/1.1 402 Authorized Expired');
                header('status: 402 Authorized Expired');
                exit;
            }
        } catch (\Exception $e) {
            header('HTTP/1.1 401 Unauthorized');
            header('status: 401 Unauthorized');
            exit;
        }
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }
}
