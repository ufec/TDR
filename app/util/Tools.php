<?php

namespace app\util;

/**
 * 系统的工具类，包含常用的工具函数
 * Class Tools
 * @package app\util
 */
class Tools
{
    /**
     * @param array $list 二维索引数组
     * @param string $pk 主键名
     * @param string $pid 父节点名
     * @param string $child 子ID名
     * @param int $root 根节点
     * @return array
     */
    public static function list_to_tree(array $list, string $pk="id", string $pid="fid", string $child="children", int $root=0): array
    {
        $tree = [];
        if (is_array($list)) {
            $refer = [];
            // 遍历传入的二维索引数组
            foreach ($list as $key => $data) {
                // 取每个数组的id作为$refer的键，原二维数组的每个数据项作为$refer的值
                $refer[$data[$pk]] = &$list[$key];
            }
            // 遍历传入的二维索引数组
            foreach ($list as $key => $data) {
                // 取每个数组的父ID
                $parentId = $data[$pid];
                // 父ID与root(一级菜单)相等
                if ($root == $parentId) {
                    // 直接将该数据赋值给tree作为一级菜单
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 去除字符串中所有的空格
     * @param string $str
     * @return string
     */
    public static function remove_space(string $str):string
    {
        return str_replace(' ','', $str);
    }

    /**
     * 过滤混合数组，只返回数字
     * @param array $arr
     * @return array
     */
    public static function get_number_array(array $arr): array
    {
        return array_filter($arr, function ($v){
            if (intval($v) == $v) {
                return intval($v);
            }
        });
    }

    /**
     * 更改二维数组的键
     * @param array $arr
     * @param string $key
     * @return array
     */
    public static function change_arr_key(array $arr, string $key): array
    {
        $newArr = [];
        foreach ($arr as $item){
            $newArr[$item[$key]] = $item;
        }
        return $newArr;
    }

    /**
     * 判断当前用户是否为管理员
     * @param int $uid
     * @return bool
     */
    public static function check_user_is_admin(int $uid):bool
    {
         return config('daily.super_admin_id') == $uid;
    }

    /**
     * 计算二维数组差集 - 过滤掉target在src中重复项，保留src
     * @param array $src 源数组
     * @param array $target 目标数组
     * @return array 返回值
     */
    public static function array_diff_2(array $src, array $target): array
    {
        foreach($src as $k=>$v) if(in_array($v, $target)) unset($src[$k]);
        return $src;
    }
}