<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'graphql:publish {--force : Overwrite any existing files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes GraphQL configuration file to config directory of app';

    /**
     * Filesystem instance for fs operations.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * A list of files (source => destination).
     *
     * @var array<string,string>
     */
    protected $fileMap = [];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;

        $fromPath = __DIR__ . '/../..';
        $this->fileMap = [
            $fromPath . '/config/config.php' => app()->basePath('config/graphql.php'),
            $fromPath . '/resources/views/graphiql.php' => app()->basePath('resources/views/vendor/graphql/graphiql.php'),
        ];
    }

    public function handle(): void
    {
        foreach ($this->fileMap as $from => $to) {
            if ($this->files->exists($to) && !$this->option('force')) {
                continue;
            }
            $this->createParentDirectory(\dirname($to));
            $this->files->copy($from, $to);
            $this->status($from, $to);
        }
    }

    /**
     * Create the directory to house the published files if needed.
     */
    protected function createParentDirectory(string $directory): void
    {
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Write a status message to the console.
     */
    protected function status(string $from, string $to): void
    {
        $this->line("<info>Copied File</info> <comment>[$from]</comment> <info>To</info> <comment>[$to]</comment>");
    }
}
