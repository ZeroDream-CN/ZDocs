<?php
if (!defined('ROOT_PATH')) die('Forbidden');

$breadcrums = $zdocs->GetBreadcrumb();
$categories = $zdocs->GetCategories();
if (isset($_GET['id']) && is_string($_GET['id']) && preg_match('/^\d+$/', $_GET['id'])) {
    $loadedArticle = $zdocs->GetArticle($_GET['id']);
} elseif (isset($_GET['category']) && is_string($_GET['category']) && preg_match('/^\d+$/', $_GET['category'])) {
    $loadedCategory = $zdocs->GetCategory($_GET['category']);
}
?>
<body>
    <div class="switch-theme">
        <i class="fas fa-sun"></i>
        <i class="fas fa-moon"></i>
    </div>
    <div class="popup-login">
        <div class="popup-login-title">
            <span class="popup-login-title-text">登录</span>
            <span class="popup-login-title-close"><i class="fas fa-times"></i></span>
        </div>
        <div class="popup-login-content">
            <form action="?action=login" method="post">
                <div class="form-group">
                    <label for="login-username">用户名</label>
                    <input type="text" class="form-control" id="login-username" name="username" placeholder="用户名" required>
                </div>
                <div class="form-group">
                    <label for="login-password">密码</label>
                    <input type="password" class="form-control" id="login-password" name="password" placeholder="密码" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">登录</button>
            </form>
        </div>
    </div>
    <?php if (isset($_SESSION['user'], $_SESSION['role'])) { ?>
        <?php if ($_SESSION['role'] == 'root') { ?>
        <div class="popup-admin">
            <div class="popup-admin-title">
                <span class="popup-admin-title-text">用户管理</span>
                <span class="popup-admin-title-close"><i class="fas fa-times"></i></span>
            </div>
            <div class="popup-admin-content">
                <div class="admin-user-list">
                    <h3>用户列表</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users = $zdocs->GetUsers();
                            foreach ($users as $user) {
                                $uid = $user['id'];
                                $username = htmlspecialchars($user['username']);
                                $email = htmlspecialchars($user['email']);
                                // 修改密码和删除用户
                                echo sprintf("<tr><td>%s</td><td>%s</td><td><button class=\"btn btn-warning btn-sm\" onclick=\"ChangePassword('%s')\">修改密码</button> <button class=\"btn btn-danger btn-sm\" onclick=\"DeleteUser('%s')\">删除用户</button></td></tr>", $username, $email, $uid, $uid);
                            }
                            ?>
                        </tbody>
                    </table>
                    <h3>添加用户</h3>
                    <form id="add-user-form">
                        <div class="row">
                            <div class="col s6" style="padding-left: 0px;">
                                <div class="form-group">
                                    <label for="add-user-username">用户名</label>
                                    <input type="text" class="form-control" id="add-user-username" name="username" placeholder="用户名" required>
                                </div>
                                <div class="form-group">
                                    <label for="add-user-password">密码</label>
                                    <input type="password" class="form-control" id="add-user-password" name="password" placeholder="密码" required>
                                </div>
                            </div>
                            <div class="col s6" style="padding-right: 0px;">
                                <div class="form-group">
                                    <label for="add-user-email">邮箱</label>
                                    <input type="email" class="form-control" id="add-user-email" name="email" placeholder="邮箱" required>
                                </div>
                                <div class="form-group">
                                    <label for="add-user-role">角色</label>
                                    <select class="form-control" id="add-user-role" name="role">
                                        <option value="root">超级管理员</option>
                                        <option value="admin">管理员</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">添加用户</button>
                    </form>
                </div>
            </div>
        </div>
        <?php } else { ?>
            <!-- 普通用户可以修改自己的密码 -->
            <div class="popup-admin">
                <div class="popup-admin-title">
                    <span class="popup-admin-title-text">修改密码</span>
                    <span class="popup-admin-title-close"><i class="fas fa-times"></i></span>
                </div>
                <div class="popup-admin-content">
                    <form id="change-password-form">
                        <div class="form-group">
                            <label for="change-password-old">旧密码</label>
                            <input type="password" class="form-control" id="change-password-old" name="old" placeholder="旧密码" required>
                        </div>
                        <div class="form-group">
                            <label for="change-password-new">新密码</label>
                            <input type="password" class="form-control" id="change-password-new" name="new" placeholder="新密码" required>
                        </div>
                        <div class="form-group">
                            <label for="change-password-new-confirm">确认新密码</label>
                            <input type="password" class="form-control" id="change-password-new-confirm" name="new_confirm" placeholder="确认新密码" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">修改密码</button>
                    </form>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
    <div class="main-container row">
        <div class="navbar">
            <span class="menu"><i class="fas fa-bars"></i></span>
            <span class="logo"><?php echo SITE_NAME; ?></span>
            <span class="page-breadcrumb">
                <?php
                if ($breadcrums) {
                    echo '<a href="/">首页</a> / ';
                    for ($i = 0; $i < count($breadcrums); $i++) {
                        $breadcrum = $breadcrums[$i];
                        if ($breadcrum['name']) {
                            echo sprintf('<a class="article-link" data-category-id="%s">%s</a>', $breadcrum['id'], $breadcrum['name']);
                            if ($i < count($breadcrums) - 1) {
                                echo ' / ';
                            }
                        } else {
                            echo sprintf('<a href="/article-%s.html" data-id="%s">%s</a>', $breadcrum['id'], $breadcrum['id'], $breadcrum['title']);
                        }
                    }
                } else {
                    echo '<a href="/">首页</a>';
                }
                ?>
            </span>
            <span class="login">
                <?php
                if (isset($_SESSION['user'])) {
                    $avatar = sprintf(AVATAR_API, md5($_SESSION['email']));
                    echo sprintf("<span class=\"login-info\"><a class=\"admin-btn\" href=\"javascript:ShowAdmin();\"><img src=\"%s\" class=\"login-avatar\" /> %s</a></span> | <a href=\"?action=logout\"><i class=\"fas fa-sign-out-alt\"></i>退出</a>", $avatar, $_SESSION['user']);
                } else {
                    echo '<a href="javascript:ShowLogin();"><b><i class="fas fa-sign-in-alt"></i>登录账户</b></a>';
                }
                ?>
            </span>
        </div>
        <div class="categories">
            <ul>
                <li class="category-title">目录</li>
                <?php
                $html = [];
                foreach ($categories as $category) {
                    $active = '';
                    if (isset($loadedArticle) && $loadedArticle['category'] == $category['id']) {
                        $active = ' class="active"';
                    }
                    if (isset($loadedCategory) && ($category['id'] == $loadedCategory['id'] || $category['id'] == $loadedCategory['parent'])) {
                        $active = ' class="active"';
                    }
                    $name = htmlspecialchars($category['name']);
                    $html[] = sprintf("<li%s><a data-category-id=\"%s\">%s</a>", $active, $category['id'], $name);
                    if (isset($category['subCategory'])) {
                        $html[] = '<ul>';
                        foreach ($category['subCategory'] as $subCategory) {
                            $active = '';
                            if (isset($loadedArticle) && $loadedArticle['category'] == $subCategory['id']) {
                                $active = ' class="active"';
                            }
                            if (isset($loadedCategory) && $subCategory['id'] == $loadedCategory['id']) {
                                $active = ' class="active"';
                            }
                            $name = htmlspecialchars($subCategory['name']);
                            $html[] = sprintf("<li%s><a data-category-id=\"%s\">%s</a>", $active, $subCategory['id'], $name);
                            // articles
                            $articles = $zdocs->GetArticles($subCategory['id']);
                            if ($articles) {
                                $html[] = '<ul>';
                                foreach ($articles as $article) {
                                    $active = '';
                                    if (isset($loadedArticle) && $loadedArticle['id'] == $article['id']) {
                                        $active = ' active';
                                    }
                                    $title = htmlspecialchars($article['title']);
                                    $html[] = sprintf("<li><a href=\"/article-%s.html\" class=\"article-link%s\" data-id=\"%s\">%s</a></li>", $article['id'], $active, $article['id'], $title);
                                }
                                $html[] = '</ul>';
                            }
                            $html[] = '</li>';
                        }
                        $html[] = '</ul>';
                    }
                    // articles
                    $articles = $zdocs->GetArticles($category['id']);
                    if ($articles) {
                        $html[] = '<ul>';
                        foreach ($articles as $article) {
                            $active = '';
                            if (isset($loadedArticle) && $loadedArticle['id'] == $article['id']) {
                                $active = ' active';
                            }
                            $title = htmlspecialchars($article['title']);
                            $html[] = sprintf("<li><a href=\"/article-%s.html\" class=\"article-link%s\" data-id=\"%s\">%s</a></li>", $article['id'], $active, $article['id'], $title);
                        }
                        $html[] = '</ul>';
                    }
                    $html[] = '</li>';
                }
                echo implode('', $html);
                ?>
            </ul>
            <?php if (isset($_SESSION['user'])) {
                echo '<div class="admin-action">
                    <button class="btn btn-block btn-primary" onclick="CreateCategory(false)">创建分类</button>
                </div>';
            } ?>
            <div class="login-info-mobile">
                <?php
                if (isset($_SESSION['user'])) {
                    echo sprintf("<p><span class=\"login-info\"><a class=\"admin-btn\" href=\"javascript:ShowAdmin();\"><img src=\"%s\" class=\"login-avatar\" /> %s</a></span></p><p><a href=\"?action=logout\"><button class=\"btn btn-primary btn-block\"><i class=\"fas fa-sign-out-alt\"></i>退出</a></button></p>", $avatar, $_SESSION['user']);
                } else {
                    echo '<p><a href="javascript:ShowLogin();"><button class="btn btn-primary btn-block"><i class="fas fa-user"></i>登录</button></a></p>';
                }
                ?>
            </div>
        </div>
        <div class="main-articles">
            <h1 class="article-title"><?php if (isset($loadedArticle) && $loadedArticle) {
                    echo htmlspecialchars($loadedArticle['title']);
                } elseif (isset($loadedCategory) && $loadedCategory) {
                    echo htmlspecialchars($loadedCategory['name']);
                } else {
                    echo '';
                }
            ?></h1>
            <div class="article-editor">
                <div id="article-editor-inst"></div>
                <div class="article-editor-btns">
                    <button id="article-editor-save" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                    <button id="article-editor-cancel" class="btn btn-warning"><i class="fas fa-times"></i> 取消</button>
                </div>
            </div>
            <div class="article-content">
                <?php if (isset($loadedCategory) && $loadedCategory) {
                    $articles = $zdocs->GetArticles($loadedCategory['id']);
                    if ($articles) {
                        echo sprintf('<h3>文章列表</h3><p>当前分类下共有 %d 篇文章</p><ol class="article-list">', count($articles));
                        foreach ($articles as $article) {
                            $title = htmlspecialchars($article['title']);
                            $link = STATIC_URL ? sprintf("article-%s.html", $article['id']) : sprintf("?id=%s", $article['id']);  // 修复文章链接
                            echo sprintf("<li><a href=\"%s\"><i class=\"fas fa-angle-right\"></i> %s</a></li>", $link, $title);
                        }
                        echo '</ol>';
                    } else {
                        echo '暂无文章';
                    }
                    if (isset($_SESSION['user'])) {
                        echo '<div class="admin-actions">
                            <hr>
                            <h3>管理员操作</h3>
                            <p>您可以对当前分类进行管理操作，鼠标拖动上方文章可进行排序</p>
                            <p>
                                <button class="btn btn-primary waves-effect waves-light" onclick="CreateArticle()">创建文章</button>
                                <button class="btn btn-warning waves-effect waves-light" onclick="CreateCategory(true)">创建子分类</button>
                                <button class="btn btn-danger waves-effect waves-light" onclick="DeleteCategory()">删除分类</button>
                            </p>
                        </div>';
                    }
                } else {
                    echo '';
                }

                if (isset($loadedArticle) && isset($_SESSION['user'])) {
                    echo '<div class="admin-actions">
                        <hr>
                        <h3>管理员操作</h3>
                        <p>您可以对当前文章进行管理操作</p>
                        <p>
                            <button class="btn btn-warning waves-effect waves-light" onclick="MoveArticle()">切换分类</button>
                            <button class="btn btn-danger waves-effect waves-light" onclick="DeleteArticle()">删除文章</button>
                        </p>
                    </div>';
                } ?>
            </div>
            <div class="article-catalog">
                <h3>目录</h3>
                <ul>
                    <li><a href="#test">test</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
<?php
$categoryId = isset($loadedCategory) ? Intval($loadedCategory['id']) : 0;
$articleId = isset($loadedArticle) ? Intval($loadedArticle['id']) : 0;
if (isset($_SESSION['user'])) {
    $username = $_SESSION['user'];
    $username = str_replace("'", "\'", $username);
    echo sprintf(<<<SCRIPT
        <script>
        var loginInfo = {
            username: '%s',
            category: %d,
            article: %d
        }
        </script>
        SCRIPT, $username, $categoryId, $articleId);
}

echo sprintf(<<<SCRIPT
    <script>
    var pageInfo = {
        category: %d,
        article: %d
    }
    </script>
    SCRIPT, $categoryId, $articleId);
