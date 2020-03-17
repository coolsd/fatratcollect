<?php
/**
 * Copyright (c) 2018 Fat Rat Collect . All rights reserved.
 * 胖鼠采集要做wordpress最好用的采集器.
 * 如果你觉得我写的还不错.可以去Github上 Star
 * 现在架子已经有了.欢迎大牛加入开发.一起丰富胖鼠的功能
 * Github: https://github.com/fbtopcn/fatratcollect
 * @Author: fbtopcn
 * @CreateTime: 2018年12月30日 02:24
 */

use Illuminate\Support\Str;
use QL\QueryList;
use GuzzleHttp\Exception\RequestException;

class FRC_Spider
{

    protected $wpdb;
    protected $table_post;
    protected $table_options;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_post = $wpdb->prefix . 'fr_post';
        $this->table_options = $wpdb->prefix . 'fr_options';
    }

    /**
     * 微信
     * @return array
     */
    public function grab_wx_page(){
        $urls = !empty($_REQUEST['collect_wx_urls']) ? sanitize_text_field($_REQUEST['collect_wx_urls']) : '' ;
        if (empty($urls)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '链接不能为空'];
        }
        $option = $this->wpdb->get_row("SELECT * FROM {$this->table_options} WHERE `collect_name` = '微信'", ARRAY_A );
        if (empty($option)){
            // 默认生成基础配置
            $sql = "INSERT INTO `{$this->table_options}` SET `collect_name` = '微信', `collect_describe` = '胖鼠创建. 可修改为更适合你的微信采集规则. 不可删除..', `collect_type` = 'single', `collect_content_range` = '#img-content',  `collect_content_rules` = 'title%#activity-name|text|null)(content%#js_content|html|null' ";
            $this->wpdb->query($sql);
            $option = $this->wpdb->get_row("SELECT * FROM {$this->table_options} WHERE `collect_name` = '微信'", ARRAY_A );
        }

        if ($this->run_spider_single_page($option, $urls)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];
        } else {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
        }

    }

    /**
     * 简书
     * @return array
     */
    public function grab_js_page(){
        $urls = !empty($_REQUEST['collect_js_urls']) ? sanitize_text_field($_REQUEST['collect_js_urls']) : '' ;
        if (empty($urls)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '链接不能为空'];
        }
        $option = $this->wpdb->get_row("SELECT * FROM {$this->table_options} WHERE `collect_name` = '简书'", ARRAY_A );
        if (empty($option)){
            // 默认生成基础配置
            $sql = "INSERT INTO `{$this->table_options}` SET `collect_name` = '简书', `collect_describe` = '胖鼠创建. 可修改为更适合你的简书采集规则. 不可删除..', `collect_type` = 'single', `collect_content_range` = '.article',  `collect_content_rules` = 'title%h1[class=title]|text|null)(content%div[class=show-content]|html|a' ";
            $this->wpdb->query($sql);
            $option = $this->wpdb->get_row("SELECT * FROM {$this->table_options} WHERE `collect_name` = '简书'", ARRAY_A );
        }

        if ($this->run_spider_single_page($option, $urls)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];
        } else {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
        }

    }


    /**
     * 抓取历史页面
     */
    public function grab_history_page()
    {

        $history_url            = !empty($_REQUEST['collect_history_url']) ? sanitize_text_field($_REQUEST['collect_history_url']) : '';
        $history_page_number    = !empty($_REQUEST['collect_history_page_number']) ? sanitize_text_field($_REQUEST['collect_history_page_number']) : '';
        $option_id              = !empty($_REQUEST['collect_history_relus_id']) ? sanitize_text_field($_REQUEST['collect_history_relus_id']) : '';

        if (!strstr($history_url, '{page}')){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'URL不正确。未包含 {page} 关键字 or URL不能为空'];
        }

        if (empty($history_page_number)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '请填写要采集的页面'];
        }

        $page_count = explode(',', $history_page_number);
        if (count($page_count) < 0 || count($page_count) > 10){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '页码不建议超过10页'];
        }

        $option = $this->get_option($option_id);
        if (!$option) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '请选择一个有效的配置, 配置异常'];
        }

        if (parse_url($history_url)['host'] != parse_url($option['collect_list_url'])['host']){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '你的规则配置肯定选错了。自己检查一下改改'];
        }

        collect($page_count)->map(function($digital) use ($history_url, $option){
            $option['collect_list_url'] = str_replace('{page}', $digital, $history_url);
            $this->run_spider_list_page($option);
        });


        return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];

    }


    /**
     * 抓取列表页面
     * @return array
     */
    public function grab_list_page()
    {
        $option_id = !empty($_REQUEST['option_id']) ? sanitize_text_field($_REQUEST['option_id']) : 0;

        $option = $this->get_option($option_id);
        if (!$option) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '未查询到配置, 配置ID错误'];
        }

        if ($this->run_spider_list_page($option)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];
        } else {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
        }
    }


    /**
     * 全站采集
     * @return array
     */
    public function grab_all_page()
    {
        $option_id = !empty($_REQUEST['option_id']) ? sanitize_text_field($_REQUEST['option_id']) : 0;

        $option = $this->get_option($option_id);
        if (!$option) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '未查询到配置, 配置ID错误'];
        }

        if ($this->run_spider_all_page($option)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];
        } else {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
        }
    }


    /**
     * 关键字采集
     * @return array
     */
    public function grab_keyword_page()
    {
        $option_id = !empty($_REQUEST['option_id']) ? sanitize_text_field($_REQUEST['option_id']) : 0;
        $keyword_name = !empty($_REQUEST['keyword_name']) ? sanitize_text_field($_REQUEST['keyword_name']) : '';
        $keyword_number = !empty($_REQUEST['keyword_number']) ? sanitize_text_field($_REQUEST['keyword_number']) : 10;

        $option = $this->get_option($option_id);
        if (!$option) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '未查询到配置, 配置ID错误'];
        }

        $option['collect_name'] = $keyword_name;
        $option['collect_number'] = $keyword_number;
        if ($this->run_spider_keyword_page($option)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];
        } else {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
        }
    }


    /**
     * 抓取详情
     * @return array
     */
    public function grab_details_page(){
        $urls       = !empty($_REQUEST['collect_details_urls']) ? sanitize_text_field($_REQUEST['collect_details_urls']) : '' ;
        $option_id  = !empty($_REQUEST['collect_details_relus']) ? sanitize_text_field($_REQUEST['collect_details_relus']) : 0 ;
        if (empty($urls)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '链接不能为空'];
        }
        if (empty($option_id)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '请选择一个有效的详情配置'];
        }
        $option = $this->get_option($option_id);
        if (!$option) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '未查询到配置, 配置ID错误'];
        }

        if ($this->run_spider_single_page($option, $urls)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.'];
        } else {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
        }

    }

    /**
     * TODO 此函数抽空优化
     * @param $option
     * @return bool
     */
    public function run_spider_list_page($option)
    {
        // TODO 错误信息再优化
        if ($option['collect_type'] != 'list'){
            return false;
        }

        $articles = $this->_QueryList($option['collect_list_url'], $option['collect_remove_head'])
            ->range($option['collect_list_range'])
            ->encoding('UTF-8')
            ->rules( $this->rulesFormat($option['collect_list_rules']) )
            ->query(function($item) use ($option) {
                // 新闻详情

                if (!empty($item['link'])) {
                    // 如果没有域名头自动拼接一下
                    if (!isset(parse_url($item['link'])['host'])){
                        $item['link'] = parse_url($option['collect_list_url'])['scheme'].'://'.parse_url($option['collect_list_url'])['host'].'/'.ltrim($item['link'], '/');
                    }

                    try {
                        $ql = $this->_QueryList($item['link'], $option['collect_remove_head'])
                            ->range($option['collect_content_range'])
                            ->encoding('UTF-8')
                            ->rules( $this->rulesFormat($option['collect_content_rules']) )
                            ->queryData();
                    } catch (RequestException $e) {
                        return false;
                    }

                    $ql = current($ql);
                    $item = array_merge($item, $ql);

                    // 图片本地化
                    $item = $this->matching_img($item);

                    return $item;
                }
                return false;
            })
            ->getData();

        if ($articles->isEmpty()){
            return false;
        }

        // 过滤
        $last_sign_array = array_column($this->wpdb->get_results(
            "select md5(`link`) as `sign` from $this->table_post where `post_type` = {$option['id']} order by id desc limit 200",
            ARRAY_A
        ), 'sign');
        $articles = $articles->filter(function ($item) use ($last_sign_array) {
            if ($item != false && !in_array(md5($item['link']), $last_sign_array)) {
                return true;
            }
            return false;
        });

        $articles->map(function ($article) use ($option) {
            if ($article != false && !empty($article['title']) && !empty($article['content'])) {
                $data['title'] = $this->text_keyword_replace($article['title'], $option['id']);
                $data['content'] = $this->text_keyword_replace($article['content'], $option['id']);
                $data['image'] = isset($article['image']) ? $article['image'] : '';
                $data['post_type'] = $option['id'];
                $data['link'] = $article['link'];
                $data['author'] = get_current_user_id();
                $data['created'] = date('Y-m-d H:i:s');
                if ($this->wpdb->insert($this->table_post, $data)){
                    $this->download_img($article['download_img']);
                }
            }
        });

        return true;
    }

    /**
     * TODO 此函数抽空优化
     * @param $option
     * @return bool
     */
    public function run_spider_all_page($option)
    {
        // TODO 错误信息再优化
        if ($option['collect_type'] != 'all'){
            return false;
        }

        $articles = $this->_QueryList($option['collect_list_url'], $option['collect_remove_head'])
            ->encoding('UTF-8')
            ->find('a')->attrs('href')->filter(function ($item) use ($option){
                if ($item === null){
                    return false;
                }
                $pattern = '/'.str_replace('/', '\/', $option['collect_list_range']).'/';
                if (preg_match($pattern, $item, $matches)){
                    return $item;
                }
            });

        foreach($articles as &$article){
            $article = $this->urlFormat($article, $option['collect_list_url']);
        }

        $string = implode("','", $articles->values()->toArray());
        $last_sign_array = array_column($this->wpdb->get_results(
            "select link as `sign` from $this->table_post where `link` in ('{$string}') order by id desc",
            ARRAY_A
        ), 'sign');

        $articles = $articles->map(function ($item) use ($option, $last_sign_array){
            if (in_array($item, $last_sign_array)){
                return false;
            }

            try {
                $ql = $this->_QueryList($item, $option['collect_remove_head'])
                    ->range($option['collect_content_range'])
                    ->encoding('UTF-8')
                    ->rules( $this->rulesFormat($option['collect_content_rules']) )
                    ->queryData();
            } catch (RequestException $e) {
                return false;
            }

            $ql = current($ql);
            $ql['link'] = $item;
            $article = $ql;
            $article = $this->matching_img($article);

            return $article;
        });

        $articles->map(function ($article) use ($option) {
            if ($article != false && !empty($article['title']) && !empty($article['content'])) {
                $data['title'] = $this->text_keyword_replace($article['title'], $option['id']);
                $data['content'] = $this->text_keyword_replace($article['content'], $option['id']);
                $data['image'] = isset($article['image']) ? $article['image'] : '';
                $data['post_type'] = $option['id'];
                $data['link'] = $article['link'];
                $data['author'] = get_current_user_id();
                $data['created'] = date('Y-m-d H:i:s');
                if ($this->wpdb->insert($this->table_post, $data)){
                    $this->download_img($article['download_img']);
                }
            }
        });

        return true;
    }

    /**
     * TODO 此函数抽空优化
     * @param $option
     * @return bool
     */
    public function run_spider_keyword_page($option)
    {
        // TODO 错误信息再优化
        if ($option['collect_type'] != 'keyword'){
            return false;
        }

        if ($option['collect_remove_head'] == '1'){
            $ql = QueryList::range($option['collect_list_range'])
                ->encoding('UTF-8')
                ->removeHead()
                ->rules($this->rulesFormat($option['collect_list_rules']));
        } else {
            $ql = QueryList::range($option['collect_list_range'])
                ->encoding('UTF-8')
                ->rules($this->rulesFormat($option['collect_list_rules']));
        }

        $articles = collect();
        $page = 1;
        $option['collect_list_url'] = str_replace('{keyword}', $option['collect_name'], $option['collect_list_url']);
        while (true){
            $url = str_replace('{page}', $page, $option['collect_list_url']);

            $articles_tmp = $ql->get($url)->query()->getData();

            if ($articles_tmp->count() == 0){
                break;
            }

            // 滤重
            foreach ($articles_tmp as $key => $article_tmp){
                $article_tmp['link'] = $this->urlFormat($article_tmp['link'], $option['collect_list_url']);
                $res = $this->wpdb->get_results(
                    "select link from $this->table_post where `link` = '{$article_tmp['link']}' ",
                    ARRAY_A
                );
                if (!empty($res)){
                    unset($articles_tmp[$key]);
                }
            }

            $articles = $articles->merge($articles_tmp);

            if ($articles->count() > $option['collect_number']){
                $articles = $articles->slice(0, $option['collect_number']);
                break;
            }

            $page++;
        }

        $articles = $articles->map(function ($item) use ($option){
            try {
                $ql = $this->_QueryList($item['link'], $option['collect_remove_head'])
                    ->range($option['collect_content_range'])
                    ->encoding('UTF-8')
                    ->rules( $this->rulesFormat($option['collect_content_rules']) )
                    ->queryData();
            } catch (RequestException $e) {
                return false;
            }

            $ql = current($ql);
            $ql['link'] = $item['link'];
            $article = $ql;
            $article = $this->matching_img($article);

            return $article;
        });

        $articles->map(function ($article) use ($option) {
            if ($article != false && !empty($article['title']) && !empty($article['content'])) {
                $data['title'] = $this->text_keyword_replace($article['title'], $option['id']);
                $data['content'] = $this->text_keyword_replace($article['content'], $option['id']);
                $data['image'] = isset($article['image']) ? $article['image'] : '';
                $data['post_type'] = $option['id'];
                $data['link'] = $article['link'];
                $data['author'] = get_current_user_id();
                $data['created'] = date('Y-m-d H:i:s');
                if ($this->wpdb->insert($this->table_post, $data)){
                    $this->download_img($article['download_img']);
                }
            }
        });

        return true;
    }


    /**
     * TODO 此函数抽空优化
     * @param $option
     * @return bool
     */
    protected function run_spider_single_page($option, $urls)
    {
        // TODO 错误信息再优化
        if ($option['collect_type'] != 'single'){
            return false;
        }

        if ($option['collect_remove_head'] == '1'){
            $ql = QueryList::range($option['collect_content_range'])
                ->encoding('UTF-8')
                ->removeHead()
                ->rules($this->rulesFormat($option['collect_content_rules']));
        } else {
            $ql = QueryList::range($option['collect_content_range'])
                ->encoding('UTF-8')
                ->rules($this->rulesFormat($option['collect_content_rules']));
        }

        if (empty($ql)){
            return false;
        }
        collect(explode(' ', $urls))->map(function($url) use ($ql, $option) {
            $article = $ql->get($url)->queryData();
            $article = current($article);

            $article = $this->matching_img($article);
            if ($article != false && !empty($article['title']) && !empty($article['content'])) {
                $data['title'] = $this->text_keyword_replace($article['title'], $option['id']);
                $data['content'] = $this->text_keyword_replace($article['content'], $option['id']);
                $data['image'] = isset($article['image']) ? $article['image'] : '';
                $data['post_type'] = $option['id'];
                $data['link'] = $url;
                $data['author'] = get_current_user_id();
                $data['created'] = date('Y-m-d H:i:s');
                if ($this->wpdb->insert($this->table_post, $data)){
                    $this->download_img($article['download_img']);
                }
            }
        });

        return true;
    }


    protected function _QueryList($url, $remove_head){
        if ( $remove_head == 1 ){
            return QueryList::get($url)->removeHead();
        }
        return QueryList::get($url);
    }


    protected function matching_img($article)
    {
        //  图片的异步加载src属性值
        $img_special_src = ['src', 'data-src', 'data-original-src'];
        $doc = phpQuery::newDocumentHTML($article['content']);
        $images = collect();
        foreach ($img_special_src as $special_src){
            foreach (pq($doc)->find('img') as $img) {
                $originImg = pq($img)->attr($special_src);
                if (!$originImg){
                    break;
                }

                $suffix = '';
                if (in_array(strtolower(strrchr($originImg, '.')), ['.jpg', '.png', '.jpeg', '.gif', '.swf'])) {
                    $suffix = strrchr($originImg, '.');
                } else {
                    switch (getimagesize($originImg)[2]) {
                        case IMAGETYPE_GIF:
                            $suffix = '.gif';
                            break;
                        case IMAGETYPE_JPEG:
                            $suffix = '.jpeg';
                            break;
                        case IMAGETYPE_PNG:
                            $suffix = '.png';
                            break;
                        case IMAGETYPE_SWF:
                            $suffix = '.swf';
                            break;
                    }
                }
                $newImg = 'frc-' . md5($originImg) . $suffix;

                $article['content'] = str_replace($originImg, '/wp-content/uploads' . wp_upload_dir()['subdir'] . DIRECTORY_SEPARATOR . $newImg, $article['content']);
                // src format
                if ($special_src != 'src') {
                    $article['content'] = str_replace($special_src.'="', 'src="', $article['content']);
                }
                $images->put($newImg, $originImg);
            }
        }

        $article['download_img'] = $images;

        return $article;
    }

    private function urlFormat($url, $domain){

        if (empty($url) || empty($domain)){
            return $url;
        }

        if (Str::startsWith($url, "http://") ||
            Str::startsWith($url, "https://")){
            return $url;
        }

        if (Str::startsWith($url, "//")){
            return 'http:'.$url;
        }

        $domainFormat = parse_url($domain);

        return $domainFormat['scheme'].'://'.$domainFormat['host'].'/'.ltrim($url, '/');
    }


    protected function download_img($download_img)
    {
        $http = new \GuzzleHttp\Client();
        $download_img->map(function ($url, $imgName) use ($http) {
            try {
                $data = $http->request('get', $url)->getBody()->getContents();
                file_put_contents(wp_upload_dir()['path'] . DIRECTORY_SEPARATOR . $imgName, $data);
            } catch (\Exception $e) {
                // ..记日志
            }
        });
    }


    // TODO 此函数要移走
    public function get_option_list()
    {
        return $this->wpdb->get_results("select * from $this->table_options",ARRAY_A);
    }


    // TODO 此函数要移走
    protected function get_option($option_id)
    {
        return $this->wpdb->get_row("select * from $this->table_options where `id` = $option_id",ARRAY_A);
    }


    private function rulesFormat($rules)
    {
        $resRule = [];
        collect( explode(")(", $rules) )->map(function ($item) use (&$resRule){
            list($key, $value) = explode("%", $item);
            list($label, $rule, $filter) = explode("|", $value);
            $label == 'null' && $label = null;
            $rule == 'null' && $rule = null;
            $filter == 'null' && $filter = null;
            $resRule[$key] = [$label, $rule, $filter];
        });

        return $resRule;
    }


    private function text_keyword_replace($text, $option_id)
    {
        if (!$text || !$option_id) {
            return $text;
        }
        $options = $this->wpdb->get_row("select * from $this->table_options where `id` = $option_id limit 1", ARRAY_A);
        $keywords_array = explode(" ", trim($options['collect_keywords_replace_rule']));

        collect($keywords_array)->map(function ($keywords) use (&$text) {
            list($string, $replace) = explode('=', $keywords);
            $text = str_replace($string, $replace, $text);
        });

        return $text;
    }
}


/**
 * FRC_Spider (入口)
 * TODO code => msg 单独提出来
 * TODO 抽空合并其他入口
 */
function frc_spider_interface()
{
    if(version_compare(PHP_VERSION,'7.0.0', '<')){
        wp_send_json(['code' => 5003, 'msg' => '不支持PHP7以下版本, 当前PHP版本为'.phpversion().'. 请升级php后重试!']);
        wp_die();
    }
    $action_func = !empty($_REQUEST['action_func']) ? sanitize_text_field($_REQUEST['action_func']) : '';
    if (empty($action_func)){
        wp_send_json(['code' => 5001, 'msg' => 'Parameter error!']);
        wp_die();
    }

    $result = null;
    $action_func = 'grab_'.$action_func;
    $frc_spider = new FRC_Spider();
    method_exists($frc_spider, $action_func) && $result = (new FRC_Spider())->$action_func();
    if ($result != null){
        wp_send_json($result);
        wp_die();
    }
    wp_send_json(['code' => 5002, 'result' => $result, 'msg' => 'Action there is no func! or Func is error!']);
    wp_die();
}
add_action('wp_ajax_frc_spider_interface', 'frc_spider_interface');


/**
 * 此函数要处理掉
 * debug 规则
 */
function frc_ajax_frc_debug_option() {

    $debug = [];
    $debug['debug_url']             = !empty($_REQUEST['debug_url']) ? sanitize_text_field($_REQUEST['debug_url']) : '';
    $debug['debug_range']           = !empty($_REQUEST['debug_range']) ? sanitize_text_field($_REQUEST['debug_range']) : '';
    $debug['debug_rules_origin']    = !empty($_REQUEST['debug_rules']) ? sanitize_text_field($_REQUEST['debug_rules']) : '';
    $debug['debug_remove_head']     = !empty($_REQUEST['debug_remove_head']) ? sanitize_text_field($_REQUEST['debug_remove_head']) : 0;
    $debug['debug_rules_new']       = !empty($_REQUEST['debug_rules']) ? rulesFormat($debug['debug_rules_origin']) : '';

    if ($debug['debug_remove_head'] == 1)
        $ql = QueryList::get($debug['debug_url'])->removeHead();
    else
        $ql = QueryList::get($debug['debug_url']);

    $info = $ql
        ->range($debug['debug_range'])
        ->encoding('UTF-8')
        ->rules( rulesFormat($debug['debug_rules_origin']) )
        ->queryData();

    $debug['result'] = $info;

    wp_send_json($debug);
    wp_die();
}
add_action( 'wp_ajax_frc_debug_option', 'frc_ajax_frc_debug_option' );

function rulesFormat($rules)
{
    $resRule = [];
    collect( explode(")(", $rules) )->map(function ($item) use (&$resRule){
        list($key, $value) = explode("%", $item);
        list($label, $rule, $filter) = explode("|", $value);
        $label == 'null' && $label = null;
        $rule == 'null' && $rule = null;
        $filter == 'null' && $filter = null;
        $resRule[$key] = [$label, $rule, $filter];
    });

    return $resRule;
}


/**
 * 定时爬取 cron
 */
if (!wp_next_scheduled('frc_cron_spider_hook')) {
    wp_schedule_event(time(), 'everyhour', 'frc_cron_spider_hook');
}


function frc_spider_timing_task()
{
    $frc_spider = new FRC_Spider();
    $options = $frc_spider->get_option_list();
    foreach ($options as $option){
        if ($option->collect_type == 'keyword'){
            continue;
        }
        $frc_spider->run_spider_list_page($option);
    }
}
add_action('frc_cron_spider_hook', 'frc_spider_timing_task');
//wp_clear_scheduled_hook('frc_cron_spider_hook');


function frc_spider()
{
    $frc_spider = new FRC_Spider();
    $options = collect($frc_spider->get_option_list())->groupBy('collect_type');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('胖鼠爬虫', 'Fat Rat Collect') ?></h1>
        <p></p>
        <span>Advanced customization </span>
        <p></p>
        <div>
            <!-- bootstrap tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#single_wx" data-toggle="tab">微信爬虫</a></li>
                <li><a href="#single_js" data-toggle="tab">简书爬虫</a></li>
                <li><a href="#list" data-toggle="tab">列表爬虫</a></li>
                <li><a href="#historypage" data-toggle="tab">列表爬虫->分页数据爬取</a></li>
                <li><a href="#all" data-toggle="tab">全站采集</a></li>
                <li><a href="#keyword" data-toggle="tab">关键词采集</a></li>
                <li><a href="#details" data-toggle="tab">详情爬虫</a></li>
                <li><a href="#autospider" data-toggle="tab">自动爬虫</a></li>
            </ul>
            <div class="tab-content spider-tab-content">
                <input type="hidden" hidden id="request_url" value="<?php echo admin_url('admin-ajax.php'); ?>">
<!--                微信爬虫-->
                <div class="tab-pane fade in active" id="single_wx">
                    <table class="form-table">
                        <tr>
                            <th>微信文章地址</th>
                            <td>
                                <textarea name="collect_wx_urls" cols="80" rows="14" placeholder="多篇文章使用回车区分,一行一个。每次不要太多、要对自己的服务器心里要有数"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <!-- bootstrap进度条 -->
                                <div class="progress progress-striped active">
                                    <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success wx-spider-progress-bar" role="progressbar"
                                         aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                         style="width: 0%;">
                                        <span class="sr-only">90% 完成（成功）</span>
                                    </div>
                                </div>
                                <input class="button button-primary wx-spider-run-button" type="button" value="运行"/>
                            </th>
                        </tr>
                    </table>
                </div>
                <!--                简书爬虫-->
                <div class="tab-pane fade" id="single_js">
                    <table class="form-table">
                        <tr>
                            <th>简书文章地址</th>
                            <td>
                                <textarea name="collect_js_urls" cols="80" rows="14" placeholder="多篇文章使用回车区分,一行一个"></textarea>
                                <p>简书默认规则过滤了a标签,你们可以在配置中心看到,也可以自定义过滤任何内容.去尝试吧</p>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <!-- bootstrap进度条 -->
                                <div class="progress progress-striped active">
                                    <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success js-spider-progress-bar" role="progressbar"
                                         aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                         style="width: 0%;">
                                        <span class="sr-only">90% 完成（成功）</span>
                                    </div>
                                </div>
                                <input class="button button-primary js-spider-run-button" type="button" value="运行"/>
                            </th>
                        </tr>
                    </table>
                </div>
<!--                列表爬虫-->
                <div class="tab-pane fade spider-tab-content" id="list">
                    <?php
                    if (!isset($options['list'])) {
                        echo '<p></p>';
                        echo "<h4><a href='". admin_url('admin.php?page=frc-options') ."'>亲爱的皮皮虾: 目前没有任何一个列表配置。皮皮虾我们走 </a></h4>";
                    } else {
                    ?>
                    <ul class="list-group">
                        <p></p>
                        <a disabled class="list-group-item active">
                            <h5 class="list-group-item-heading">
                                列表爬虫(点击运行)
                            </h5>
                        </a>
                        <p></p>
                        <?php
                        foreach ($options['list'] as $option) {
                            echo "<a href='#' data-id='{$option['id']}' class='list-spider-run-button list-group-item'>{$option['collect_name']}</a>";
                        }
                        ?>
                        <!-- bootstrap进度条 -->
                        <p></p>
                        <div class="progress progress-striped active">
                            <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success list-spider-progress-bar" role="progressbar"
                                 aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                 style="width: 0%;">
                                <span class="sr-only">90% 完成（成功）</span>
                            </div>
                        </div>
                    </ul>
                    <?php } ?>
                </div>
<!--                全站采集-->
                <div class="tab-pane fade spider-tab-content" id="all">
                    <?php
                    if (!isset($options['all'])) {
                        echo '<p></p>';
                        echo "<h4><a href='". admin_url('admin.php?page=frc-options') ."'>亲爱的皮皮虾: 目前没有任何一个列表配置。皮皮虾我们走 </a></h4>";
                    } else {
                    ?>
                    <ul class="list-group">
                        <p></p>
                        <a disabled class="list-group-item active">
                            <h5 class="list-group-item-heading">
                                全站采集
                            </h5>
                        </a>
                        <p></p>
                        <?php
                        foreach ($options['all'] as $option) {
                            echo "<a href='#' data-id='{$option['id']}' class='all-spider-run-button list-group-item'>{$option['collect_name']}</a>";
                        }
                        ?>
                        <!-- bootstrap进度条 -->
                        <p></p>
                        <div class="progress progress-striped active">
                            <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success all-spider-progress-bar" role="progressbar"
                                 aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                 style="width: 0%;">
                                <span class="sr-only">90% 完成（成功）</span>
                            </div>
                        </div>
                    </ul>
                    <?php } ?>
                </div>
<!--                关键词采集-->
                <div class="tab-pane fade spider-tab-content" id="keyword">
                    <?php
                    if (!isset($options['keyword'])) {
                        echo '<p></p>';
                        echo "<h4><a href='". admin_url('admin.php?page=frc-options') ."'>亲爱的皮皮虾: 目前没有任何一个列表配置。皮皮虾我们走 </a></h4>";
                    } else {
                    ?>
                    <h4>关键字采集</h4>
                    <table class="form-table">
                        <tr>
                            <th>关键字</th>
                            <td>
                                <input name="keyword_name" size="82" placeholder="读书" />
                            </td>
                        </tr>
                        <tr>
                            <th>采集数量</th>
                            <td>
                                <input name="keyword_number" size="82" placeholder="30" />
                            </td>
                        </tr>
                    </table>
                    <ul class="list-group">
                        <p></p>
                        <a disabled class="list-group-item active">
                            <h5 class="list-group-item-heading">
                                请选择配置
                            </h5>
                        </a>
                        <p></p>
                        <?php
                        foreach ($options['keyword'] as $option) {
                            echo "<a href='#' data-id='{$option['id']}' class='keyword-spider-run-button list-group-item'>{$option['collect_name']}</a>";
                        }
                        ?>
                        <!-- bootstrap进度条 -->
                        <p></p>
                        <div class="progress progress-striped active">
                            <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success keyword-spider-progress-bar" role="progressbar"
                                 aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                 style="width: 0%;">
                                <span class="sr-only">90% 完成（成功）</span>
                            </div>
                        </div>
                    </ul>
                    <?php } ?>
                </div>
<!--                分页爬虫-->
                <div class="tab-pane fade" id="historypage">
                    <?php
                    if (!isset($options['list'])) {
                        echo '<p></p>';
                        echo "<h4><a href='". admin_url('admin.php?page=frc-options') ."'>亲爱的毛毛虫: 目前没有任何一个分页配置。毛毛虫我们走 </a></h4>";
                    } else {
                    ?>
                    <table class="form-table">
                        <tr>
                            <td colspan="2">
                                <p>这个功能其实是列表爬取的附加功能. 嫌弃列表页最新的文章太少? 那就先用这个功能采集一下他们分页的历史新闻吧.</p>
                            </td>
                        </tr><tr>
                            <th>文章分页地址</th>
                            <td>
                                <input name="collect_history_url" size="82" placeholder="http://timshengmingguoke.bokee.com/newest/{page}" />
                                <p>把页码的码数替换为 {page}</p>
                                <p>例子: http://news.17173.com/z/pvp/list/zxwz_{page}.shtml</p>
                                <p>例子: http://xy2.yzz.cn/guide/skill/477,{page}.shtml</p>
                            </td>
                        </tr>
                        <tr>
                            <th>要采集的页码</th>
                            <td>
                                <input name="collect_history_page_number" size="82" placeholder="2,3,4,5,6,7,8,9,10" />
                                <p>页数用逗号隔开 2,3,4 慢点采集。一次1 ~ 3页慢慢来</p>
                            </td>
                        </tr>
                        <tr>
                            <th>选择页面的规则配置</th>
                            <td>
                                <?php
                                $string = '<select name="collect_history_relus"><option value="0">请选择</option>';
                                foreach ($options['list'] as $option) {
                                    $string .= '<option value="'.$option['id'].'">'.$option['collect_name'].'</option>';
                                }
                                $string .= '</select>';

                                echo $string;
                                ?>
                                <p>配置创建在 新建配置->配置类型=列表</p>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <!-- bootstrap进度条 -->
                                <div class="progress progress-striped active">
                                    <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success history-page-spider-progress-bar" role="progressbar"
                                         aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                         style="width: 0%;">
                                        <span class="sr-only">90% 完成（成功）</span>
                                    </div>
                                </div>
                                <input class="button button-primary history-page-spider-run-button" type="button" value="运行"/>
                            </th>
                        </tr>
                    </table>
                    <?php } ?>
                </div>
<!--                详情爬虫-->
                <div class="tab-pane fade" id="details">
                    <?php
                    if (!isset($options['single'])) {
                        echo '<p></p>';
                        echo "<h4><a href='". admin_url('admin.php?page=frc-options') ."'>亲爱的皮皮: 目前没有任何一个详情配置。胖鼠我们走 </a></h4>";
                    } else {
                    ?>
                    <table class="form-table">
                        <tr>
                            <th>详情地址</th>
                            <td>
                                <textarea name="collect_details_urls" cols="80" rows="14" placeholder="这里使用你设置过的详情配置, 来输入一条目标url, 多篇文章使用回车键, 尽情享受吧！"></textarea>
                                <p></p>
                            </td>
                        </tr>
                        <tr>
                            <th>详情配套配置</th>
                            <td>
                                <?php
                                $string = '<select name="collect_details_relus"><option value="0">请选择</option>';
                                foreach ($options['single'] as $option) {
                                    if (in_array($option['collect_name'], FRC_Api_Error::BUTTON_DISABLED)){
                                        $string .= '<option disabled value="'.$option['id'].'">'.$option['collect_name'].'</option>';
                                    } else {
                                        $string .= '<option value="'.$option['id'].'">'.$option['collect_name'].'</option>';
                                    }
                                }
                                $string .= '</select>';

                                echo $string;
                                ?>
                                <p>配置创建在 新建配置->配置类型=详情</p>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <!-- bootstrap进度条 -->
                                <div class="progress progress-striped active">
                                    <div id="bootstrop-progress-bar" class="progress-bar progress-bar-success details-spider-progress-bar" role="progressbar"
                                         aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"
                                         style="width: 0%;">
                                        <span class="sr-only">90% 完成（成功）</span>
                                    </div>
                                </div>
                                <input class="button button-primary details-spider-run-button" type="button" value="运行"/>
                            </th>
                        </tr>
                    </table>
                    <?php } ?>
                </div>
<!--                自动爬虫-->
                <div class="tab-pane fade" id="autospider">
                    <p>已自动开启</p>
                    <p>12小时爬取一次</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
