<?php
declare(strict_types=1);

namespace SixShop\System\Command;

use Ergebnis\Classy\Constructs;
use SixShop\Core\Helper;
use think\console\Command;
use think\console\input\Argument;
use think\console\Input\InputOption;

class ModelPropertyCommand extends Command
{
    public function configure(): void
    {
        // Annotation for Model Properties
        $this->setName('amp:property')
            ->setDescription('Set the model property for extensions')
            ->addArgument('module', Argument::REQUIRED, 'The module name to process')
            ->addOption('all', 'a', Argument::OPTIONAL, 'Process all modules');
    }

    public function handle(): void
    {
        $modules = [];
        if ($this->input->getOption('all')) {
            $modules = module_name_list();
        } else {
            $modules[] = $this->input->getArgument('module');
        }

        foreach ($modules as $module) {
            $modelDir = Helper::extension_path($module . '/src/Model');
            $this->output->info("Generating model property for model directory: $modelDir");
            if (!file_exists($modelDir)) {
                $this->output->error("Model directory does not exist: $modelDir");
                continue;
            }
            $constructs = Constructs::fromDirectory($modelDir);
            foreach ($constructs as $construct) {
                $this->output->info("Generating model property for model: " . $construct->name());
                $this->getConsole()->call('ide-helper:model', ['--overwrite' => true, 'model' => $construct->name()]);
            }
        }
    }
}