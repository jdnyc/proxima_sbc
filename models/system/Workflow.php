<?php

namespace Proxima\models\system;

use Proxima\core\Query;
use \Proxima\core\ModelBase;

/**
 * Workflow model class
 */
class Workflow extends ModelBase
{       
    /**
     * finds the workflow by task_workflow_id
     *
     * @param mixed $workflowId task_workflow_id
     * @return Workflow Workflow object
     */
    public static function find($workflowId)
    {
        $query = "SELECT * FROM bc_task_workflow WHERE task_workflow_id = '{$workflowId}'";
        return ModelBase::queryObject($query, Workflow::class);
    }

    /**
     * finds the workflow list by preset channel name
     *
     * @param string $presetChannel preset workflow channel name
     * @return array Workflow object array
     */
    public static function findWorkflowsByPresetChannel($presetChannel)
    {
        $channel = $presetChannel . '_GWR_';
        $query = "SELECT * FROM bc_task_workflow WHERE register like '{$channel}%' AND type != 'p'";
        return ModelBase::queryCollection($query, Workflow::class);
    }

    /**
     * 전체 워크플로우 조회
     *
     * @param array $conditions key/value의 배열이여야 함(ex ['user_id'=> 'admin', 'is_deleted' => true])
     * @return array Workflow object array
     */
    public static function all($conditions = [], $withoutPreset = true)
    {
        // 새로만든 Query클래스를 사용해 보았다
        $query = new Query("SELECT * FROM bc_task_workflow");
        
        $where = [];
        if (!empty($conditions)) {
            foreach($conditions as $field => $value) {                
                $query->addWhere($field, $value);
            }
        }

        if ($withoutPreset) {
            $query->addWhere('type', 'p', '!=');
        }

        return ModelBase::queryCollection($query->getAndQuery(), Workflow::class);
    }
}