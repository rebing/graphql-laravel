<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\UploadTests;

use Illuminate\Http\UploadedFile;
use Rebing\GraphQL\Support\UploadType;
use Rebing\GraphQL\Tests\TestCase;

class UploadTest extends TestCase
{
    public function testSingleFile(): void
    {
        $fileToUpload = UploadedFile::fake()->create('file.txt');
        fwrite($fileToUpload->tempFile, "This is the\nuploaded\ndata");

        $result = $this
            ->call(
                'POST',
                '/graphql',
                // $parameters
                [
                    'operations' => json_encode([
                        'query' => 'mutation($file: Upload!) { uploadSingleFile(file: $file) }',
                        'variables' => [
                            'file' => null,
                        ],
                    ]),
                    'map' => json_encode([
                        '0' => ['variables.file'],
                    ]),
                ],
                // $cookies
                [],
                // $files
                [
                    '0' => $fileToUpload,

                ],
                // $server
                [
                    'CONTENT_TYPE' => 'multipart/form-data',
                ]
            )
            ->json();

        $expectedResult = [
            'data' => [
                'uploadSingleFile' => "This is the\nuploaded\ndata",
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testMultipleFiles(): void
    {
        $file1ToUpload = UploadedFile::fake()->create('file1.txt');
        fwrite($file1ToUpload->tempFile, 'File 1 to upload');

        $file2ToUpload = UploadedFile::fake()->create('file2.txt');
        fwrite($file2ToUpload->tempFile, 'File 2 to upload');

        $result = $this
            ->call(
                'POST',
                '/graphql',
                // $parameters
                [
                    'operations' => json_encode([
                        'query' => 'mutation($files: [Upload!]!) { uploadMultipleFiles(files: $files) }',
                        'variables' => [
                            'files' => [null, null],
                        ],
                    ]),
                    'map' => json_encode([
                        '0' => ['variables.files.0'],
                        '1' => ['variables.files.1'],
                    ]),
                ],
                // $cookies
                [],
                // $files
                [
                    '0' => $file1ToUpload,
                    '1' => $file2ToUpload,

                ],
                // $server
                [
                    'CONTENT_TYPE' => 'multipart/form-data',
                ]
            )
            ->json();

        $expectedResult = [
            'data' => [
                'uploadMultipleFiles' => [
                    'File 1 to upload',
                    'File 2 to upload',
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    public function testBatchUploads(): void
    {
        $file1ToUpload = UploadedFile::fake()->create('file1.txt');
        fwrite($file1ToUpload->tempFile, 'File 1 to upload');

        $file2ToUpload = UploadedFile::fake()->create('file2.txt');
        fwrite($file2ToUpload->tempFile, 'File 2 to upload');

        $file3ToUpload = UploadedFile::fake()->create('file3.txt');
        fwrite($file3ToUpload->tempFile, 'File 3 to upload');

        $result = $this
            ->call(
                'POST',
                '/graphql',
                // $parameters
                [
                    'operations' => json_encode([
                        [
                            'query' => 'mutation($file: Upload!) { uploadSingleFile(file: $file) }',
                            'variables' => [
                                'file' => null,
                            ],
                        ],
                        [
                            'query' => 'mutation($files: [Upload!]!) { uploadMultipleFiles(files: $files) }',
                            'variables' => [
                                'files' => [null, null],
                            ],
                        ],
                    ]),
                    'map' => json_encode([
                        '0' => ['0.variables.file'],
                        '1' => ['1.variables.files.0'],
                        '2' => ['1.variables.files.1'],
                    ]),
                ],
                // $cookies
                [],
                // $files
                [
                    '0' => $file1ToUpload,
                    '1' => $file2ToUpload,
                    '2' => $file3ToUpload,

                ],
                // $server
                [
                    'CONTENT_TYPE' => 'multipart/form-data',
                ]
            )
            ->json();

        $expectedResult = [
            [
                'data' => [
                    'uploadSingleFile' => 'File 1 to upload',
                ],
            ],
            [
                'data' => [
                    'uploadMultipleFiles' => [
                            'File 2 to upload',
                            'File 3 to upload',
                        ],
                ],
            ],
        ];
        $this->assertSame($expectedResult, $result);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                UploadMultipleFilesMutation::class,
                UploadSingleFileMutation::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            UploadType::class,
        ]);
    }
}
