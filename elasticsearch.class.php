<?php
/*! Copyright (C) 2021 BGM STORAGE. All rights reserved. */
/**
 * @class  elasticsearch
 * @author Huhani (mmia268@gmail.com)
 * @brief  ElasticSearch module high class.
 */

class elasticsearch extends ModuleObject
{


    private $triggers = array(
        array( 'document.insertDocument',	'elasticsearch',	'controller',	'triggerAfterInsertDocument',       'after'	),
        array( 'document.updateDocument',	'elasticsearch',	'controller',	'triggerAfterUpdateDocument',      'after'	),
        array( 'document.deleteDocument',	'elasticsearch',	'controller',	'triggerAfterDeleteDocument',      'after'	),
        array( 'document.moveDocumentToTrash',	'elasticsearch',	'controller',	'triggerAfterTrashDocument',      'after'	),
        array( 'document.updateReadedCount',	'elasticsearch',	'controller',	'triggerAfterRdadedDocument',      'after'	),
        array( 'document.updateVotedCount',	'elasticsearch',	'controller',	'triggerAfterUpdateVotedCountDocument',      'after'	),
        array( 'document.getDocumentList',	'elasticsearch',	'controller',	'triggerBeforeGetDocumentList',      'before'	),
        array( 'comment.insertComment',		'elasticsearch',	'controller',	'triggerBeforeInsertComment',       'after'	),
        array( 'comment.updateComment',		'elasticsearch',	'controller',	'triggerBeforeUpdateComment',       'after'	),
        array( 'comment.deleteComment',		'elasticsearch',	'controller',	'triggerBeforeDeleteComment',       'after'	),
    );

    function moduleInstall()
    {
        return new BaseObject();
    }

    function moduleUninstall()
    {
        return new BaseObject();
    }


    function checkUpdate()
    {
        $oModuleModel = getModel('module');
        foreach ($this->triggers as $trigger)
        {
            if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
            {
                return true;
            }
        }

        return false;
    }

    function moduleUpdate()
    {

        $oModuleModel = getModel('module');
        $oModuleController = getController('module');
        foreach ($this->triggers as $trigger)
        {
            if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
            {
                $oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
            }
        }

        return new BaseObject();
    }

}

/* End of file elasticsearch.class.php */
/* Location: ./modules/elasticsearch/elasticsearch.class.php */
