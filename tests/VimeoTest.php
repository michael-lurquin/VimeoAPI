<?php

namespace MichaelLurquin\Vimeo\Tests;

use Mockery;
use MichaelLurquin\Vimeo\Vimeo;

class VimeoTest extends TestCase
{
    private $vimeo;

    public function setUp() : void
    {
        parent::setUp();

        $this->vimeo = Mockery::mock(Vimeo::class);
    }

    /** @test */
    public function example()
    {
        $this->assertTrue(true);
    }

    /** @test */
    // public function get_specification()
    // {
    //     $data = new Collection([
    //         'methods' => ['string'],
    //         'path' => '/me',
    //     ]);

    //     $this->vimeo->shouldReceive('specification')->once()->andReturn($data);

    //     $response = $this->vimeo->specification();

    //     $this->assertEquals($data->get('methods'), $response->get('methods'));
    //     $this->assertEquals($data->get('path'), $response->get('path'));
    // }

    // /** @test */
    // public function get_folders()
    // {
    //     $data = new Collection([
    //         'methods' => ['string'],
    //         'path' => '/me',
    //     ]);

    //     $this->vimeo->shouldReceive('folders')->once()->andReturn($data);

    //     $response = $this->vimeo->folders();

    //     $this->assertEquals($data->get('methods'), $response->get('methods'));
    //     $this->assertEquals($data->get('path'), $response->get('path'));
    // }

    // /** @test */
    // public function get_folder()
    // {
    //     $data = new Collection([
    //         'methods' => ['string'],
    //         'path' => '/me',
    //     ]);

    //     $folderID = 9767557;

    //     $this->vimeo->shouldReceive('folder')->withArgs(['folderID' => $folderID])->once()->andReturn($data);

    //     $response = $this->vimeo->folder($folderID);

    //     $this->assertEquals($data->get('methods'), $response->get('methods'));
    //     $this->assertEquals($data->get('path'), $response->get('path'));
    // }
}