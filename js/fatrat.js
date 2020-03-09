(function($){

    var option_id                       = $('#option_id').val();
    var request_url                     = $('#request_url').val();
    var success_redirect_url            = $('#success_redirect_url').val();
    var collect_name                    = '默认代号-全军出击';
    var collect_describe                = '';
    var collect_type                    = 'list';
    var collect_remove_outer_link       = '1';
    var collect_remove_head             = '0';
    var collect_list_url                = '';
    var collect_list_range              = '';
    var collect_list_rules              = '';
    var collect_content_range           = '';
    var collect_content_rules           = '';
    var collect_keywords_replace_rule   = '';

    /**
     * Spider Ajax
     */

    // 微信爬虫
    $('.wx-spider-run-button').on('click', function(){
        var collect_wx_urls   = $('textarea[name="collect_wx_urls"]').val();

        ajax_collect_request_tool(request_url, {
            action_func: 'wx_page',
            collect_wx_urls: collect_wx_urls,
        }, '.wx-spider-progress-bar', '.wx-spider-run-button');
    });

    // 简书爬虫
    $('.js-spider-run-button').on('click', function(){
        var collect_js_urls   = $('textarea[name="collect_js_urls"]').val();

        ajax_collect_request_tool(request_url, {
            action_func: 'js_page',
            collect_js_urls: collect_js_urls,
        }, '.js-spider-progress-bar', '.js-spider-run-button');
    });

    // 列表爬虫
    $('.list-spider-run-button').on('click', function(){
        if(!confirm("列表爬取时间会久点, 请耐心等待...")){
            return;
        }

        var option_id = $(this).attr('data-id');

        ajax_collect_request_tool(request_url, {
            action_func: 'list_page',
            option_id: option_id,
        }, '.list-spider-progress-bar', '.list-spider-run-button');
    });

    // 全站采集
    $('.all-spider-run-button').on('click', function(){
        if(!confirm("全站采集马上开始, 请耐心等待...")){
            return;
        }

        var option_id = $(this).attr('data-id');

        ajax_collect_request_tool(request_url, {
            action_func: 'all_page',
            option_id: option_id,
        }, '.all-spider-progress-bar', '.all-spider-run-button');
    });

    // 关键字采集
    $('.keyword-spider-run-button').on('click', function(){
        if(!confirm("关键字采集马上开始, 请耐心等待...")){
            return;
        }

        var option_id = $(this).attr('data-id');
        var keyword_name = $('input[name=keyword_name]').val();
        var keyword_number = $('input[name=keyword_number]').val();

        ajax_collect_request_tool(request_url, {
            action_func: 'keyword_page',
            option_id: option_id,
            keyword_name: keyword_name,
            keyword_number: keyword_number,
        }, '.keyword-spider-progress-bar', '.keyword-spider-run-button');
    });

    // 历史文章
    $('.history-page-spider-run-button').on('click', function(){
        if(!confirm("请核实输入信息.")){
            return;
        }

        var collect_history_url           = $('input[name="collect_history_url"]').val();
        var collect_history_page_number   = $('input[name="collect_history_page_number"]').val();
        var collect_history_relus_id      = $('select[name="collect_history_relus"]').val();

        ajax_collect_request_tool(request_url, {
            action_func: 'history_page',
            collect_history_url: collect_history_url,
            collect_history_page_number: collect_history_page_number,
            collect_history_relus_id: collect_history_relus_id,
        }, '.history-page-spider-progress-bar', '.history-page-spider-run-button');

    });

    // 详情爬虫
    $('.details-spider-run-button').on('click', function(){
        if(!confirm("请确认..")){
            return;
        }

        var collect_details_urls   = $('textarea[name="collect_details_urls"]').val();
        var collect_details_relus  = $('select[name="collect_details_relus"]').val();

        ajax_collect_request_tool(request_url, {
            action_func: 'details_page',
            collect_details_urls: collect_details_urls,
            collect_details_relus: collect_details_relus,
        }, '.details-spider-progress-bar', '.details-spider-run-button');
    });



    /**
     * Option Ajax
     */

    $('#save-option-button').on('click', function(){
        if(!confirm("好好检查一下配置别错了..")){
            return;
        }

        var tmp_link = new Array();
        var tmp_title = new Array();
        var tmp_content = new Array();

        tmp_link['a'] = $('input[name="collect_list_rule_link_a"]').val() != "" ? $('input[name="collect_list_rule_link_a"]').val() : null ;
        tmp_link['b'] = $('input[name="collect_list_rule_link_b"]').val() != "" ? $('input[name="collect_list_rule_link_b"]').val() : null ;
        tmp_link['c'] = $('input[name="collect_list_rule_link_c"]').val() != "" ? $('input[name="collect_list_rule_link_c"]').val() : null ;
        tmp_link['d'] = $('input[name="collect_list_rule_link_d"]').val() != "" ? $('input[name="collect_list_rule_link_d"]').val() : null ;
        tmp_title['a'] = $('input[name="collect_content_rule_title_a"]').val() != "" ? $('input[name="collect_content_rule_title_a"]').val() : null ;
        tmp_title['b'] = $('input[name="collect_content_rule_title_b"]').val() != "" ? $('input[name="collect_content_rule_title_b"]').val() : null ;
        tmp_title['c'] = $('input[name="collect_content_rule_title_c"]').val() != "" ? $('input[name="collect_content_rule_title_c"]').val() : null ;
        tmp_title['d'] = $('input[name="collect_content_rule_title_d"]').val() != "" ? $('input[name="collect_content_rule_title_d"]').val() : null ;
        tmp_content['a'] = $('input[name="collect_content_rule_content_a"]').val() != "" ? $('input[name="collect_content_rule_content_a"]').val() : null ;
        tmp_content['b'] = $('input[name="collect_content_rule_content_b"]').val() != "" ? $('input[name="collect_content_rule_content_b"]').val() : null ;
        tmp_content['c'] = $('input[name="collect_content_rule_content_c"]').val() != "" ? $('input[name="collect_content_rule_content_c"]').val() : null ;
        tmp_content['d'] = $('input[name="collect_content_rule_content_d"]').val() != "" ? $('input[name="collect_content_rule_content_d"]').val() : null ;

        collect_name                    = $('input[name="collect_name"]').val();
        collect_describe                = $('input[name="collect_describe"]').val();
        collect_type                    = $('input[name="collect_type"]:checked').val();
        collect_remove_outer_link       = $('input[name="collect_remove_outer_link"]:checked').val();
        collect_remove_head             = $('input[name="collect_remove_head"]:checked').val();
        collect_list_url                = $('input[name="collect_list_url"]').val();
        collect_list_range              = $('input[name="collect_list_range"]').val();
        collect_list_rules              = tmp_link['a']+'%'+tmp_link['b']+'|'+tmp_link['c']+'|'+tmp_link['d'];
        collect_content_range           = $('input[name="collect_content_range"]').val();
        collect_content_rules           = tmp_title['a']+'%'+tmp_title['b']+'|'+tmp_title['c']+'|'+tmp_title['d']+')('+tmp_content['a']+'%'+tmp_content['b']+'|'+tmp_content['c']+'|'+tmp_content['d'];
        collect_keywords_replace_rule   = $('textarea[name="collect_keywords_replace_rule"]').val();

        ajax_option_request_tool(request_url, {
            action_func: 'save_option',
            option_id: option_id,
            collect_name: collect_name,
            collect_describe: collect_describe,
            collect_type: collect_type,
            collect_remove_outer_link: collect_remove_outer_link,
            collect_remove_head: collect_remove_head,
            collect_list_url: collect_list_url,
            collect_list_range: collect_list_range,
            collect_list_rules: collect_list_rules,
            collect_content_range: collect_content_range,
            collect_content_rules: collect_content_rules,
            collect_keywords_replace_rule: collect_keywords_replace_rule,
        }, success_redirect_url);
    });

    $('.delete-option-button').on('click', function(){
        if(!confirm("删除就彻底没了..")){
            return;
        }

        option_id = $(this).attr('data-value');

        ajax_option_request_tool(request_url, {
            action_func: 'del_option',
            option_id: option_id,
        }, success_redirect_url);
    });

    $('.import_default_configuration').on('click', function(){
        if(!confirm("亲, 此功能会创建几个默认的 爬取列表的配置和爬取详情 的配置.. 供你参考学习")){
            return;
        }
        if(!confirm("创建成功后， 你要注意。配置是怎么写的, 然后用debug模式多测试一下。 争取早日熟练使用胖鼠")){
            return;
        }
        if(!confirm("重要的事情再说一下，看看例子 配合Debug功能。去享受吧!")){
            return;
        }

        ajax_option_request_tool(request_url, {
            action_func: 'import_default_configuration',
        }, success_redirect_url);
    });

    /**
     * Import Ajax
     */

    // import article
    $('#import-articles-button').on('click', function(){
        if(!confirm("确认一下..")){
            return;
        }
        var collect_count = $('input[name="import-articles-count-button"]').val();

        ajax_import_data_request_tool(request_url, {
            action_func: 'import_article',
            collect_count: collect_count,
        });
    });


    $('#import-articles-button_group').on('click', function(){
        if(!confirm("确认一下..")){
            return;
        }

        ajax_import_data_request_tool(request_url, {
            action_func: 'import_group_article',
        });
    });


    $('.publish-articles').on('click', function(){
        if(!confirm("请确定发布这篇文章.")){
            return;
        }

        var article_id   = $(this).attr('value');
        var post_category = [];
        $("input[type='checkbox']:checked").each(function (index, item) {
            post_category.push($(this).val());
        });
        var post_user = $('select[name="post_user"]').val();
        var post_status = $('input[name="post_status"]:checked').val();

        ajax_import_data_request_tool(request_url, {
            action_func: 'publish_article',
            article_id: article_id,
            post_category: post_category,
            post_user: post_user,
            post_status: post_status,
        }, success_redirect_url);
    });

    $('.preview-article').on('click', function(){
        if(!confirm("注意 *_* !, 点击确定 会把这篇文章发送到到你的文章列表里面 文章状态是: 草稿, 但你可以随意删除这篇草稿.. 取消不会创建草稿..")){
            return;
        }

        var article_id   = $(this).attr('value');
        var post_category = [];
        $("input[type='checkbox']:checked").each(function (index, item) {
            post_category.push($(this).val());
        });
        var post_user = $('select[name="post_user"]').val();
        var post_status = $('input[name="post_status"]:checked').val();

        ajax_import_data_request_tool(request_url, {
            action_func: 'preview_article',
            article_id: article_id,
            post_category: post_category,
            post_user: post_user,
            post_status: post_status,
        }, success_redirect_url, '', 'preview_article');
    });

    function preview_article(response){
        window.location.href=response.result.preview_url;
    }



    /**
     * style
     */
    if ($('input[type=radio][name=collect_type]:checked').val() == 'single'){
        $('.collect_type_radio_change').hide();
    }

    $('#todo—more-button').on('click', function(){
        $('.todo—more-show').attr("style","display:block;");
    });

    $('input[type=radio][name=collect_type]').change(function () {
        if (this.value == 'single') {
            $('.collect_type_radio_change').hide();
        }
        else {
            $('.collect_type_radio_change').show();
        }
    });

    /**
     * tool function
     *
     * request_tool 方法均可以使用回调函数
     */
    $(".debug-button").on('click', function(){
        $(".debug-table").show();
    })

    function ajax_collect_request_tool(request_url, data, progress_bar = '', input_disabled = '')
    {
        // console.log(request_url, data, progress_bar, input_disabled);

        $.ajax(request_url, {
            method: 'POST',
            dataType: 'json',
            data: $.extend({action: 'frc_spider_interface'}, data),
            beforeSend : function(){
                if (progress_bar != ''){
                    $(progress_bar).css('width', '20%');
                }
                if (input_disabled != ''){
                    $(input_disabled).attr('disabled', 'disabled');
                }
            },
            success: function(response) {
                // console.log(response);
                if (progress_bar != ''){
                    $(progress_bar).css('width', '100%');
                }
                setTimeout(function() {
                    if (response.code == 200) {
                        alert(response.msg);
                    } else {
                        alert('错误码:'+response.code+' '+response.msg);
                    }
                }, 500);
            },
            complete: function() {
                setTimeout(function() {
                    if (progress_bar != ''){
                        $(progress_bar).css('width', '0%');
                    }
                    if (input_disabled != ''){
                        $(input_disabled).removeAttr('disabled');
                    }
                }, 2000);
            },
            error: function(error) {
                alert('error!, 异常了! 出现这个错误不必惊慌. 可能是你的网络太差或服务器带宽小或 采集的时间太久超时了。你可以 数据中心看一下。是不是已经采集好了?  ');
                if (progress_bar != ''){
                    $(progress_bar).css('width', '0%');
                }
                if (input_disabled != ''){
                    $(input_disabled).removeAttr('disabled');
                }
                console.log('error:', error);
            }
        });
    }

    function ajax_option_request_tool(request_url, data, success_redirect_url = '', error_redirect_url = ''){
        // console.log(request_url, data);

        $.ajax(request_url, {
            method: 'POST',
            dataType: 'json',
            data: $.extend({action: 'frc_option_interface'}, data),
            success: function(response) {
                // console.log(response);
                if (response.code == 200) {
                    alert(response.msg);
                    if (success_redirect_url != ''){
                        window.location.href=success_redirect_url;
                    }
                } else {
                    alert('错误码:'+response.code+' '+response.msg);
                    if (error_redirect_url != ''){
                        window.location.href=error_redirect_url;
                    }
                }
            },
            error: function(error) {
                alert('error!');
                console.log('error:', error)
            }
        })
    }

    function ajax_import_data_request_tool(request_url, data, success_redirect_url = '', error_redirect_url = '', callback = ''){
        // console.log(request_url, data);

        $.ajax(request_url, {
            method: 'POST',
            dataType: 'json',
            data: $.extend({action: 'frc_import_data_interface'}, data),
            success: function(response) {
                // console.log(response);
                if (response.code == 200) {
                    if (callback != ''){
                        eval(callback+"(response)");
                        return ;
                    }
                    alert(response.msg);
                    if (success_redirect_url != ''){
                        window.location.href=success_redirect_url;
                    }
                } else {
                    alert('错误码:'+response.code+' '+response.msg);
                    if (error_redirect_url != ''){
                        window.location.href=error_redirect_url;
                    }
                }
            },
            error: function(error) {
                alert('error!');
                console.log('error:', error)
            }
        })
    }



// ****分割线

    // debug
    $('#debug-option').on('click', function(){
        debug_rule = new Array();

        debug_rule['a'] = $('input[name="collect_debug_rule_a"]').val() != "" ? $('input[name="collect_debug_rule_a"]').val() : null ;
        debug_rule['b'] = $('input[name="collect_debug_rule_b"]').val() != "" ? $('input[name="collect_debug_rule_b"]').val() : null ;
        debug_rule['c'] = $('input[name="collect_debug_rule_c"]').val() != "" ? $('input[name="collect_debug_rule_c"]').val() : null ;
        debug_rule['d'] = $('input[name="collect_debug_rule_d"]').val() != "" ? $('input[name="collect_debug_rule_d"]').val() : null ;

        debug_url      = $('input[name="debug_url"]').val();
        debug_range    = $('input[name="debug_range"]').val();
        debug_remove_head    = $('input[name="debug_remove_head"]:checked').val();
        debug_rules    = debug_rule['a']+'%'+debug_rule['b']+'|'+debug_rule['c']+'|'+debug_rule['d'];

        console.log('Request Params: ',debug_url, debug_remove_head, debug_range, debug_rules);

        $.ajax(request_url, {
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'frc_debug_option',
                debug_url: debug_url,
                debug_remove_head: debug_remove_head,
                debug_range: debug_range,
                debug_rules: debug_rules,
            },
            success: function(response) {
                console.log(response);
            },
            error: function(error) {
                alert('error!, 异常了! 出现这个错误不必惊慌. 可能是你的网络太差或服务器带宽小或 采集的时间太久超时了。你可以 数据中心看一下。是不是已经采集好了?  ');
                console.log('error:', error)
            }
        })
    });

})(jQuery);
