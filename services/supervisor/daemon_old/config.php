<?php
/**
 * Config (Daemon)
 *
 * Loads configuration for the supervisor supervisor daemon.
 * Reads daemon/config.php and resolves paths relative to CARTOGRAPHICA_DATA.
 */

namespace Cartographica\Supervisor;

class Config
{
    private string $ipcHost;
    private int $ipcPort;
    private string $logDir;
    private array $islands;

    public function __construct(string $config_filename, array $bootstrap)
    {
      if (!file_exists($config_filename))
      {
        throw new \RuntimeException("Config file not found: $config_filename");
      }

      $data=file_get_contents($config_filename);
      foreach ($bootstrap as $template => $substitute)
      {
        $substitute=str_replace("\\","/",$substitute);
        $data=str_replace("{".$template."}",$substitute,$data);
      }

      $data = json_decode($data, true);
      if (!is_array($data))
      {
        throw new \RuntimeException("Invalid JSON in config file: $config_filename");
      }

      // IPC settings
      $this->ipcHost = $data['ipc_listening_host'] ?? '127.0.0.1';
      $this->ipcPort = $data['ipc_listening_port'] ?? 9001;

      if (!isset($data['command_template'])) {
          throw new \RuntimeException("command_template missing");
      }

      if (strpos($data['command_template'], '{island_id}') === false) {
          throw new \RuntimeException("command_template must contain {island_id}");
      }

      $this->logDir = $data['log_dir'];
      if (!is_dir($this->logDir)) {
          mkdir($this->logDir, 0777, true);
      }


      // ---------------------------------------------
      // Load island definitions from islands_dir
      // ---------------------------------------------
      $this->islands = [];
      
      $islandsDir = $data['islands_dir'] ?? null;
      if (!$islandsDir) {
          throw new \RuntimeException("Missing 'islands_dir' in supervisor config");
      }
      
      if (!is_dir($islandsDir)) {
          // Create the directory if it doesn't exist yet
          mkdir($islandsDir, 0777, true);
      }
      
      // Scan for island folders
      $entries = scandir($islandsDir);
      foreach ($entries as $entry) {
      
          // Skip dot entries
          if ($entry === '.' || $entry === '..') {
              continue;
          }
      
          $islandPath = $islandsDir . "/" . $entry;
          if (!is_dir($islandPath)) {
              continue; // skip files
          }
      
          $configFile = $islandPath . "/island_config.json";
          echo $configFile."\n";
          if (!file_exists($configFile)) {
              // Not an island instance
              echo "   ".$configFile." - not found\n";
              continue;
          }
          

      
          // Load island config
          $raw = file_get_contents($configFile);
      
          // Apply templating to island config too
          foreach ($bootstrap as $template => $substitute) {
              $substitute = str_replace("\\", "/", $substitute);
              $raw = str_replace("{" . $template . "}", $substitute, $raw);
          }
      
          $islandConfig = json_decode($raw, true);
          if (!is_array($islandConfig)) {
              // Malformed config.json — skip it
              echo "   ".$configFile." - invalid json\n";
              error_log("Invalid island config: $configFile");
              continue;
          }
      
          // Validate required fields
          if (empty($islandConfig['island_id']) ||
              empty($islandConfig['port'])) {
      
              error_log("Island config missing required fields: $configFile");
              echo "   ".$configFile." - missing fields\n";
              continue;
          }
      
          // Add useful metadata
          $islandConfig["data_path"] = $islandPath;
          $islandConfig["command"] = str_replace("{island_id}",$islandConfig["island_id"],$data["command_template"]);
      
          // Store it
          $this->islands[] = $islandConfig;
          echo "   ".$configFile." - loaded successfully\n";
          var_dump($islandConfig);
          echo "\n";
      }

    }

    // -----------------------------
    // Getters
    // -----------------------------

    public function getIpcHost(): string
    {
        return $this->ipcHost;
    }

    public function getIpcPort(): int
    {
        return $this->ipcPort;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getLogFile(string $name = 'supervisor.log'): string
    {
        return $this->logDir . "/" . $name;
    }

    public function getIslands(): array
    {
        return $this->islands;
    }
}
