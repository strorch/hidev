<?php

/*
 * Task runner, code generator and build tool for easier continuos integration
 *
 * @link      https://github.com/hiqdev/hidev
 * @package   hidev
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2016, HiQDev (http://hiqdev.com/)
 */

namespace hidev\controllers;

use yii\helpers\Json;

/**
 * Goal for GitHub.
 */
class GithubController extends CommonController
{
    protected $_name;
    protected $_vendor;
    protected $_package;
    protected $_description;

    /**
     * @var string GitHub OAuth access token
     */
    protected $_token;

    public function setName($value)
    {
        list($vendor, $package) = explode('/', $value, 2);
        $this->_name    = $value;
        $this->_vendor  = $vendor ?: $package;
        $this->_package = $package ?: $vendor;
    }

    public function getName()
    {
        if ($this->_name === null) {
            $this->setName($this->takePackage()->fullName);
        }

        return $this->_name;
    }

    public function setVendor($value)
    {
        $this->_vendor = $value;
    }

    public function getVendor()
    {
        if ($this->_vendor === null) {
            $this->_vendor = $this->takeVendor()->name;
        }

        return $this->_vendor;
    }

    public function setPackage($value)
    {
        $this->_package = $value;
    }

    public function getPackage()
    {
        if ($this->_package === null) {
            $this->_package = $this->takePackage()->name;
        }

        return $this->_package;
    }

    public function setDescription($value)
    {
        $this->_description = $value;
    }

    public function getDescription()
    {
        if ($this->_description === null) {
            $this->_description = $this->takePackage()->getTitle();
        }

        return $this->_description;
    }

    /**
     * Create the repo on GitHub.
     * @return int exit code
     */
    public function actionCreate()
    {
        return $this->request('POST', '/orgs/' . $this->getVendor() . '/repos', [
            'name'        => $this->getPackage(),
            'description' => $this->getDescription(),
        ]);
    }

    /**
     * Clone repo from github.
     * TODO this action must be run without `start`.
     * @param string $repo full name vendor/package
     * @return int exit code
     */
    public function actionClone($repo)
    {
        return $this->passthru('git', ['clone', 'git@github.com:' . $repo]);
    }

    /**
     * Check if repo exists.
     * @param string $repo full name vendor/package defaults to this repo name
     * @return int exit code
     */
    public function actionExists($repo = null)
    {
        return $this->exec('git', ['ls-remote', 'git@github.com:' . ($repo ?: $this->getName())], true);
    }

    public function actionRelease($version = null)
    {
        $this->runRequest('CHANGELOG.md');
        $changelog = $this->takeGoal('CHANGELOG.md');
        $notes = reset($changelog->getFile()->getHandler()->releaseNotes);
        $version = $this->takeGoal('bump')->getVersion($version);
        $wait = $this->actionWaitPush();
        if ($wait) {
            return $wait;
        }

        return $this->request('POST', '/repos/' . $this->getName() . '/releases', [
            'tag_name'  => $version,
            'name'      => $version,
            'body'      => $notes,
        ]);
    }

    /**
     * Waits until push is actually finished.
     * TODO Check github if it really has latest local commit.
     * @return int 0 - success, 1 - failed
     */
    public function actionWaitPush()
    {
        sleep(7);

        return 0;
    }

    public function request($method, $path, $data)
    {
        $url = 'https://api.github.com' . $path;

        return $this->passthru('curl', ['-X', $method, '-H', 'Authorization: token ' . $this->getToken(), '--data', Json::encode($data), $url]);
    }

    public function findToken()
    {
        return $_SERVER['GITHUB_TOKEN'] ?: $this->readpassword('GitHub token:');
    }

    public function getToken()
    {
        if ($this->_token === null) {
            $this->_token = $this->findToken();
        }

        return $this->_token;
    }
}
