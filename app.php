<?php
/**
 * Copyright 2015 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Client;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\HttpFoundation\Request;

// create the Silex application
$app = new Application();
$app->register(new FormServiceProvider());
$app->register(new TranslationServiceProvider());
$app->register(new TwigServiceProvider(), [
    'twig.path' => [ __DIR__ ],
]);

$app->match('/', function (Application $app, Request $request) {
    $form = $app['form.factory']
        ->createBuilder('form')
        ->add('Image1', 'file')
        ->add('Image2', 'file')
        ->getForm();

    if ($request->isMethod('POST')) {
        $form->bind($request);
        if ($form->isValid()) {
            $projectId = getenv('GCLOUD_PROJECT');
            $storage = new StorageClient([
                'projectId' => $projectId,
            ]);
            $bucketName = getenv('GCS_BUCKET_NAME');
            $bucket = $storage->bucket($bucketName);
            $prefix = 'tmp-' . time() . '/';

            $files = $request->files->get($form->getName());
            $img1 = 'uploads/' . $prefix . $files['Image1']->getClientOriginalName();
            $img2 = 'uploads/' . $prefix . $files['Image2']->getClientOriginalName();
            $bucket->upload(
                fopen($files['Image1']->getPathname(), 'r'),
                ['name' => $img1]
            );
            $bucket->upload(
                fopen($files['Image2']->getPathname(), 'r'),
                ['name' => $img2]
            );

            // make the call to the faceswap app
            $http = new Client([
                'base_uri' => sprintf(
                    'https://worker-dot-%s.appspot.com/',
                    $projectId
                ),
            ]);
            $response = $http->get('/', ['query' => [
                'image1' => $img1,
                'image2' => $img2,
                'bucket' => $bucketName
            ]]);

            $object = $bucket->object('output.jpg');
            $callback = function() use ($object) {
                echo $object->downloadAsString();
            };

            return $app->stream($callback, 200, array(
                'Content-Type' => 'image/jpeg',
                'Content-length' => $object->info()['size'],
                'Content-Disposition' => 'attachment; filename="output.jpg"'
            ));
        }
    }

    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    return $twig->render('index.html.twig', [
        'form' => $form->createView()
    ]);
}, 'GET|POST');



return $app;
