<?php
/**
 * @class elasticsearchAdminView
 * @author Huhani (mmia268@gmail.com)
 * @brief elasticsearch 모듈의 admin.view class
 **/

class elasticsearchAdminView extends elasticsearch
{
    public function init() {
        $this->setTemplatePath($this->module_path.'tpl');
    }


    function dispElasticsearchAdminDebugSetting() {

    }

    function dispElasticsearchAdminIndexList() {
        $oElasticsearchModel = getModel('elasticsearch');
        $indices = $oElasticsearchModel->getIndexList();
        if($indices === null) {
            //return new BaseObject(-1, "정보를 불러오는 도중 오류가 발생했습니다.");
        }

        Context::set('indices', $indices);
        $this->setTemplateFile('indexList');
    }

    function dispElasticsearchAdminIndexState() {
        $oElasticsearchModel = getModel('elasticsearch');
        $targetIndex = Context::get('target_index');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        $params = null;
        if($prefix) {
            $prefix .= "_";
        }
        try {
            $stats = $client->indices()->stats(array('index' => $targetIndex));
        } catch(Exception $e) {
            return new BaseObject(-1, "올바르지 않은 인덱스 접근입니다.");
        }

        $shards = $stats['_shards'];
        $indices = $stats['indices'];
        if(!isset($indices[$targetIndex])) {
            return new BaseObject(-1, "올바르지 않은 인덱스 접근입니다.");
        }
        $targetIndex = $indices[$targetIndex];

        Context::set('shards', $shards);
        Context::set('uuid', $targetIndex['uuid']);
        Context::set('primaries', $targetIndex['primaries']);
        Context::set('total', $targetIndex['total']);

        $this->setTemplateFile('indexState');
    }

    function dispElasticsearchAdminIndexMapping() {
        $oElasticsearchModel = getModel('elasticsearch');
        $targetIndex = Context::get('target_index');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        $params = null;
        if($prefix) {
            $prefix .= "_";
        }
        try {
            $mapping = $client->indices()->getMapping(array('index' => $targetIndex));
        } catch(Exception $e) {
            return new BaseObject(-1, "올바르지 않은 인덱스 접근입니다.");
        }
        if(!isset($mapping[$targetIndex])) {
            return new BaseObject(-1, "올바르지 않은 인덱스 접근입니다.");
        }
        $targetIndex = $mapping[$targetIndex];
        $properties = $targetIndex['mappings']['properties'];

        Context::set('properties', $properties);
        $this->setTemplateFile('indexMapping');
    }

    function dispElasticsearchAdminIndexSettingView() {
        $oElasticsearchModel = getModel('elasticsearch');
        $targetIndex = Context::get('target_index');
        if(!$targetIndex || !$oElasticsearchModel->hasIndices([$targetIndex])[0]) {
            return new BaseObject(-1, "올바르지 않은 인덱스 접근입니다.");
        }

        $result = $oElasticsearchModel->getIndexSettings($targetIndex);
        $resultJSON = $result ? $this->prettyPrint(json_encode($result)) : $result;
        Context::set('index_settings', $resultJSON);
        $this->setTemplateFile('indexSettingsView');
    }

    function dispElasticsearchAdminIndexSetting() {
        $oElasticsearchModel = getModel('elasticsearch');
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $targetIndex = Context::get('target_index');

        if(!$targetIndex || !$oElasticsearchModel->hasIndices([$targetIndex])[0] || substr($targetIndex, 0, 1) == ".") {
            return new BaseObject(-1, "올바르지 않은 인덱스 접근입니다.");
        }

        if($targetIndex === $prefix."documents" || $targetIndex === $prefix."document_extra_vars") {
            Context::set('last_document_srl', $oElasticsearchModel->getLastDocumentSrl());
        } else if($targetIndex === $prefix."comments") {
            Context::set('last_comment_srl', $oElasticsearchModel->getLastCommentSrl());
        } else if($targetIndex === $prefix."files") {
            Context::set('last_file_srl', $oElasticsearchModel->getLastFileSrl());
        }

        Context::set('index_prefix', $prefix);
        $this->setTemplateFile('indexSetting');
    }

    function dispElasticsearchAdminIndexDocumentList() {
        $oElasticsearchModel = getModel('elasticsearch');
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $target_index = Context::get('target_index');
        $sort_index = Context::get('sort_index');
        $list_count = Context::get('list_count');
        $page_count = Context::get('page_count');
        $order_type = Context::get('order_type');
        $search_target = Context::get('search_target');
        $search_keyword = Context::get('search_keyword');
        $page = Context::get('page');
        $show_id = false;
        if(!$target_index) {
            $target_index = $prefix."documents";
        }
        if(!in_array($target_index, array($prefix.'documents', $prefix.'comments', $prefix.'document_extra_vars', $prefix.'files'))) {
            return new BaseObject(-1, "올바르지 않은 대상입니다.");
        }
        $columnList = array();
        $searchColumnList = array();

        switch($target_index) {
            case $prefix."documents":
                if(!$sort_index) {
                    $sort_index = "document_srl";
                }
                if(!$order_type) {
                    $order_type = "desc";
                }
                $columnList = array('document_srl', 'title', 'nick_name', 'module_srl', 'regdate', 'status', 'comment_status');
                $searchColumnList = array('document_srl', 'module_srl', 'category_srl', 'title', 'title.my_ngram', 'content', 'content.my_ngram', 'user_id', 'user_name', 'nick_name', 'email_address', 'tags', 'regdate', 'ipaddress', 'status', 'comment_status');
                break;

            case $prefix."comments":
                if(!$sort_index) {
                    $sort_index = "comment_srl";
                }
                if(!$order_type) {
                    $order_type = "desc";
                }
                $columnList = array('comment_srl', 'document_srl', 'content', 'nick_name', 'module_srl', 'regdate');
                $searchColumnList = array('document_srl', 'comment_srl', 'module_srl', 'parent_srl', 'content', 'content.my_ngram', 'user_id', 'user_name', 'nick_name', 'email_address', 'regdate', 'ipaddress', 'status', 'doc_list_order', 'doc_user_id', 'doc_regdate', 'doc_member_srl', 'doc_category_srl');
                break;

            case $prefix."document_extra_vars":
                if(!$sort_index) {
                    $sort_index = "document_srl";
                }
                if(!$order_type) {
                    $order_type = "desc";
                }
                $columnList = array('document_srl', 'var_idx', 'value', 'eid', 'module_srl');
                $searchColumnList = array('document_srl', 'module_srl', 'var_idx', 'lang_code', 'value', 'value.my_ngram', 'eid', 'doc_list_order', 'doc_user_id', 'doc_regdate', 'doc_member_srl', 'doc_category_srl');
                $show_id = true;
                break;

            case $prefix."files":
                if(!$sort_index) {
                    $sort_index = "file_srl";
                }
                if(!$order_type) {
                    $order_type = "desc";
                }
                $columnList = array('file_srl', 'nick_name', 'file_extension', 'isvalid', 'source_filename', 'module_srl', 'regdate');
                $searchColumnList = array('document_srl', 'comment_srl', 'upload_target_type', 'module_srl', 'uploaded_filename', 'source_filename', 'nick_name', 'isvalid');
                break;
        }


        $oElasticsearchAdminModel = getAdminModel('elasticsearch');
        $args = new stdClass();
        $args->target_index = $target_index;
        $args->page = $page;
        $args->search_target = $search_target;
        $args->search_keyword = $search_keyword;
        $args->sort_index = $sort_index;
        $args->order_type = $order_type;
        $args->list_count = $list_count;
        $args->page_count = $page_count;
        $args->columnList = $columnList;
        $output = $oElasticsearchAdminModel->getIndexDocumentList($args);

        Context::set('index_prefix', $prefix);
        Context::set('target_index', $target_index);
        Context::set('show_id', $show_id);
        Context::set('columnList', $columnList);
        Context::set('searchColumnList', $searchColumnList);
        Context::set('list', $output->data);
        Context::set('total_count', $output->total_count);
        Context::set('page_navigation', $output->page_navigation);

        $this->setTemplateFile('documentList');
    }

    function dispElasticsearchAdminIndexDocumentDetail() {
        $oElasticsearchModel = getModel('elasticsearch');
        $target_index = Context::get('target_index');
        $id = Context::get('_id'); // 그냥 id를 사용했다간 충돌발생을 대비

        $result = $oElasticsearchModel->getIndexDocument($target_index, $id);
        Context::set('result', $result);

        $this->setTemplateFile('documentDetail');
    }

    function dispElasticsearchAdminOtherSetting() {
        $oElasticsearchModel = getModel('elasticsearch');
        $oModuleModel = getModel('module');
        $config = $oElasticsearchModel->getModuleConfig();
        $skin_list = $oModuleModel->getSkins($this->module_path);

        Context::set('config', $config);
        Context::set('skin_list', $skin_list);
        Context::set('moduleConfig', $config);
        Context::set('sample_code', htmlspecialchars('<form action="{getUrl()}" method="get"><input type="hidden" name="vid" value="{$vid}" /><input type="hidden" name="mid" value="{$mid}" /><input type="hidden" name="act" value="EIS" /><input type="text" name="is_keyword"  value="{$is_keyword}" /><input class="btn" type="submit" value="{$lang->cmd_search}" /></form>', ENT_COMPAT | ENT_HTML401, 'UTF-8', false) );
        $this->setTemplateFile('otherSetting');
    }

    function dispElasticsearchAdminErrorLogList() {
        $search_target = Context::get('search_target');
        $search_keyword = Context::get('search_keyword');
        $page = Context::get('page');
        $oElasticsearchAdminModel = getAdminModel('elasticsearch');

        $args = new stdClass();
        $args->page = $page;
        $args->page_count = 30;
        $output = $oElasticsearchAdminModel->getErrorLogList($args);
        if(!$output->toBool()) {
            return $output;
        }
        Context::set('errorList', $output->data);
        Context::set('total_count', $output->total_count);
        Context::set('page_navigation', $output->page_navigation);

        $this->setTemplateFile('errorLogList');
    }

    function dispElasticsearchAdminErrorLogDetail() {
        $error_id = Context::get('error_id');
        $oElasticsearchAdminModel = getAdminModel('elasticsearch');
        $output = $oElasticsearchAdminModel->getErrorLog($error_id);
        if(!$output->toBool()) {
            return $output;
        }
        if($output->data->params) {
            $output->data->params = $this->prettyPrint($output->data->params);
        }
        if($output->data->error) {
            $output->data->error = $this->prettyPrint($output->data->error);
        }
        Context::set('data', $output->data);

        $this->setTemplateFile('errorLogDetail');
    }

    private function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $in_quotes = false;
        $in_escape = false;
        $ends_line_level = NULL;
        $json_length = strlen( $json );

        for( $i = 0; $i < $json_length; $i++ ) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if( $ends_line_level !== NULL ) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if ( $in_escape ) {
                $in_escape = false;
            } else if( $char === '"' ) {
                $in_quotes = !$in_quotes;
            } else if( ! $in_quotes ) {
                switch( $char ) {
                    case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                    case '{': case '[':
                    $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
                }
            } else if ( $char === '\\' ) {
                $in_escape = true;
            }
            if( $new_line_level !== NULL ) {
                $result .= "\n".str_repeat( "\t", $new_line_level );
            }
            $result .= $char.$post;
        }

        return $result;
    }

}

/* End of file : elasticsearch.admin.view.php */
/* Location : ./modules/elasticsearch/elasticsearch.admin.view.php */
