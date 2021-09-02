<?php

class elasticsearchView extends elasticsearch
{

    var $target_mid = array();
    var $skin = 'default';
    function init() {
        Context::loadLang(_XE_PATH_ . 'modules/elasticsearch/lang');
    }


    function EIS()
    {
        $oFile = getClass('file');
        $oElasticsearchModel = getModel('elasticsearch');
        $logged_info = Context::get('logged_info');

        // Check permissions
        if(!$this->grant->access) {
            return new BaseObject(-1,'msg_not_permitted');
        }

        $config = $oElasticsearchModel->getModuleConfig();
        if(!$config)  {
            $config = new stdClass;
        }
        if(!$config->skin)
        {
            $config->skin = 'default';
            $template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
        }
        else
        {
            //check theme
            $config_parse = explode('|@|', $config->skin);
            if (count($config_parse) > 1)
            {
                $template_path = sprintf('./themes/%s/modules/integration_search/', $config_parse[0]);
            }
            else
            {
                $template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
            }
        }
        // Template path
        $this->setTemplatePath($template_path);
        $skin_vars = ($config->skin_vars) ? unserialize($config->skin_vars) : new stdClass;
        Context::set('module_info', $skin_vars);

        $target = $config->search_module_target ? $config->search_module_target : 'include';
        $module_srl_list = empty($config->search_target_module_srl) ? array() : explode(',',$config->search_target_module_srl);

        if($target === 'include' && !count($module_srl_list))
        {
            exit();
            $oMessageObject = ModuleHandler::getModuleInstance('message');
            $oMessageObject->setError(-1);
            $oMessageObject->setMessage('msg_not_enabled');
            $oMessageObject->dispMessage();
            $this->setTemplatePath($oMessageObject->getTemplatePath());
            $this->setTemplateFile($oMessageObject->getTemplateFile());
            return;
        }



        // Set a variable for search keyword
        $is_keyword = Context::get('is_keyword');
        // Set page variables
        $page = (int)Context::get('page');
        if(!$page) $page = 1;
        // Search by search tab
        $where = Context::get('where');
        // Create integration search model object
        if($is_keyword)
        {
            switch($where)
            {
                case 'document' :
                    $search_target = Context::get('search_target');
                    if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
                    Context::set('search_target', $search_target);

                    $output = $oElasticsearchModel->getIntegrationSearchDocuments($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
                    Context::set('output', $output);
                    $this->setTemplateFile("document", $page);
                    break;
                case 'comment' :
                    $output = $oElasticsearchModel->getIntegrationSearchComments($target, $module_srl_list, $is_keyword, $page, 10);
                    Context::set('output', $output);
                    $this->setTemplateFile("comment", $page);
                    break;
                case 'trackback' :
                    $search_target = Context::get('search_target');
                    if(!in_array($search_target, array('title','url','blog_name','excerpt'))) $search_target = 'title';
                    Context::set('search_target', $search_target);

                    $output = $oElasticsearchModel->getIntegrationSearchTrackbacks($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
                    Context::set('output', $output);
                    $this->setTemplateFile("trackback", $page);
                    break;
                case 'multimedia' :
                    $output = $oElasticsearchModel->getIntegrationSearchFiles($target, $module_srl_list, $is_keyword, $page, 20, "Y");
                    Context::set('output', $output);
                    $this->setTemplateFile("multimedia", $page);
                    break;
                case 'file' :
                    $output = $oElasticsearchModel->getIntegrationSearchFiles($target, $module_srl_list, $is_keyword, $page, 20, "N");
                    Context::set('output', $output);
                    $this->setTemplateFile("file", $page);
                    break;
                default :
                    $output['document'] = $oElasticsearchModel->getIntegrationSearchDocuments($target, $module_srl_list, 'title', $is_keyword, $page, 5);
                    $output['comment'] = $oElasticsearchModel->getIntegrationSearchComments($target, $module_srl_list, $is_keyword, $page, 5);
                    $output['trackback'] = $oElasticsearchModel->getIntegrationSearchTrackbacks($target, $module_srl_list, 'title', $is_keyword, $page, 5);
                    $output['multimedia'] = $oElasticsearchModel->getIntegrationSearchFiles($target, $module_srl_list, $is_keyword, $page, 5, "Y");
                    $output['file'] = $oElasticsearchModel->getIntegrationSearchFiles($target, $module_srl_list, $is_keyword, $page, 5, "N");
                    Context::set('search_result', $output);
                    Context::set('search_target', 'title');
                    $this->setTemplateFile("index", $page);
                    break;
            }
        }
        else
        {
            $this->setTemplateFile("no_keywords");
        }

        $security = new Security();
        $security->encodeHTML('is_keyword', 'search_target', 'where', 'page');
    }
}
/* End of file elasticsearch.view.php */
/* Location: ./modules/elasticsearch/elasticsearch.view.php */
