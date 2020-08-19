<?php


namespace FuzzyFox\ComposerVersion;

use Composer\Composer;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable, CommandProvider
{
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public function getCapabilities()
    {
        return [CommandProvider::class => static::class];
    }

    public function getCommands()
    {
        return [new Command()];
    }
}
