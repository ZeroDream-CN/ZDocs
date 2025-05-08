<?php
/* 站点信息配置 */
define('SITE_NAME', '这里是站点名字');
define('SITE_DESC', '这里是站点介绍');
define('SITE_URL', 'https://这里填写你的网站实际网址/');
define('STATIC_URL', false); // 是否启用伪静态

/* 数据库配置 */
define('DB_HOST', '数据库地址');
define('DB_USER', '数据库账号');
define('DB_PASS', '数据库密码');
define('DB_NAME', '数据库名称');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

/* 头像配置 */
define('AVATAR_API', 'https://cdn.sep.cc/avatar/%s?s=128');

/* 自动安装和注册用户（首次运行后请删除下面的内容） */
define('INSTALL_DB', true);
define('REGISTER_USER', '管理员用户名'); // 请勿使用除了英文、数字、字母、下划线、减号以外的字符，否则无法登陆
define('REGISTER_PASS', '管理员密码');
define('REGISTER_MAIL', '管理员邮箱');