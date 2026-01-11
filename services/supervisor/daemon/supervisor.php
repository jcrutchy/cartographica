<?php

/*

### 1. **Add per-island stdout capture**
Right now you're using:

```php
"php://stdout", "w"
```

That just dumps output to the supervisor's console.  
Instead, capture each island’s output into a log file:

```php
$log_stdout = $island["path"] . "/stdout.log";
$descriptor_spec = [
    ["file", "php://stdin", "r"],
    ["file", $log_stdout, "w"],
    ["file", $log_stdout, "a"]
];
```

Then store:

```php
$island["log_stdout"] = $log_stdout;
```

Now you can tail logs per island or expose them via IPC.

---

### 2. **Add IPC command routing**
Right now `service_ipc_msg()` is hardcoded to echo `msg`.  
Instead, parse `$data` as JSON and route commands:

```php
function service_ipc_msg($data, &$response, &$island_data, &$supervisor_config)
{
    $msg = json_decode($data, true);
    if (!is_array($msg)) {
        $response["error"] = "Invalid IPC message";
        return;
    }

    switch ($msg["cmd"] ?? "") {
        case "status":
            $response["status"] = array_map(fn($i) => [
                "island_id" => $i["config"]["island_id"],
                "pid" => $i["pid"] ?? null,
                "uptime" => isset($i["start"]) ? round(microtime(true) - $i["start"], 2) : null
            ], $island_data);
            break;

        case "stop":
            $target = $msg["island_id"] ?? null;
            foreach ($island_data as &$island) {
                if ($island["config"]["island_id"] === $target) {
                    kill_island($island, get_running_procs());
                    $response["stopped"] = $target;
                    return;
                }
            }
            $response["error"] = "Island not found: $target";
            break;

        default:
            $response["error"] = "Unknown command";
    }
}
```

Now you can send:

```json
{"cmd":"status"}
{"cmd":"stop","island_id":"island_01"}
```

And get structured responses.

---

### 3. **Add auto-restart or health checks**
You already have `get_running_procs()` — so you can periodically check if any island has died and restart it.

Just add a tick loop:

```php
foreach ($island_data as &$island) {
    if (!is_island_running($island["command"], get_running_procs())) {
        service_msg("Restarting island: " . $island["config"]["island_id"]);
        run_process($supervisor_config, $island, get_running_procs());
    }
}
```

---

### 4. **Add REST or WebSocket bridge**
You’ve got IPC working — next step is exposing it to external tools.  
You could add a simple HTTP server that proxies IPC commands, or a WebSocket bridge for live control.

---

### 5. **Add supervisor config validation**
Before merging templates, validate required keys:

```php
$required = ["ipc_listening_port", "islands_dir", "command_template"];
foreach ($required as $key) {
    if (!isset($supervisor_config[$key])) {
        service_error("Missing config key: $key");
    }
}
```

*/


#####################################################################################################

/*
Windows CLI colors:
  39 = default
  30 = black
  31 = red
  32 = green
  33 = yellow
  34 = blue
  35 = magenta
  36 = cyan
  37 = light gray
  90 = dark gray
  91 = light red
  92 = light green
  93 = light yellow
  94 = light blue
  95 = light magenta
  96 = light cyan
  97 = white
https://learn.microsoft.com/en-us/windows/console/console-virtual-terminal-sequences
*/

ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);
ini_set("memory_limit","-1");
ini_set("max_execution_time","0");

date_default_timezone_set("UTC");

require_once __DIR__."/../../bootstrap.php";

define("CARTOGRAPHICA_DATA",$bootstrap["data_root"]);

$supervisor_config_filename=CARTOGRAPHICA_DATA."/services/supervisor/daemon_config.json";
$supervisor_config=file_get_contents($supervisor_config_filename);
foreach ($bootstrap as $template => $substitute)
{
  $supervisor_config=str_replace("{".$template."}",$substitute,$supervisor_config);
}
$supervisor_config=json_decode($supervisor_config,true);
if (is_array($supervisor_config)==false)
{
  service_error("invalid json in supervisor config file: ".$supervisor_config_filename);
}
$supervisor_config=array_merge($bootstrap,$supervisor_config);

service_msg("supervisor service started");

if (isset($supervisor_config["ipc_listening_port"])==false)
{
  service_error("ipc_listening_port not found");
}

$url="[::1]:".$supervisor_config["ipc_listening_port"];
$err_no=0;
$err_msg="";
$ipc_server=stream_socket_server($url,$err_no,$err_msg);
if ($ipc_server===false)
{
  service_error("error initializing supervisor service ipc socket");
}
stream_set_blocking($ipc_server,0);
service_msg("supervisor service ipc server listening: ".$url,94);

$islands_dir=$supervisor_config["islands_dir"];

if (is_dir($islands_dir)==false)
{
  service_error("islands_dir not found: ".$islands_dir);
}

$island_data=array();

$files=scandir($islands_dir);
$n=count($files);
for ($i=0;$i<$n;$i++)
{
  $fn=$files[$i];
  if (($fn==".") or ($fn=="..") or ($fn==".git"))
  {
    continue;
  }
  $full=$islands_dir."/".$fn;
  if (is_dir($full)==false)
  {
    continue;
  }
  service_msg("island config directory found: ".$full,32);
  $island=array();
  $island["path"]=$full;
  $island_config_filename=$full."/island_config.json";
  if (file_exists($island_config_filename)==false)
  {
    service_error("island config not found: ".$island_config_filename);
  }
  $island_config=file_get_contents($island_config_filename);
  foreach ($supervisor_config as $template => $substitute)
  {
    $island_config=str_replace("{".$template."}",$substitute,$island_config);
  }
  $island_config=json_decode($island_config,true);
  if (is_array($island_config)==false)
  {
    service_error("invalid json in island config file: ".$island_config_filename);
  }
  $island["config"]=$island_config;
  $island_data[]=$island;
}

$procs=get_running_procs();

$running_count=0;

$n=count($island_data);
for ($i=0;$i<$n;$i++)
{
  $running_count+=run_process($supervisor_config,$island_data[$i],$procs);
}

if ($running_count==0)
{
  service_error("no island server processes running");
}

var_dump($island_data);

$ipc_clients=array();

$direct_stdin=fopen("php://stdin","r");
stream_set_blocking($direct_stdin,0);

while (true)
{
  $msg=trim(fgets($direct_stdin));
  if ($msg)
  {
    service_msg("*** DIRECT STDIN: ".$msg);
    switch ($msg)
    {
      case "q":
        break 2;
    }
  }
  $read=array($ipc_server);
  $write=null;
  $except=null;
  $changed=stream_select($read,$write,$except,0);
  if ($changed===false)
  {
    service_msg("stream_select error on service socket");
    break;
  }
  if ($changed>=1)
  {
    $ipc_client=stream_socket_accept($ipc_server,30);
    if (($ipc_client===false) or ($ipc_client==null))
    {
      service_msg("stream_socket_accept error on supervisor service ipc server socket");
      continue;
    }
    $data="";
    do
    {
      $buffer=fread($ipc_client,1024);
      if ($buffer===false)
      {
        service_msg("socket read error");
        continue 2;
      }
      $data.=$buffer;
    }
    while (strlen($buffer)>0);
    if (strlen($data)==0)
    {
      service_msg("connection terminated by remote host");
      continue;
    }
    $data=trim($data);
    if ($data<>"")
    {
      $response=array();
      service_ipc_msg($data,$response,$island_data,$supervisor_config);
      fwrite($ipc_client,json_encode($response));
    }
    $ipc_clients[]=$ipc_client;
  }
  usleep(0.1e6); # 0.1 sec
}

$n=count($ipc_clients);
for ($i=0;$i<$n;$i++)
{
  $ipc_client=$ipc_clients[$i];
  stream_socket_shutdown($ipc_client,STREAM_SHUT_RDWR);
  fclose($ipc_client);
}

stream_socket_shutdown($ipc_server,STREAM_SHUT_RDWR);
fclose($ipc_server);

kill_all_islands($island_data);

#####################################################################################################

function get_running_procs()
{
  $procs=shell_exec("wmic process get CommandLine,ParentProcessId,ProcessId");
  $procs=explode(PHP_EOL,$procs);
  $result=array();
  $n=count($procs);
  for ($i=0;$i<$n;$i++)
  {
    $proc=trim($procs[$i]);
    if ($proc=="")
    {
      continue;
    }
    while (strpos($proc,"  ")!==false)
    {
      $proc=str_replace("  "," ",$proc);
    }
    $proc=strtolower(trim($proc));
    if ($proc=="")
    {
      continue;
    }
    $proc=str_replace("\\","/",$proc);
    $proc=explode(" ",$proc);
    $ProcessId=array_pop($proc);
    $ParentProcessId=array_pop($proc);
    $CommandLine=implode(" ",$proc);
    $proc=array();
    $proc["CommandLine"]=$CommandLine;
    $proc["ParentProcessId"]=$ParentProcessId;
    $proc["ProcessId"]=$ProcessId;
    $result[]=$proc;
  }
  return $result;
}

#####################################################################################################

function kill_all_islands(&$island_data)
{
  $procs=get_running_procs();
  $n=count($island_data);
  for ($i=0;$i<$n;$i++)
  {
    kill_island($island_data[$i],$procs);
  }
}

#####################################################################################################

function kill_island(&$island,&$procs)
{
  if (isset($island["pid"])==false)
  {
    return;
  }
  $pid=$island["pid"];
  $kill_pids=array();
  pid_recurse($pid,$procs,$kill_pids);
  $n=count($kill_pids)-1;
  for ($i=$n;$i>=0;$i--)
  {
    pid_kill($kill_pids[$i]);
  }
  proc_close($island["process"]);
  unset($island["process"]);
  unset($island["command"]);
  unset($island["pid"]);
  unset($island["start"]);
}

#####################################################################################################

function pid_kill($pid)
{
  service_msg("  killing process: ".$pid);
  $out=shell_exec("taskkill /F /pid ".$pid);
  if (empty($out)==true)
  {
    return;
  }
  $out=trim($out);
  if (empty($out)==false)
  {
    service_msg($out);
  }
}

#####################################################################################################

function pid_recurse($pid,&$procs,&$kill_pids)
{
  $n=count($procs);
  for ($i=0;$i<$n;$i++)
  {
    $proc=$procs[$i];
    $child_pid=$proc["ProcessId"];
    $parent_pid=$proc["ParentProcessId"];
    if ($parent_pid<>$pid)
    {
      continue;
    }
    service_msg("  child process found: ".$child_pid);
    pid_recurse($child_pid,$procs,$kill_pids);
  }
  if (in_array($pid,$kill_pids)==false)
  {
    $kill_pids[]=$pid;
  }
}

#####################################################################################################

function is_island_running($island_command,&$procs)
{
  $n=count($procs);
  for ($i=0;$i<$n;$i++)
  {
    $proc=$procs[$i];
    if (strpos($proc["CommandLine"],$island_command)!==false)
    {
      return true;
    }
  }
  return false;
}

#####################################################################################################

function run_process($supervisor_config,&$island,&$procs)
{
  $island_command=$supervisor_config["command_template"];
  foreach ($island as $template => $substitute)
  {
    if (is_string($substitute)==false)
    {
      continue;
    }
    $island_command=str_replace("{".$template."}",$substitute,$island_command);
  }
  foreach ($island["config"] as $template => $substitute)
  {
    if (is_string($substitute)==false)
    {
      continue;
    }
    $island_command=str_replace("{".$template."}",$substitute,$island_command);
  }
  if (is_island_running($island_command,$procs)==true)
  {
    service_msg("island server with island_id \"".$island["config"]["island_id"]."\" is already running",33);
    return 0;
  }
  if (strpos($island_command,"2>&1")===false)
  {
    $island_command.=" 2>&1";
  }
  $cwd=null;
  $env=null;
  $descriptor_spec=array();
  $descriptor_spec[]=array("file","php://stdin","r");
  $descriptor_spec[]=array("file","php://stdout","w");
  $descriptor_spec[]=array("file","php://stdout","a");
  $start=microtime(true);
  $process=proc_open($island_command,$descriptor_spec,$pipes,$cwd,$env);
  if (is_resource($process)==true)
  {
    $status=proc_get_status($process);
    $pid=$status["pid"];
    service_msg("island server with island_id \"".$island["config"]["island_id"]."\" has been started [pid: ".$pid."]: ".$island_command,95);
    $island["process"]=$process;
    $island["command"]=$island_command;
    $island["pid"]=$pid;
    $island["start"]=$start;
    return 1;
  }
  service_msg("error running island server with island_id \"".$island["config"]["island_id"]."\"",33);
  return 0;
}

#####################################################################################################

function service_msg($msg,$color=false)
{
  term_echo(gmdate("Y-m-d h:i:s")." [supervisor_service:".getmypid()."] > ".$msg,$color);
}

#####################################################################################################

function service_error($msg)
{
  service_msg($msg,31);
  die;
}

#####################################################################################################

function service_ipc_msg($data)
{
  $prefix="\033[";
  $suffix="m";
  $reset=0;
  $color1=30;
  $color2=92;
  $bg_color=103;
  $msg1=gmdate("Y-m-d h:i:s");
  $msg2=" ".$data["msg"];
  echo $prefix.$color1.$suffix.$prefix.$bg_color.$suffix.$msg1.$prefix.$reset.$suffix;
  $triangle="►";
  echo $prefix."93".$suffix.$triangle.$prefix.$reset.$suffix;
  echo $prefix.$color2.$suffix.$msg2.$prefix.$reset.$suffix.PHP_EOL;
}

#####################################################################################################

function term_echo($msg,$color=false)
{
  if ($color===false)
  {
    echo $msg.PHP_EOL;
  }
  else
  {
    echo "\033[".$color."m".$msg."\033[0m".PHP_EOL;
  }
}

#####################################################################################################
