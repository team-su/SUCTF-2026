<?php
return array (
  'ADMIN_LOGIN_NAME' => '',
  'URL_MODEL' => '1',
  'URL_HTML_SUFFIX' => 'html',
  'LANG_AUTO_DETECT' => 0,
  'DEFAULT_LANG' => 'cn',
  'HOME_DEFAULT_THEME' => 'Default',
  'WAP_DEFAULT_THEME' => 'Default',
  'ADMIN_DEFAULT_THEME' => 'Default',
  'MEMBER_DEFAULT_THEME' => 'Default',
  'LANG_LIST' => 
  array (
    'cn' => 
    array (
      'LanguageID' => '1',
      'LanguageName' => '中文',
      'LanguageMark' => 'cn',
      'LanguageDomain' => '',
    ),
  ),
  'APP_SUB_DOMAIN_RULES' => NULL,
  'HTML_CACHE_ON' => false,
  'HTML_CACHE_RULES' => 
  array (
    'index:index' => 
    array (
      0 => '{:group}/index_{0|get_language_mark}',
      1 => '0',
    ),
    'channel:index' => 
    array (
      0 => '{:group}/channel/{id}{jobid}{infoid}_{0|get_language_mark}_{0|get_para}',
      1 => '0',
    ),
    'info:read' => 
    array (
      0 => '{:group}/info/{id}_{0|get_para}',
      1 => '0',
    ),
  ),
);