<?php

namespace Tests\Groups;

class Creation extends \Tests\Groups\Base
{

    public function testCreatingTests()
    {
        echo "<pre>";
        $this->_session->start();

        $this->_signIn();

        vaR_dump($this->_session->getCurrentUrl());
    }

    protected function _signIn()
    {
        # Visit the admin page and fill out our username/password combo
        $base_url = \Tests\Loader::getUrl();
        $this->_session->visit($base_url."/admin");

        $page = $this->_session->getPage();

        # $page->findById("username")->setValue(\Tests\Loader::getUsername());
        # $page->findById("login")->setValue(\Tests\Loader::getPassword());

        $page->find("xpath", "//*[@id='loginForm']/div/div[5]/input")->click();

        // echo $page->getHtml();
        // $button = $this->_session->getDriver()->find('//*[@id="loginForm"]/div/div[5]/input');
        // var_dump( $button);
    }

}
