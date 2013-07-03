<?php
namespace CI;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;


class Application extends SymfonyApplication
{
	public $mongo = null;
	public $project = array();
	public $test = null;
	public $output = null;
	public $db = array();

	public function setMongo($mongo)
	{
        $this->mongo = $mongo;
        $this->db = $this->mongo->ci;
	}

	public function setProject($project)
	{
		$this->project = $project;
	}

	public function settest($test)
	{
		$this->test = $test;
	}

	public function setOutput($output)
	{
		$this->output = $output;
	}

	public function executeAndLog($command)
	{

		ob_start();
		passthru($command . ' 2>&1', $response);
		$output = ob_get_contents();
		ob_end_clean();

		$output = trim($output);
		$output = str_replace(TEST_DIR, null, $output);

		$data = array(
			'output' => $output,
			'response' => $response,
			'command' => $command,
			'test_id' => new \MongoId($this->test),
			'project_id' => $this->project['id'],
			'time' => new \MongoDate()
		);
		$this->db->logs->save($data);

		$output = $response == 0 ? "<info>success</info>" : "<error>failed</error>";

		$this->output->writeln($data['command']);
		$this->output->writeln('... ' . $output);
		$this->output->writeln('');

		return $data;
	}

	public function testFailed($command_response)
	{
		$this->db->tests->update(array(
            '_id' => $this->test,
            'project_id' => $this->project['id'],
        ), array(
            '$set' => array(
                'status' => array(
                    'message' => 'Failed',
                    'code' => '0',
                    'command' => $command_response
                ),
                'finished' => new \MongoDate()
            )
        ));

		$this->output->writeln('');
		$this->output->writeln('<question>Running "fail" commands</question>');
        foreach ($this->project['commands']['fail'] as $fail)
        {
            $response = $this->executeAndLog($fail);
        }

        $this->output->writeln('<error>test failed</error>');
		return false;
	}

	public function testPassed()
	{
		$this->db->tests->update(array(
            '_id' => $this->test,
            'project_id' => $this->project['id'],
        ), array(
            '$set' => array(
                'status' => array(
                    'message' => 'Passed',
                    'code' => '1'
                ),
                'finished' => new \MongoDate()
            )
        ));

		$this->output->writeln('');
		$this->output->writeln('<question>Running "pass" commands</question>');
        foreach ($this->project['commands']['pass'] as $pass)
        {
            $response = $this->executeAndLog($pass);
        }

        $this->output->writeln('<question>test passed</question>');
		return false;
	}

	public function parseConfig($config)
	{
        $project = Yaml::parse($config);

        if( ! isset($project['commands']) || ! is_array($project['commands']))
        {
        	return false;
        }

        foreach (array('setup', 'test', 'fail', 'pass') as $section)
        {
        	if ( ! isset($project['commands'][$section]) ||  ! is_array($project['commands'][$section]))
        	{
        		 $project['commands'][$section] = array();
        	}
        }

        $this->db->tests->update(array(
        	'_id' => $this->test,
        ), array(
        	'$set' => array(
        		'config' => $project
        	)
        ));

        return $project;
	}


}