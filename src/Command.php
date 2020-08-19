<?php


namespace FuzzyFox\ComposerVersion;

use Composer\IO\IOInterface;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Composer\Command\BaseCommand;
use PHLAK\SemVer;

class Command extends BaseCommand
{
    private $composerJsonPath = null;

    private $keywords = [
        'from-git', 'major', 'minor', 'patch', 'premajor', 'preminor', 'prepatch', 'prerelease', 'devmajor', 'devminor',
        'devpatch',
    ];

    private $validPreIds = ['alpha', 'a', 'beta', 'b', 'patch', 'p', 'rc'];

    protected function configure()
    {
        $this->setName('version')
            ->addArgument('newversion', InputArgument::OPTIONAL,
                '<newversion> | '.join(' | ', $this->keywords))
            ->addOption('preid', null, InputOption::VALUE_REQUIRED, 'prerelease-id', 'alpha');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newversion = $input->getArgument('newversion');

        if (!in_array(strtolower($input->getOption('preid')), $this->validPreIds)) {
            throw new \InvalidArgumentException('preid must be one of: '.join(' | ', $this->validPreIds));
        }

        $version = $this->getCurrentVersion();
        $oldVersion = $this->getCurrentVersion();

        switch ($newversion) {
            case null:
                $output->writeln("<info>{$this->getComposer()->getPackage()->getName()}: {$version}</info>");
                $output->writeln("<info>Composer: {$this->getComposer()->getVersion()}</info>");
                return 0;
            case 'from-git':
                $version = $this->getCurrentVersionFromGit();
                if (!$version) {
                    $output->writeln('<error>Current commit does not have a valid version tag.</error>');
                    return 1;
                }
                break;
            case 'major':
                $version->incrementMajor();
                break;
            case 'minor':
                $version->incrementMinor();
                break;
            case 'patch':
                $version->incrementPatch();
                break;
            case 'premajor':
                $version->incrementMajor();
                $version->setPreRelease($input->getOption('preid').'.0');
                break;
            case 'preminor':
                $version->incrementMinor();
                $version->setPreRelease($input->getOption('preid').'.0');
                break;
            case 'prepatch':
                $version->incrementPatch();
                $version->setPreRelease($input->getOption('preid').'.0');
                break;
            case 'prerelease':
                $prerelease = $version->preRelease;

                if (!$prerelease) {
                    $prerelease = $input->getOption('preid');
                }

                $prerelease = explode('.', $prerelease, 2);
                $prerelease = $prerelease[0].'.'.((int) ($prerelease[1] ?? -1) + 1);
                $version->setPreRelease($prerelease);
                break;
            case 'devmajor':
                $version->incrementMajor();
                $version->setPreRelease('dev');
                break;
            case 'devminor':
                $version->incrementMinor();
                $version->setPreRelease('dev');
                break;
            case 'devpatch':
                $version->incrementPatch();
                $version->setPreRelease('dev');
                break;
            default:
                $version = $this->parseVersion($newversion);
        }

//        if (shell_exec('git status --untracked-files=no --porcelain')) {
//            $output->writeln('<error>You have uncommitted changes.</error>');
//            return 1;
//        }

        $output->writeln($oldVersion->prefix());

        if ($this->runScript('pre-version')) {
            $output->writeln('<error>pre-version script failed</error>');
            return 1;
        }

        $output->writeln($version->prefix());
        $this->setVersion($version);

        $this->resetComposer();

        if ($this->runScript('pre-version-commit')) {
            $output->writeln('<error>pre-version-commit script failed</error>');
            $this->setVersion($oldVersion);
            return 1;
        }

        shell_exec("git add {$this->getComposerJsonPath()}");
        shell_exec("git commit -m '{$version->prefix()}'");
        shell_exec("git tag {$version->prefix()}");

        if ($this->runScript('post-version')) {
            $output->writeln('<warning>post-version script failed</warning>');
        }

        $output->writeln("<info>New version: {$version->prefix()}</info>");

        return 0;
    }

    private function getCurrentVersion()
    {
        $currentVersion = null;

        $composerVersion = $this->getComposer()->getPackage()->getPrettyVersion();

        try {
            $currentVersion = $this->parseVersion($composerVersion);
        } catch (InvalidVersionException $e) {
            $currentVersion = $this->parseVersion('0.0.0');

            $this->getIO()->write('<warning>Version not currently defined, assuming 0.0.0</warning>', true,
                IOInterface::VERBOSE);
        }

        return $currentVersion;
    }

    /**
     * @param  string|null  $version
     * @return SemVer\Version
     * @throws InvalidVersionException
     */
    private function parseVersion($version)
    {
        return $currentVersion = new SemVer\Version($version);
    }

    private function runScript($scriptName)
    {
        $scripts = $this->getComposer()->getPackage()->getScripts();
        if (!array_key_exists($scriptName, $scripts)) {
            return null;
        }

        return $this->getComposer()->getEventDispatcher()->dispatchScript($scriptName, false, []);
    }

    private function setVersion(SemVer\Version $version)
    {
        $composerJson = $this->getComposerJson();
        $composerJson->version = $version->prefix();
        file_put_contents($this->getComposerJsonPath(),
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return mixed
     */
    private function getComposerJson()
    {
        return json_decode(file_get_contents($this->getComposerJsonPath()), false);
    }

    private function getComposerJsonPath()
    {
        if (!$this->composerJsonPath) {
            $path = $this->getComposer()->getConfig()->get('vendor-dir');
            $path = preg_split("/(\/|\\\)/", $path);
            array_splice($path, count($path) - 1, 1, 'composer.json');
            $this->composerJsonPath = join(DIRECTORY_SEPARATOR, $path);
        }

        return $this->composerJsonPath;
    }

    private function getCurrentVersionFromGit()
    {
        try {
            $tag = shell_exec('git describe --tags');
            if ($tag && $this->parseVersion($tag)) {
                return $this->parseVersion($tag);
            }
        } catch (InvalidVersionException $e) {
            // suppress exception
        }

        return null;
    }
}
