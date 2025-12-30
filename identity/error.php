<?php

function error_handler($errno,$errstr,$errfile,$errline)
{
  $message="[".date("Y-m-d, H:i:s T",time())."] ".$errstr." in \"".$errfile."\" on line ".$errline;
  #email_admin($message,"cartographica identity service: error_handler");
  json_error($message);
}

function exception_handler($exception)
{
  $message="[".date("Y-m-d, H:i:s T",time())."] ".$exception->getMessage()." in \"".$exception->getFile()."\" on line ".$exception->getLine();
  #email_admin($message,"cartographica identity service: exception_handler");
  json_error($message);
}
