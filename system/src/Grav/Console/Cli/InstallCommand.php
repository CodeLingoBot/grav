<?php
/**
 * @package    Grav.Console
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Console\Cli;

use Grav\Console\ConsoleCommand;
use RocketTheme\Toolbox\File\YamlFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends ConsoleCommand
{
    /**
     * @var
     */
    protected $config;
    /**
     * @var
     */
    protected $local_config;
    /**
     * @var
     */
    protected $destination;
    /**
     * @var
     */
    protected $user_path;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName("install")
            ->addOption(
                'symlink',
                's',
                InputOption::VALUE_NONE,
                'Symlink the required bits'
            )
            ->addArgument(
                'destination',
                InputArgument::OPTIONAL,
                'Where to install the required bits (default to current project)'
            )
            ->setDescription("Installs the dependencies needed by Grav. Optionally can create symbolic links")
            ->setHelp('The <info>install</info> command installs the dependencies needed by Grav. Optionally can create symbolic links');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $dependencies_file = '.dependencies';
        $this->destination = ($this->input->getArgument('destination')) ? $this->input->getArgument('destination') : ROOT_DIR;

        // fix trailing slash
        $this->destination = rtrim($this->destination, DS) . DS;
        $this->user_path = $this->destination . USER_PATH;
        if ($local_config_file = $this->loadLocalConfig()) {
            $this->output->writeln('Read local config from <cyan>' . $local_config_file . '</cyan>');
        }

        // Look for dependencies file in ROOT and USER dir
        if (file_exists($this->user_path . $dependencies_file)) {
            $file = YamlFile::instance($this->user_path . $dependencies_file);
        } elseif (file_exists($this->destination . $dependencies_file)) {
            $file = YamlFile::instance($this->destination . $dependencies_file);
        } else {
            $this->output->writeln('<red>ERROR</red> Missing .dependencies file in <cyan>user/</cyan> folder');
            if ($this->input->getArgument('destination')) {
                $this->output->writeln('<yellow>HINT</yellow> <info>Are you trying to install a plugin or a theme? Make sure you use <cyan>bin/gpm install <something></cyan>, not <cyan>bin/grav install</cyan>. This command is only used to install Grav skeletons.');
            } else {
                $this->output->writeln('<yellow>HINT</yellow> <info>Are you trying to install Grav? Grav is already installed. You need to run this command only if you download a skeleton from GitHub directly.');
            }

            return;
        }

        $this->config = $file->content();
        $file->free();

        // If yaml config, process
        if ($this->config) {
            if (!$this->input->getOption('symlink')) {
                // Updates composer first
                $this->output->writeln("\nInstalling vendor dependencies");
                $this->output->writeln($this->composerUpdate(GRAV_ROOT, 'install'));

                $this->gitclone();
            } else {
                $this->symlink();
            }
        } else {
            $this->output->writeln('<red>ERROR</red> invalid YAML in ' . $dependencies_file);
        }


    }

    /**
     * Clones from Git
     */
    

    /**
     * Symlinks
     */
    
}
