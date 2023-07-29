<?php

return array (
  'name' => '代码分支控制',
  'beian' => '',
  'cdnurl' => '',
  'version' => '1.0.1',
  'timezone' => 'Asia/Shanghai',
  'forbiddenip' => '',
  'languages' => 
  array (
    'backend' => 'zh-cn',
    'frontend' => 'zh-cn',
  ),
  'fixedpage' => 'dashboard',
  'categorytype' => 
  array (
    'default' => '默认',
    'page' => '单页',
    'article' => '文章',
    'test' => 'Test',
  ),
  'configgroup' => 
  array (
    'basic' => '基础配置',
    'email' => '邮件配置',
    'dictionary' => '字典配置',
    'user' => '会员配置',
    'gitinfo' => 'Git服务端配置',
    'branchcontrol' => '分支控制配置',
  ),
  'mail_type' => '1',
  'mail_smtp_host' => 'smtp.qq.com',
  'mail_smtp_port' => '465',
  'mail_smtp_user' => '10000',
  'mail_smtp_pass' => 'password',
  'mail_verify_type' => '2',
  'mail_from' => '10000@qq.com',
  'attachmentcategory' => 
  array (
    'category1' => '分类一',
    'category2' => '分类二',
    'custom' => '自定义',
  ),
  'branchcontrol_true' => '1',
  'gitinfo_gitlabip' => '192.168.2.115',
  'gitinfo_gitlabpath' => '/var/opt/gitlab',
  'gitinfo_gitres' => '/var/opt/gitlab/git-data/repositories/@hashed',
  'branchcontrol_variable' => 
  array (
    'type' => 'feature',
    'username' => '${user}',
    'version' => '${version1}',
  ),
);
