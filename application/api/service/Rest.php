<?php

/**
 *  +----------------------------------------------------------------------
 *  | 草帽支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

namespace app\api\service;

use app\common\controller\Common;
use app\common\library\exception\ParameterException;
use app\common\library\RsaUtils;

class Rest extends Common
{
    /**
     * 请求参数
     *
     * @var array
     */
    protected static $context = [];

    /**
     * @var
     */
    protected static $conId;

    /**
     * 创建当前请求上下文
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @throws ParameterException
     */
    public static function createContext(){
        $conId = self::$conId = self::createUniqid();
        if (!isset(self::$context[$conId])){
            self::$context[$conId] = [];
        }else{
            throw new ParameterException(['msg'=>'Create context failed,cannot create a duplicate context']);
        }
    }


    /**
     * 销毁当前请求上下文
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @throws ParameterException
     */
    public static function destoryContext(){
        if (isset(self::$context[self::$conId])){
            unset(self::$context[self::$conId]);
        }else{
            throw new ParameterException(['msg'=>'Destory context failed,cannot destory a duplicate context']);
        }
    }

    /**
     * 判断当前请求上下文是否存在
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return bool
     */
    public static function exsits()
    {
        return isset(self::$context[self::createUniqid()]);
    }

    /**
     * 获取上下文数据
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $name
     * @param null $default
     * @return null
     */
    public static function get($name, $default = null)
    {
        if(!isset(self::$context[self::$conId]))
        {
            throw new \RuntimeException('get context data failed, current context is not found');
        }
        if(isset(self::$context[self::$conId][$name]))
        {
            return self::$context[self::$conId][$name];
        }
        else
        {
            return $default;
        }
    }

    /**
     * 设置上下文数据
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $name
     * @param $value
     */
    public static function set($name, $value)
    {
        if(!isset(self::$context[self::$conId]))
        {
            throw new \RuntimeException('set context data failed, current context is not found');
        }
        self::$context[self::$conId][$name] = $value;
    }

    /**
     * 获取当前上下文
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public static function getContext()
    {
        if(!isset(self::$context[self::$conId]))
        {
            throw new \RuntimeException('get context failed, current context is not found');
        }
        return self::$context[self::$conId];
    }

    /**
     * 获取当前的服务器对象
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return mixed
     */
    public static function getServer()
    {
        return self::get('request')->getServerInstance();
    }

    /**
     * 在当前服务器上下文中获取Bean对象
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $name
     * @param $params
     * @return mixed
     */
    public static function getBean($name, &$params)
    {
        return self::getServer()->getBean($name, $params);
    }

    /**
     * 获取当前时间的毫秒数
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @return float
     */
    public static function getMicroTime(){
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    /**
     * 生成唯一id[32位]
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param string $namespace
     * @return string
     */
    public static function createUniqid($namespace = ''){
        static $uniqid = '';
        $uid = uniqid("", true);
        $data = $namespace;
        $data .= isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : "";
        $data .= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        $data .= isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : "";
        $data .= isset($_SERVER['LOCAL_PORT']) ? $_SERVER['LOCAL_PORT'] : "";
        $data .= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
        $data .= isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : "";
        $hash = strtoupper(hash('ripemd128', $uid . $uniqid . md5($data)));
        $uniqid = substr($hash,  0,  8) .
            substr($hash,  8,  4) .
            substr($hash, 12,  4) .
            substr($hash, 16,  4) .
            substr($hash, 20, 12);
        return $uniqid;
    }

    /**
     * 生成签名字符串
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param $to_sign_data
     * @return string
     */
    protected static function sign($to_sign_data){
        $Rsa = new RsaUtils();
        //平台私钥生成签名
        $Rsa->setPrivateKey(CRET_PATH . 'rsa_private_key.pem');
        return $Rsa->sign($to_sign_data);
    }

    /**
     * 数据验签  -- 验证来自商户的数据
     *
     * @author 勇敢的小笨羊 <brianwaring98@gmail.com>
     *
     * @param string $data 验签数据
     * @param string $sign 签名串
     * @param string $key  请求key
     * @return bool
     */
    protected static function verify($data, $sign, $key){
        $Rsa = new RsaUtils();
        //指定商户公钥验证签名
        $Rsa->setPublicKey(CRET_PATH . $key . DS .'rsa_public_key.pem');
        return $Rsa->verify($data, $sign, $code = 'base64');
    }
}