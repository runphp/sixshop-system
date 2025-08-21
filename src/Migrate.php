<?php
declare(strict_types=1);

namespace SixShop\System;

use Phinx\Config\Config;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use Phinx\Util\Util;
use SixShop\Core\Helper;
use SixShop\System\Model\MigrationsModel;
use think\App;

class Migrate
{
    private string $moduleName;

    private string $path;
    protected ?array $migrations = null;
    private $input;
    private $output;

    protected AdapterInterface $adapter;

    protected App $app;

    public function __construct(App $app, string $moduleName)
    {
        $this->app = $app;
        $this->moduleName = $moduleName;
        $this->path = Helper::extension_path($this->moduleName) . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $this->migrations = $this->getMigrations();
        $this->input = null;
        $this->output = null;
    }

    public function install(): array
    {
        $migrations = $this->getMigrations();
        $versions = $this->getVersions();
        $currentVersion = $this->getCurrentVersion();
        if (empty($versions) && empty($migrations)) {
            return [];
        }
        ksort($migrations);
        $installVersions = [];
        foreach ($migrations as $migration) {
            if ($migration->getVersion() <= $currentVersion) {
                continue;
            }
            if (!in_array($migration->getVersion(), $versions)) {
                $installVersions[] = $migration->getVersion();
                $this->executeMigration($migration);
            }
        }
        return $installVersions;
    }

    public function uninstall(): void
    {
        $migrations = $this->getMigrations();
        $versionLog = $this->getVersionLog();
        $versions = array_keys($versionLog);

        ksort($migrations);
        sort($versions);
        if (empty($versions)) {
            return;
        }
        krsort($migrations);
        foreach ($migrations as $migration) {
            if (in_array($migration->getVersion(), $versions)) {
                if (isset($versionLog[$migration->getVersion()]) && 0 != $versionLog[$migration->getVersion()]['breakpoint']) {
                    break;
                }
                $this->executeMigration($migration, MigrationInterface::DOWN);
            }
        }
    }

    public function getMigrationList(): array
    {
        $migrations = $this->getMigrations();
        MigrationsModel::maker(function (MigrationsModel $model) {
            $model->setOption('suffix', $this->moduleName);
        });
        $versionLog =  MigrationsModel::column('*', 'version');
        foreach ($migrations as $key => $migration) {
            $migrations[$key] = $versionLog[$key] ?? ['version'=> $key];
        }
        return array_values($migrations);
    }

    protected function getMigrations(): ?array
    {
        if (null === $this->migrations) {
            if (!is_dir($this->path)) {
                return [];
            }
            $allFiles = array_diff(scandir($this->path), ['.', '..']);
            $phpFiles = [];
            foreach ($allFiles as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $phpFiles[] = $this->path . DIRECTORY_SEPARATOR . $file;
                }
            }

            // filter the files to only get the ones that match our naming scheme
            $fileNames = [];
            /** @var Migrator[] $versions */
            $versions = [];

            foreach ($phpFiles as $filePath) {
                if (Util::isValidMigrationFileName(basename($filePath))) {
                    $version = Util::getVersionFromFileName(basename($filePath));

                    if (isset($versions[$version])) {
                        throw new \InvalidArgumentException(sprintf('Duplicate migration - "%s" has the same version as "%s"', $filePath, $versions[$version]->getVersion()));
                    }

                    // convert the filename to a class name
                    $class = Util::mapFileNameToClassName(basename($filePath));

                    if (isset($fileNames[$class])) {
                        throw new \InvalidArgumentException(sprintf('Migration "%s" has the same name as "%s"', basename($filePath), $fileNames[$class]));
                    }

                    $fileNames[$class] = basename($filePath);

                    // load the migration file
                    /** @noinspection PhpIncludeInspection */
                    require_once $filePath;
                    if (!class_exists($class)) {
                        throw new \InvalidArgumentException(sprintf('Could not find class "%s" in file "%s"', $class, $filePath));
                    }

                    // instantiate it
                    $migration = new $class('default', $version, $this->input, $this->output);

                    if (!($migration instanceof AbstractMigration)) {
                        throw new \InvalidArgumentException(sprintf('The class "%s" in file "%s" must extend \Phinx\Migration\AbstractMigration', $class, $filePath));
                    }

                    $versions[$version] = $migration;
                }
            }

            ksort($versions);
            $this->migrations = $versions;
        }

        return $this->migrations;
    }

    protected function getVersions()
    {
        return $this->getAdapter()->getVersions();
    }

    protected function getVersionLog()
    {
        return $this->getAdapter()->getVersionLog();
    }

    public function getAdapter()
    {
        if (isset($this->adapter)) {
            return $this->adapter;
        }

        $options = $this->getDbConfig();

        $adapterFactory = AdapterFactory::instance();
        $adapterFactory->registerAdapter('mysql', ExtensionMysqlAdapter::class);
        $adapter = $adapterFactory->getAdapter($options['adapter'], $options);

        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            $adapter = $adapterFactory->getWrapper('prefix', $adapter);
        }


        $this->adapter = $adapter;

        return $adapter;
    }

    protected function getDbConfig(): array
    {
        $default = $this->app->config->get('database.default');

        $config = $this->app->config->get("database.connections.{$default}");

        if (0 == $config['deploy']) {
            $dbConfig = [
                'adapter' => $config['type'],
                'host' => $config['hostname'],
                'name' => $config['database'],
                'user' => $config['username'],
                'pass' => $config['password'],
                'port' => $config['hostport'],
                'charset' => $config['charset'],
                'suffix' => $config['suffix'] ?? '',
                'table_prefix' => $config['prefix'],
            ];
        } else {
            $dbConfig = [
                'adapter' => explode(',', $config['type'])[0],
                'host' => explode(',', $config['hostname'])[0],
                'name' => explode(',', $config['database'])[0],
                'user' => explode(',', $config['username'])[0],
                'pass' => explode(',', $config['password'])[0],
                'port' => explode(',', $config['hostport'])[0],
                'charset' => explode(',', $config['charset'])[0],
                'suffix' => explode(',', $config['suffix'] ?? '')[0],
                'table_prefix' => explode(',', $config['prefix'])[0],
            ];
        }

        $table = $this->app->config->get('database.extension_migration_table', 'migrations_' . $this->moduleName);

        $dbConfig['migration_table'] = $dbConfig['table_prefix'] . $table;
        $dbConfig['version_order'] = Config::VERSION_ORDER_CREATION_TIME;

        return $dbConfig;
    }

    protected function getCurrentVersion()
    {
        $versions = $this->getVersions();
        $version = 0;

        if (!empty($versions)) {
            $version = end($versions);
        }

        return $version;
    }

    protected function executeMigration(MigrationInterface $migration, $direction = MigrationInterface::UP)
    {

        $startTime = time();
        $direction = (MigrationInterface::UP === $direction) ? MigrationInterface::UP : MigrationInterface::DOWN;
        $migration->setMigratingUp($direction === MigrationInterface::UP);
        $migration->setAdapter($this->getAdapter());

        $migration->preFlightCheck();

        if (method_exists($migration, MigrationInterface::INIT)) {
            $migration->{MigrationInterface::INIT}();
        }

        // begin the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->beginTransaction();
        }

        // Run the migration
        if (method_exists($migration, MigrationInterface::CHANGE)) {
            if (MigrationInterface::DOWN === $direction) {
                // Create an instance of the ProxyAdapter so we can record all
                // of the migration commands for reverse playback
                /** @var \Phinx\Db\Adapter\ProxyAdapter $proxyAdapter */
                $proxyAdapter = AdapterFactory::instance()->getWrapper('proxy', $this->getAdapter());
                $migration->setAdapter($proxyAdapter);
                $migration->{MigrationInterface::CHANGE}();
                $proxyAdapter->executeInvertedCommands();
                $migration->setAdapter($this->getAdapter());
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                $migration->change();
            }
        } else {
            $migration->{$direction}();
        }

        // commit the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->commitTransaction();
        }

        $migration->postFlightCheck();

        // Record it in the database
        $this->getAdapter()
            ->migrated($migration, $direction, date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', time()));
    }
}