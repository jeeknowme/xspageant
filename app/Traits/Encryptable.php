<?php

namespace App\Traits;

// use Illuminate\Support\Facades\Schema;

use Crypt;

trait Encryptable{


    public static function encrypt($params){

        $params = (array)$params;

        if(is_array($params)){
            foreach($params as $keys=>$values){
                if(is_array($values)){
                    foreach($values as $key=>$value){
                        if(is_array($value)){
                            foreach($value as $key1=>$value1){
                                if(is_array($value1)){
                                    foreach($value1 as $key2 => $value2){
                                        if(strstr($params[$keys][$key][$key1][$key2],"id")){
                                            $params[$keys][$key][$key1][$key2] = Crypt::encrypt($value2);
                                        }
                                    }
                                }
                                if(strstr($params[$keys][$key][$key1],"id")){
                                    $params[$keys][$key][$key1] = Crypt::encrypt($value1);
                                }
                            }
                        }
                        if(strstr($params[$keys][$key],"id")){
                            $params[$keys][$key] = Crypt::encrypt($value);
                        }
                    }
                }
                if(strstr($params[$key],"id")){
                    $params[$key] = Crypt::encrypt($values);
                }
            }
            return $params;
        }
        return false;
        return Crypt::encrypt($params);
    }

    private static function isId($value){
        return strstr($value,"id");
    }
}