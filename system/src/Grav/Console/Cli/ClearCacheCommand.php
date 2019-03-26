<?php
/**
 * @package    Grav.Console
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Console\Cli;

use Grav\Common\Cache;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputOption;

class ClearCacheCommand extends ConsoleCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('clear-cache')
            ->setAliases(['clearcache'])
            ->setDescription('Clears Grav cache')
            ->addOption('all', null, InputOption::VALUE_NONE, 'If set will remove all including compiled, twig, doctrine caches')
            ->addOption('assets-only', null, InputOption::VALUE_NONE, 'If set will remove only assets/*')
            ->addOption('images-only', null, InputOption::VALUE_NONE, 'If set will remove only images/*')
            ->addOption('cache-only', null, InputOption::VALUE_NONE, 'If set will remove only cache/*')
            ->addOption('tmp-only', null, InputOption::VALUE_NONE, 'If set will remove only tmp/*')
            ->setHelp('The <info>clear-cache</info> deletes all cache files');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $this->cleanPaths();
    }

    /**
     * loops over the array of paths and deletes the files/folders
     */
    
}

