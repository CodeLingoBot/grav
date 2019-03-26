<?php
/**
 * @package    Grav.Console
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Console\Gpm;

use Grav\Common\GPM\GPM;
use Grav\Common\GPM\Installer;
use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UninstallCommand extends ConsoleCommand
{
    /**
     * @var
     */
    protected $data;

    /** @var GPM */
    protected $gpm;

    /**
     * @var
     */
    protected $destination;
    /**
     * @var
     */
    protected $file;
    /**
     * @var
     */
    protected $tmp;

    protected $dependencies= [];

    protected $all_yes;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName("uninstall")
            ->addOption(
                'all-yes',
                'y',
                InputOption::VALUE_NONE,
                'Assumes yes (or best approach) instead of prompting'
            )
            ->addArgument(
                'package',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The package(s) that are desired to be removed. Use the "index" command for a list of packages'
            )
            ->setDescription("Performs the uninstallation of plugins and themes")
            ->setHelp('The <info>uninstall</info> command allows to uninstall plugins and themes');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $this->gpm = new GPM();

        $this->all_yes = $this->input->getOption('all-yes');

        $packages = array_map('strtolower', $this->input->getArgument('package'));
        $this->data = ['total' => 0, 'not_found' => []];

        foreach ($packages as $package) {
            $plugin = $this->gpm->getInstalledPlugin($package);
            $theme = $this->gpm->getInstalledTheme($package);
            if ($plugin || $theme) {
                $this->data[strtolower($package)] = $plugin ?: $theme;
                $this->data['total']++;
            } else {
                $this->data['not_found'][] = $package;
            }
        }

        $this->output->writeln('');

        if (!$this->data['total']) {
            $this->output->writeln("Nothing to uninstall.");
            $this->output->writeln('');
            exit;
        }

        if (count($this->data['not_found'])) {
            $this->output->writeln("These packages were not found installed: <red>" . implode('</red>, <red>',
                    $this->data['not_found']) . "</red>");
        }

        unset($this->data['not_found']);
        unset($this->data['total']);

        foreach ($this->data as $slug => $package) {
            $this->output->writeln("Preparing to uninstall <cyan>" . $package->name . "</cyan> [v" . $package->version . "]");

            $this->output->write("  |- Checking destination...  ");
            $checks = $this->checkDestination($slug, $package);

            if (!$checks) {
                $this->output->writeln("  '- <red>Installation failed or aborted.</red>");
                $this->output->writeln('');
            } else {
                $uninstall = $this->uninstallPackage($slug, $package);

                if (!$uninstall) {
                    $this->output->writeln("  '- <red>Uninstallation failed or aborted.</red>");
                } else {
                    $this->output->writeln("  '- <green>Success!</green>  ");
                }
            }

        }

        // clear cache after successful upgrade
        $this->clearCache();
    }


    /**
     * @param $slug
     * @param $package
     *
     * @return bool
     */
    

    /**
     * @param $slug
     * @param $package
     *
     * @return bool
     */

    

    /**
     * Check if package exists
     *
     * @param $slug
     * @param $package
     * @return int
     */
    
}
