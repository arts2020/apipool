<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/21
 * Time: 上午9:43
 */

namespace App\Http\Services;

class ApiService
{
    public static function doGet($api, $uri, $query=[])
    {
        $queryStr = "";
        if (!empty($query)) {
            $queryStr = http_build_query($query);
        }
        $url = $api . $uri . '?' . $queryStr;
        $str = file_get_contents($url, false, stream_context_create(array(
            "http" => array(
                'ignore_errors' => true,
                "method" => "GET"
            )
        )));
        return json_decode($str);
    }

    public static function doPost($api, $uri, $data=[], $query=[])
    {
        $queryStr = "";
        if (!empty($query)) {
            $queryStr = http_build_query($query);
        }
        $opts = stream_context_create(array(
            'http' => array(
                'ignore_errors' => true,
                "method" => "POST",
                'header' => "Content-type: application/json",
                'content' => json_encode($data)
            )
        ));
        $str = file_get_contents($api . $uri . '?' . $queryStr, false, $opts);
        return json_decode($str);
    }

    public static function doPut($api, $uri, $data=[], $query=[])
    {
        $queryStr = "";
        if (!empty($query)) {
            $queryStr = http_build_query($query);
        }
        $opts = stream_context_create(array(
            'http' => array(
                'ignore_errors' => true,
                "method" => "PUT",
                'header' => "Content-type: application/json",
                'content' => json_encode($data)
            )
        ));
        $str = file_get_contents($api . $uri . '?' . $queryStr, false, $opts);
        return json_decode($str);
    }

    public static function doDelete($api, $uri, $query=[])
    {
        $queryStr = "";
        if (!empty($query)) {
            $queryStr = http_build_query($query);
        }
        $opts = stream_context_create(array(
            'http' => array(
                'ignore_errors' => true,
                "method" => "DELETE"
            )
        ));
        $str = file_get_contents($api . $uri . '?' . $queryStr, false, $opts);
        return json_decode($str);
    }

}
