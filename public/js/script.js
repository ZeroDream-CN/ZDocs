var editorInstance = null;
var articleSort = null;
// 伪静态
var usingStatic = true;
// 添加滚动监听变量
var scrollListener = null;

function LoadArticle(articleId) {
    if (editorInstance) {
        editorInstance.destroy();
        editorInstance = null;
        $('.main-container .main-articles .article-content').show();
        $('.main-container .main-articles .article-editor').hide();
    }
    $.ajax({
        type: 'GET',
        url: '?action=loadArticle&id=' + encodeURIComponent(articleId),
        // response is json
        async: true,
        dataType: 'json',
        success: function (data) {
            if (data && data.article) {
                CleanupTooltips();
                var htmlContent = ConvertHtml(data.article.content);
                $('.main-container .main-articles .article-title').text(data.article.title);
                $('.main-container .main-articles .article-content').html(htmlContent);
                $('.main-container .main-articles .article-link').click(ProcessLinkClick);
                $(`a[data-id]`).parent().parent().find('li').removeClass('active');
                $(`a[data-id]`).removeClass('active');
                $(`.main-container .categories>ul>li`).each(function () {
                    var line = $(this).find('li');
                    if (!line.find(`a[data-id=${articleId}]`).length) {
                        line.removeClass('active');
                    }
                });
                $(`a[data-id=${articleId}]`).addClass('active');
                $(`a[data-id=${articleId}]`).parent().addClass('active');
                $('.main-container .categories').removeClass('open');
                if (typeof (loginInfo) != 'undefined' && loginInfo) {
                    loginInfo.article = articleId;
                    loginInfo.category = 0;
                    $('.main-container .main-articles .article-content').append('<div class="admin-actions"><hr><h3>管理员操作</h3><p>您可以对当前文章进行管理操作</p><p><button class="btn btn-warning waves-effect waves-light" onclick="MoveArticle()">切换分类</button> <button class="btn btn-danger waves-effect waves-light" onclick="DeleteArticle()">删除文章</button></p></div>');
                }
                // scroll to top
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
                ParseBreadcrums(data.breadcrumb);
                ChangeUrl(null, articleId);
                ProcessCatalog();
                ProcessElements();
            } else if (data && data.error) {
                Swal.fire({
                    icon: 'error',
                    title: '错误',
                    text: data.error
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '错误',
                    text: '未知错误'
                });
            }
        },
        error: function (err) {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: err.responseText
            });
        }
    });
}

function LoadCategory(categoryId) {
    if (editorInstance) {
        editorInstance.destroy();
        editorInstance = null;
        $('.main-container .main-articles .article-content').show();
        $('.main-container .main-articles .article-editor').hide();
    }
    $.ajax({
        type: 'GET',
        url: '?action=loadCategory&id=' + encodeURIComponent(categoryId),
        // response is json
        async: true,
        dataType: 'json',
        success: function (data) {
            if (data && data.articles && data.articles.length > 0) {
                var html = '<h3>文章列表</h3><p>当前分类下共有 ' + data.articles.length + ' 篇文章</p><ol class="article-list">';
                for (var i = 0; i < data.articles.length; i++) {
                    var article = data.articles[i];
                    html += '<li><a class="article-link" data-id="' + article.id + '"><i class="fas fa-angle-right"></i> ' + article.title + '</a></li>';
                }
                html += '</ol>';
                $('.main-container .main-articles .article-title').text(data.category.name);
                $('.main-container .main-articles .article-content').html(html);
                $('.main-container .main-articles .article-link').click(ProcessLinkClick);
                $('.main-container .categories').removeClass('open');
                if (typeof (loginInfo) != 'undefined' && loginInfo) {
                    loginInfo.category = categoryId;
                    loginInfo.article = 0;
                    $('.main-container .main-articles .article-content').append('<div class="admin-actions"><hr><h3>管理员操作</h3><p>您可以对当前分类进行管理操作，鼠标拖动上方文章可进行排序</p><p>您还可以将 .md 文件拖入到此处来导入，导入后会自动创建文章</p><p><button class="btn btn-primary waves-effect waves-light" onclick="CreateArticle()">创建文章</button> <button class="btn btn-warning waves-effect waves-light" onclick="CreateCategory(true)">创建子分类</button> <button class="btn btn-danger waves-effect waves-light" onclick="DeleteCategory()">删除分类</button></p></div>');
                    ProcessArticleSort();
                }
                // scroll to top
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
                ParseBreadcrums(data.breadcrumb);
                ChangeUrl(categoryId, null);
                ProcessCatalog();
            } else if (data && data.error) {
                $('.main-container .main-articles .article-title').text(data.category.name);
                $('.main-container .main-articles .article-content').html('<h3>错误</h3><p>' + data.error + '</p>');
                $('.main-container .categories').removeClass('open');
                ParseBreadcrums();
                ChangeUrl(categoryId, null);
                ProcessCatalog();
                // scroll to top
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
                if (typeof (loginInfo) != 'undefined' && loginInfo) {
                    loginInfo.category = categoryId;
                    loginInfo.article = 0;
                    $('.main-container .main-articles .article-content').append('<div class="admin-actions"><hr><h3>管理员操作</h3><p>您可以对当前分类进行管理操作，鼠标拖动上方文章可进行排序</p><p>您还可以将 .md 文件拖入到此处来导入，导入后会自动创建文章</p><p><button class="btn btn-primary waves-effect waves-light" onclick="CreateArticle()">创建文章</button> <button class="btn btn-warning waves-effect waves-light" onclick="CreateCategory(true)">创建子分类</button> <button class="btn btn-danger waves-effect waves-light" onclick="DeleteCategory()">删除分类</button></p></div>');
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '错误',
                    text: '未知错误'
                });
            }
        },
        error: function (err) {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: err.responseText
            });
        }
    });
}

function MoveArticle() {
    var articleId = loginInfo.article;
    if (articleId) {
        $.ajax({
            type: 'POST',
            url: '?action=getCategories',
            dataType: 'json',
            success: function (data) {
                if (data && data.categories) {
                    var inputOptions = {};
                    for (var i = 0; i < data.categories.length; i++) {
                        var category = data.categories[i];
                        inputOptions[category.id] = category.name;
                        if (category.subCategory && category.subCategory.length > 0) {
                            for (var j = 0; j < category.subCategory.length; j++) {
                                var subCategory = category.subCategory[j];
                                inputOptions[subCategory.id] = '    ' + subCategory.name;
                            }
                        }
                    }
                    Swal.fire({
                        title: '移动分类',
                        input: 'select',
                        inputOptions: inputOptions,
                        inputPlaceholder: '请选择目标分类',
                        showCancelButton: true,
                        confirmButtonText: '移动',
                        cancelButtonText: '取消',
                        preConfirm: (value) => {
                            return $.ajax({
                                type: 'POST',
                                url: '?action=moveArticle',
                                dataType: 'json',
                                data: {
                                    id: articleId,
                                    category: value
                                }
                            });
                        }
                    }).then((result) => {
                        if (result.value && result.value.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '成功',
                                text: result.value.message
                            }).then(() => {
                                window.location.href = usingStatic ? `article-${articleId}.html` : `?id=${articleId}`;
                            });
                        } else if (result.value && result.value.error) {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: result.value.error
                            });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: '未知错误'
                    });
                }
            },
            error: function (err) {
                Swal.fire({
                    icon: 'error',
                    title: '错误',
                    text: err.responseText
                });
            }
        });
    }
}

function CreateCategory(isChild) {
    var categoryId = isChild ? loginInfo.category : '';
    Swal.fire({
        title: '创建分类',
        input: 'text',
        inputPlaceholder: '请输入分类名称',
        showCancelButton: true,
        confirmButtonText: '创建',
        cancelButtonText: '取消',
        inputValidator: (value) => {
            if (!value) {
                return '分类名称不能为空';
            }
        },
        preConfirm: (value) => {
            return $.ajax({
                type: 'POST',
                url: '?action=createCategory',
                dataType: 'json',
                data: {
                    name: value,
                    parent: categoryId
                }
            });
        }
    }).then((result) => {
        if (result.value && result.value.success) {
            Swal.fire({
                icon: 'success',
                title: '成功',
                text: '分类创建成功'
            }).then(() => {
                window.location.href = usingStatic ? `category-${result.value.message}.html` : `?category=${result.value.message}`;
            });
        } else if (result.value && result.value.message) {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: result.value.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: '未知错误'
            });
        }
    });
}

function DeleteCategory() {
    var categoryId = loginInfo.category;
    if (categoryId) {
        Swal.fire({
            title: '删除分类',
            text: '您确定要删除当前分类吗？这将会连带删除所有的子分类以及它们的所有文章，此操作不可撤销！',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '删除',
            cancelButtonText: '取消'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    type: 'POST',
                    url: '?action=deleteCategory',
                    dataType: 'json',
                    data: {
                        id: categoryId
                    },
                    success: function (data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '成功',
                                text: data.message
                            }).then(() => {
                                window.location.href = '/';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: data.message
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });
    }
}

function DeleteArticle() {
    var articleId = loginInfo.article;
    if (articleId) {
        Swal.fire({
            title: '删除文章',
            text: '您确定要删除当前文章吗？此操作不可撤销！',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '删除',
            cancelButtonText: '取消'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    type: 'POST',
                    url: '?action=deleteArticle',
                    dataType: 'json',
                    data: {
                        id: articleId
                    },
                    success: function (data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '成功',
                                text: data.message
                            }).then(() => {
                                window.location.href = '/';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: data.message
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });
    }
}

function ProcessArticleSort() {
    if (articleSort) {
        articleSort.destroy();
    }
    var element = $('.main-container .main-articles .article-content .article-list')[0];
    if (element) {
        articleSort = new Sortable(element, {
            animation: 150,
            delay: 300,
            delayOnTouchOnly: true,
            onEnd: function (e) {
                var articles = [];
                $('.main-container .main-articles .article-content .article-list li').each(function () {
                    var element = $(this).find('a');
                    if (element.length > 0) {
                        var href = element.attr('href');
                        if (href) {
                            var id = href.split('=')[1];
                            articles.push(id);
                        } else {
                            articles.push(element.data('id'));
                        }
                    }
                });
                $.ajax({
                    type: 'POST',
                    url: '?action=sortArticle',
                    dataType: 'json',
                    data: {
                        articles: articles
                    },
                    success: function (data) {
                        if (data.success) {
                            Materialize.toast(data.message, 4000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: data.message
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });
    }
}

function CleanupTooltips() {
    $('.main-container.main-articles.article-content code').each(function () {
        $(this).removeAttr('data-tooltip');
        $(this).removeAttr('data-position');
        $(this).removeClass('tooltipped');
        $(this).tooltip('remove');
        M.Tooltip.getInstance(this).destroy();
    });
    $('.material-tooltip').remove();
}

function ProcessElements() {
    // 判断表格的父级是否有 responsive-table 类
    $('.main-container .main-articles .article-content table').each(function () {
        if (!$(this).parent().hasClass('responsive-table')) {
            $(this).wrap('<div class="responsive-table"></div>');
        }
    });
    // code 复制
    $('.main-container .main-articles .article-content code').each(function () {
        // 如果父级不是 hljs 类
        if (!$(this).parent().hasClass('hljs')) {
            $(this).click(function () {
                var text = $(this)[0].innerText;
                var textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
                Materialize.toast('已复制代码片段', 4000);
            });
            // 添加复制 tooltip
            $(this).attr('data-tooltip', '点击复制代码片段');
            $(this).data('position', 'bottom');
            $(this).addClass('tooltipped');
            $(this).tooltip({ delay: 1 });
        }
    });
    // 图片点击放大
    $('.main-container .main-articles .article-content img').each(function () {
        $(this).click(function () {
            var src = $(this).attr('src');
            window.open(src);
        });
    });
}

function ParseBreadcrums(breadcrums) {
    var html = '';
    if (breadcrums) {
        html += '<a href="/">首页</a> / ';
        for (var i = 0; i < breadcrums.length; i++) {
            var breadcrum = breadcrums[i];
            if (breadcrum.name) {
                html += '<a class="article-link" data-category-id="' + breadcrum.id + '">' + breadcrum.name + '</a>';
                if (i < breadcrums.length - 1) {
                    html += ' / ';
                }
            } else {
                html += '<a data-id="' + breadcrum.id + '">' + breadcrum.title + '</a>';
            }
        }
    } else {
        html += '<a href="/">首页</a>';
    }
    $('.main-container .page-breadcrumb').html(html);
}

function ProcessLinkClick(e) {
    e.preventDefault();
    var categoryId = $(this).data('category-id');
    var articleId = $(this).data('id');
    if (categoryId) {
        if ($(this).parent().find('ul').length > 0) {
            e.preventDefault();
            if ($(this).parent().hasClass('active')) {
                $(this).parent().removeClass('active');
            } else {
                $(this).parent().addClass('active');
            }
        } else {
            if (articleId) {
                LoadArticle(articleId);
            } else {
                $(this).parent().parent().find('li').removeClass('active');
                $(this).parent().addClass('active');
                LoadCategory(categoryId);
            }
        }
    } else {
        if (articleId) {
            LoadArticle(articleId);
        }
    }
}

function ProcessCatalog() {
    var id = 0;
    $('.article-catalog ul').html('');
    $('.main-container .main-articles .article-content h3').each(function () {
        id++;
        $(this).attr('id', 'catalog-' + id);
        $('.article-catalog ul').append('<li data-element="catalog-' + id + '">' + $(this).text() + '</li>');
    });
    $('.article-catalog ul li').click(function () {
        var element = $(this).data('element');
        $('html, body').animate({
            scrollTop: $('#' + element).offset().top - 80
        }, 500);
    });

    // 移除之前的滚动监听器（如果存在）
    if (scrollListener) {
        $(window).off('scroll', scrollListener);
    }

    scrollListener = function () {
        var scrollPosition = $(window).scrollTop();
        var windowHeight = $(window).height();
        var documentHeight = $(document).height();
        var h3Elements = $('.main-container .main-articles .article-content h3');
        var foundActive = false;

        // 特殊处理：如果滚动接近页面底部，自动高亮最后一个目录项
        if (scrollPosition + windowHeight >= documentHeight - 50) {
            // 滚动到接近底部，高亮最后一个目录项
            $('.article-catalog ul li').removeClass('active');
            $('.article-catalog ul li:last').addClass('active');
            return;
        }

        // 从下往上遍历所有h3标题
        for (var i = h3Elements.length - 1; i >= 0; i--) {
            var currentElement = $(h3Elements[i]);
            var currentId = currentElement.attr('id');
            var targetOffset = currentElement.offset().top - 100;

            // 如果滚动位置超过了当前标题位置
            if (scrollPosition >= targetOffset) {
                // 移除所有目录项的active类
                $('.article-catalog ul li').removeClass('active');
                // 为当前标题对应的目录项添加active类
                $('.article-catalog ul li[data-element="' + currentId + '"]').addClass('active');
                foundActive = true;
                break; // 找到第一个符合条件的就停止
            }
        }

        // 如果没有找到任何活动项，且页面不在顶部，则激活第一个目录项
        if (!foundActive && h3Elements.length > 0) {
            $('.article-catalog ul li').removeClass('active');
            $('.article-catalog ul li:first').addClass('active');
        }
    };

    // 绑定滚动事件
    $(window).on('scroll', scrollListener);

    // 使用setTimeout确保在DOM完全渲染后执行初始高亮
    setTimeout(function () {
        // 初始触发一次滚动事件，确保页面加载时就能正确高亮
        scrollListener();

        // 如果滚动位置为0，则默认高亮第一个目录项
        if ($(window).scrollTop() <= 10 && $('.main-container .main-articles .article-content h3').length > 0) {
            $('.article-catalog ul li').removeClass('active');
            $('.article-catalog ul li:first').addClass('active');
        }
    }, 300); // 300毫秒延迟，确保DOM已完全渲染
}

function ChangeUrl(categoryId, articleId) {
    var url = '/';
    if (usingStatic) {
        url += categoryId ? `category-${categoryId}.html` : '';
        url += articleId ? `article-${articleId}.html` : '';
    } else {
        url += categoryId ? 'category=' + categoryId : '';
        url += articleId ? 'id=' + articleId : '';
    }
    window.history.pushState({}, '', url);
}

function CreateArticle() {
    var categoryId = loginInfo.category;
    if (categoryId) {
        $.ajax({
            type: 'POST',
            url: '?action=createArticle',
            dataType: 'json',
            data: {
                category: categoryId
            },
            success: function (data) {
                if (data.success) {
                    LoadArticle(data.message);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: data.message
                    });
                }
            },
            error: function (err) {
                Swal.fire({
                    icon: 'error',
                    title: '错误',
                    text: err.responseText
                });
            }
        });
    }
}

function ShowLogin() {
    $('.popup-login').fadeIn(200);
}

function ShowAdmin() {
    $('.popup-admin').fadeIn(200);
}

function ChangePassword(uid) {
    Swal.fire({
        title: '修改密码',
        html: '<input type="password" id="password" class="swal2-input" placeholder="请输入新密码">' +
            '<input type="password" id="password-confirm" class="swal2-input" placeholder="请再次输入新密码">',
        showCancelButton: true,
        confirmButtonText: '修改',
        cancelButtonText: '取消',
        preConfirm: () => {
            const password = $('#password').val();
            const passwordConfirm = $('#password-confirm').val();
            if (!password || !passwordConfirm) {
                Swal.showValidationMessage('请输入密码');
            }
            if (password !== passwordConfirm) {
                Swal.showValidationMessage('两次输入的密码不一致');
            }
            return {
                password: password,
                passwordConfirm: passwordConfirm
            };
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: '?action=changePassword',
                data: {
                    uid: uid,
                    password: result.value.password
                },
                success: function (data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: data.message
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: data.message
                        });
                    }
                },
                error: function (err) {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: err.responseText
                    });
                }
            })
        }
    });
}

function DeleteUser(uid) {
    Swal.fire({
        title: '删除用户',
        text: '您确定要删除当前用户吗？此操作不可撤销！',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '删除',
        cancelButtonText: '取消'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                type: 'POST',
                url: '?action=deleteUser',
                data: {
                    uid: uid
                },
                success: function (data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: data.message
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: data.message
                        });
                    }
                },
                error: function (err) {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: err.responseText
                    });
                }
            });
        }
    });
}

function ConvertHtml(markdown) {
    if (typeof marked != 'undefined') {
        return marked(markdown);
    }
    if (typeof Cherry != 'undefined') {
        var cherry = new Cherry({
            engine: {
                global: {
                    classicBr: false,
                    htmlWhiteList: '.*',
                },
            }
        });
        var markdown = cherry.engine.makeHtml(markdown);
        cherry.destroy();
        return markdown;
    }
    return '';
}

function DestroyEditor(editor) {
    if (editor) {
        if (typeof editor.editor != 'undefined' && typeof editor.editor.remove != 'undefined') {
            editor.editor.remove();
        }
        if (typeof editor.destroy != 'undefined') {
            editor.destroy();
        }
    }
}

function InitEditor(id, height) {
    var editor = null;
    var containerName = id.replace('main', 'container');
    height = height || '70vh';
    if (typeof editormd != 'undefined') {
        $(`.${containerName}`).html('<div id="' + id + '"></div>');
        editor = editormd(id, {
            width: "100%",
            height: height,
            watch: false,
            placeholder: "请输入内容",
            path: "https://cfdx.zerodream.net/js/editormd/lib/",
            toolbarIcons: function () {
                return ["bold", "italic", "quote", "link", "image", "code", "list-ul", "list-ol", "hr", "fullscreen"];
            },
        });
    }
    if (typeof Cherry != 'undefined') {
        editor = new Cherry({
            id: id,
            themeSettings: {
                mainTheme: $('body').hasClass('theme-dark') ? 'dark' : 'light',
            },
            editor: {
                height: height,
                defaultModel: 'editOnly',
            },
            toolbars: {
                toolbar: ['bold', 'italic', 'strikethrough', '|', 'color', 'header', '|', 'list', { insert: ['image', 'link', 'hr', 'br', 'code', 'table', 'line-table', 'bar-table'], }, 'settings'],
            },
            // fileUpload: PasteUpload,
            engine: {
                global: {
                    classicBr: false,
                    htmlWhiteList: '.*',
                },
            }
        });
    }
    return editor;
}

function ClearEditor(editor) {
    if (editor) {
        if (typeof editor.clear != 'undefined') {
            editor.clear();
        }
        if (typeof editor.setMarkdown != 'undefined') {
            editor.setMarkdown('');
        }
    }
}

function GetEditorContent(editor) {
    if (editor) {
        if (typeof editor.getMarkdown != 'undefined') {
            return editor.getMarkdown();
        }
    }
    return '';
}

function SetEditorValue(editor, value) {
    if (editor) {
        if (typeof editor.setMarkdown != 'undefined') {
            editor.setMarkdown(value);
        } else if (typeof editor.setHtml != 'undefined') {
            editor.setHtml(value);
        }
    }
}

function InsertEditorValue(editor, value) {
    if (editor) {
        if (typeof editor.insertValue != 'undefined') {
            editor.insertValue(value);
        } else if (typeof editor.insert != 'undefined') {
            editor.insert(value);
        }
    }
}

$(document).ready(function () {
    $('.main-container .categories').on('selectstart', function () {
        return false;
    });
    $('.main-container .menu').click(function () {
        $('.main-container .categories').toggleClass('open');
    });
    $('.main-container .categories>ul>li a').click(ProcessLinkClick);
    $('.main-container .categories>ul>li a').dblclick(function (e) {
        e.preventDefault();
        var categoryId = $(this).data('category-id');
        if (categoryId) {
            LoadCategory(categoryId);
        }
    });
    $('.main-container .categories>ul>li>ul>li.active').parent().parent().addClass('active');
    if (!pageInfo.category && !pageInfo.article) {
        LoadArticle(1);
    } else if (pageInfo.article) {
        LoadArticle(pageInfo.article);
    }
    var markdown = $('.markdown-body');
    if (markdown.length > 0) {
        $('.article-content').prepend(ConvertHtml(markdown.html()));
        markdown.remove();
    }
    ProcessCatalog();
    ProcessElements();
    if (typeof (loginInfo) != 'undefined' && loginInfo) {
        // drag to sort order
        ProcessArticleSort();
        new Sortable(document.querySelector('.main-container .categories>ul'), {
            animation: 150,
            delay: 300,
            delayOnTouchOnly: true,
            onEnd: function (e) {
                var categories = [];
                $('.main-container .categories>ul>li').each(function () {
                    var element = $(this).find('a');
                    if (element.data('category-id')) {
                        categories.push(element.data('category-id'));
                    }
                });
                $.ajax({
                    type: 'POST',
                    url: '?action=sortCategory',
                    dataType: 'json',
                    data: {
                        categories: categories
                    },
                    success: function (data) {
                        if (data.success) {
                            Materialize.toast(data.message, 4000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: data.message
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });
        // 标题编辑
        $('.main-container .main-articles .article-title').on('dblclick', function (e) {
            e.preventDefault();
            if ($(this).attr('contenteditable') === 'plaintext-only') return;
            var updateId = loginInfo.article || loginInfo.category;
            if (updateId) {
                $(this).attr('contenteditable', 'plaintext-only');
                $(this).focus();
            }
        });
        $('.main-container .main-articles .article-title').on('blur', function (e) {
            $(this).attr('contenteditable', 'false');
            var updateType = loginInfo.article ? 'article' : 'category';
            var updateId = loginInfo.article || loginInfo.category;
            var updateValue = $(this).text();
            if (updateId) {
                $.ajax({
                    type: 'POST',
                    url: '?action=updateTitle',
                    dataType: 'json',
                    data: {
                        type: updateType,
                        id: updateId,
                        value: updateValue
                    },
                    success: function (data) {
                        if (data.success) {
                            Materialize.toast(data.message, 4000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: data.message
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });
        // 内容编辑
        $('.main-container .main-articles .article-content').on('dblclick', function (e) {
            e.preventDefault();
            // if ($(this).attr('contenteditable') === 'plaintext-only') return;
            if (editorInstance) return;
            var updateId = loginInfo.article;
            if (updateId) {
                $.ajax({
                    type: 'GET',
                    url: '?action=loadRawArticleContent&id=' + encodeURIComponent(updateId),
                    success: function (data) {
                        if (data) {
                            if (data.success) {
                                // data.message = data.message.replace(/\n/g, '<br>');
                                // $('.main-container .main-articles .article-content').html(data.message);
                                // $('.main-container .main-articles .article-content').attr('contenteditable', 'plaintext-only');
                                // $('.main-container .main-articles .article-content-edit').focus();
                                $('.main-container .main-articles .article-content').hide();
                                $('.main-container .main-articles .article-editor').show()
                                editorInstance = InitEditor('article-editor-inst', '70vh');
                                Materialize.toast('进入编辑模式', 4000);
                                setTimeout(() => {
                                    SetEditorValue(editorInstance, data.message);
                                }, 100);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '错误',
                                    text: data.message
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: '未知错误'
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });
        // $('.main-container .main-articles .article-content').on('blur', function (e) {
        $('#article-editor-save').click(function () {
            if (!editorInstance) return;
            var updateId = loginInfo.article;
            if (updateId) {
                // var updateValue = $(this)[0].innerText;
                var updateValue = GetEditorContent(editorInstance);
                $.ajax({
                    type: 'POST',
                    url: '?action=updateContent',
                    dataType: 'json',
                    data: {
                        id: updateId,
                        value: updateValue
                    },
                    success: function (data) {
                        if (data.success) {
                            // $('.main-container .main-articles .article-content').attr('contenteditable', 'false');
                            $('.main-container .main-articles .article-content').show();
                            $('.main-container .main-articles .article-editor').hide();
                            DestroyEditor(editorInstance);
                            editorInstance = null;
                            Materialize.toast(data.message, 4000);
                            LoadArticle(updateId);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '错误',
                                text: data.message
                            });
                        }
                    },
                    error: function (err) {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: err.responseText
                        });
                    }
                });
            }
        });

        $('#article-editor-cancel').click(function() {
            if (editorInstance) {
                Swal.fire({
                    title: '确认取消',
                    text: '您确定要取消编辑吗？未保存的内容将会丢失！',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确定',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.value) {
                        editorInstance.destroy();
                        editorInstance = null;
                        $('.main-container .main-articles .article-content').show();
                        $('.main-container .main-articles .article-editor').hide();
                        // LoadArticle(loginInfo.article);
                    }
                });
            }
        });
    }

    setInterval(function () {
        try {
            $('pre code').each(function (i, block) {
                if (!$(block).hasClass('hljs')) {
                    hljs.highlightBlock(block);
                }
            });
        } catch (e) {
            // 无可奉告
        }
    }, 100);

    // 页面回退
    window.addEventListener('popstate', function (e) {
        if (e.state) {
            window.location.href = window.location.href;
        }
    });

    // 登录弹窗
    $('.popup-login-title-close').click(function () {
        $('.popup-login').fadeOut(200);
    });

    $('.popup-admin-title-close').click(function () {
        $('.popup-admin').fadeOut(200);
    });

    $('.popup-login-content form').submit(function (e) {
        e.preventDefault();
        var username = $('.popup-login-content form input[name="username"]').val();
        var password = $('.popup-login-content form input[name="password"]').val();
        if (username && password) {
            $('.popup-login-content form input').attr('disabled', 'disabled');
            $('.popup-login-content form button').attr('disabled', 'disabled');
            $('.popup-login-content form button').text('登录中...');
            $.ajax({
                type: 'POST',
                url: '?action=login',
                async: true,
                data: {
                    username: username,
                    password: password
                },
                success: function (data) {
                    $('.popup-login-content form input').removeAttr('disabled');
                    $('.popup-login-content form button').removeAttr('disabled');
                    $('.popup-login-content form button').text('登录');
                    if (data.success) {
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: data.message
                        });
                    }
                },
                error: function (err) {
                    $('.popup-login-content form input').removeAttr('disabled');
                    $('.popup-login-content form button').removeAttr('disabled');
                    $('.popup-login-content form button').text('登录');
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: err.responseText
                    })
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: '用户名或密码不能为空'
            });
        }
    });

    $('#add-user-form').submit(function (e) {
        e.preventDefault();
        var username = $('#add-user-username').val();
        var password = $('#add-user-password').val();
        var email = $('#add-user-email').val();
        var role = $('#add-user-role').val();
        if (username && password && email && role) {
            $.ajax({
                type: 'POST',
                url: '?action=addUser',
                async: true,
                data: {
                    username: username,
                    password: password,
                    email: email,
                    role: role
                },
                success: function (data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: data.message
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: data.message
                        });
                    }
                },
                error: function (err) {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: err.responseText
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: '用户名、密码、邮箱和角色不能为空'
            });
        }
    });

    // 普通用户修改自己的密码
    $('#change-password-form').submit(function (e) {
        e.preventDefault();
        var oldPassword = $('#change-password-old').val();
        var newPassword = $('#change-password-new').val();
        var newPasswordConfirm = $('#change-password-new-confirm').val();
        if (oldPassword && newPassword && newPasswordConfirm) {
            if (newPassword !== newPasswordConfirm) {
                Swal.fire({
                    icon: 'error',
                    title: '错误',
                    text: '两次输入的新密码不一致'
                });
                return;
            }
            $.ajax({
                type: 'POST',
                url: '?action=changeSelfPassword',
                async: true,
                data: {
                    oldPassword: oldPassword,
                    newPassword: newPassword
                },
                success: function (data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: data.message
                        });
                        $('#change-password-old').val('');
                        $('#change-password-new').val('');
                        $('#change-password-new-confirm').val('');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '错误',
                            text: data.message
                        });
                    }
                },
                error: function (err) {
                    Swal.fire({
                        icon: 'error',
                        title: '错误',
                        text: err.responseText
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: '错误',
                text: '旧密码、新密码和确认密码不能为空'
            });
        }
    });

    // 判断 localStorage 是否有 theme
    if (localStorage.getItem('theme') !== null) {
        let theme = localStorage.getItem('theme');
        if (theme === 'dark') {
            $('body').addClass('theme-dark');
            $('meta[name="theme-color"]').attr('content', '#1e212e');
        } else {
            $('body').removeClass('theme-dark');
            $('meta[name="theme-color"]').attr('content', '#ffffff');
        }
    } else {
        // 如果用户访问时的时间在 21:00 到 6:00 之间，则切换到夜间模式
        let now = new Date();
        if (now.getHours() >= 21 || now.getHours() < 6) {
            $('body').addClass('theme-dark');
            localStorage.setItem('theme', 'dark');
            $('meta[name="theme-color"]').attr('content', '#1e212e');
        } else {
            $('body').removeClass('theme-dark');
            localStorage.setItem('theme', 'light');
            $('meta[name="theme-color"]').attr('content', '#ffffff');
        }
    }

    // 切换主题
    $('.switch-theme').click(function () {
        $('body').toggleClass('theme-dark');
        if ($('body').hasClass('theme-dark')) {
            localStorage.setItem('theme', 'dark');
            $('meta[name="theme-color"]').attr('content', '#1e212e');
        } else {
            localStorage.setItem('theme', 'light');
            $('meta[name="theme-color"]').attr('content', '#ffffff');
        }
    });
    
    // 批量导入文章
    function preventDefault(e) {
        e = e || window.event;
        if (e.preventDefault) {
            e.preventDefault();
        }
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        e.returnValue = false;
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        $('.article-content').on(eventName, preventDefault);
    });

    $('.article-content').on('drop', function (e) {
        if (!loginInfo.category) {
            return;
        }

        function importArticle(file, cb) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var content = e.target.result;
                var title = file.name.replace('.md', '');
                $.ajax({
                    type: 'POST',
                    url: '?action=importArticle',
                    async: false,
                    data: {
                        category: loginInfo.category,
                        title: title,
                        content: content
                    },
                    success: function (data) {
                        if (data.success) {
                            cb(true, data.message);
                        } else {
                            cb(false, data.message);
                        }
                    },
                    error: function (err) {
                        cb(false, err.responseText);
                    }
                })
            };
            reader.readAsText(file);
        }

        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            var importFiles = [];
            for (var i = 0; i < files.length; i++) {
                if (files[i].name.endsWith('.md')) {
                    importFiles.push(files[i]);
                }
            }
            if (importFiles.length > 0) {
                Swal.fire({
                    title: '确认导入',
                    text: '您确定要导入 ' + importFiles.length + ' 个文件吗？',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确定',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.value) {
                        // 创建文章，更新标题，更新内容
                        var importCount = 0;
                        var importSuccessCount = 0;
                        var importErrorCount = 0;
                        var importErrorList = [];
                        for (var i = 0; i < importFiles.length; i++) {
                            var file = importFiles[i];
                            importArticle(file, function (success, message) {
                                importCount++;
                                if (success) {
                                    importSuccessCount++;
                                } else {
                                    importErrorCount++;
                                    importErrorList.push(message);
                                }
                            });
                        }
                        var timer = setInterval(function () {
                            if (importCount === importFiles.length) {
                                clearInterval(timer);
                                if (importErrorCount > 0) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '错误',
                                        text: '导入失败：' + importErrorList.join('\n')
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '成功',
                                        text: '导入成功：' + importSuccessCount + '个文件'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                }
                            }
                        });
                    }
                })
            }
        }
    });
});