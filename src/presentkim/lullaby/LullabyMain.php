<?php

namespace presentkim\lullaby;

use pocketmine\command\{
  CommandExecutor, PluginCommand
};
use pocketmine\entity\Attribute;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use presentkim\lullaby\{
  listener\PlayerEventListener, command\CommandListener, util\Translation
};

class LullabyMain extends PluginBase{

    /** @var self */
    private static $instance = null;

    /** @var PluginCommand[] */
    private $commands = [];

    /** @return self */
    public static function getInstance() : self{
        return self::$instance;
    }

    public function onLoad() : void{
        if (self::$instance === null) {
            // register instance
            self::$instance = $this;

            // load utils
            $this->getServer()->getLoader()->loadClass('presentkim\lullaby\util\Utils');
        }
    }

    public function onEnable() : void{
        $this->load();

        // register event listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener(), $this);
    }

    public function onDisable() : void{
        $this->save();
    }

    public function load() : void{
        $dataFolder = $this->getDataFolder();
        if (!file_exists($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }

        // load db
        $this->saveDefaultConfig();
        $this->reloadConfig();

        // load lang
        $langfilename = $dataFolder . 'lang.yml';
        if (!file_exists($langfilename)) {
            Translation::loadFromResource($this->getResource('lang/eng.yml'));
            Translation::save($langfilename);
        } else {
            Translation::load($langfilename);
        }

        // unregister commands
        foreach ($this->commands as $command) {
            $this->getServer()->getCommandMap()->unregister($command);
        }
        $this->commands = [];

        // register commands
        $this->registerCommand(new CommandListener($this), Translation::translate('command-lullaby'), 'Lullaby', 'lullaby.cmd', Translation::translate('command-lullaby@description'), Translation::translate('command-lullaby@usage'), Translation::getArray('command-lullaby@aliases'));
    }

    public function save() : void{
        $dataFolder = $this->getDataFolder();
        if (!file_exists($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }

        // save db
        $this->saveConfig();

        // save lang
        $langfilename = $dataFolder . 'lang.yml';
        if (!file_exists($langfilename)) {
            Translation::loadFromResource($this->getResource('lang/eng.yml'));
            Translation::save($langfilename);
        } else {
            Translation::load($langfilename);
        }
    }

    /**
     * @param CommandExecutor $executor
     * @param                 $name
     * @param                 $fallback
     * @param                 $permission
     * @param string          $description
     * @param null            $usageMessage
     * @param array|null      $aliases
     */
    private function registerCommand(CommandExecutor $executor, $name, $fallback, $permission, $description = "", $usageMessage = null, array $aliases = null) : void{
        $command = new PluginCommand($name, $this);
        $command->setExecutor($executor);
        $command->setPermission($permission);
        $command->setDescription($description);
        $command->setUsage($usageMessage ?? ('/' . $name));
        if (is_array($aliases)) {
            $command->setAliases($aliases);
        }

        $this->getServer()->getCommandMap()->register($fallback, $command);
        $this->commands[] = $command;
    }
}