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
        array( 'document.moveDocumentModule',	'elasticsearch',	'controller',	'triggerBeforeMoveDocumentModule',      'before'	),
        array( 'document.moveDocumentModule',	'elasticsearch',	'controller',	'triggerAfterMoveDocumentModule',      'after'	),
        array( 'document.moveDocumentToTrash',	'elasticsearch',	'controller',	'triggerAfterTrashDocument',      'after'	),
        array( 'document.restoreTrash',      	'elasticsearch',	'controller',	'triggerAfterRestoreTrashDocument',      'after'	),
        array( 'document.copyDocumentModule',      	'elasticsearch',	'controller',	'triggerBeforeCopyDocument',      'before'	),
        array( 'document.copyDocumentModule',      	'elasticsearch',	'controller',	'triggerAfterCopyDocument',      'after'	),
        array( 'document.getDocumentList',	'elasticsearch',	'controller',	'triggerBeforeGetDocumentList',      'before'	),
        array( 'comment.insertComment',		'elasticsearch',	'controller',	'triggerAfterInsertComment',       'after'	),
        array( 'comment.updateComment',		'elasticsearch',	'controller',	'triggerAfterUpdateComment',       'after'	),
        array( 'comment.deleteComment',		'elasticsearch',	'controller',	'triggerAfterDeleteComment',       'after'	),
        array( 'comment.copyCommentByDocument',		'elasticsearch',	'controller',	'triggerAddCopyComment',       'add'	),

        array( 'file.insertFile',		'elasticsearch',	'controller',	'triggerAfterInsertFile',       'after'	),
        array( 'file.deleteFile',		'elasticsearch',	'controller',	'triggerAfterInsertDelete',       'after'	),
        array( 'moduleHandler.init',			'elasticsearch',	'controller',	'triggerBeforeModuleInit',				'before'	),
        array( 'moduleHandler.proc',			'elasticsearch',	'controller',	'triggerAfterModuleProc',				'after'	),
        array( 'module.deleteModule',			'elasticsearch',	'controller',	'triggerDeleteModuleData',				'before'	)



    );

    function moduleInstall()
    {
        $oModuleController = getController('module');
        $oModuleController->insertActionForward('elasticsearch', 'view', 'EIS');

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
        if(!isset($oModuleModel->getActionForward("EIS")->act)) {
            return true;
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

        if(!isset($oModuleModel->getActionForward("EIS")->act)) {
            $oModuleController->insertActionForward('elasticsearch', 'view', 'EIS');
        }

        return new BaseObject();
    }

}

/* End of file elasticsearch.class.php */
/* Location: ./modules/elasticsearch/elasticsearch.class.php */
