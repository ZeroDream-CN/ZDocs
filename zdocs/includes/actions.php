<?php
if (!defined('ROOT_PATH')) die('Forbidden');

RegisterAction('login', function() {
    global $zdocs;
    if (!isset($_POST['username']) || !is_string($_POST['username']) || !isset($_POST['password']) || !is_string($_POST['password'])) {
        JsonOutput(false, '用户名和密码不能为空');
        exit;
    }
    if (empty($_POST['username']) || empty($_POST['password'])) {
        JsonOutput(false, '用户名和密码不能为空');
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9\_\-]{1,32}$/', $_POST['username']) && !filter_var($_POST['username'], FILTER_VALIDATE_EMAIL)) {
        JsonOutput(false, '用户名或邮箱格式错误');
        exit;
    }
    $stmt = $zdocs->pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
    $stmt->execute(['username' => $_POST['username']]);
    $user = $stmt->fetch();
    if ($user) {
        $salt = $user['salt'];
        if (password_verify($_POST['password'] . $salt, $user['password'])) {
            $_SESSION['user']  = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['uid']   = $user['id'];
            $_SESSION['role']  = $user['role'];
            JsonOutput(true, '登录成功');
        } else {
            JsonOutput(false, '用户名或密码错误');
        }
    } else {
        JsonOutput(false, '用户名或密码错误');
    }
});

RegisterAction('logout', function() {
    unset($_SESSION['user']);
    Header(sprintf('Location: %s', SITE_URL));
});

RegisterAction('changePassword', function() {
    global $zdocs;
    if (isset($_POST['uid']) && is_string($_POST['uid']) && preg_match('/^\d+$/', $_POST['uid']) && isset($_POST['password']) && is_string($_POST['password'])) {
        $stmt = $zdocs->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $_POST['uid']]);
        $user = $stmt->fetch();
        if ($user) {
            $salt = $user['salt'];
            $stmt = $zdocs->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(['password' => password_hash($_POST['password']. $salt, PASSWORD_BCRYPT), 'id' => $_POST['uid']]);
            JsonOutput(true, '密码已更新');
        } else {
            JsonOutput(false, '用户不存在');
        }
    } else {
        JsonOutput(false, '参数错误');
    }
}, true, true);

RegisterAction('changeSelfPassword', function() {
    global $zdocs;
    if (isset($_POST['oldPassword']) && is_string($_POST['oldPassword']) && isset($_POST['newPassword']) && is_string($_POST['newPassword'])) {
        $stmt = $zdocs->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['uid']]);
        $user = $stmt->fetch();
        if ($user) {
            $salt = $user['salt'];
            if (password_verify($_POST['oldPassword']. $salt, $user['password'])) {
                $stmt = $zdocs->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute(['password' => password_hash($_POST['newPassword']. $salt, PASSWORD_BCRYPT), 'id' => $_SESSION['uid']]);
                JsonOutput(true, '密码已更新');
            } else {
                JsonOutput(false, '原密码错误');
            }
        } else {
            JsonOutput(false, '用户不存在');
        }
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('deleteUser', function() {
    global $zdocs;
    if (isset($_POST['uid']) && is_string($_POST['uid']) && preg_match('/^\d+$/', $_POST['uid'])) {
        $stmt = $zdocs->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $_POST['uid']]);
        $user = $stmt->fetch();
        if ($user && $user['role'] !== 'root') {
            $stmt = $zdocs->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $_POST['uid']]);
            JsonOutput(true, '用户已删除');
        } else {
            JsonOutput(false, '用户不存在或无法删除');
        }
    } else {
        JsonOutput(false, '参数错误');
    }
}, true, true);

RegisterAction('addUser', function() {
    global $zdocs;
    $requireFields = [
        'username',
        'email',
        'password',
        'role'
    ];
    foreach ($requireFields as $field) {
        if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
            JsonOutput(false, '参数错误');
            exit;
        }
    }
    if (!preg_match('/^[a-zA-Z0-9\_\-]{1,32}$/', $_POST['username'])) {
        JsonOutput(false, '用户名格式错误，只能包含字母、数字、下划线和减号');
        exit;
    }
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        JsonOutput(false, '邮箱格式错误');
        exit;
    }
    if ($_POST['role'] !== 'root' && $_POST['role'] !== 'admin') {
        JsonOutput(false, '角色错误');
        exit;
    }
    $stmt = $zdocs->pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $_POST['username'], 'email' => $_POST['email']]);
    $user = $stmt->fetch();
    if ($user) {
        JsonOutput(false, '用户名或邮箱已被使用');
        exit;
    }
    $salt = substr(md5(microtime(true)), 0, 16);
    $stmt = $zdocs->pdo->prepare("INSERT INTO users (username, email, password, salt, role) VALUES (:username, :email, :password, :salt, :role)");
    $stmt->execute([
        'username' => $_POST['username'],
        'email'    => $_POST['email'],
        'password' => password_hash($_POST['password']. $salt, PASSWORD_BCRYPT),
        'salt'     => $salt,
        'role'     => $_POST['role']
    ]);
    JsonOutput(true, '用户已添加');
}, true, true);

RegisterAction('loadCategory', function() {
    global $zdocs;
    if (isset($_GET['id']) && is_string($_GET['id']) && preg_match('/^\d+$/', $_GET['id'])) {
        $loadedCategory = $zdocs->GetCategory($_GET['id']);
        $breadcrumb     = $zdocs->GetBreadcrumbByCategory($_GET['id']);
        $response       = [
            'breadcrumb' => $breadcrumb,
            'articles'   => []
        ];
        if ($loadedCategory) {
            $response['category'] = [
                'id'   => $loadedCategory['id'],
                'name' => $loadedCategory['name'],
            ];
            $articles = $zdocs->GetArticles($loadedCategory['id']);
            if ($articles) {
                foreach ($articles as $article) {
                    $title = htmlspecialchars($article['title']);
                    $response['articles'][] = [
                        'id'     => $article['id'],
                        'title'  => $title,
                        'author' => $article['author'],
                        'time'   => $article['time'],
                    ];
                }
            } else {
                $response['error'] = '暂无文章';
            }
        } else {
            $response['category'] = [
                'id'   => 0,
                'name' => '404 Not Found'
            ];
            $response['error'] = '分类不存在';
        }
        Header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        JsonOutput(false, '参数错误');
    }
});

RegisterAction('loadArticle', function() {
    global $zdocs;
    if (isset($_GET['id']) && is_string($_GET['id']) && preg_match('/^\d+$/', $_GET['id'])) {
        $loadedArticle = $zdocs->GetArticle($_GET['id']);
        $breadcrumb    = $zdocs->GetBreadcrumbByArticle($_GET['id']);
        $response      = [
            'breadcrumb' => $breadcrumb,
            'article'    => []
        ];
        if ($loadedArticle) {
            $response['article'] = [
                'id'      => $loadedArticle['id'],
                'title'   => $loadedArticle['title'],
                'author'  => $loadedArticle['author'],
                'time'    => $loadedArticle['time'],
                'data'    => $loadedArticle['data'],
                'content' => ($loadedArticle['content'] ?? '暂无内容')
            ];
        } else {
            $response['error'] = '文章不存在';
        }
        Header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        Header("Content-Type: application/json", true);
        echo json_encode(['success' => false, 'message' => '参数错误']);
    }
});

RegisterAction('loadRawArticleContent', function() {
    global $zdocs;
    if (isset($_GET['id']) && is_string($_GET['id']) && preg_match('/^\d+$/', $_GET['id'])) {
        $content = $zdocs->GetArticleContent($_GET['id']);
        if ($content) {
            $content = isset($_GET['escape']) && $_GET['escape'] === 'true' ? htmlspecialchars($content) : $content;
            JsonOutput(true, $content);
        } else {
            JsonOutput(false, '文章不存在');
        }
    } else {
        JsonOutput(false, '参数错误');
    }
});

RegisterAction('updateTitle', function() {
    global $zdocs;
    if (isset($_POST['type']) && is_string($_POST['type'])) {
        switch($_POST['type']) {
            case 'article':
                if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^\d+$/', $_POST['id']) && isset($_POST['value']) && is_string($_POST['value'])) {
                    $article = $zdocs->GetArticle($_POST['id']);
                    if ($article) {
                        $stmt = $zdocs->pdo->prepare("UPDATE articles SET title = :title WHERE id = :id");
                        $stmt->execute(['title' => $_POST['value'], 'id' => $_POST['id']]);
                        JsonOutput(true, '标题已更新');
                    } else {
                        JsonOutput(false, '文章不存在');
                    }
                } else {
                    JsonOutput(false, '参数错误');
                }
                break;
            case 'category':
                if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^\d+$/', $_POST['id']) && isset($_POST['value']) && is_string($_POST['value'])) {
                    $category = $zdocs->GetCategory($_POST['id']);
                    if ($category) {
                        $stmt = $zdocs->pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
                        $stmt->execute(['name' => $_POST['value'], 'id' => $_POST['id']]);
                        JsonOutput(true, '分类名已更新');
                    } else {
                        JsonOutput(false, '分类不存在');
                    }
                } else {
                    JsonOutput(false, '参数错误');
                }
                break;
        }
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('updateContent', function() {
    global $zdocs;
    if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^\d+$/', $_POST['id']) && isset($_POST['value']) && is_string($_POST['value'])) {
        $article = $zdocs->GetArticle($_POST['id']);
        if ($article) {
            $stmt = $zdocs->pdo->prepare("UPDATE articles SET content = :content WHERE id = :id");
            $stmt->execute(['content' => $_POST['value'], 'id' => $_POST['id']]);
            JsonOutput(true, '内容已更新');
        } else {
            JsonOutput(false, '文章不存在');
        }
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('createArticle', function() {
    global $zdocs;
    if (isset($_POST['category']) && is_string($_POST['category']) && preg_match('/^\d+$/', $_POST['category'])) {
        $stmt = $zdocs->pdo->prepare("INSERT INTO articles (category, title, content, author, time) VALUES (:category, :title, :content, :author, :time)");
        $stmt->execute([
            'category' => $_POST['category'],
            'title' => '新文章',
            'content' => '输入内容...',
            'author' => $_SESSION['user'],
            'time' => time()
        ]);
        $id = $zdocs->pdo->lastInsertId();
        JsonOutput(true, $id);
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('createCategory', function() {
    global $zdocs;
    $parent = isset($_POST['parent']) && is_string($_POST['parent']) && preg_match('/^\d+$/', $_POST['parent']) ? $_POST['parent'] : null;
    $name = isset($_POST['name']) && is_string($_POST['name']) && !empty($_POST['name']) ? $_POST['name'] : '新分类';
    // 不允许二次嵌套
    if ($parent) {
        $parentCategory = $zdocs->GetCategory($parent);
        if ($parentCategory && isset($parentCategory['parent'])) {
            JsonOutput(false, '不允许二次嵌套');
            exit;
        }
    }
    $stmt = $zdocs->pdo->prepare("INSERT INTO categories (parent, name) VALUES (:parent, :name)");
    $stmt->execute([
        'parent' => $parent,
        'name' => $name
    ]);
    $id = $zdocs->pdo->lastInsertId();
    JsonOutput(true, $id);
}, true);

RegisterAction('deleteArticle', function() {
    global $zdocs;
    if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^\d+$/', $_POST['id'])) {
        $stmt = $zdocs->pdo->prepare("DELETE FROM articles WHERE id = :id");
        $stmt->execute(['id' => $_POST['id']]);
        JsonOutput(true, '文章已删除');
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('deleteCategory', function() {
    global $zdocs;
    if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^\d+$/', $_POST['id'])) {
        $stmt = $zdocs->pdo->prepare("DELETE FROM articles WHERE category = :id");
        $stmt->execute(['id' => $_POST['id']]);
        $subCategories = $zdocs->pdo->prepare("SELECT id FROM categories WHERE parent = :id");
        $subCategories->execute(['id' => $_POST['id']]);
        $subCategories = $subCategories->fetchAll();
        foreach ($subCategories as $subCategory) {
            $stmt = $zdocs->pdo->prepare("DELETE FROM articles WHERE category = :id");
            $stmt->execute(['id' => $subCategory['id']]);
        }
        $stmt = $zdocs->pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute(['id' => $_POST['id']]);
        $stmt = $zdocs->pdo->prepare("DELETE FROM categories WHERE parent = :id");
        $stmt->execute(['id' => $_POST['id']]);
        JsonOutput(true, '分类已删除');
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('getCategories', function() {
    global $zdocs;
    $result = $zdocs->GetCategories();
    $categories = [];
    foreach ($result as $category) {
        $categories[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'parent' => $category['parent'] ?? 0,
            'subCategory' => $category['subCategory'] ?? []
        ];
    }
    $response = [
        'categories' => $categories
    ];
    if (count($categories) == 0) {
        $response['error'] = '暂无分类';
    }
    Header('Content-Type: application/json');
    echo json_encode($response);
});

RegisterAction('moveArticle', function() {
    global $zdocs;
    if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^\d+$/', $_POST['id']) && isset($_POST['category']) && is_string($_POST['category']) && preg_match('/^\d+$/', $_POST['category'])) {
        $article = $zdocs->GetArticle($_POST['id']);
        if ($article) {
            $stmt = $zdocs->pdo->prepare("UPDATE articles SET category = :category WHERE id = :id");
            $stmt->execute(['category' => $_POST['category'], 'id' => $_POST['id']]);
            JsonOutput(true, '已移动文章到新分类中');
        } else {
            JsonOutput(false, '文章不存在');
        }
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('sortCategory', function() {
    global $zdocs;
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        $stmt = $zdocs->pdo->prepare("UPDATE categories SET `order` = :order WHERE `id` = :id");
        foreach ($_POST['categories'] as $order => $id) {
            if (is_string($id) && preg_match('/^\d+$/', $id)) {
                $stmt->execute(['order' => $order, 'id' => $id]);
            }
        }
        JsonOutput(true, '分类已排序');
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);

RegisterAction('sortArticle', function() {
    global $zdocs;
    if (isset($_POST['articles']) && is_array($_POST['articles'])) {
        $stmt = $zdocs->pdo->prepare("UPDATE articles SET `order` = :order WHERE `id` = :id");
        foreach ($_POST['articles'] as $order => $id) {
            if (is_string($id) && preg_match('/^\d+$/', $id)) {
                $stmt->execute(['order' => $order, 'id' => $id]);
            }
        }
        JsonOutput(true, '文章已排序');
    } else {
        JsonOutput(false, '参数错误');
    }
}, true);
