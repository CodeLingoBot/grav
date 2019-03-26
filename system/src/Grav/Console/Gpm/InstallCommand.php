<?php
/**
 * @package    Grav.Console
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Console\Gpm;

use Grav\Common\Filesystem\Folder;
use Grav\Common\GPM\GPM;
use Grav\Common\GPM\Installer;
use Grav\Common\GPM\Licenses;
use Grav\Common\GPM\Response;
use Grav\Common\GPM\Remote\Package as Package;
use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

define('GIT_REGEX', '/http[s]?:\/\/(?:.*@)?(github|bitbucket)(?:.org|.com)\/.*\/(.*)/');

class InstallCommand extends ConsoleCommand
{
    /** @var */
    protected $data;

    /** @var GPM */
    protected $gpm;

    /** @var */
    protected $destination;

    /** @var */
    protected $file;

    /** @var */
    protected $tmp;

    /** @var */
    protected $local_config;

    /** @var bool */
    protected $use_symlinks;

    /** @var array */
    protected $demo_processing = [];

    protected $all_yes;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName("install")
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force re-fetching the data from remote'
            )
            ->addOption(
                'all-yes',
                'y',
                InputOption::VALUE_NONE,
                'Assumes yes (or best approach) instead of prompting'
            )
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_OPTIONAL,
                'The destination where the package should be installed at. By default this would be where the grav instance has been launched from',
                GRAV_ROOT
            )
            ->addArgument(
                'package',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Package(s) to install. Use "bin/gpm index" to list packages. Use "bin/gpm direct-install" to install a specific version'
            )
            ->setDescription("Performs the installation of plugins and themes")
            ->setHelp('The <info>install</info> command allows to install plugins and themes');
    }

    /**
     * Allows to set the GPM object, used for testing the class
     *
     * @param $gpm
     */
    public function setGpm($gpm)
    {
        $this->gpm = $gpm;
    }

    /**
     * @return bool
     */
    protected function serve()
    {
        $this->gpm = new GPM($this->input->getOption('force'));

        $this->all_yes = $this->input->getOption('all-yes');

        $this->displayGPMRelease();

        $this->destination = realpath($this->input->getOption('destination'));

        $packages = array_map('strtolower', $this->input->getArgument('package'));
        $this->data = $this->gpm->findPackages($packages);
        $this->loadLocalConfig();

        if (
            !Installer::isGravInstance($this->destination) ||
            !Installer::isValidDestination($this->destination, [Installer::EXISTS, Installer::IS_LINK])
        ) {
            $this->output->writeln("<red>ERROR</red>: " . Installer::lastErrorMsg());
            exit;
        }

        $this->output->writeln('');

        if (!$this->data['total']) {
            $this->output->writeln("Nothing to install.");
            $this->output->writeln('');
            exit;
        }

        if (count($this->data['not_found'])) {
            $this->output->writeln("These packages were not found on Grav: <red>" . implode('</red>, <red>',
                    array_keys($this->data['not_found'])) . "</red>");
        }

        unset($this->data['not_found']);
        unset($this->data['total']);


        if (isset($this->local_config)) {
            // Symlinks available, ask if Grav should use them
            $this->use_symlinks = false;
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Should Grav use the symlinks if available? [y|N] ', false);

            $answer = $this->all_yes ? false : $helper->ask($this->input, $this->output, $question);

            if ($answer) {
                $this->use_symlinks = true;
            }


        }

        $this->output->writeln('');

        try {
            $dependencies = $this->gpm->getDependencies($packages);
        } catch (\Exception $e) {
            //Error out if there are incompatible packages requirements and tell which ones, and what to do
            //Error out if there is any error in parsing the dependencies and their versions, and tell which one is broken
            $this->output->writeln("<red>" . $e->getMessage() . "</red>");
            return false;
        }

        if ($dependencies) {
            try {
                $this->installDependencies($dependencies, 'install', "The following dependencies need to be installed...");
                $this->installDependencies($dependencies, 'update',  "The following dependencies need to be updated...");
                $this->installDependencies($dependencies, 'ignore',  "The following dependencies can be updated as there is a newer version, but it's not mandatory...", false);
            } catch (\Exception $e) {
                $this->output->writeln("<red>Installation aborted</red>");
                return false;
            }

            $this->output->writeln("<green>Dependencies are OK</green>");
            $this->output->writeln("");
        }


        //We're done installing dependencies. Install the actual packages
        foreach ($this->data as $data) {
            foreach ($data as $package_name => $package) {
                if (array_key_exists($package_name, $dependencies)) {
                    $this->output->writeln("<green>Package " . $package_name . " already installed as dependency</green>");
                } else {
                    $is_valid_destination = Installer::isValidDestination($this->destination . DS . $package->install_path);
                    if ($is_valid_destination || Installer::lastErrorCode() == Installer::NOT_FOUND) {
                        $this->processPackage($package, false);
                    } else {
                        if (Installer::lastErrorCode() == Installer::EXISTS) {

                            try {
                                $this->askConfirmationIfMajorVersionUpdated($package);
                                $this->gpm->checkNoOtherPackageNeedsThisDependencyInALowerVersion($package->slug, $package->available, array_keys($data));
                            } catch (\Exception $e) {
                                $this->output->writeln("<red>" . $e->getMessage() . "</red>");
                                return false;
                            }

                            $helper = $this->getHelper('question');
                            $question = new ConfirmationQuestion("The package <cyan>$package_name</cyan> is already installed, overwrite? [y|N] ", false);
                            $answer = $this->all_yes ? true : $helper->ask($this->input, $this->output, $question);

                            if ($answer) {
                                $is_update = true;
                                $this->processPackage($package, $is_update);
                            } else {
                                $this->output->writeln("<yellow>Package " . $package_name . " not overwritten</yellow>");
                            }
                        } else {
                            if (Installer::lastErrorCode() == Installer::IS_LINK) {
                                $this->output->writeln("<red>Cannot overwrite existing symlink for </red><cyan>$package_name</cyan>");
                                $this->output->writeln("");
                            }
                        }
                    }
                }
            }
        }

        if (count($this->demo_processing) > 0) {
            foreach ($this->demo_processing as $package) {
                $this->installDemoContent($package);
            }
        }

        // clear cache after successful upgrade
        $this->clearCache();

        return true;
    }

    /**
     * If the package is updated from an older major release, show warning and ask confirmation
     *
     * @param $package
     */
    public function askConfirmationIfMajorVersionUpdated($package)
    {
        $helper = $this->getHelper('question');
        $package_name = $package->name;
        $new_version = $package->available ? $package->available : $this->gpm->getLatestVersionOfPackage($package->slug);
        $old_version = $package->version;

        $major_version_changed = explode('.', $new_version)[0] !== explode('.', $old_version)[0];

        if ($major_version_changed) {
            if ($this->all_yes) {
                $this->output->writeln("The package <cyan>$package_name</cyan> will be updated to a new major version <green>$new_version</green>, from <magenta>$old_version</magenta>");
                return;
            }

            $question = new ConfirmationQuestion("The package <cyan>$package_name</cyan> will be updated to a new major version <green>$new_version</green>, from <magenta>$old_version</magenta>. Be sure to read what changed with the new major release. Continue? [y|N] ", false);

            if (!$helper->ask($this->input, $this->output, $question)) {
                $this->output->writeln("<yellow>Package " . $package_name . " not updated</yellow>");
                exit;
            }
        }
    }

    /**
     * Given a $dependencies list, filters their type according to $type and
     * shows $message prior to listing them to the user. Then asks the user a confirmation prior
     * to installing them.
     *
     * @param array  $dependencies The dependencies array
     * @param string $type         The type of dependency to show: install, update, ignore
     * @param string $message      A message to be shown prior to listing the dependencies
     * @param bool   $required     A flag that determines if the installation is required or optional
     *
     * @throws \Exception
     */
    public function installDependencies($dependencies, $type, $message, $required = true)
    {
        $packages = array_filter($dependencies, function ($action) use ($type) { return $action === $type; });
        if (count($packages) > 0) {
            $this->output->writeln($message);

            foreach ($packages as $dependencyName => $dependencyVersion) {
                $this->output->writeln("  |- Package <cyan>" . $dependencyName . "</cyan>");
            }

            $this->output->writeln("");

            $helper = $this->getHelper('question');

            if ($type == 'install') {
                $questionAction = 'Install';
            } else {
                $questionAction = 'Update';
            }

            if (count($packages) == 1) {
                $questionArticle = 'this';
            } else {
                $questionArticle = 'these';
            }

            if (count($packages) == 1) {
                $questionNoun = 'package';
            } else {
                $questionNoun = 'packages';
            }

            $question = new ConfirmationQuestion("$questionAction $questionArticle $questionNoun? [Y|n] ", true);
            $answer = $this->all_yes ? true : $helper->ask($this->input, $this->output, $question);

            if ($answer) {
                foreach ($packages as $dependencyName => $dependencyVersion) {
                    $package = $this->gpm->findPackage($dependencyName);
                    $this->processPackage($package, ($type == 'update') ? true : false);
                }
                $this->output->writeln('');
            } else {
                if ($required) {
                    throw new \Exception();
                }
            }
        }
    }

    /**
     * @param      $package
     * @param bool $is_update      True if the package is an update
     */
    

    /**
     * Add package to the queue to process the demo content, if demo content exists
     *
     * @param $package
     */
    

    /**
     * Prompt to install the demo content of a package
     *
     * @param $package
     */
    

    /**
     * @param $package
     *
     * @return array|bool
     */
    

    /**
     * @param $package
     *
     * @return bool|string
     */
    

    /**
     * @param      $package
     */
    

    /**
     * @param      $package
     * @param bool $is_update
     *
     * @return bool
     */
    

    /**
     * @param Package $package
     *
     * @param string    $license
     *
     * @return string
     */
    

    /**
     * @param      $package
     *
     * @return bool
     */
    

    /**
     * Install a package
     *
     * @param Package $package
     * @param bool    $is_update True if it's an update. False if it's an install
     *
     * @return bool
     */
    

    /**
     * @param $progress
     */
    public function progress($progress)
    {
        $this->output->write("\x0D");
        $this->output->write("  |- Downloading package... " . str_pad($progress['percent'], 5, " ",
                STR_PAD_LEFT) . '%');
    }
}
