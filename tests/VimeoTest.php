<?php

namespace MichaelLurquin\Vimeo\Tests;

use Mockery;
use MichaelLurquin\Vimeo\Vimeo;
use Illuminate\Support\Collection;

class VimeoTest extends TestCase
{
    private $vimeo;

    public function setUp()
    {
        parent::setUp();

        $this->vimeo = Mockery::mock(Vimeo::class);
    }

    /** @test */
    public function get_specification()
    {
        $data = new Collection([
            'methods' => ['string'],
            'path' => '/me',
        ]);

        $this->vimeo->shouldReceive('getSpecification')->once()->andReturn($data);

        $response = $this->vimeo->getSpecification();

        $this->assertEquals($data->get('methods'), $response->get('methods'));
        $this->assertEquals($data->get('path'), $response->get('path'));
    }

    /** @test */
    public function get_folders()
    {
        $data = new Collection([
            'methods' => ['string'],
            'path' => '/me',
        ]);

        $this->vimeo->shouldReceive('getFolders')->once()->andReturn($data);

        $response = $this->vimeo->getFolders();

        $this->assertEquals($data->get('methods'), $response->get('methods'));
        $this->assertEquals($data->get('path'), $response->get('path'));
    }

    /** @test */
    public function get_folder()
    {
        $data = new Collection([
            'methods' => ['string'],
            'path' => '/me',
        ]);

        $folderID = 9767557;

        $this->vimeo->shouldReceive('getFolder')->withArgs($folderID)->once()->andReturn($data);

        $response = $this->vimeo->getFolder($folderID);

        $this->assertEquals($data->get('methods'), $response->get('methods'));
        $this->assertEquals($data->get('path'), $response->get('path'));
    }
}