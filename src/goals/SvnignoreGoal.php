<?php

/*
 * Task runner, code generator and build tool for easier continuos integration
 *
 * @link      https://hidev.me/
 * @package   hidev
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2014-2015, HiQDev (http://hiqdev.com/)
 */

namespace hidev\goals;

/**
 * Goal for svn:ignore.
 */
class SvnignoreGoal extends VcsignoreGoal
{
    protected $_fileType = 'svnignore';
}
