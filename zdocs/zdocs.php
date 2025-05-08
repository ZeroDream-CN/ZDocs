<?php
if (!defined('ROOT_PATH')) die('Forbidden');

require_once(ROOT_PATH . '/config.php');

class ZDocs {
    
    public $pdo;

    public function __construct()
    {
        $this->pdo = $this->InitDatabase();
    }

    public function InitDatabase()
    {
        $pdo = new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, DB_NAME, DB_CHARSET), DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function InstallDatabase()
    {
        // 检查数据库是否存在
        if (!$this->IsTableExists('categories')) {
            $stmt = $this->pdo->exec("CREATE TABLE `categories` (
                `id` int(10) NOT NULL AUTO_INCREMENT,
                `parent` int(10) DEFAULT NULL,
                `name` varchar(255) DEFAULT NULL,
                `order` int(5) DEFAULT NULL,
                PRIMARY KEY (`id`) USING BTREE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;");
        }
        if (!$this->IsTableExists('articles')) {
            $stmt = $this->pdo->exec("CREATE TABLE `articles` (
                `id` int(10) NOT NULL AUTO_INCREMENT,
                `category` int(10) DEFAULT NULL,
                `title` varchar(255) DEFAULT NULL,
                `content` longtext DEFAULT NULL,
                `author` varchar(255) DEFAULT NULL,
                `time` bigint(16) DEFAULT NULL,
                `data` longtext DEFAULT NULL,
                `order` int(5) DEFAULT NULL,
                PRIMARY KEY (`id`) USING BTREE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;");
        }
        if (!$this->IsTableExists('users')) {
            $stmt = $this->pdo->exec("CREATE TABLE `users` (
                `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '用户 ID',
                `username` varchar(255) NOT NULL COMMENT '用户名',
                `password` varchar(255) NOT NULL COMMENT '密码哈希',
                `email` varchar(255) NOT NULL COMMENT '邮箱',
                `salt` varchar(16) NOT NULL COMMENT '密码 Salt',
                `role` varchar(32) NOT NULL DEFAULT 'admin' COMMENT '用户角色 root / admin',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }
    }

    public function IsTableExists($table)
    {
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        $result = $stmt->fetch();
        return $result ? true : false;
    }

    public function GetCategories()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY `order` ASC");
        $stmt->execute();
        $result = $stmt->fetchAll();
        $categories = [];
        foreach ($result as $row) {
            if (!isset($row['parent']) || empty($row['parent'])) {
                $categories[$row['id']] = $categories[$row['id']] ?? [];
                foreach ($row as $key => $value) {
                    if (is_numeric($key)) continue;
                    $categories[$row['id']][$key] = $value;
                }
            } else {
                $categories[$row['parent']] = $categories[$row['parent']] ?? [];
                $categories[$row['parent']]['subCategory'] = $categories[$row['parent']]['subCategory'] ?? [];
                $categories[$row['parent']]['subCategory'][$row['id']] = $categories[$row['parent']]['subCategory'][$row['id']] ?? [];
                foreach ($row as $key => $value) {
                    if (is_numeric($key)) continue;
                    $categories[$row['parent']]['subCategory'][$row['id']][$key] = $value;
                }
            }
        }
        // sort by order
        foreach ($categories as $key => $value) {
            if (isset($value['subCategory'])) {
                $categories[$key]['subCategory'] = array_values($value['subCategory']);
                usort($categories[$key]['subCategory'], function ($a, $b) {
                    return $a['order'] - $b['order'];
                });
            }
        }
        usort($categories, function ($a, $b) {
            return $a['order'] - $b['order'];
        });
        return $categories;
    }

    public function GetCategory($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result;
    }
    
    public function GetArticles($category)
    {
        $stmt = $this->pdo->prepare("SELECT `id`,`title`,`author`,`time`,`data` FROM articles WHERE category = :category  ORDER BY `order` ASC");
        $stmt->execute(['category' => $category]);
        $result = $stmt->fetchAll();
        return $result;
    }

    public function GetArticle($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result;
    }

    public function GetArticleContent($id)
    {
        $stmt = $this->pdo->prepare("SELECT content FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        $content = $result ? $result['content'] : false;
        return $content;
    }

    public function GetBreadcrumbByArticle($articleId)
    {
        $stmt = $this->pdo->prepare("SELECT category FROM articles WHERE id = :id");
        $stmt->execute(['id' => $articleId]);
        $result = $stmt->fetch();
        if ($result) {
            $category = $result['category'];
            $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id");
            $stmt->execute(['id' => $category]);
            $result = $stmt->fetch();
            $breadcrumb = [];
            while ($result) {
                $breadcrumb[] = $result;
                $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id");
                $stmt->execute(['id' => $result['parent']]);
                $result = $stmt->fetch();
            }
            return array_reverse($breadcrumb);
        } else {
            return false;
        }
    }

    public function GetBreadcrumbByCategory($categoryId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $categoryId]);
        $result = $stmt->fetch();
        $breadcrumb = [];
        while ($result) {
            $breadcrumb[] = $result;
            $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id");
            $stmt->execute(['id' => $result['parent']]);
            $result = $stmt->fetch();
        }
        return array_reverse($breadcrumb);
    }

    public function GetBreadcrumb()
    {
        if (isset($_GET['id'])) {
            return $this->GetBreadcrumbByArticle($_GET['id']);
        } elseif (isset($_GET['category'])) {
            return $this->GetBreadcrumbByCategory($_GET['category']);
        } else {
            return false;
        }
    }

    public function GetUsers()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users");
        $stmt->execute();
        $result = $stmt->fetchAll();
        $users = [];
        foreach ($result as $row) {
            $users[] = [
                'id'       => Intval($row['id']),
                'username' => $row['username'],
                'email'    => $row['email'],
                'role'     => $row['role'],
            ];
        }
        return $users;
    }
}
