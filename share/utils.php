<?php

namespace cartographica\share;

class Utils
{
  public static function isCliMode(): bool
  {
    if (defined("STDIN")==true)
    {
      return true;
    }
    if (php_sapi_name()==="cli")
    {
      return true;
    }
    if (array_key_exists("SHELL",$_ENV)==true)
    {
      return true;
    }
    if ((empty($_SERVER["REMOTE_ADDR"])==true) and (isset($_SERVER["HTTP_USER_AGENT"])==false) and (count($_SERVER["argv"])>0))
    {
      return true;
    }
    if (array_key_exists("REQUEST_METHOD",$_SERVER)==false)
    {
      return true;
    }
    return false;
  }
}
