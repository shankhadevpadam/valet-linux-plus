<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;

class Pacman implements PackageManager
{
    public $cli;

    public $redisPackageName = 'redis';
    public $mysqlPackageName = 'mysql';
    public $mariaDBPackageName = 'mariadb';

    const SUPPORTED_PHP_VERSIONS = [
        'php',
    ];

    const SUPPORTED_PHP_SERVICE_PATTERN = 'php-fpm';

    const PHP_EXTENSION_PATTERN_BY_VERSION = [
        '8.2' => 'php',
    ];

    /**
     * Create a new Pacman instance.
     *
     * @param CommandLine $cli
     *
     * @return void
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Get array of installed packages.
     *
     * @param string $package
     *
     * @return array
     */
    public function packages($package)
    {
        $query = "pacman -Qqs {$package}";

        return explode(PHP_EOL, $this->cli->run($query));
    }

    /**
     * Determine if the given package is installed.
     *
     * @param string $package
     *
     * @return bool
     */
    public function installed($package)
    {
        return in_array($package, $this->packages($package));
    }

    /**
     * Ensure that the given package is installed.
     *
     * @param string $package
     *
     * @return void
     */
    public function ensureInstalled($package)
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given package and throw an exception on failure.
     *
     * @param string $package
     *
     * @return void
     */
    public function installOrFail($package)
    {
        output('<info>['.$package.'] is not installed, installing it now via Pacman...</info> 🍻');

        $this->cli->run(trim('pacman --noconfirm --needed -S '.$package), function ($exitCode, $errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Pacman was unable to install ['.$package.'].');
        });
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    public function setup()
    {
        // Nothing to do
    }

    /**
     * Restart dnsmasq in Ubuntu.
     */
    public function nmRestart($sm)
    {
        $sm->restart('NetworkManager');
    }

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $output = $this->cli->run('which pacman', function ($exitCode, $output) {
                throw new DomainException('Pacman not available');
            });

            return $output != '';
        } catch (DomainException $e) {
            return false;
        }
    }

    public function supportedPhpVersions()
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
    }

    public function getPhpServicePattern()
    {
        return self::SUPPORTED_PHP_SERVICE_PATTERN;
    }

    public function getPhpExtensionPattern($version)
    {
        return !empty(self::PHP_EXTENSION_PATTERN_BY_VERSION[$version])
            ? self::PHP_EXTENSION_PATTERN_BY_VERSION[$version] : 'php{VERSION_WITHOUT_DOT}';
    }
}
