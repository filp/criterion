<?php

namespace Criterion\Test\Model;

class ProjectModelTest extends \Criterion\Test\TestCase
{

    public function testInit()
    {
        $project = new \Criterion\Model\Project();
        $this->assertTrue(is_object($project));
    }

    public function testInitWithRepo()
    {
        $project = new \Criterion\Model\Project(array(
            'repo' => 'https://github.com/romhut/criterion'
        ));

        $this->assertTrue(is_object($project));
    }

    public function testGetTests()
    {
        $project = new \Criterion\Model\Project();
        $this->assertTrue(is_object($project));

        $test = new \Criterion\Model\Test();
        $this->assertTrue(is_object($test));
        $test->project_id = $project->id;
        $test->save();

        $tests = $project->getTests();

        $this->assertTrue(is_array($tests));
        $this->assertTrue(count($tests) === 1);
    }
}
