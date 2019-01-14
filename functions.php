<?php
error_reporting(E_ALL ^ E_NOTICE);//镇魔石，镇压一切魑魅魍魉！
if (phpversion() < 5.5) {
    wp_die('本主题不支持在PHP5.5以下版本运行，请升级PHP版本 ^_^');
}
/*定义一些常量*/
define('GIT_VER', wp_get_theme()->get('Version'));
define('GIT_URL', get_template_directory_uri());
add_action('after_setup_theme', 'deel_setup');
include ('inc/theme-options.php');
include ('inc/theme-widgets.php');
include ('inc/theme-metabox.php');
include ('inc/theme-function.php');
if (!defined('POINTS_CORE_DIR')) {
    include ('modules/points.php');
}
function deel_setup() {
    //添加主题特性
    add_theme_support('post-thumbnails');//缩略图设置
    add_theme_support('post-formats', array('aside')); //增加文章形式
    add_theme_support('custom-background', array(
        'default-image' => GIT_URL . '/assets/img/bg.png',
        'default-repeat' => 'repeat',
        'default-position-x' => 'left',
        'default-position-y' => 'top',
        'default-size' => 'auto',
        'default-attachment' => 'fixed'
    ));
    add_editor_style('editor-style.css');
    //定义菜单
    if (function_exists('register_nav_menus')) {
        register_nav_menus(array(
            'nav' => '网站导航',
            'pagemenu' => '页面导航'
        ));
    }
}
//禁用响应式图片
function msiw(){
	return 1;
}
add_filter('max_srcset_image_width', 'msiw');
//去除部分默认小工具
function unregister_d_widget() {
	unregister_widget('WP_Widget_Search');
	unregister_widget('WP_Widget_Recent_Comments');
	unregister_widget('WP_Widget_Tag_Cloud');
	unregister_widget('WP_Nav_Menu_Widget');
}
add_action('widgets_init', 'unregister_d_widget');
//添加后台左下角文字
function git_admin_footer_text($text){
    $text = '感谢使用<a target="_blank" href="https://gitcafe.net/" >Git主题 ' . GIT_VER . '</a>进行创作';
    return $text;
}
add_filter('admin_footer_text', 'git_admin_footer_text');
//修改默认发信地址
add_filter('wp_mail_from', 'deel_res_from_email');
add_filter('wp_mail_from_name', 'deel_res_from_name');
//分类，标签描述添加图片
remove_filter('pre_term_description', 'wp_filter_kses');
remove_filter('pre_link_description', 'wp_filter_kses');
remove_filter('pre_link_notes', 'wp_filter_kses');
remove_filter('term_description', 'wp_kses_data');
//屏蔽顶部工具栏
add_filter('show_admin_bar', '__return_false');
//关键字
if (git_get_option('git_keywords')) add_action('wp_head', 'deel_keywords');
//页面描述 d_description
if (git_get_option('git_description')) add_action('wp_head', 'deel_description');

//阻止站内PingBack
if (git_get_option('git_pingback_b')) add_action('pre_ping', 'deel_noself_ping');

// 友情链接扩展
add_filter('pre_option_link_manager_enabled', '__return_true');
//评论回复邮件通知
add_action('comment_post', 'comment_mail_notify');
//自动勾选评论回复邮件通知，不勾选则注释掉
add_action('comment_form', 'deel_add_checkbox');
//评论表情改造，如需更换表情，assets/img/smilies/下替换
add_filter('smilies_src', 'deel_smilies_src', 1, 10);
//移除自动保存和修订版本
if (git_get_option('git_autosave_b')) {
    add_action('wp_print_scripts', 'disable_autosave');
    function disable_autosave() {
        wp_deregister_script('autosave');
    }
    add_filter('wp_revisions_to_keep', 'specs_wp_revisions_to_keep', 10, 2);
    function specs_wp_revisions_to_keep($num, $post) {
        return 0;
    }
}
//去除头部冗余代码
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2, 1);
remove_action('wp_head', 'rsd_link'); //移除离线编辑器开放接口
remove_action('wp_head', 'wlwmanifest_link'); //移除离线编辑器开放接口
remove_action('wp_head', 'index_rel_link'); //本页链接
remove_action('wp_head', 'parent_post_rel_link'); //清除前后文信息
remove_action('wp_head', 'start_post_rel_link'); //清除前后文信息
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
remove_action('wp_head', 'rel_canonical'); //本页链接
remove_action('wp_head', 'wp_generator'); //移除WordPress版本号
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); //本页短链接
add_filter('xmlrpc_enabled', '__return_false');
add_filter('embed_oembed_discover', '__return_false');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
// 屏蔽 REST API
add_filter('rest_enabled', '__return_false');
add_filter('rest_jsonp_enabled', '__return_false');
// 移除头部 wp-json 标签和 HTTP header 中的 link
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('template_redirect', 'rest_output_link_header', 11);
//清除wp_footer带入的embed.min.js
function git_deregister_embed_script() {
    wp_deregister_script('wp-embed');
}
add_action('wp_footer', 'git_deregister_embed_script');
//显示数据库查询次数、查询时间及内存占用的代码
function iperformance($visible = false) {
    $stat = sprintf('%d 次查询 用时 %.3f 秒, 耗费了 %.2fMB 内存', get_num_queries() , timer_stop(0, 3) , memory_get_peak_usage() / 1024 / 1024);
    echo $visible ? $stat : "<!-- {$stat} -->";
}
add_action('wp_footer', 'iperformance', 20);
//禁止 s.w.org
function git_remove_dns_prefetch($hints, $relation_type) {
    if ('dns-prefetch' === $relation_type) {
        return array_diff(wp_dependencies_unique_hosts() , $hints);
    }
    return $hints;
}
add_filter('wp_resource_hints', 'git_remove_dns_prefetch', 10, 2);
//站长评论邮件添加评论链接
function git_notify_postauthor($notify_message,$comment_ID) {
    $notify = $notify_message;
    $notify.= '快速回复此评论: ' . admin_url("edit-comments.php").'#comment-'.$comment_ID;
    return $notify;
}
add_filter('comment_notification_text', 'git_notify_postauthor', 10, 2);
add_filter('wp_password_change_notification_email', '__return_false'); //关闭密码修改站长邮件
add_filter('password_change_email', '__return_false'); //关闭密码修改用户邮件
if (git_get_option('git_user_notification_to_admin')) {
    add_filter('wp_new_user_notification_email_admin', '__return_false');
} //关闭新用户注册站长邮件
//欢迎新用户邮件
if (git_get_option('git_user_notification_to_user')) {
    function git_register_mail($user_id) {
        $user = get_user_by('id', $user_id);
        $user_pass = $_POST['password'];
        $blogname = get_option('blogname');
        $message = '<div class="emailcontent" style="width:100%;max-width:720px;text-align:left;margin:0 auto;padding-top:80px;padding-bottom:20px"><div class="emailtitle"><h1 style="color:#fff;background:#51a0e3;line-height:70px;font-size:24px;font-weight:400;padding-left:40px;margin:0">注册成功通知</h1><div class="emailtext" style="background:#fff;padding:20px 32px 20px"><div style="padding:0;font-weight:700;color:#6e6e6e;font-size:16px">尊敬的' . $user->user_login . ',您好！</div><p style="color:#6e6e6e;font-size:13px;line-height:24px">欢迎您注册[' . $blogname . ']，下面是您的账号信息，请妥善保管！</p><table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-top:1px solid #eee;border-left:1px solid #eee;color:#6e6e6e;font-size:16px;font-weight:normal"><thead><tr><th colspan="2" style="padding:10px 0;border-right:1px solid #eee;border-bottom:1px solid #eee;text-align:center;background:#f8f8f8">您的详细注册信息</th></tr></thead><tbody><tr><td style="padding:10px 0;border-right:1px solid #eee;border-bottom:1px solid #eee;text-align:center;width:100px">登录邮箱</td><td style="padding:10px 20px 10px 30px;border-right:1px solid #eee;border-bottom:1px solid #eee;line-height:30px">' . $user->user_email . '</td></tr><tr><td style="padding:10px 0;border-right:1px solid #eee;border-bottom:1px solid #eee;text-align:center">登录密码</td><td style="padding:10px 20px 10px 30px;border-right:1px solid #eee;border-bottom:1px solid #eee;line-height:30px">' . $user_pass . '</td></tr></tbody></table><p style="color:#6e6e6e;font-size:13px;line-height:24px">如果您的账号有异常，请您在第一时间和我们取得联系哦，联系邮箱：' . get_bloginfo('admin_email') . '</p></div><div class="emailad" style="margin-top:4px"><a href="' . home_url() . '"><img src="http://reg.163.com/images/secmail/adv.png" alt="" style="margin:auto;width:100%;max-width:700px;height:auto"></a></div></div></div>';
        $headers = "Content-Type:text/html;charset=UTF-8\n";
        wp_mail($user->user_email, '[' . $blogname . ']欢迎注册' . $blogname, $message, $headers);
    }
    add_action('user_register', 'git_register_mail');
    add_filter('wp_new_user_notification_email', '__return_false'); //关闭新用户注册用户邮件
}
//禁用WordPress活动
function git_dweandw_remove() {
    remove_meta_box('dashboard_primary', get_current_screen() , 'side');
}
add_action('wp_network_dashboard_setup', 'git_dweandw_remove', 20);
add_action('wp_user_dashboard_setup', 'git_dweandw_remove', 20);
add_action('wp_dashboard_setup', 'git_dweandw_remove', 20);
if (function_exists('register_sidebar')) {
    register_sidebar(array(
        'name' => '全站侧栏',
        'id' => 'widget_sitesidebar',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<div class="title"><h2>',
        'after_title' => '</h2></div>'
    ));
    register_sidebar(array(
        'name' => '首页侧栏',
        'id' => 'widget_sidebar',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<div class="title"><h2>',
        'after_title' => '</h2></div>'
    ));
    register_sidebar(array(
        'name' => '分类/标签/搜索页侧栏',
        'id' => 'widget_othersidebar',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<div class="title"><h2>',
        'after_title' => '</h2></div>'
    ));
    register_sidebar(array(
        'name' => '文章页侧栏',
        'id' => 'widget_postsidebar',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<div class="title"><h2>',
        'after_title' => '</h2></div>'
    ));
    register_sidebar(array(
        'name' => '页面侧栏',
        'id' => 'widget_pagesidebar',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<div class="title"><h2>',
        'after_title' => '</h2></div>'
    ));
}

//面包屑导航
function deel_breadcrumbs(){
    if (!is_single() || get_post_type() != 'post') {
        return false;
    }
    $categorys = get_the_category();
    $category = $categorys[0];
    return '<a title="返回首页" href="' . home_url() . '"><i class="fa fa-home"></i></a> <small>></small> ' . get_category_parents($category->term_id, true, ' <small>></small> ') . '<span class="muted">' . get_the_title() . '</span>';
}
// 取消原有jQuery，加载自定义jQuery
function footerScript() {
    if (!is_admin()) {
        wp_deregister_script('jquery');
        if (git_get_option('git_jqcdn') == 'git_jqcdn_upai') {
            wp_register_script('jquery', 'https://upcdn.b0.upaiyun.com/libs/jquery/jquery-1.8.3.min.js', false, '1.0', true); //底部加载,速度快,兼容差
        } else {
            wp_register_script('jquery', GIT_URL . '/assets/js/jquery.min.js', false, '1.0', false); //头部加载,速度慢,兼容好
        }
        wp_enqueue_script('jquery');
        wp_register_script('default', GIT_URL . '/assets/js/global.js', false, '1.0', true); //底部加载
        wp_enqueue_script('default');
        wp_register_style('style', GIT_URL . '/style.css', false, '1.0');
        wp_enqueue_style('style');
    }
}
add_action('wp_enqueue_scripts', 'footerScript');
if (!function_exists('deel_paging')) {
    function deel_paging(){
        $p = 4;
        if (is_singular()) {
            return;
        }
        global $wp_query, $paged;
        $max_page = $wp_query->max_num_pages;
        if ($max_page == 1) {
            return;
        }
        echo '<div class="pagination"><ul>';
        if (empty($paged)) {
            $paged = 1;
        }
        // echo '<span class="pages">Page: ' . $paged . ' of ' . $max_page . ' </span> ';
        echo '<li class="prev-page">';
        previous_posts_link('上一页');
        echo '</li>';
        if ($paged > $p + 1) {
            p_link(1, '<li>第一页</li>');
        }
        if ($paged > $p + 2) {
            echo "<li><span>&middot;&middot;&middot;</span></li>";
        }
        for ($i = $paged - $p; $i <= $paged + $p; $i++) {
            if ($i > 0 && $i <= $max_page) {
                $i == $paged ? print "<li class=\"active\"><span>{$i}</span></li>" : p_link($i);
            }
        }
        if ($paged < $max_page - $p - 1) {
            echo "<li><span> ... </span></li>";
        }
        //if ( $paged < $max_page - $p ) p_link( $max_page, '&raquo;' );
        echo '<li class="next-page">';
        next_posts_link('下一页');
        echo '</li>';
        // echo '<li><span>共 '.$max_page.' 页</span></li>';
        echo '</ul></div>';
    }
    function p_link($i, $title = '')
    {
        if ($title == '') {
            $title = "第 {$i} 页";
        }
        echo "<li><a href='", esc_html(get_pagenum_link($i)), "'>{$i}</a></li>";
    }
}
function deel_strimwidth($str, $start, $width, $trimmarker) {
    $output = preg_replace('/^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $start . '}((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $width . '}).*/s', '\1', $str);
    return $output . $trimmarker;
}
function git_get_option($e) {
    return stripslashes(get_option($e));
}
if (!function_exists('deel_views')) {
    function deel_record_visitors(){
        if (is_singular()) {
            global $post;
            $post_ID = $post->ID;
            if ($post_ID) {
                $post_views = (int) get_post_meta($post_ID, 'views', true);
                if (!update_post_meta($post_ID, 'views', $post_views + 1)) {
                    add_post_meta($post_ID, 'views', 1, true);
                }
            }
        }
    }
    add_action('wp_head', 'deel_record_visitors');
    function deel_views($after = '')
    {
        global $post;
        $post_ID = $post->ID;
        $views = (int) get_post_meta($post_ID, 'views', true);
        echo $views, $after;
    }
}
//页面伪静态
if (git_get_option('git_pagehtml_b')) {
    function html_page_permalink(){
        global $wp_rewrite;
        if (!strpos($wp_rewrite->get_page_permastruct(), '.html')) {
            $wp_rewrite->page_structure = $wp_rewrite->page_structure . '.html';
        }
    }
    add_action('init', 'html_page_permalink', -1);
}
//baidu分享
$dHasShare = false;
function deel_share() {
    if (!git_get_option('git_bdshare_b')) return false;
    echo '<span class="action action-share bdsharebuttonbox"><i class="fa fa-share-alt"></i>分享 (<span class="bds_count" data-cmd="count" title="累计分享0次">0</span>)<div class="action-popover"><div class="popover top in"><div class="arrow"></div><div class="popover-content"><a href="#" class="sinaweibo fa fa-weibo" data-cmd="tsina" title="分享到新浪微博"></a><a href="#" class="bds_qzone fa fa-star" data-cmd="qzone" title="分享到QQ空间"></a><a href="#" class="qq fa fa-qq" data-cmd="sqq" title="分享到QQ好友"></a><a href="#" class="bds_renren fa fa-renren" data-cmd="renren" title="分享到人人网"></a><a href="#" class="bds_weixin fa fa-weixin" data-cmd="weixin" title="分享到微信"></a><a href="#" class="bds_more fa fa-ellipsis-h" data-cmd="more"></a></div></div></div></span>';
    global $dHasShare;
    $dHasShare = true;
}

//搜索表单
function git_searchform() {
$search_placeholder = git_get_option('git_search_placeholder');
?>
<form method="get" class="searchform themeform" onsubmit="location.href='<?php echo home_url('/search/'); ?>' + encodeURIComponent(this.s.value).replace(/%20/g, '+'); return false;" action="/"><div><input type="ext" class="search" name="s" onblur="if(this.value=='')this.value='<?php echo $search_placeholder; ?>';" onfocus="if(this.value=='<?php echo $search_placeholder; ?>')this.value='';" value="<?php echo $search_placeholder; ?>"></div></form></div></div>
<?php
}

function deel_avatar_default() {
    return GIT_URL . '/assets/img/default.png';
}
//评论头像缓存
function deel_avatar($avatar) {
    $tmp = strpos($avatar, 'http');
    $g = substr($avatar, $tmp, strpos($avatar, "'", $tmp) - $tmp);
    $tmp = strpos($g, 'avatar/') + 7;
    $f = substr($g, $tmp, strpos($g, "?", $tmp) - $tmp);
    $w = site_url();
    $e = ABSPATH . 'avatar/' . $f . '.png';
    $t = 30 * 24 * 60 * 60;
    if (!is_file($e) || (time() - filemtime($e)) > $t) copy(htmlspecialchars_decode($g) , $e);
    else $avatar = strtr($avatar, array(
        $g => $w . '/avatar/' . $f . '.png'
    ));
    if (filesize($e) < 500) copy(GIT_URL . '/assets/img/default.png', $e);
    return $avatar;
}
if (git_get_option('git_avater') == 'git_avatar_b') {
    add_filter('get_avatar', 'deel_avatar');
}
//头像镜像
function git_avatar_cache($avatar) {
    $avatar = str_replace(array(
        "www.gravatar.com",
        "0.gravatar.com",
        "1.gravatar.com",
        "2.gravatar.com"
    ) , git_get_option('git_avatar_qnurl') , $avatar);
    return $avatar;
}
if (git_get_option('git_avater') == 'git_avatar_qn') {
    add_filter('get_avatar', 'git_avatar_cache', 10, 3);
}

//随机头像
function local_random_avatar($avatar) {
    //weibo图片数组
    $random_avatar_arr = array('1mknpj301s01sq2p','1muzjj301s01sa9u','1nu4uj301s01s742','1vr2tj301s01s3ya','1ynnvj301s01s742','21h0xj301s01st8h','25715j301s01swe9','1ztzkj301s01s3ya','28gq6j301s01sdfm','2bnlaj301s01swe9','2gfu8j301s01s0si','2ji9yj301s01sjr6','2nm3ej301s01s3ya','2pxloj301s01sa9u','2t4ooj301s01s3ya','2wx4gj301s01sjr5','2eb8ij301s01sq2q','358evj301s01s742','37tkaj301s01st8h','3c0jsj301s01st8h','3egr8j301s01sdfm','3hnkaj301s01sjr5','3jtrmj301s01sgle','3oqcij301s01swe9','3rbkaj301s01sq2p','37si0j301s01smwx','3wryvj301s01sq2q','3wxndj301s01sdfm','46z1aj301s01sjr6','474jwj301s01sgld','4933uj301s01sa9t','4elr0j301s01s0si','4hemtj301s01s3ya','4hp1jj301s01swe9','4l437j301s01st8h','4p7guj301s01st8i','4ssbvj301s01sdfm','4xt1nj301s01s742','4zt3ij301s01sdfm','54yemj301s01s743','5aue3j301s01s742','5fqmsj301s01s0si','5irw7j301s01s0si','5pwg6j301s01sjr6','4orlpj301s01sq2p','60tpnj301s01smwy','63xd5j301s01st8h','67mwfj301s01sgle','5mpmhj301s01swe9','6brl7j301s01sa9u','6enj2j301s01sjr6','6fcy3j301s01st8h','6ighbj301s01sq2q','6l0ckj301s01sq2p','6o857j301s01st8i','6r1gej301s01st8h','6vf84j301s01sgld','6xv53j301s01sa9u','712o4j301s01s3ya','73fh4j301s01s3ya','6nd20j301s01sdfm','77f9vj301s01sq2p','7dffqj301s01sgld','7e6ytj301s01s3ya','7mmy6j301s01sa9u','7tffsj301s01s0si','78mwgj301s01swea','80qg1j301s01smwy','83d77j301s01sjr6','86p3wj301s01s0si','8ctn7j301s01sgle','8iwdlj301s01smwx','8prg4j301s01sa9u','7kvtcj301s01sgle','8velij301s01sgle','89dobj301s01swea','909gfj301s01sq2p','92fmmj301s01sq2p','933ykj301s01sjr6','9654fj301s01swe9','98z1rj301s01s3ya','9ce7ej301s01sgle','9d5frj301s01st8i','9fogej301s01swea','9j6f3j301s01sgle','9ji1qj301s01s742','9m619j301s01sa9u','9qtxnj301s01sgle','9tn7mj301s01sgle','9w2o6j301s01swe9');
        $random_avatar = array_rand($random_avatar_arr,1);
        $sinasrc = $random_avatar_arr[$random_avatar] ;
    if(git_get_option('git_avatar_qnurl')){
        $avatarsrc = '//' . git_get_option('git_avatar_qnurl') . '/myavatar/' . mt_rand(1, git_get_option('git_avatar_randnum')) . '.jpg';
    }else{
        $avatarsrc = '//wx2.sinaimg.cn/large/005zWjpngy1frvlt' . $sinasrc . '.jpg';
    }
    $avatar = "<img src=$avatarsrc class='avatar rand_avatar photo' />";
    return $avatar;
}
if (git_get_option('git_avater') == 'git_avatar_rand') {
    add_filter('get_avatar', 'local_random_avatar', 1, 5);
}
//给外部链接加上跳转
if (git_get_option('git_go')) {
    function git_go_url($content){
        preg_match_all('/<a(.*?)href="(.*?)"(.*?)>/', $content, $matches);
        if ($matches && !is_page('about')) {
            foreach ($matches[2] as $val) {
                if (strpos($val, '://') !== false && strpos($val, home_url()) === false && !preg_match('/\\.(jpg|jpeg|png|ico|bmp|gif|tiff)/i', $val)) {
                    $content = str_replace("href=\"{$val}\"", "href=\"" . get_permalink(git_page_id('go')) . "?url={$val}\" ", $content);
                }
            }
        }
        return $content;
    }
    add_filter('the_content', 'git_go_url', 999);
}
//关键字
function deel_keywords() {
    global $s, $post;
    $keywords = '';
    if (is_single()) {
        if (get_the_tags($post->ID)) {
            foreach (get_the_tags($post->ID) as $tag) $keywords.= $tag->name . ', ';
        }
        foreach (get_the_category($post->ID) as $category) $keywords.= $category->cat_name . ', ';
        $keywords = substr_replace($keywords, '', -2);
    } elseif (is_home()) {
        $keywords = git_get_option('git_keywords');
    } elseif (is_tag()) {
        $keywords = single_tag_title('', false);
    } elseif (is_category()) {
        $keywords = single_cat_title('', false);
    } elseif (is_search()) {
        $keywords = esc_html($s, 1);
    } else {
        $keywords = trim(wp_title('', false));
    }
    if ($keywords) {
        echo "<meta name=\"keywords\" content=\"$keywords\">\n";
    }
}
//网站描述
function deel_description() {
    global $s, $post;
    $description = '';
    $blog_name = get_bloginfo('name');
    $iexcerpt = $post->post_excerpt;
    if (is_singular()) {
        if (!empty($iexcerpt)) {
            $text = $iexcerpt;
        } else {
            $text = strip_shortcodes($post->post_content);
        }
        $description = trim(str_replace(array(
            "\r\n",
            "\r",
            "\n",
            "　",
            " "
        ) , " ", str_replace("\"", "'", strip_tags($text))));
        if (!($description)) $description = $blog_name . "-" . trim(wp_title('', false));
    } elseif (is_home()) {
        $description = git_get_option('git_description'); // 首頁要自己加
    } elseif (is_tag()) {
        $description = $blog_name . "'" . single_tag_title('', false) . "'";
    } elseif (is_category()) {
        $description = trim(strip_tags(category_description()));
    } elseif (is_archive()) {
        $description = $blog_name . "'" . trim(wp_title('', false)) . "'";
    } elseif (is_search()) {
        $description = $blog_name . ": '" . esc_html($s, 1) . "' 的搜索結果";
    } else {
        $description = $blog_name . "'" . trim(wp_title('', false)) . "'";
    }
    $description = mb_substr($description, 0, 220, 'utf-8');
    echo "<meta name=\"description\" content=\"$description\">\n";
}
//最新发布加new 单位'小时'
function deel_post_new($timer = '48') {
    $t = (strtotime(date("Y-m-d H:i:s")) - strtotime($post->post_date)) / 3600;
    if ($t < $timer) echo "<i>new</i>";
}
//修改评论表情调用路径
function deel_smilies_src($img_src, $img, $siteurl) {
    return GIT_URL . '/assets/img/smilies/' . $img;
}
//阻止站内文章Pingback
function deel_noself_ping(&$links) {
    $home = home_url();
    foreach ($links as $l => $link) if (0 === strpos($link, $home)) unset($links[$l]);
}
//修改默认发信地址
function deel_res_from_email($email) {
    $wp_from_email = get_option('admin_email');
    return $wp_from_email;
}
function deel_res_from_name($email) {
    $wp_from_name = get_option('blogname');
    return $wp_from_name;
}
//评论回应邮件通知
function comment_mail_notify($comment_id) {
    $admin_notify = '0'; // admin 要不要收回复通知 ( '1'=要 ; '0'=不要 )
    $admin_email = get_bloginfo('admin_email'); // $admin_email 可改为你指定的 e-mail.
    $comment = get_comment($comment_id);
    $comment_author_email = trim($comment->comment_author_email);
    $parent_id = $comment->comment_parent ? $comment->comment_parent : '';
    $blogname = get_option("blogname");
    global $wpdb;
    if ($wpdb->query("Describe {$wpdb->comments} comment_mail_notify") == '') $wpdb->query("ALTER TABLE {$wpdb->comments} ADD COLUMN comment_mail_notify TINYINT NOT NULL DEFAULT 0;");
    if (($comment_author_email != $admin_email && isset($_POST['comment_mail_notify'])) || ($comment_author_email == $admin_email && $admin_notify == '1')) $wpdb->query("UPDATE {$wpdb->comments} SET comment_mail_notify='1' WHERE comment_ID='$comment_id'");
    $notify = $parent_id ? get_comment($parent_id)->comment_mail_notify : '0';
    $spam_confirmed = $comment->comment_approved;
    if ($parent_id != '' && $spam_confirmed != 'spam' && $notify == '1') {
        $wp_email = 'no-reply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'])); // e-mail 发出点, no-reply 可改为可用的 e-mail.
        $to = trim(get_comment($parent_id)->comment_author_email);
        $subject = 'Hi，您在 [' . get_option("blogname") . '] 的留言有人回复啦！';
        $message = '<div style="color:#555;font:12px/1.5 微软雅黑,Tahoma,Helvetica,Arial,sans-serif;width:650px;margin:50px auto;border-top: none;box-shadow:0 0px 3px #aaaaaa;" ><table border="0" cellspacing="0" cellpadding="0"><tbody><tr valign="top" height="2"><td valign="top"><div style="background-color:white;border-top:2px solid #12ADDB;line-padding:0 15px 12px;width:650px;color:#555555;font-family:微软雅黑, Arial;;font-size:12px;"><h2 style="border-bottom:1px solid #DDD;font-size:14px;font-weight:normal;padding:8px 0 10px 8px;"><span style="color: #12ADDB;font-weight: bold;">&gt; </span>您在 <a style="text-decoration:none; color:#58B5F5;font-weight:600;" target="_blank" href="' . home_url() . '">' . $blogname . '</a> 网站上的留言有回复啦！</h2><div style="padding:0 12px 0 12px;margin-top:18px"><p>您好, ' . trim(get_comment($parent_id)->comment_author) . '! 您发表在文章 <a style="text-decoration:none;" target="_blank" href="' . get_the_permalink($comment->comment_post_ID) . '">《' . get_the_title($comment->comment_post_ID) . '》</a> 的评论:</p><p style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">' . nl2br(strip_tags(get_comment($parent_id)->comment_content)) . '</p><p>' . trim($comment->comment_author) . ' 给您的回复如下:</p><p style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">' . nl2br(strip_tags($comment->comment_content)) . '</p><p>您可以点击 <a style="text-decoration:none; color:#5692BC" target="_blank" href="' . htmlspecialchars(get_comment_link($parent_id)) . '">这里查看回复的完整內容</a>，也欢迎再次光临 <a style="text-decoration:none; color:#5692BC" target="_blank" href="' . home_url() . '">' . $blogname . '</a>。祝您天天开心，欢迎下次访问 <a style="text-decoration:none; color:#5692BC" target="_blank" href="' . home_url() . '">' . $blogname . '</a>！谢谢。</p><p style="float:right;">(此邮件由系统自动发出, 请勿回复)</p></div></div></td></tr></tbody></table><div style="color:#fff;background-color: #12ADDB;text-align : center;height:35px;padding-top:15px">Copyright © 2013-2018 ' . $blogname . '</div></div>';
        $from = "From: \"" . get_option('blogname') . "\" <$wp_email>";
        $headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";
        wp_mail($to, $subject, $message, $headers);
        //echo 'mail to ', $to, '<br/> ' , $subject, $message; // for testing

    }
}
//自动勾选
function deel_add_checkbox() {
    echo '<label for="comment_mail_notify" class="checkbox inline" style="padding-top:0;"><input name="comment_mail_notify" id="comment_mail_notify" value="comment_mail_notify" checked="checked" type="checkbox">评论通知</label>';
}
//时间显示方式‘xx以前’
function time_ago($type = 'commennt', $day = 7) {
    $d = $type == 'post' ? 'get_post_time' : 'get_comment_time';
    if (time() - $d('U') > 60 * 60 * 24 * $day) return;
    echo ' (', human_time_diff($d('U') , strtotime(current_time('mysql', 0))) , '前)';
}
function timeago($ptime) {
    $ptime = strtotime($ptime);
    $etime = time() - $ptime;
    if ($etime < 1) return '刚刚';
    $interval = array(
        12 * 30 * 24 * 60 * 60 => '年前 (' . date('Y-m-d', $ptime) . ')',
        30 * 24 * 60 * 60 => '个月前 (' . date('m-d', $ptime) . ')',
        7 * 24 * 60 * 60 => '周前 (' . date('m-d', $ptime) . ')',
        24 * 60 * 60 => '天前',
        60 * 60 => '小时前',
        60 => '分钟前',
        1 => '秒前'
    );
    foreach ($interval as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . $str;
        }
    };
}
//评论样式
function deel_comment_list($comment, $args, $depth) {
    echo '<li ';
    comment_class();
    echo ' id="comment-' . get_comment_ID() . '">';
    //头像
    echo '<div class="c-avatar">';
    echo str_replace(' src=', ' data-original=', get_avatar($comment->comment_author_email, $size = '54', deel_avatar_default()));
    //内容
    echo '<div class="c-main" id="div-comment-' . get_comment_ID() . '">';
    echo str_replace(' src=', ' data-original=', convert_smilies(get_comment_text()));
    if ($comment->comment_approved == '0') {
        echo '<span class="c-approved">您的评论正在排队审核中，请稍后！</span><br />';
    }
    //信息
    echo '<div class="c-meta">';
    if ($comment->user_id !== '0') {
        echo '<span class="c-author"><a target="_blank" href="' . get_author_posts_url($comment->user_id) . '">' . get_comment_author() . '</a></span>';
    } else {
        echo '<span class="c-author">' . get_comment_author_link() . '</span>';
    }
    if ($comment->user_id == '1') {
        echo '<a title="博主认证" class="vip"></a>';
    } elseif (git_get_option('git_vip')) {
        echo get_author_class($comment->comment_author_email, $comment->user_id);
    }
    echo get_comment_time('Y-m-d H:i ');
    echo time_ago();
    if ($comment->comment_approved !== '0') {
        echo comment_reply_link(array_merge($args, array(
            'add_below' => 'div-comment',
            'depth' => $depth,
            'max_depth' => $args['max_depth']
        )));
        echo edit_comment_link('(编辑)', ' - ', '');
        if (git_get_option('git_ua_b')) echo '<span style="color: #ff6600;"> ' . user_agent($comment->comment_agent) . '</span>';
    }
    echo '</div>';
    echo '</div></div>';
}
//添加钮Download
function DownloadUrl($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="dl" href="' . $href . '" target="_blank" rel="nofollow"><i class="fa fa-cloud-download"></i>' . $content . '</a>';
}
add_shortcode("dl", "DownloadUrl");
//添加钮git
function GithubUrl($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="dl" href="' . $href . '" target="_blank" rel="nofollow"><i class="fa fa-github-alt"></i>' . $content . '</a>';
}
add_shortcode('gt', 'GithubUrl');
//添加钮Demo
function DemoUrl($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="dl" href="' . $href . '" target="_blank" rel="nofollow"><i class="fa fa-external-link"></i>' . $content . '</a>';
}
add_shortcode('dm', 'DemoUrl');
//添加编辑器快捷按钮
function my_quicktags() {
    global $pagenow;
    if ($pagenow == 'post-new.php' || $pagenow == 'post.php') {
        wp_enqueue_script('my_quicktags', GIT_URL . '/assets/js/my_quicktags.js', array(
            'quicktags'
        ) , '1.0', true);
    }
};
add_action('admin_print_scripts', 'my_quicktags');
//过滤外文评论
if (git_get_option('git_spam_lang')) {
    function refused_spam_comments($commentdata){
        if (is_user_logged_in()) {
            return $commentdata;
        }
        $pattern = '/[一-龥]/u';
        $jpattern = '/[ぁ-ん]+|[ァ-ヴ]+/u';
        if (!preg_match($pattern, $commentdata['comment_content'])) {
            err('写点汉字吧，博主外语很捉急！You should type some Chinese word!');
        }
        if (preg_match($jpattern, $commentdata['comment_content'])) {
            err('日文滚粗！Japanese Get out！日本语出て行け！ You should type some Chinese word！');
        }
        return $commentdata;
    }
    add_filter('preprocess_comment', 'refused_spam_comments');
}
//屏蔽关键词，email，url，ip
if (git_get_option('git_spam_keywords')) {
    function Googlofuckspam($commentdata){
        if (is_user_logged_in()) {
            return $commentdata;
        }
        if (wp_blacklist_check($commentdata['comment_author'], $commentdata['comment_author_email'], $commentdata['comment_author_url'], $commentdata['comment_content'], $commentdata['comment_author_IP'], $commentdata['comment_agent'])) {
            header("Content-type: text/html; charset=utf-8");
            err('不好意思，您的评论违反本站评论规则');
        } else {
            return $commentdata;
        }
    }
    add_filter('preprocess_comment', 'Googlofuckspam');
}
//屏蔽长连接评论
if (git_get_option('git_spam_long') && !is_user_logged_in()) {
    function lang_url_spamcheck($approved, $commentdata)
    {
        return strlen($commentdata['comment_author_url']) > 50 ? 'spam' : $approved;
    }
    add_filter('pre_comment_approved', 'lang_url_spamcheck', 99, 2);
}
//屏蔽昵称，评论内容带链接的评论
if (git_get_option('git_spam_url')) {
    function Googlolink($commentdata){
        if (is_user_logged_in()) {
            return $commentdata;
        }
        $links = '/http:\\/\\/|https:\\/\\/|www\\./u';
        if (preg_match($links, $commentdata['comment_author']) || preg_match($links, $commentdata['comment_content'])) {
            err('在昵称和评论里面是不准发链接滴.');
        }
        return $commentdata;
    }
    add_filter('preprocess_comment', 'Googlolink');
}
//点赞
add_action('wp_ajax_nopriv_bigfa_like', 'bigfa_like');
add_action('wp_ajax_bigfa_like', 'bigfa_like');
function bigfa_like() {
    global $wpdb, $post;
    $id = filter_var($_POST["um_id"], FILTER_SANITIZE_NUMBER_INT);
    $action = $_POST["um_action"];
    if ($action == 'ding') {
        $bigfa_raters = get_post_meta($id, 'bigfa_ding', true);
        $expire = time() + 99999999;
        $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false; // make cookies work with localhost
        setcookie('bigfa_ding_' . $id, $id, $expire, '/', $domain, false);
        if (!$bigfa_raters || !is_numeric($bigfa_raters)) {
            update_post_meta($id, 'bigfa_ding', 1);
        } else {
            update_post_meta($id, 'bigfa_ding', ($bigfa_raters + 1));
        }
        echo get_post_meta($id, 'bigfa_ding', true);
    }
    die;
}
//最热排行
function hot_posts_list() {
    if (git_get_option('git_hot_b') == 'git_hot_zd') {
        $result = get_posts(array(
            'post__in' => get_option('sticky_posts') ,
            'order' => 'DESC',
            'orderby' => 'comment_count',
            'posts_per_page' => '10'
        ));
    } elseif (git_get_option('git_hot_b') == 'git_hot_comment') {
        $result = get_posts("numberposts=5&orderby=comment_count&order=desc");
    }
    $output = '';
    if (empty($result)) {
        $output = '<li>暂无数据</li>';
    } else {
        $i = 1;
        foreach ($result as $topten) {
            $postid = $topten->ID;
            $title = $topten->post_title;
            $commentcount = $topten->comment_count;
                $output.= '<li><p><span class="post-comments">评论 (' . $commentcount . ')</span><span class="muted"><a href="javascript:;" data-action="ding" data-id="' . $postid . '" id="Addlike" class="action';
                if (isset($_COOKIE['bigfa_ding_' . $postid])) $output.= ' actived';
                $output.= '"><i class="fa fa-heart-o"></i><span class="count">';
                if (get_post_meta($postid, 'bigfa_ding', true)) {
                    $output.= get_post_meta($postid, 'bigfa_ding', true);
                } else {
                    $output.= '0';
                }
                $output.= '</span>赞</a></span></p><span class="label label-' . $i . '">' . $i . '</span><a href="' . get_permalink($postid) . '" title="' . $title . '">' . $title . '</a></li>';
                $i++;
        }
    }
    echo $output;
}
//在 WordPress 编辑器添加“下一页”按钮
function add_next_page_button($mce_buttons) {
    $pos = array_search('wp_more', $mce_buttons, true);
    if ($pos !== false) {
        $tmp_buttons = array_slice($mce_buttons, 0, $pos + 1);
        $tmp_buttons[] = 'wp_page';
        $mce_buttons = array_merge($tmp_buttons, array_slice($mce_buttons, $pos + 1));
    }
    return $mce_buttons;
}
add_filter('mce_buttons', 'add_next_page_button');
//判断手机广告
function git_is_mobile() {
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    } elseif ((strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') === false) // many mobile devices (all iPh, etc.)
     || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'NetType/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MQQBrowser') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'HUAWEI') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'TBS/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Mi') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false) {
        return true;
    } else {
        return false;
    }
}
//搜索结果排除所有页面
function search_filter_page($query) {
    if ($query->is_search && !$query->is_admin) {
        $query->set('post_type', 'post');
    }
    return $query;
}
add_filter('pre_get_posts', 'search_filter_page');
// 更改后台字体
function git_admin_style() {
    echo '<style type="text/css">
	.setting select.link-to option[value="post"],.setting select[data-setting="link"] option[value="post"]{display:none;}
	#wp-admin-bar-git_guide>.ab-item::before {content:"\f331";top:3px;}#wp-admin-bar-git_option>.ab-item::before{content:"\f507";top:3px;}.users #the-list tr:hover{background:rgba(132,219,162,.61)}#role {width:8%;}* { font-family: "Microsoft YaHei" !important; }.wp-admin img.rand_avatar {max-Width:50px !important;}i, .ab-icon, .mce-close, i.mce-i-aligncenter, i.mce-i-alignjustify, i.mce-i-alignleft, i.mce-i-alignright, i.mce-i-blockquote, i.mce-i-bold, i.mce-i-bullist, i.mce-i-charmap, i.mce-i-forecolor, i.mce-i-fullscreen, i.mce-i-help, i.mce-i-hr, i.mce-i-indent, i.mce-i-italic, i.mce-i-link, i.mce-i-ltr, i.mce-i-numlist, i.mce-i-outdent, i.mce-i-pastetext, i.mce-i-pasteword, i.mce-i-redo, i.mce-i-removeformat, i.mce-i-spellchecker, i.mce-i-strikethrough, i.mce-i-underline, i.mce-i-undo, i.mce-i-unlink, i.mce-i-wp-media-library, i.mce-i-wp_adv, i.mce-i-wp_fullscreen, i.mce-i-wp_help, i.mce-i-wp_more, i.mce-i-wp_page, .qt-fullscreen, .star-rating .star,.qt-dfw{ font-family: dashicons !important; }.mce-ico { font-family: tinymce, Arial}.fa { font-family: FontAwesome !important; }.genericon { font-family: "Genericons" !important; }.appearance_page_scte-theme-editor #wpbody *, .ace_editor * { font-family: Monaco, Menlo, "Ubuntu Mono", Consolas, source-code-pro, monospace !important; }
    </style>';
}
add_action('admin_head', 'git_admin_style');
//输出缩略图地址
function post_thumbnail_src() {
    global $post;
    if ($values = get_post_custom_values("git_thumb")) { //输出自定义域图片地址
        $values = get_post_custom_values("git_thumb");
        $post_thumbnail_src = $values[0];
    } elseif (has_post_thumbnail()) { //如果有特色缩略图，则输出缩略图地址
        $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID) , 'full');
        $post_thumbnail_src = $thumbnail_src[0];
    } else {
        $post_thumbnail_src = '';
        ob_start();
        ob_end_clean();
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
        @$post_thumbnail_src = $matches[1][0]; //获取该图片 src
        if (empty($post_thumbnail_src)) { //如果日志中没有图片，则显示随机图片
            $random = mt_rand(1, 12);
            echo GIT_URL;
            echo '/assets/img/pic/' . $random . '.jpg';
            //如果日志中没有图片，则显示默认图片
            //echo '/assets/img/thumbnail.png';

        }
    };
    echo $post_thumbnail_src;
}
//禁用谷歌字体
function git_remove_open_sans() {
    wp_deregister_style('open-sans');
    wp_register_style('open-sans', false);
    wp_enqueue_style('open-sans', '');
}
add_action('init', 'git_remove_open_sans');
//免插件去除Category
if (git_get_option('git_categroy_b')):
    add_action('load-themes.php', 'no_category_base_refresh_rules');
    add_action('created_category', 'no_category_base_refresh_rules');
    add_action('edited_category', 'no_category_base_refresh_rules');
    add_action('delete_category', 'no_category_base_refresh_rules');
    function no_category_base_refresh_rules() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
    add_action('init', 'no_category_base_permastruct');
    function no_category_base_permastruct() {
        global $wp_rewrite;
        $wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
    }
    add_filter('category_rewrite_rules', 'no_category_base_rewrite_rules');
    function no_category_base_rewrite_rules($category_rewrite) {
        $category_rewrite = array();
        $categories = get_categories(array(
            'hide_empty' => false
        ));
        foreach ($categories as $category) {
            $category_nicename = $category->slug;
            if ($category->parent == $category->cat_ID) // recursive recursion
            $category->parent = 0;
            elseif ($category->parent != 0) $category_nicename = get_category_parents($category->parent, false, '/', true) . $category_nicename;
            $category_rewrite['(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
            $category_rewrite['(' . $category_nicename . ')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
            $category_rewrite['(' . $category_nicename . ')/?$'] = 'index.php?category_name=$matches[1]';
        }
        global $wp_rewrite;
        $old_category_base = get_option('category_base') ? get_option('category_base') : 'category';
        $old_category_base = trim($old_category_base, '/');
        $category_rewrite[$old_category_base . '/(.*)$'] = 'index.php?category_redirect=$matches[1]';
        return $category_rewrite;
    }
    add_filter('query_vars', 'no_category_base_query_vars');
    function no_category_base_query_vars($public_query_vars) {
        $public_query_vars[] = 'category_redirect';
        return $public_query_vars;
    }
    add_filter('request', 'no_category_base_request');
    function no_category_base_request($query_vars) {
        if (isset($query_vars['category_redirect'])) {
            $catlink = trailingslashit(home_url()) . user_trailingslashit($query_vars['category_redirect'], 'category');
            status_header(301);
            header("Location: $catlink");
            exit();
        }
        return $query_vars;
    }
endif;
/*cURL库 */
if (function_exists('curl_init')) {
    function curl_post($url, $postfields = '', $headers = '', $timeout = 20, $file = 0) {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => false,
            CURLOPT_POST => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_USERAGENT => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        );
        if (is_array($postfields) && $file == 0) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($postfields);
        } else {
            $options[CURLOPT_POSTFIELDS] = $postfields;
        }
        curl_setopt_array($ch, $options);
        if (is_array($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $result = curl_exec($ch);
        $code = curl_errno($ch);
        $msg = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array(
            'data' => $result,
            'code' => $code,
            'msg' => $msg,
            'info' => $info
        );
    }
}
//添加文章版权信息
function git_copyright($content) {
    if ((is_single() || is_feed()) && git_get_option('git_copyright_b')) {
        $copyright = str_replace(array(
            '{{title}}',
            '{{link}}'
        ) , array(
            get_the_title() ,
            get_permalink()
        ) , stripslashes(git_get_option('git_copyright_b')));
        $content.= '<hr /><div class="open-message">' . $copyright . '</div>';
    }
    return $content;
}
add_filter('the_content', 'git_copyright');
//fancybox图片灯箱效果
function fancybox($content) {
    $pattern = "/<a(.*?)href=('|\")([^>]*).(bmp|gif|jpeg|jpg|png|swf)('|\")(.*?)>(.*?)<\\/a>/i";
    $replacement = '<a$1href=$2$3.$4$5 rel="box" class="fancybox"$6>$7</a>';
    $content = preg_replace($pattern, $replacement, $content);
    return $content;
}
add_filter('the_content', 'fancybox');
 
//WordPress文字标签关键词自动内链
$match_num_min = git_get_option('git_autolink_1'); //一篇文章中同一個標籤少於幾次不自動鏈接
$match_num_max = git_get_option('git_autolink_2'); //一篇文章中同一個標籤最多自動鏈接幾次
function tag_sort($a, $b) {
    if ($a->name == $b->name) return 0;
    return (strlen($a->name) > strlen($b->name)) ? -1 : 1;
}
function tag_link($content) {
    global $match_num_min, $match_num_max;
    $posttags = get_the_tags();
    if ($posttags) {
        usort($posttags, "tag_sort");
        foreach ($posttags as $tag) {
            $link = get_tag_link($tag->term_id);
            $keyword = $tag->name;
            $cleankeyword = stripslashes($keyword);
            $url = "<a href=\"$link\" title=\"" . str_replace('%s', addcslashes($cleankeyword, '$') , '查看更多关于%s的文章') . "\"";
            $url.= ' target="_blank"';
            $url.= ">" . addcslashes($cleankeyword, '$') . "</a>";
            $limit = $match_num_max;
            $content = preg_replace('|(<a[^>]+>)(.*)(' . $keyword . ')(.*)(</a[^>]*>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
            $content = preg_replace('|(<img)(.*?)(' . $keyword . ')(.*?)(>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
            $content = preg_replace('|(<h[^>]+>)(.*)(' . $keyword . ')(.*)(</h[^>]*>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
            $cleankeyword = preg_quote($cleankeyword, '\'');
            $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            $content = preg_replace($regEx, $url, $content, $limit);
            $content = str_replace('%&&&&&%', stripslashes($keyword) , $content);
        }
    }
    return $content;
}
if (git_get_option('git_autolink_b')) {
    add_filter('the_content', 'tag_link', 1);
}
if (git_get_option('git_imgalt_b')) {
    //图片img标签添加alt，title属性
    function imagesalt($content){
        global $post;
        $pattern = "/<img(.*?)src=('|\")(.*?).(bmp|gif|jpeg|jpg|png)('|\")(.*?)>/i";
        $replacement = '<img$1src=$2$3.$4$5 alt="' . $post->post_title . '" title="' . $post->post_title . '"$6>';
        $content = preg_replace($pattern, $replacement, $content);
        return $content;
    }
    add_filter('the_content', 'imagesalt');
    //图片A标签添加title属性
    function aimagesalt($content){
        global $post;
        $pattern = "/<a(.*?)href=('|\")(.*?).(bmp|gif|jpeg|jpg|png)('|\")(.*?)>/i";
        $replacement = '<a$1href=$2$3.$4$5 title="' . $post->post_title . '"$6>';
        $content = preg_replace($pattern, $replacement, $content);
        return $content;
    }
    add_filter('the_content', 'aimagesalt');
}
//自动给文章以及评论添加nofollow属性
if (git_get_option('git_nofollow')) {
    function git_auto_nofollow($content)
    {
        $regexp = "<a\\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
        if (preg_match_all("/{$regexp}/siU", $content, $matches, PREG_SET_ORDER)) {
            if (!empty($matches)) {
                $srcUrl = get_option('siteurl');
                for ($i = 0; $i < count($matches); $i++) {
                    $tag = $matches[$i][0];
                    $tag2 = $matches[$i][0];
                    $url = $matches[$i][0];
                    $noFollow = '';
                    $pattern = '/rel\\s*=\\s*"\\s*[n|d]ofollow\\s*"/';
                    preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
                    if (count($match) < 1) {
                        $noFollow .= ' rel="nofollow" ';
                    }
                    $pos = strpos($url, $srcUrl);
                    if ($pos === false) {
                        $tag = rtrim($tag, '>');
                        $tag .= $noFollow . '>';
                        $content = str_replace($tag2, $tag, $content);
                    }
                }
            }
        }
        $content = str_replace(']]>', ']]>', $content);
        return $content;
    }
    add_filter('the_content', 'git_auto_nofollow');
}
//输出WordPress表情
function fa_get_wpsmiliestrans() {
    global $wpsmiliestrans;
    $wpsmilies = array_unique($wpsmiliestrans);
    $output = '';
    foreach ($wpsmilies as $alt => $src_path) {
        $output.= '<a class="add-smily" data-smilies="' . $alt . '"><img class="wp-smiley" style="height:24px;width:24px;" src="' . GIT_URL . '/assets/img/smilies/' . rtrim($src_path, "gif") . 'gif" /></a>';
    }
    return $output;
}
add_action('media_buttons_context', 'fa_smilies_custom_button');
function fa_smilies_custom_button($context) {
    $context = '';
    $context.= '<style>.smilies-wrap{background:#fff;border: 1px solid #ccc;box-shadow: 2px 2px 3px rgba(0, 0, 0, 0.24);padding: 10px;position: absolute;top: 60px;width: 375px;display:none}.smilies-wrap img{height:24px;width:24px;cursor:pointer;margin-bottom:5px} .is-active.smilies-wrap{display:block}</style> <a id="insert-media-button" style="position:relative" class="button insert-smilies add_smilies" title="添加表情" data-editor="content" href="javascript:;">^_^ 添加表情</a><div class="smilies-wrap">' . fa_get_wpsmiliestrans() . '</div><script>jQuery(document).ready(function(){jQuery(document).on("click", ".insert-smilies",function() { if(jQuery(".smilies-wrap").hasClass("is-active")){jQuery(".smilies-wrap").removeClass("is-active");}else{jQuery(".smilies-wrap").addClass("is-active");}});jQuery(document).on("click", ".add-smily",function() { send_to_editor(" " + jQuery(this).data("smilies") + " ");jQuery(".smilies-wrap").removeClass("is-active");return false;});});</script>';
    return $context;
}
//////// 后台评论列表获取表情按钮//////
function zfunc_smiley_button($custom = false, $before = '', $after = '') {
    if ($custom == true) $smiley_url = site_url() . '/wp-includes/images/smilies';
    else $customsmiley_url = GIT_URL . '/assets/img/smilies';
    echo $before;
?>
		<a href="javascript:grin(':?:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_question.gif" alt="" /></a>
		<a href="javascript:grin(':razz:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_razz.gif" alt="" /></a>
		<a href="javascript:grin(':sad:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_sad.gif" alt="" /></a>
		<a href="javascript:grin(':evil:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_evil.gif" alt="" /></a>
		<a href="javascript:grin(':!:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_exclaim.gif" alt="" /></a>
		<a href="javascript:grin(':smile:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_smile.gif" alt="" /></a>
		<a href="javascript:grin(':oops:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_redface.gif" alt="" /></a>
		<a href="javascript:grin(':grin:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_biggrin.gif" alt="" /></a>
		<a href="javascript:grin(':eek:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_surprised.gif" alt="" /></a>
		<a href="javascript:grin(':shock:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_eek.gif" alt="" /></a>
		<a href="javascript:grin(':???:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_confused.gif" alt="" /></a>
		<a href="javascript:grin(':cool:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_cool.gif" alt="" /></a>
		<a href="javascript:grin(':lol:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_lol.gif" alt="" /></a>
		<a href="javascript:grin(':mad:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_mad.gif" alt="" /></a>
		<a href="javascript:grin(':twisted:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_twisted.gif" alt="" /></a>
		<a href="javascript:grin(':roll:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_rolleyes.gif" alt="" /></a>
		<a href="javascript:grin(':wink:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_wink.gif" alt="" /></a>
		<a href="javascript:grin(':idea:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_idea.gif" alt="" /></a>
		<a href="javascript:grin(':arrow:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_arrow.gif" alt="" /></a>
		<a href="javascript:grin(':neutral:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_neutral.gif" alt="" /></a>
		<a href="javascript:grin(':cry:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_cry.gif" alt="" /></a>
		<a href="javascript:grin(':mrgreen:')"><img src="<?php
    echo $customsmiley_url; ?>/icon_mrgreen.gif" alt="" /></a>
<?php
    echo $after;
}
//Ajax_data_zfunc_smiley_button
function Ajax_data_zfunc_smiley_button() {
    if (isset($_GET['action']) && $_GET['action'] == 'Ajax_data_zfunc_smiley_button') {
        nocache_headers();
        zfunc_smiley_button(false, '<br />');
        die();
    }
}
add_action('admin_init', 'Ajax_data_zfunc_smiley_button');

//后台回复评论支持表情插入
function zfunc_admin_enqueue_scripts($hook_suffix) {
    global $pagenow;
    if ($pagenow == 'edit-comments.php') {
        wp_enqueue_script('zfunc-comment-reply', GIT_URL . '/assets/js/admin_reply.js', false, '1.0', true);
    }
}
add_action('admin_print_styles', 'zfunc_admin_enqueue_scripts');
//使用短代码添加回复后可见内容开始
function reply_to_read($atts, $content = null) {
    extract(shortcode_atts(array(
        "notice" => '<blockquote><center><p class="reply-to-read" style="color: blue;">注意：本段内容须成功“<a href="' . get_permalink() . '#respond" title="回复本文">回复本文</a>”后“<a href="javascript:window.location.reload();" title="刷新本页">刷新本页</a>”方可查看！</p></center></blockquote>'
    ) , $atts));
    $email = null;
    $user_ID = get_current_user_id();
    if ($user_ID > 0) {
        $email = get_user_by('id', $user_ID)->user_email;
        //对博主直接显示内容
        $admin_email = get_bloginfo('admin_email');
        if ($email == $admin_email) {
            return $content;
        }
    } else if (isset($_COOKIE['comment_author_email_' . COOKIEHASH])) {
        $email = str_replace('%40', '@', $_COOKIE['comment_author_email_' . COOKIEHASH]);
    } else {
        return $notice;
    }
    if (empty($email)) {
        return $notice;
    }
    global $wpdb;
    $post_id = get_the_ID();
    $query = "SELECT `comment_ID` FROM {$wpdb->comments} WHERE `comment_post_ID`={$post_id} and `comment_approved`='1' and `comment_author_email`='{$email}' LIMIT 1";
    if ($wpdb->get_results($query)) {
        return do_shortcode($content);
    } else {
        return $notice;
    }
}
add_shortcode('reply', 'reply_to_read');
//bing美图自定义登录页面背景
function custom_login_head() {
    if (git_get_option('git_loginbg')) {
        $imgurl = git_get_option('git_loginbg');
    } else {
        $imgurl = get_transient('Bing_img');
        if(false === $imgurl){ 
        $arr = json_decode(curl_post('https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1')['data']);
        $imgurl = 'http://cn.bing.com' . $arr->images[0]->url;
        set_transient('Bing_img', $imgurl, 60*60*24);
        }
    }
    if (defined('UM_DIR')) {
        echo '<style type="text/css">#um_captcha{width:170px!important;}</style>';
    }
    echo '<style type="text/css">#reg_passmail{display:none!important}body{background: url(' . $imgurl . ') center center no-repeat;-moz-background-size: cover;-o-background-size: cover;-webkit-background-size: cover;background-size: cover;background-attachment: fixed;}.login label,a {font-weight: bold;}.login-action-register #login{padding: 5% 0 0;}.login p {line-height: 1;}.login form {margin-top: 10px;padding: 16px 24px 16px;}h1 a { background-image:url(' . home_url() . '/favicon.ico)!important;width:32px;height:32px;-webkit-border-radius:50px;-moz-border-radius:50px;border-radius:50px;}#registerform,#loginform {background-color:rgba(251,251,251,0.3)!important;}.login label,a{color:#000!important;}form label input{margin-top:10px!important;}@media screen and (max-width:600px){.login-action-register h1 {display: none;}.login-action-register #login{top:50%!important;}}</style>';
}
add_action('login_head', 'custom_login_head');
function lhu(){
	return home_url();
}
add_filter('login_headerurl', 'lhu');
function lht(){
	return get_bloginfo('name');
}
add_filter('login_headertitle', 'lht');
/*
 * 强制阻止WordPress代码转义，关于代码高亮可以看这里http://gitcafe.net/archives/2986.html
*/
function git_esc_html($content) {
    $regex = '/(<pre\s+[^>]*?class\s*?=\s*?[",\'].*?prettyprint.*?[",\'].*?>)(.*?)(<\/pre>)/sim';
    return preg_replace_callback($regex, 'git_esc_callback', $content);
}
function git_esc_callback($matches) {
    $tag_open = $matches[1];
    $content = $matches[2];
    $tag_close = $matches[3];
    $content = esc_html($content);
    return $tag_open . $content . $tag_close;
}
add_filter('the_content', 'git_esc_html', 2);
add_filter('comment_text', 'git_esc_html', 2);
//强制兼容<pre>
function git_prettify_replace($text) {
    $replace = array(
        '<pre>' => '<pre class="prettyprint linenums" >'
    );
    $text = str_replace(array_keys($replace) , $replace, $text);
    return $text;
}
add_filter('the_content', 'git_prettify_replace');
//首页隐藏一些分类
function exclude_category_home($query) {
    if ($query->is_home) {
        $query->set('cat', git_get_option('git_blockcat')); //隐藏这两个分类
    }
    return $query;
}
add_filter('pre_get_posts', 'exclude_category_home');
function git_exclude_category_search($query) {
    if (!$query->is_admin && $query->is_search) {
        $query->set('cat', git_get_option('git_blockcat_search')); //隐藏这两个分类
    }
    return $query;
}
add_filter('pre_get_posts', 'git_exclude_category_search');
function git_exclude_category_rss($query) {
    if ($query->is_feed) {
        $query->set('cat', git_get_option('git_blockcat_rss')); //隐藏这两个分类
    }
    return $query;
}
add_filter('pre_get_posts', 'git_exclude_category_rss');
//固化插入图片选项
function git_attachment_display_settings() {
    update_option('image_default_align', 'center'); //居中显示
    update_option('image_default_link_type', 'file'); //连接到媒体文件本身
    update_option('image_default_size', 'full'); //完整尺寸
}
add_action('after_setup_theme', 'git_attachment_display_settings');
//后台日志阅读统计
add_filter('manage_posts_columns', 'postviews_admin_add_column');
function postviews_admin_add_column($columns) {
    $columns['views'] = '阅读';
    return $columns;
}
add_action('manage_posts_custom_column', 'postviews_admin_show', 10, 2);
function postviews_admin_show($column_name, $id) {
    if ($column_name != 'views') return;
    $post_views = get_post_meta($id, "views", true);
    echo $post_views;
}
/*短代码信息框 开始*/
/*绿色提醒框*/
function toz($atts, $content = null) {
    return '<div id="sc_notice">' . $content . '</div>';
}
add_shortcode('v_notice', 'toz');
/*红色提醒框*/
function toa($atts, $content = null) {
    return '<div id="sc_error">' . $content . '</div>';
}
add_shortcode('v_error', 'toa');
/*黄色提醒框*/
function toc($atts, $content = null) {
    return '<div id="sc_warn">' . $content . '</div>';
}
add_shortcode('v_warn', 'toc');
/*灰色提醒框*/
function tob($atts, $content = null) {
    return '<div id="sc_tips">' . $content . '</div>';
}
add_shortcode('v_tips', 'tob');
/*蓝色提醒框*/
function tod($atts, $content = null) {
    return '<div id="sc_blue">' . $content . '</div>';
}
add_shortcode('v_blue', 'tod');
/*蓝边文本框*/
function toe($atts, $content = null) {
    return '<div  class="sc_act">' . $content . '</div>';
}
add_shortcode('v_act', 'toe');
/*绿色按钮*/
function toi($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="greenbtn" href="' . $href . '" target="_blank" rel="nofollow">' . $content . '</a>';
}
add_shortcode('gb', 'toi');
/*蓝色按钮*/
function toj($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="bluebtn" href="' . $href . '" target="_blank" rel="nofollow">' . $content . '</a>';
}
add_shortcode('bb', 'toj');
/*黄色按钮*/
function tok($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="yellowbtn" href="' . $href . '" target="_blank" rel="nofollow">' . $content . '</a>';
}
add_shortcode('yb', 'tok');
/*灵魂按钮*/
function tom($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://'
    ) , $atts));
    return '<a class="lhb" href="' . $href . '" target="_blank" rel="nofollow">' . $content . '</a>';
}
add_shortcode('lhb', 'tom');
/*添加视频按钮*/
function too($atts, $content = null) {
    extract(shortcode_atts(array(
        "play" => '0'
    ) , $atts));
    if ($play == 0) {
        return '<video style="width:100%;" src="' . $content . '" controls preload >您的浏览器不支持HTML5的 video 标签，无法为您播放！</video>';
    }
    if ($play == 1) {
        return '<video style="width:100%;" src="' . $content . '" controls preload autoplay >您的浏览器不支持HTML5的 video 标签，无法为您播放！</video>';
    }
}
add_shortcode('video', 'too');
/*添加音频按钮*/
function tkk($atts, $content = null) {
    extract(shortcode_atts(array(
        "play" => '0'
    ) , $atts));
    if ($play == 0) {
        return '<audio style="width:100%;" src="' . $content . '" controls loop>您的浏览器不支持 audio 标签。</audio>';
    }
    if ($play == 1) {
        return '<audio style="width:100%;" src="' . $content . '" controls autoplay loop>您的浏览器不支持 audio 标签。</audio>';
    }
}
add_shortcode('audio', 'tkk');
/*弹窗下载*/
function ton($atts, $content = null) {
    extract(shortcode_atts(array(
        "href" => 'http://',
        "filename" => '',
        "filesize" => '',
        "filedown" => ''
    ) , $atts));
    return '<a class="lhb" id="showdiv" href="#fancydlbox" >文件下载</a><div id="fancydlbox" style="cursor:default;display:none;width:800px;"><div class="part" style="padding:20px 0;"><h2>下载声明:</h2> <div class="fancydlads" align="left"><p>' . git_get_option('git_fancydlcp') . '</p></div></div><div class="part" style="padding:20px 0;"><h2>文件信息：</h2> <div class="dlnotice" align="left"><p>文件名称：' . $filename . '<br />文件大小:' . $filesize . '<br />发布日期:' . get_the_modified_time('Y年n月j日') . '</p></div></div><div class="part" id="download_button_part"><a id="download_button" target="_blank" href="' . $href . '"><span></span>' . $filedown . '</a> </div><div class="part" style="padding:20px 0;"><div class="moredl" style="text-align:center;">[更多地址] : ' . $content . '</div></div><div class="dlfooter">' . git_get_option('git_fancydlad') . '</div></div>';
}
add_shortcode('fanctdl', 'ton');
//代码演示短代码
function git_demo($atts, $content = null) {
    return '<a class="lhb" href="' . get_permalink(git_page_id('demo')) . '?pid=' . get_the_ID() . '" target="_blank" rel="nofollow">' . $content . '</a>';
}
add_shortcode('demo', 'git_demo');
//下载单页短代码
function git_download($atts, $content = null) {
    return '<a class="lhb" href="' . get_permalink(git_page_id('download')) . '?pid=' . get_the_ID() . '" target="_blank" rel="nofollow">' . $content . '</a>';
}
add_shortcode('download', 'git_download');
/* 短代码信息框 完毕*/
//为WordPress添加展开收缩功能
function xcollapse($atts, $content = null) {
    extract(shortcode_atts(array(
        "title" => ""
    ) , $atts));
    return '<div style="margin: 0.5em 0;"><div class="xControl"><a href="javascript:void(0)" class="collapseButton xButton"><i class="fa fa-plus-square" ></i> ' . $title . '</a><div style="clear: both;"></div></div><div class="xContent" style="display: none;">' . $content . '</div></div>';
}
add_shortcode('collapse', 'xcollapse');
//简单的下载面板
function xdltable($atts, $content = null) {
    extract(shortcode_atts(array(
        "file" => "",
        "size" => ""
    ) , $atts));
    return '<table class="dltable"><tbody><tr><td style="background-color:#F9F9F9;" rowspan="3"><p>文件下载</p></td><td><i class="fa fa-list-alt"></i>&nbsp;&nbsp;文件名称：' . $file . '</td><td><i class="fa fa-th-large"></i>&nbsp;&nbsp;文件大小：' . $size . '</td></tr><tr><td colspan="2"><i class="fa fa-volume-up"></i>&nbsp;&nbsp;下载声明：' . git_get_option('git_dltable_b') . '</td></tr><tr><td colspan="2"><i class="fa fa-download"></i>&nbsp;&nbsp;下载地址：' . $content . '</td></tr></tbody></table>';
}
add_shortcode('dltable', 'xdltable');
//网易云音乐
function music163($atts, $content = null) {
    extract(shortcode_atts(array(
        "play" => "1"
    ) , $atts));
    return '<iframe style="width:100%;max-height:86px;" frameborder="no" border="0" marginwidth="0" marginheight="0" src="http://music.163.com/outchain/player?type=2&id=' . $content . '&auto=' . $play . '&height=66"></iframe>';
}
add_shortcode('netmusic', 'music163');
// add youku using iframe
function wp_iframe_handler_youku($matches, $attr, $url, $rawattr) {
    if (git_is_mobile()) {
        $height = 200;
    } else {
        $height = 485;
    }
    $iframe = '<iframe width=100% height=' . $height . 'px src="http://player.youku.com/embed/' . esc_attr($matches[1]) . '" frameborder=0 allowfullscreen></iframe>';
    return apply_filters('iframe_youku', $iframe, $matches, $attr, $url, $ramattr);
}
wp_embed_register_handler('youku_iframe', '#http://v.youku.com/v_show/id_(.*?).html#i', 'wp_iframe_handler_youku');
// add tudou using iframe
function wp_iframe_handler_tudou($matches, $attr, $url, $rawattr) {
    if (git_is_mobile()) {
        $height = 200;
    } else {
        $height = 485;
    }
    $iframe = '<iframe width=100% height=' . $height . 'px src="http://www.tudou.com/programs/view/html5embed.action?code=' . esc_attr($matches[1]) . '" frameborder=0 allowfullscreen></iframe>';
    return apply_filters('iframe_tudou', $iframe, $matches, $attr, $url, $ramattr);
}
wp_embed_register_handler('tudou_iframe', '#http://www.tudou.com/programs/view/(.*?)/#i', 'wp_iframe_handler_tudou');
wp_embed_unregister_handler('youku');
wp_embed_unregister_handler('tudou');
//添加后台个人信息
function git_add_contact_fields($contactmethods) {
    $contactmethods['qq'] = 'QQ';
    $contactmethods['sina_weibo'] = '新浪微博';
    $contactmethods['baidu'] = '百度ID';
    $contactmethods['twitter'] = 'Twitter';
    $contactmethods['github'] = 'GitHub';
    unset($contactmethods['yim']);
    unset($contactmethods['aim']);
    unset($contactmethods['jabber']);
    return $contactmethods;
}
add_filter('user_contactmethods', 'git_add_contact_fields');
//后台快捷键回复
function hui_admin_comment_ctrlenter() {
    echo '<script type="text/javascript">
        jQuery(document).ready(function($){
            $("textarea").keypress(function(e){
                if(e.ctrlKey&&e.which==13||e.which==10){
                    $("#replybtn").click();
                }
            });
        });
    </script>';
};
add_action('admin_footer', 'hui_admin_comment_ctrlenter');

//获取所有站点分类id
function Bing_category(){
    $cat_ids = get_transient('Bing_category');
    if (false === $cat_ids) {
        $categories = get_terms('category', 'hide_empty=0');
        $k = [];
        foreach ($categories as $categorie) {
            $k[] = $categorie->term_id;
        }
        $cat_ids = implode(",", $k);
        set_transient('Bing_category', $cat_ids, 60*60*24*5);//缓存5天
    }
    $cat_ids = explode(",", $cat_ids);
    foreach ($cat_ids as $catid) {
        $cat_name = get_cat_name($catid);
        $output = '<span>' . $cat_name . "=(<b>" . $catid . '</b>)</span>&nbsp;&nbsp;';
        echo $output;
    }
}

//用于自动清理未付款订单，每天一次
if(git_get_option('git_pay_way')=='git_eapay_ok'){
function git_clear_activation() {
	if ( !wp_next_scheduled( 'clean_daily_unpay' ) ) {
		wp_schedule_event(time(), 'daily', 'clean_daily_unpay');
	}
}
add_action('wp', 'git_clear_activation');
function do_this_daily() {
	global $wpdb;
	$wcu_sql = "DELETE FROM `" . Points_Database::points_get_table("users") . "` WHERE `status` = 'pending'";
	$wpdb->query($wcu_sql);
}
add_action('clean_daily_unpay', 'do_this_daily');
}

//新文章同步到新浪微博
function post_to_sina_weibo($post_ID) {
    if (get_post_meta($post_ID, 'git_weibo_sync', true) == 1) return;
    $get_post_info = get_post($post_ID);
    $get_post_centent = get_post($post_ID)->post_content;
    $get_post_title = get_post($post_ID)->post_title;
    if ($get_post_info->post_status == 'publish' && $_POST['original_post_status'] != 'publish') {
        $appkey = git_get_option('git_wbapky_b');
        $username = git_get_option('git_wbuser_b');
        $userpassword = git_get_option('git_wbpasd_b');
        $request = new WP_Http;
        $keywords = "";
        $tags = wp_get_post_tags($post_ID);
        foreach ($tags as $tag) {
            $keywords = $keywords . '#' . $tag->name . "#";
        }
        $string1 = '【' . strip_tags($get_post_title) . '】：';
        $string2 = $keywords . ' [阅读全文]：' . get_permalink($post_ID);
        /* 微博字数控制，避免超标同步失败 */
        $wb_num = (138 - WeiboLength($string1 . $string2)) * 2;
        $status = $string1 . mb_strimwidth(strip_tags(apply_filters('the_content', $get_post_centent)) , 0, $wb_num, '...') . $string2;
        $api_url = 'https://api.weibo.com/2/statuses/update.json';
        $body = array(
            'status' => $status,
            'source' => $appkey
        );
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode("$username:$userpassword")
        );
        $result = $request->post($api_url, array(
            'body' => $body,
            'headers' => $headers
        ));
        /* 若同步成功，则给新增自定义栏目git_weibo_sync，避免以后更新文章重复同步 */
        add_post_meta($post_ID, 'git_weibo_sync', 1, true);
    }
}
if (git_get_option('git_sinasync_b')) {
    add_action('publish_post', 'post_to_sina_weibo', 0);
}

/*
//获取微博字符长度函数
*/
function WeiboLength($str) {
    $arr = arr_split_zh($str); //先将字符串分割到数组中
    foreach ($arr as $v) {
        $temp = ord($v); //转换为ASCII码
        if ($temp > 0 && $temp < 127) {
            $len = $len + 0.5;
        } else {
            $len++;
        }
    }
    return ceil($len); //加一取整

}
/*
//拆分字符串函数,只支持 gb2312编码
//参考：http://u-czh.iteye.com/blog/1565858
*/
function arr_split_zh($tempaddtext) {
    $tempaddtext = iconv("UTF-8", "GBK//IGNORE", $tempaddtext);
    $cind = 0;
    $arr_cont = array();
    for ($i = 0; $i < strlen($tempaddtext); $i++) {
        if (strlen(substr($tempaddtext, $cind, 1)) > 0) {
            if (ord(substr($tempaddtext, $cind, 1)) < 0xA1) { //如果为英文则取1个字节
                array_push($arr_cont, substr($tempaddtext, $cind, 1));
                $cind++;
            } else {
                array_push($arr_cont, substr($tempaddtext, $cind, 2));
                $cind+= 2;
            }
        }
    }
    foreach ($arr_cont as & $row) {
        $row = iconv("gb2312", "UTF-8", $row);
    }
    return $arr_cont;
}
//百度收录提示
if (git_get_option('git_baidurecord_b') && function_exists('curl_init')) {
    function baidu_check($url, $post_id){
        $baidu_record = get_post_meta($post_id, 'baidu_record', true);
        if ($baidu_record != 1) {
            $url = 'http://www.baidu.com/s?wd=' . $url;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $rs = curl_exec($curl);
            curl_close($curl);
            if (!strpos($rs, '没有找到该URL，您可以直接访问') && !strpos($rs, '很抱歉，没有找到与')) {
                update_post_meta($post_id, 'baidu_record', 1) || add_post_meta($post_id, 'baidu_record', 1, true);
                return 1;
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }
    function baidu_record(){
        global $wpdb;
        $post_id = null === $post_id ? get_the_ID() : $post_id;
        if (baidu_check(get_permalink($post_id), $post_id) == 1) {
            echo '<a target="_blank" title="点击查看" rel="external nofollow" href="http://www.baidu.com/s?wd=' . get_the_title() . '">已收录</a>';
        } else {
            echo '<a style="color:red;" rel="external nofollow" title="点击提交，谢谢您！" target="_blank" href="http://zhanzhang.baidu.com/sitesubmit/index?sitename=' . get_permalink() . '">未收录</a>';
        }
    }
}

//主题自动更新服务
if (!git_get_option('git_updates_b')) {
    require 'modules/updates.php';
    $example_update_checker = new ThemeUpdateChecker('Git-alpha', 'https://raw.githubusercontent.com/yunluo/GitCafeApi/master/info.json');
}

//本地头像
class simple_local_avatars {
    private $user_id_being_edited;
    public function __construct() {
        add_action('admin_init', array(
            $this,
            'admin_init'
        ));
        add_action('show_user_profile', array(
            $this,
            'edit_user_profile'
        ));
        add_action('edit_user_profile', array(
            $this,
            'edit_user_profile'
        ));
        add_action('personal_options_update', array(
            $this,
            'edit_user_profile_update'
        ));
        add_action('edit_user_profile_update', array(
            $this,
            'edit_user_profile_update'
        ));
        add_filter('get_avatar', array(
            $this,
            'get_avatar'
        ) , 10, 5);
        add_filter('avatar_defaults', array(
            $this,
            'avatar_defaults'
        ));
    }
    public function admin_init() {
        register_setting('discussion', 'simple_local_avatars_caps', array(
            $this,
            'sanitize_options'
        ));
        add_settings_field('basic-user-avatars-caps', '本地上传头像权限管理', array(
            $this,
            'avatar_settings_field'
        ) , 'discussion', 'avatars');
    }
    public function avatar_settings_field($args) {
        $options = get_option('simple_local_avatars_caps');
?>
		<label for="simple_local_avatars_caps">
			<input type="checkbox" name="simple_local_avatars_caps" id="simple_local_avatars_caps" value="1" <?php
        checked($options['simple_local_avatars_caps'], 1); ?>/>仅具有头像上传权限的用户具有设置本地头像权限（作者及更高等级角色）</label>
		<?php
    }
    public function sanitize_options($input) {
        $new_input['simple_local_avatars_caps'] = empty($input['simple_local_avatars_caps']) ? 0 : 1;
        return $new_input;
    }
    public function get_avatar($avatar, $id_or_email, $size = 96, $default, $alt) {
        if (is_numeric($id_or_email)) $user_id = (int)$id_or_email;
        elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) $user_id = $user->ID;
        elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) $user_id = (int)$id_or_email->user_id;
        if (empty($user_id)) return $avatar;
        $local_avatars = get_user_meta($user_id, 'simple_local_avatar', true);
        if (empty($local_avatars) || empty($local_avatars['full'])) return $avatar;
        $size = (int)$size;
        if (empty($alt)) $alt = get_the_author_meta('display_name', $user_id);
        if (empty($local_avatars[$size])) {
            $upload_path = wp_upload_dir();
            $avatar_full_path = str_replace($upload_path['baseurl'], $upload_path['basedir'], $local_avatars['full']);
            $image = wp_get_image_editor($avatar_full_path);
            if (!is_wp_error($image)) {
                $image->resize($size, $size, true);
                $image_sized = $image->save();
            }
            $local_avatars[$size] = is_wp_error($image_sized) ? $local_avatars[$size] = $local_avatars['full'] : str_replace($upload_path['basedir'], $upload_path['baseurl'], $image_sized['path']);
            update_user_meta($user_id, 'simple_local_avatar', $local_avatars);
        } elseif (substr($local_avatars[$size], 0, 4) != 'http') {
            $local_avatars[$size] = home_url($local_avatars[$size]);
        }
        $author_class = is_author($user_id) ? ' current-author' : '';
        $avatar = "<img alt='" . esc_attr($alt) . "' src='" . $local_avatars[$size] . "' class='avatar avatar-{$size}{$author_class} photo' height='{$size}' width='{$size}' />";
        return apply_filters('simple_local_avatar', $avatar);
    }
    public function edit_user_profile($profileuser) {
?>
		<h3>头像</h3>
		<table class="form-table">
			<tr>
				<th><label for="basic-user-avatar">上传头像</label></th>
				<td style="width: 50px;" valign="top">
					<?php
        echo get_avatar($profileuser->ID); ?>
				</td>
				<td>
				<?php
        $options = get_option('simple_local_avatars_caps');
        if (empty($options['simple_local_avatars_caps']) || current_user_can('upload_files')) {
            // Nonce security ftw
            wp_nonce_field('simple_local_avatar_nonce', '_simple_local_avatar_nonce', false);
            echo '<input type="file" name="basic-user-avatar" id="basic-local-avatar" /><br />';
            if (empty($profileuser->simple_local_avatar)) {
                echo '<span class="description">尚未设置本地头像，请点击“浏览”按钮上传本地头像</span>';
            } else {
                echo '<input type="checkbox" name="basic-user-avatar-erase" value="1" />移除本地头像<br />';
                echo '<span class="description">如需要修改本地头像，请重新上传新头像。如需要移除本地头像，请选中上方的“移除本地头像”复选框并更新个人资料即可。<br/>移除本地头像后，将恢复使用 Gravatar 头像</span>';
            }
        } else {
            if (empty($profileuser->simple_local_avatar)) {
                echo '<span class="description">尚未设置本地头像，请在 Gravatar.com 网站设置头像</span>';
            } else {
                echo '<span class="description">你没有头像上传权限，如需要修改本地头像，请联系站点管理员</span>';
            }
        }
?>
				</td>
			</tr>
		</table>
		<script type="text/javascript">var form = document.getElementById('your-profile');form.encoding = 'multipart/form-data';form.setAttribute('enctype', 'multipart/form-data');</script>
		<?php
    }
    public function edit_user_profile_update($user_id) {
        if (!isset($_POST['_simple_local_avatar_nonce']) || !wp_verify_nonce($_POST['_simple_local_avatar_nonce'], 'simple_local_avatar_nonce')) return;
        if (!empty($_FILES['basic-user-avatar']['name'])) {
            $mimes = array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png',
            );
            if (!function_exists('wp_handle_upload')) require_once ABSPATH . 'wp-admin/includes/file.php';
            $this->avatar_delete($user_id);
            if (strstr($_FILES['basic-user-avatar']['name'], '.php')) wp_die('基于安全考虑 ".php" 格式文件禁止上传');
            $this->user_id_being_edited = $user_id;
            $avatar = wp_handle_upload($_FILES['basic-user-avatar'], array(
                'mimes' => $mimes,
                'test_form' => false,
                'unique_filename_callback' => array(
                    $this,
                    'unique_filename_callback'
                )
            ));
            update_user_meta($user_id, 'simple_local_avatar', array(
                'full' => $avatar['url']
            ));
        } elseif (!empty($_POST['basic-user-avatar-erase'])) {
            $this->avatar_delete($user_id);
        }
    }
    public function avatar_defaults($avatar_defaults) {
        remove_action('get_avatar', array(
            $this,
            'get_avatar'
        ));
        return $avatar_defaults;
    }
    public function avatar_delete($user_id) {
        $old_avatars = get_user_meta($user_id, 'simple_local_avatar', true);
        $upload_path = wp_upload_dir();
        if (is_array($old_avatars)) {
            foreach ($old_avatars as $old_avatar) {
                $old_avatar_path = str_replace($upload_path['baseurl'], $upload_path['basedir'], $old_avatar);
                @unlink($old_avatar_path);
            }
        }
        delete_user_meta($user_id, 'simple_local_avatar');
    }
    public function unique_filename_callback($dir, $name, $ext) {
        $user = get_user_by('id', (int)$this->user_id_being_edited);
        $name = $base_name = sanitize_file_name($user->ID . '_avatar');
        $number = 1;
        while (file_exists($dir . "/$name$ext")) {
            $name = $base_name . '_' . $number;
            $number++;
        }
        return $name . $ext;
    }
}
$simple_local_avatars = new simple_local_avatars;
//七牛CDN
if (!is_admin() && git_get_option('git_qncdn_b')) {
    add_action('wp_loaded', 'Googlo_ob_start');
    function Googlo_ob_start() {
        ob_start('Googlo_qiniu_cdn_replace');
    }
    function Googlo_qiniu_cdn_replace($html) {
        $local_host = home_url(); //博客域名
        $qiniu_host = git_get_option('git_cdnurl_b'); //七牛域名
        $cdn_exts = git_get_option('git_cdnurl_format'); //扩展名（使用|分隔）
        $cdn_dirs = git_get_option('git_cdnurl_dir'); //目录（使用|分隔）
        $cdn_dirs = str_replace('-', '\-', $cdn_dirs);
        if ($cdn_dirs) {
            $regex = '/' . str_replace('/', '\/', $local_host) . '\/((' . $cdn_dirs . ')\/[^\s\?\\\'\"\;\>\<]{1,}.(' . $cdn_exts . '))([\"\\\'\s\?]{1})/';
            $html = preg_replace($regex, $qiniu_host . '/$1$4', $html);
        } else {
            $regex = '/' . str_replace('/', '\/', $local_host) . '\/([^\s\?\\\'\"\;\>\<]{1,}.(' . $cdn_exts . '))([\"\\\'\s\?]{1})/';
            $html = preg_replace($regex, $qiniu_host . '/$1$3', $html);
        }
        return $html;
    }
}
//自动替换媒体库图片的域名
if (is_admin() && git_get_option('git_cdnurl_b') && git_get_option('git_adminqn_b')) {
    function attachment_replace($text) {
        $replace = array(
             home_url()  => git_get_option('git_cdnurl_b')
        );
        $text = str_replace(array_keys($replace) , $replace, $text);
        return $text;
    }
    add_filter('wp_get_attachment_url', 'attachment_replace');
}
//评论分页的seo处理
function canonical_for_git(){
    global $post;
    if (get_query_var('paged') > 1) {
        echo "\n";
        echo "<link rel='canonical' href='";
        echo get_permalink($post->ID);
        echo "' />\n";
        echo "<meta name=\"robots\" content=\"noindex,follow\">";
    }
}
add_action('wp_head', 'canonical_for_git');
//移除默认的图片宽度以及高度
function remove_wps_width($html) {
    $html = preg_replace('/(width|height)=\"\d*\"\s/', "", $html);
    return $html;
}
add_filter('post_thumbnail_html', 'remove_wps_width', 10);
add_filter('image_send_to_editor', 'remove_wps_width', 10);
//评论拒绝HTML代码
if (git_get_option('git_html_comment')) {
    function git_comment_post($incoming_comment){
        $incoming_comment['comment_content'] = htmlspecialchars($incoming_comment['comment_content']);
        $incoming_comment['comment_content'] = str_replace("'", '&apos;', $incoming_comment['comment_content']);
        return $incoming_comment;
    }
    function git_comment_display($comment_to_display){
        $comment_to_display = str_replace('&apos;', "'", $comment_to_display);
        return $comment_to_display;
    }
    add_filter('preprocess_comment', 'git_comment_post', '', 1);
    add_filter('comment_text', 'git_comment_display', '', 1);
    add_filter('comment_text_rss', 'git_comment_display', '', 1);
    add_filter('comment_excerpt', 'git_comment_display', '', 1);
}
if (!defined('UM_DIR')) { /*判断是否按照UM插件*/
    //注册表单
    function git_show_extra_register_fields() {
?>
    <p>
    <label for="password">密码<br/>
    <input id="password" class="input" type="password" tabindex="30" size="25" value="" name="password" />
    </label>
    </p>
    <p>
    <label for="repeat_password">确认密码<br/>
    <input id="repeat_password" class="input" type="password" tabindex="40" size="25" value="" name="repeat_password" />
    </label>
    </p>
    <?php
    }
    add_action('register_form', 'git_show_extra_register_fields');
    /*
     * Check the form for errors
    */
    function git_check_extra_register_fields($login, $email, $errors) {
        if ($_POST['password'] !== $_POST['repeat_password']) {
            $errors->add('passwords_not_matched', "<strong>错误提示</strong>: 两次填写密码不一致");
        }
        if (strlen($_POST['password']) < 8) {
            $errors->add('password_too_short', "<strong>错误提示</strong>: 密码必须大于8个字符");
        }
    }
    add_action('register_post', 'git_check_extra_register_fields', 10, 3);
    /*
     * 提交用户密码进数据库
    */
    function git_register_extra_fields($user_id) {
        $userdata = array();
        $userdata['ID'] = $user_id;
        if ($_POST['password'] !== '') {
            $userdata['user_pass'] = $_POST['password'];
        }
        $pattern = '/[一-龥]/u';
        if (preg_match($pattern, $_POST['user_login'])) {
            $userdata['user_nicename'] = $user_id;
        }
        $new_user_id = wp_update_user($userdata);
    }
    add_action('user_register', 'git_register_extra_fields', 100);
}
//注册之后跳转
if (git_get_option('git_register_redirect_ok')) {
    function git_registration_redirect() {
        if (git_get_option('git_redirect_choise') == 'git_redirect_home') {
            $redirect_url = home_url();
        } elseif (git_get_option('git_redirect_choise') == 'git_redirect_author') {
            $redirect_url = get_author_posts_url($user_id);
        } elseif (git_get_option('git_redirect_choise') == 'git_redirect_profile') {
            $redirect_url = admin_url('wp-admin/profile.php');
        } elseif (git_get_option('git_redirect_choise') == 'git_redirect_profile' && git_get_option('git_register_redirect_url')) {
            $redirect_url = git_get_option('git_register_redirect_url');
        }
        return $redirect_url;
    }
    add_filter('registration_redirect', 'git_registration_redirect');
}
//SMTP邮箱设置
if (git_get_option('git_mailsmtp_b')) {
    function googlo_mail_smtp($phpmailer) {
        $phpmailer->From = git_get_option('git_maildizhi_b'); //发件人地址
        $phpmailer->FromName = git_get_option('git_mailnichen_b'); //发件人昵称
        $phpmailer->Host = git_get_option('git_mailsmtp_b'); //SMTP服务器地址
        $phpmailer->Port = git_get_option('git_mailport_b'); //SMTP邮件发送端口
        if (git_get_option('git_smtpssl_b')) {
            $phpmailer->SMTPSecure = 'ssl';
        } else {
            $phpmailer->SMTPSecure = '';
        } //SMTP加密方式(SSL/TLS)没有为空即可
        $phpmailer->Username = git_get_option('git_mailuser_b'); //邮箱帐号
        $phpmailer->Password = git_get_option('git_mailpass_b'); //邮箱密码
        $phpmailer->IsSMTP();
        $phpmailer->SMTPAuth = true; //启用SMTPAuth服务

    }
    add_action('phpmailer_init', 'googlo_mail_smtp');
}
/*中文文件重命名
源代码来自：http://www.aips.me/wordpress-upload-pictures-renamed.html
*/
function git_upload_filter($file) {
    $time = date("YmdHis");
    $file['name'] = $time . "" . mt_rand(1, 100) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'git_upload_filter');
//后台文章重新排序
function git_post_order_in_admin($wp_query) {
    if (is_admin()) {
        $wp_query->set('orderby', 'modified');
        $wp_query->set('order', 'DESC');
    }
}
add_filter('pre_get_posts', 'git_post_order_in_admin');
/*
UA信息
*/
if (git_get_option('git_ua_b')) {
    function user_agent($ua){
        //开始解析操作系统
        $os = null;
        if (preg_match('/Windows NT 6.0/i', $ua)) {
            $os = "Windows Vista";
        } elseif (preg_match('/Windows NT 6.1/i', $ua)) {
            $os = "Windows 7";
        } elseif (preg_match('/Windows NT 6.2/i', $ua)) {
            $os = "Windows 8";
        } elseif (preg_match('/Windows NT 6.3/i', $ua)) {
            $os = "Windows 8.1";
        } elseif (preg_match('/Windows NT 10.0/i', $ua)) {
            $os = "Windows 10";
        } elseif (preg_match('/Windows NT 5.1/i', $ua)) {
            $os = "Windows XP";
        } elseif (preg_match('/Mac OS X/i', $ua)) {
            $os = "Mac OS X";
        } elseif (preg_match('#Linux#i', $ua)) {
            $os = "Linux ";
        } elseif (preg_match('#Windows Phone#i', $ua)) {
            $os = "Windows Phone ";
        } elseif (preg_match('/Windows NT 5.2/i', $ua) && preg_match('/Win64/i', $ua)) {
            $os = "Windows XP 64 bit";
        } elseif (preg_match('/Android ([0-9.]+)/i', $ua, $matches)) {
            $os = "Android " . $matches[1];
        } elseif (preg_match('/iPhone OS ([_0-9]+)/i', $ua, $matches)) {
            $os = 'iPhone ' . $matches[1];
        } else {
            $os = '未知操作系统';
        }
        if (preg_match('#(Camino|Chimera)[ /]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Camino ' . $matches[2];
        } elseif (preg_match('#SE 2([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = '搜狗浏览器 2' . $matches[1];
        } elseif (preg_match('#360([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = '360浏览器 ' . $matches[1];
        } elseif (preg_match('#Maxthon( |\\/)([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Maxthon 浏览器' . $matches[2];
        } elseif (preg_match('#Chrome/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Chrome ' . $matches[1];
        } elseif (preg_match('#XiaoMi/MiuiBrowser/([0-9.]+)#i', $ua, $matches)) {
            $browser = '小米浏览器 ' . $matches[1];
        } elseif (preg_match('#Safari/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Safari浏览器 ' . $matches[1];
        } elseif (preg_match('#opera mini#i', $ua)) {
            preg_match('#Opera/([a-zA-Z0-9.]+)#i', $ua, $matches);
            $browser = 'Opera Mini ' . $matches[1];
        } elseif (preg_match('#Opera.([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Opera ' . $matches[1];
        } elseif (preg_match('#TencentTraveler ([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = '腾讯TT浏览器 ' . $matches[1];
        } elseif (preg_match('#(UCWEB|UBrowser|UCBrowser)/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'UC浏览器 ' . $matches[1];
        } elseif (preg_match('#Vivaldi/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Vivaldi浏览器 ' . $matches[1];
        } elseif (preg_match('#wp-(iphone|android)/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'WordPress客户端 ' . $matches[1];
        } elseif (preg_match('#Edge ([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = '微软Edge浏览器 ' . $matches[1];
        } elseif (preg_match('#MSIE ([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Internet Explorer ' . $matches[1];
        } elseif (preg_match('#(Firefox|Phoenix|SeaMonkey|Firebird|BonEcho|GranParadiso|Minefield|Iceweasel)/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
            $browser = 'Firefox浏览器 ' . $matches[2];
        } else {
            $browser = '未知浏览器';
        }
        return $os . "  |  " . $browser;
    }
}
//添加碎语功能
function git_shuoshuo() {
    $labels = array(
        'name' => '说说',
        'singular_name' => '说说',
        'add_new' => '发表说说',
        'add_new_item' => '发表说说',
        'edit_item' => '编辑说说',
        'new_item' => '新说说',
        'view_item' => '查看说说',
        'search_items' => '搜索说说',
        'not_found' => '暂无说说',
        'not_found_in_trash' => '没有已遗弃的说说',
        'parent_item_colon' => '',
        'menu_name' => '说说'
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => true,
        'menu_icon' => 'dashicons-megaphone',
        'hierarchical' => false,
        'menu_position' => 4,
        'supports' => array(
            'editor',
            'title',
            'author'
        )
    );
    register_post_type('shuoshuo', $args);
}
add_action('init', 'git_shuoshuo');
//说说的固定连接格式
function custom_shuoshuo_link($link, $post = 0) {
    if ($post->post_type == 'shuoshuo') {
        return home_url('shuoshuo/' . $post->ID . '.html');
    } else {
        return $link;
    }
}
add_filter('post_type_link', 'custom_shuoshuo_link', 1, 3);
function custom_shuoshuo_rewrites_init() {
    add_rewrite_rule('shuoshuo/([0-9]+)?.html$', 'index.php?post_type=shuoshuo&p=$matches[1]', 'top');
}
add_action('init', 'custom_shuoshuo_rewrites_init');
//添加产品功能
if (!defined('UM_DIR')) { /*如果安装um的话，就禁用这个功能*/
    function git_product() {
        $labels = array(
            'name' => '产品',
            'singular_name' => '产品',
            'menu_name' => '产品',
            'name_admin_bar' => '产品',
            'archives' => '产品分类',
            'parent_item_colon' => '父分类',
            'all_items' => '所有产品',
            'add_new_item' => '添加新的产品',
            'add_new' => '添加新产品',
            'new_item' => '新产品',
            'edit_item' => '编辑产品',
            'update_item' => '更新产品',
            'view_item' => '查看产品',
            'search_items' => '搜索产品',
            'not_found' => '没有产品',
            'not_found_in_trash' => '回收站内没有产品',
            'featured_image' => '特色图片',
            'set_featured_image' => '设置特色图片',
            'remove_featured_image' => '移除特色图片',
            'use_featured_image' => '设为特色图片',
            'insert_into_item' => '插入产品',
            'uploaded_to_this_item' => '上传',
            'items_list' => '产品列表',
            'items_list_navigation' => '产品列表导航',
            'filter_items_list' => '筛选产品列表',
        );
        $rewrite = array(
            'slug' => 'product',
            'with_front' => true,
            'pages' => true,
            'feeds' => true,
        );
        $args = array(
            'label' => '产品',
            'description' => '产品功能',
            'labels' => $labels,
            'supports' => array(
                'title',
                'editor',
                'excerpt',
                'author',
                'thumbnail',
                'comments',
                'custom-fields',
            ) ,
            'hierarchical' => false,
            'public' => true,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-cart',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => 'product',
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'rewrite' => $rewrite,
            'capability_type' => 'page',
        );
        register_post_type('product', $args);
    }
    add_action('init', 'git_product', 0);
    //产品的固定连接格式
    function custom_product_link($link, $post = 0) {
        if ($post->post_type == 'product') {
            return home_url('product/' . $post->ID . '.html');
        } else {
            return $link;
        }
    }
    add_filter('post_type_link', 'custom_product_link', 1, 3);
    function custom_product_rewrites_init() {
        add_rewrite_rule('product/([0-9]+)?.html$', 'index.php?post_type=product&p=$matches[1]', 'top');
    }
    add_action('init', 'custom_product_rewrites_init');
}
/*
修复4.2表情bug
下面代码来自：http://www.9sep.org/remove-emoji-in-wordpress
*/
function disable_emoji9s_tinymce($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array(
            'wpemoji'
        ));
    } else {
        return array();
    }
}
//取当前主题下img\smilies\下表情图片路径
function custom_gitsmilie_src($old, $img) {
    return GIT_URL . '/assets/img/smilies/' . $img;
}
function init_gitsmilie() {
    global $wpsmiliestrans;
    //默认表情文本与表情图片的对应关系(可自定义修改)
    $wpsmiliestrans = array(
        ':mrgreen:' => 'icon_mrgreen.gif',
        ':neutral:' => 'icon_neutral.gif',
        ':twisted:' => 'icon_twisted.gif',
        ':arrow:' => 'icon_arrow.gif',
        ':shock:' => 'icon_eek.gif',
        ':smile:' => 'icon_smile.gif',
        ':???:' => 'icon_confused.gif',
        ':cool:' => 'icon_cool.gif',
        ':evil:' => 'icon_evil.gif',
        ':grin:' => 'icon_biggrin.gif',
        ':idea:' => 'icon_idea.gif',
        ':oops:' => 'icon_redface.gif',
        ':razz:' => 'icon_razz.gif',
        ':roll:' => 'icon_rolleyes.gif',
        ':wink:' => 'icon_wink.gif',
        ':cry:' => 'icon_cry.gif',
        ':eek:' => 'icon_surprised.gif',
        ':lol:' => 'icon_lol.gif',
        ':mad:' => 'icon_mad.gif',
        ':sad:' => 'icon_sad.gif',
        '8-)' => 'icon_cool.gif',
        '8-O' => 'icon_eek.gif',
        ':-(' => 'icon_sad.gif',
        ':-)' => 'icon_smile.gif',
        ':-?' => 'icon_confused.gif',
        ':-D' => 'icon_biggrin.gif',
        ':-P' => 'icon_razz.gif',
        ':-o' => 'icon_surprised.gif',
        ':-x' => 'icon_mad.gif',
        ':-|' => 'icon_neutral.gif',
        ';-)' => 'icon_wink.gif',
        '8O' => 'icon_eek.gif',
        ':(' => 'icon_sad.gif',
        ':)' => 'icon_smile.gif',
        ':?' => 'icon_confused.gif',
        ':D' => 'icon_biggrin.gif',
        ':P' => 'icon_razz.gif',
        ':o' => 'icon_surprised.gif',
        ':x' => 'icon_mad.gif',
        ':|' => 'icon_neutral.gif',
        ';)' => 'icon_wink.gif',
        ':!:' => 'icon_exclaim.gif',
        ':?:' => 'icon_question.gif',
    );
    //移除WordPress4.2版本更新所带来的Emoji钩子同时挂上主题自带的表情路径
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('tiny_mce_plugins', 'disable_emoji9s_tinymce');
    add_filter('smilies_src', 'custom_gitsmilie_src', 10, 2);
}
add_action('init', 'init_gitsmilie', 5);

//压缩html代码
if (git_get_option('git_compress')) {
    function wp_compress_html(){
        function wp_compress_html_main($buffer){
            if ( substr( ltrim( $buffer ), 0, 5) == '<?xml' ) return $buffer;
            $initial = strlen($buffer);
            $buffer = explode("<!--wp-compress-html-->", $buffer);
            $count = count($buffer);
            for ($i = 0; $i <= $count; $i++) {
                if (stristr($buffer[$i], '<!--wp-compress-html no compression-->')) {
                    $buffer[$i] = str_replace("<!--wp-compress-html no compression-->", " ", $buffer[$i]);
                } else {
                    $buffer[$i] = str_replace("\t", " ", $buffer[$i]);
                    $buffer[$i] = str_replace("\n\n", "\n", $buffer[$i]);
                    $buffer[$i] = str_replace("\n", "", $buffer[$i]);
                    $buffer[$i] = str_replace("\r", "", $buffer[$i]);
                    while (stristr($buffer[$i], '  ')) {
                        $buffer[$i] = str_replace("  ", " ", $buffer[$i]);
                    }
                }
                $buffer_out .= $buffer[$i];
            }
            $final = strlen($buffer_out);
            if ($initial !== 0) {
                $savings = ($initial - $final) / $initial * 100;
            } else {
                $savings = 0;
            }
            $savings = round($savings, 2);
            $buffer_out .= "\n<!--压缩前的大小: {$initial} bytes; 压缩后的大小: {$final} bytes; 节约：{$savings}% -->";
            return $buffer_out;
        }
            ob_start("wp_compress_html_main");
    }
    add_action('get_header', 'wp_compress_html');
    function git_unCompress($content)
    {
        if (preg_match_all('/(crayon-|<?xml|script|textarea|<\\/pre>)/i', $content, $matches)) {
            $content = '<!--wp-compress-html--><!--wp-compress-html no compression-->' . $content;
            $content .= '<!--wp-compress-html no compression--><!--wp-compress-html-->';
        }
        return $content;
    }
    add_filter('the_content', 'git_unCompress');
}
//增强编辑器开始
function git_editor_buttons($buttons) {
    $buttons[] = 'fontselect';
    $buttons[] = 'fontsizeselect';
    $buttons[] = 'backcolor';
    return $buttons;
}
add_filter('mce_buttons_3', 'git_editor_buttons');
//获取访客VIP样式
if (git_get_option('git_vip')):
    function get_author_class($comment_author_email, $user_id) {
        $author_count = get_transient('author_count');
        if(false === $author_count){
        global $wpdb;
        $author_count = count($wpdb->get_results("SELECT comment_ID as author_count FROM $wpdb->comments WHERE comment_author_email = '$comment_author_email' "));
        set_transient('author_count', $author_count, 60*60*2);//缓存2小时
        }
        if ($author_count >= 1 && $author_count < git_get_option('git_vip1')) echo '<a class="vip1" title="评论达人 LV.1"></a>';
        else if ($author_count >= git_get_option('git_vip1') && $author_count < git_get_option('git_vip2')) echo '<a class="vip2" title="评论达人 LV.2"></a>';
        else if ($author_count >= git_get_option('git_vip2') && $author_count < git_get_option('git_vip3')) echo '<a class="vip3" title="评论达人 LV.3"></a>';
        else if ($author_count >= git_get_option('git_vip3') && $author_count < git_get_option('git_vip4')) echo '<a class="vip4" title="评论达人 LV.4"></a>';
        else if ($author_count >= git_get_option('git_vip4') && $author_count < git_get_option('git_vip5')) echo '<a class="vip5" title="评论达人 LV.5"></a>';
        else if ($author_count >= git_get_option('git_vip5') && $author_count < git_get_option('git_vip6')) echo '<a class="vip6" title="评论达人 LV.6"></a>';
        else if ($author_count >= git_get_option('git_vip6')) echo '<a class="vip7" title="评论达人 LV.7"></a>';
    }
endif;
//取消后台登陆错误的抖动提示
function git_wps_login_error() {
    remove_action('login_head', 'wp_shake_js', 12);
}
add_action('login_head', 'git_wps_login_error');
//使链接自动可点击
add_filter('the_content', 'make_clickable');
//管理后台添加按钮
function git_custom_adminbar_menu($meta = TRUE) {
    global $wp_admin_bar;
    if (!is_user_logged_in()) {
        return;
    }
    if (!is_super_admin() || !is_admin_bar_showing()) {
        return;
    }
    $wp_admin_bar->add_menu(array(
        'id' => 'git_option',
        'title' => '主题选项', /* 设置链接名 */
        'href' => 'admin.php?page=theme-options.php'
    ));
    $wp_admin_bar->add_menu(array(
        'id' => 'git_guide',
        'title' => 'Git主题使用文档', /* 设置链接名 */
        'href' => 'http://gitcafe.net/archives/3275.html', /* 设置链接地址 */
        'meta' => array(
            'target' => '_blank'
        )
    ));
}
add_action('admin_bar_menu', 'git_custom_adminbar_menu', 100);
//支持中文名注册，来自肚兜
function git_sanitize_user($username, $raw_username, $strict) {
    $username = wp_strip_all_tags($raw_username);
    $username = remove_accents($username);
    $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
    $username = preg_replace('/&.+?;/', '', $username); // Kill entities
    if ($strict) {
        $username = preg_replace('|[^a-z\p{Han}0-9 _.\-@]|iu', '', $username);
    }
    $username = trim($username);
    $username = preg_replace('|\s+|', ' ', $username);
    return $username;
}
add_filter('sanitize_user', 'git_sanitize_user', 10, 3);
// 评论添加@，来自：http://www.ludou.org/wordpress-comment-reply-add-at.html
function git_comment_add_at($comment_text, $comment = '') {
    if ($comment->comment_parent > 0) {
        $comment_text = '@<a href="#comment-' . $comment->comment_parent . '">' . get_comment_author($comment->comment_parent) . '</a> ' . $comment_text;
    }
    return $comment_text;
}
add_filter('comment_text', 'git_comment_add_at', 20, 2);
//导航单页函数
function get_the_link_items($id = null) {
    $bookmarks = get_bookmarks('orderby=date&category=' . $id);
    $output = '';
    if (!empty($bookmarks)) {
        $output.= '<div class="link_items fontSmooth">';
        foreach ($bookmarks as $bookmark) {
            $output.= '<div class="link_item"><a class="link_item_inner apollo_' . $bookmark->link_rating . '" rel="nofollow" href="' . $bookmark->link_url . '" title="' . $bookmark->link_description . '" target="_blank" ><span class="sitename sitecolor_' . mt_rand(1, 14) . '">' . $bookmark->link_name . '</span></a></div>';
        }
        $output.= '</div>';
    }
    return $output;
}
function get_link_items() {
    $linkcats = get_terms('link_category', 'orderby=count&hide_empty=1&exclude=' . git_get_option('git_linkpage_cat'));
    if (!empty($linkcats)) {
        foreach ($linkcats as $linkcat) {
            $result.= '<h2 class="link_title">' . $linkcat->name . '</h2>';
            if ($linkcat->description) $result.= '<div class="link_description">' . $linkcat->description . '</div>';
            $result.= get_the_link_items($linkcat->term_id);
        }
    } else {
        $result = get_the_link_items();
    }
    return $result;
}
/**
 * 修复 WordPress 找回密码提示“抱歉，该key似乎无效”
 * http://www.wpdaxue.com/lost-password-error-invalidkey.html
 */
function git_reset_password_message($message, $key) {
    if (strpos($_POST['user_login'], '@')) {
        $user_data = get_user_by('email', trim($_POST['user_login']));
    } else {
        $login = trim($_POST['user_login']);
        $user_data = get_user_by('login', $login);
    }
    $user_login = $user_data->user_login;
    $msg = "有人要求重设如下帐号的密码：\r\n\r\n";
    $msg.= network_site_url() . "\r\n\r\n";
    $msg.= sprintf('用户名：%s', $user_login) . "\r\n\r\n";
    $msg.= "若这不是您本人要求的，请忽略本邮件，一切如常。\r\n\r\n";
    $msg.= "要重置您的密码，请打开下面的链接：\r\n\r\n";
    $msg.= wp_login_url() . "?action=rp&key=$key&login=" . rawurlencode($user_login);
    return $msg;
}
add_filter('retrieve_password_message', 'git_reset_password_message', null, 2);
//保护后台登录
if (git_get_option('git_admin')) {
    function git_login_protection()
    {
        if ($_GET['' . git_get_option('git_admin_q') . ''] !== '' . git_get_option('git_admin_a') . '') {
            header('Location: http://www.baidu.com');
        }
        /* 不用密码登录，直接滚到百度去 */
        exit;
    }
    add_action('login_enqueue_scripts', 'git_login_protection');
}
/*救命啊！ps.很好，搜索这段代码很可能意味着你把自己后台给锁了，将保护后台登录这大段代码删除即可*/
//登录失败提醒
if (git_get_option('git_login_tx')) {
    function git_login_failed_notify() {
        date_default_timezone_set('PRC');
        $admin_email = get_bloginfo('admin_email');
        $to = $admin_email;
        $subject = '您的网站登录错误警告';
        $message = '<p>您好！您的网站(' . get_option("blogname") . ')有登录错误！</p>' . '<p>请确定是您自己的登录失误，以防别人攻击！登录信息如下：</p>' . '<p>登录名：' . $_POST['log'] . '</p>' . '<p>登录密码：' . $_POST['pwd'] . '</p>' . '<p>登录时间：' . date("Y-m-d H:i:s") . '</p>' . '<p>登录IP：' . $_SERVER['REMOTE_ADDR'] . '</p>' . '<p style="float:right">————本邮件由系统发送，无需回复</p>';
        $wp_email = 'no-reply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
        $from = "From: \"" . get_option('blogname') . "\" <$wp_email>";
        $headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";
        wp_mail($to, $subject, $message, $headers);
    }
    add_action('wp_login_failed', 'git_login_failed_notify');
}
//取消静态资源的版本查询
if (git_get_option('git_query')) {
    function _remove_script_version($src){
        $parts = explode('?ver', $src);
        return $parts[0];
    }
    add_filter('script_loader_src', '_remove_script_version', 15, 1);
    add_filter('style_loader_src', '_remove_script_version', 15, 1);
}
//百度主动推送
if (git_get_option('git_sitemap_api')) {
    function Git_Baidu_Submit($post_ID) {
        if (get_post_meta($post_ID, 'git_baidu_submit', true) == 1) return;
        $url = get_permalink($post_ID);
        $api = git_get_option('git_sitemap_api');
        $request = new WP_Http;
        $result = $request->request($api, array(
            'method' => 'POST',
            'body' => $url,
            'headers' => 'Content-Type: text/plain'
        ));
        if ( is_array( $result ) && !is_wp_error($result) && $result['response']['code'] == '200' ) {
            error_log('baidu_submit_result：'.$result['body']);
            $result = json_decode($result['body'], true);
        }
        if (array_key_exists('success', $result)) {
            add_post_meta($post_ID, 'git_baidu_submit', 1, true);
        }
    }
    add_action('publish_post', 'Git_Baidu_Submit', 0);
}
//登录可见
function login_to_read($atts, $content = null) {
    $logina = '<a target="_blank" href="' . esc_url(wp_login_url(get_permalink())) . '">登录</a>';
    extract(shortcode_atts(array(
        "notice" => '<blockquote><center><p class="reply-to-read" style="color: blue;">注意：本段内容须“' . $logina . '”后方可查看！</p></center></blockquote>'
    ) , $atts));
    if (is_user_logged_in() && !is_null($content) && !is_feed()) {
        return '<div class="e-secret"><fieldset><legend>隐藏的内容</legend>
	' . $content . '<div class="clear"></div></fieldset></div>';
    }
    return $notice;
}
add_shortcode('vip', 'login_to_read');
// 部分内容输入密码可见
function e_secret($atts, $content = null) {
    if (!isset($_COOKIE['weixin_fensi']) && isset($_POST['e_secret_key']) && $_POST['e_secret_key'] == git_get_option('git_mp_code')) {
        setcookie('weixin_fensi', 10086, time() + 2592000, COOKIEPATH, COOKIE_DOMAIN, false); //30天时间
        return '<script type="text/javascript">window.location = document.referrer;</script>';
    }
    extract(shortcode_atts(array(
        'wx' => null
    ) , $atts));
    if ($_COOKIE['weixin_fensi'] == '10086' || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return '<div class="e-secret"><fieldset><legend>隐藏的内容</legend>
	' . $content . '<div class="clear"></div></fieldset></div>';
    } else {
        if ($wx == '1') {
            return '<div class="wxbox"><img class="wxpic" src="' . git_get_option('git_mp_qr') . '" alt="' . git_get_option('git_mp_name') . '" title="' . git_get_option('git_mp_name') . '" align="right"><form method="post" name="e-secret" action="' . $_SERVER["REQUEST_URI"] . '"><span class="yzts" style="font-size:18px;">验证码：</span><input name="e_secret_key" id="verifycode" value="" type="text"><input id="verifybtn" name="" value="提交查看" type="submit"></form><div class="wxtips">' . git_get_option('git_mp_tips') . '</div><div class="cl"></div></div>';
        } else {
            return '<form class="e-secret" method="post" name="e-secret" action="' . $_SERVER["REQUEST_URI"] . '"><label>输入密码查看加密内容：</label><input type="password" name="e_secret_key" class="euc-y-i" maxlength="50"><input type="submit" class="euc-y-s" value="确定"><div class="euc-clear"></div></form>';
        }
    }
}
add_shortcode('secret', 'e_secret');
//加载密码可见的样式
function secret_css() {
    global $post;
    if (is_singular() && has_shortcode($post->post_content, 'secret')) {
        echo '<style type="text/css">form.e-secret{margin:20px 0;padding:20px;height:60px;background:#f8f8f8}.e-secret input.euc-y-i[type=password]{float:left;background:#fff;width:100%;line-height:36px;margin-top:5px;border-radius:3px}.e-secret input.euc-y-s[type=submit]{float:right;margin-top:-47px;width:30%;margin-right:1px;border-radius:0 3px 3px 0}input.euc-y-s[type=submit]{background-color:#3498db;color:#fff;font-size:21px;box-shadow:none;-webkit-transition:.4s;-moz-transition:.4s;-o-transition:.4s;transition:.4s;-webkit-backface-visibility:hidden;position:relative;cursor:pointer;padding:13px 20px;text-align:center;border-radius:50px;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none;border:0;height:auto;outline:medium;line-height:20px;margin:0}input.euc-y-s[type=submit]:hover{background-color:#5dade2}input.euc-y-i[type=password],input.euc-y-i[type=text]{border:1px solid #F2EFEF;color:#777;display:block;background:#FCFCFC;font-size:18px;transition:all .5s ease 0;outline:0;box-sizing:border-box;-webkit-border-radius:25px;-moz-border-radius:25px;border-radius:25px;padding:5px 16px;margin:0;height:auto;line-height:30px}input.euc-y-i[type=password]:hover,input.euc-y-i[type=text]:hover{border:1px solid #56b4ef;box-shadow:0 0 4px #56b4ef}.wxbox{border:1px dashed #F60;line-height:200%;padding-top:5px;color:red;background-color:#FFF4FF;overflow:hidden;clear:both}.wxbox.yzts{padding-left:10%}.wx form{float:left}.wxbox #verifycode{width:46%;height:32px;line-height:30px;padding:0 25px;border:1px solid #F60}.wxbox #verifybtn{width:10%;height:34px;line-height:34px;padding:0 5px;background-color:#F60;text-align:center;border:none;cursor:pointer;color:#FFF}.cl{clear:both;height:0}.wxpic{float:left;width:18%}.wxtips{color:#32B9B5;float:left;width:72%;padding-left:5%;padding-top:0;font-size:20px;line-height:150%;text-align:left;font-family:Microsoft YaHei}.yzts{margin-left: 40px}@media (max-width:600px){.yzts{margin-left:5px}.wxpic{float:left}.wxbox #verifycode{width:35%}.wxbox #verifybtn{width:22%}.wxpic,.wxtips{width:100%}.wxtips{font-size:15px;padding:2px}}</style>';
    }
}
add_action('wp_head', 'secret_css');

//WordPress函数代码结束,打算在本文件添加代码的建议参照这个方法：http://gitcafe.net/archives/4032.html

?>