<?php
include_once APP . 'Model/WorkflowModules/WorkflowBaseModule.php';
App::uses('BackgroundJobsTool', 'Tools');

class Module_parallel_task extends WorkflowBaseModule
{
    public $id = 'parallel-task';
    public $name = 'Parallel Task';
    public $description = 'Allow breaking the execution process and running parallel tasks. You can connect multiple blocks the `parallel` output.';
    public $icon = 'random';
    public $inputs = 1;
    public $outputs = 1;
    public $multiple_output_connection = true;
    public $html_template = 'parallel';
    public $params = [];

    private $Workflow;
    private $Job;

    public function __construct()
    {
        parent::__construct();
        $this->Workflow = ClassRegistry::init('Workflow');
        $this->Job = ClassRegistry::init('Job');
    }

    public function exec(array $node, WorkflowRoamingData $roamingData, array &$errors = []): bool
    {
        parent::exec($node, $roamingData, $errors);

        $data = $roamingData->getData();
        $node_id_to_exec = (int)$data['__node_id_to_exec'];
        unset($data['__node_id_to_exec']);
        $roamingData->setData($data);

        $jobId = $this->Job->createJob(
            $roamingData->getUser(),
            Job::WORKER_PRIO,
            'workflowParallelTask',
            sprintf('Workflow ID: %s', $roamingData->getWorkflow()['Workflow']['id']),
            'Running workflow parallel tasks.'
        );
        $this->Job->getBackgroundJobsTool()->enqueue(
            BackgroundJobsTool::PRIO_QUEUE,
            BackgroundJobsTool::CMD_WORKFLOW,
            [
                'walkGraph',
                $roamingData->getWorkflow()['Workflow']['id'],
                $node_id_to_exec,
                JsonTool::encode($roamingData->getData()),
                Workflow::NON_BLOCKING_PATH,
                $jobId
            ],
            true,
            $jobId
        );
        return true;
    }
}
