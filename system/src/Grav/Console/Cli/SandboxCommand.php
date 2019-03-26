<?php
/**
 * @package    Grav.Console
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Console\Cli;

use Grav\Console\ConsoleCommand;
use Grav\Common\Filesystem\Folder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SandboxCommand extends ConsoleCommand
{
    /**
     * @var array
     */
    protected $directories = [
        '/assets',
        '/backup',
        '/cache',
        '/images',
        '/logs',
        '/tmp',
        '/user/accounts',
        '/user/config',
        '/user/data',
        '/user/pages',
        '/user/plugins',
        '/user/themes',
    ];

    /**
     * @var array
     */
    protected $files = [
        '/.dependencies',
        '/.htaccess',
        '/user/config/site.yaml',
        '/user/config/system.yaml',
    ];

    /**
     * @var array
     */
    protected $mappings = [
        '/.gitignore'           => '/.gitignore',
        '/CHANGELOG.md'         => '/CHANGELOG.md',
        '/LICENSE.txt'          => '/LICENSE.txt',
        '/README.md'            => '/README.md',
        '/CONTRIBUTING.md'      => '/CONTRIBUTING.md',
        '/index.php'            => '/index.php',
        '/composer.json'        => '/composer.json',
        '/bin'                  => '/bin',
        '/system'               => '/system',
        '/vendor'               => '/vendor',
        '/webserver-configs'    => '/webserver-configs',
    ];

    /**
     * @var string
     */

    protected $default_file = "---\ntitle: HomePage\n---\n# HomePage\n\nLorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque porttitor eu felis sed ornare. Sed a mauris venenatis, pulvinar velit vel, dictum enim. Phasellus ac rutrum velit. Nunc lorem purus, hendrerit sit amet augue aliquet, iaculis ultricies nisl. Suspendisse tincidunt euismod risus, quis feugiat arcu tincidunt eget. Nulla eros mi, commodo vel ipsum vel, aliquet congue odio. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Pellentesque velit orci, laoreet at adipiscing eu, interdum quis nibh. Nunc a accumsan purus.";

    protected $source;
    protected $destination;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('sandbox')
            ->setDescription('Setup of a base Grav system in your webroot, good for development, playing around or starting fresh')
            ->addArgument(
                'destination',
                InputArgument::REQUIRED,
                'The destination directory to symlink into'
            )
            ->addOption(
                'symlink',
                's',
                InputOption::VALUE_NONE,
                'Symlink the base grav system'
            )
            ->setHelp("The <info>sandbox</info> command help create a development environment that can optionally use symbolic links to link the core of grav to the git cloned repository.\nGood for development, playing around or starting fresh");
        $this->source = getcwd();
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $this->destination = $this->input->getArgument('destination');

        // Symlink the Core Stuff
        if ($this->input->getOption('symlink')) {
            // Create Some core stuff if it doesn't exist
            $this->createDirectories();

            // Loop through the symlink mappings and create the symlinks
            $this->symlink();

            // Copy the Core STuff
        } else {
            // Create Some core stuff if it doesn't exist
            $this->createDirectories();

            // Loop through the symlink mappings and copy what otherwise would be symlinks
            $this->copy();
        }

        $this->pages();
        $this->initFiles();
        $this->perms();
    }

    /**
     *
     */
    

    /**
     *
     */
    

    /**
     *
     */
    

    /**
     *
     */
    

    /**
     *
     */
    

    /**
     *
     */
    

    /**
     *
     */
    
}
