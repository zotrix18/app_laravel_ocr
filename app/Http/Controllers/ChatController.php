<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChatController extends Controller {

    public function OCR(Request $request) {
        $archivo = $request->file('file');
        $jsonResponse = (bool) $request->jsonResponse;
        $peticion = '';
        if($jsonResponse){
            $peticion = 'Deberás actuar como un OCR, analizando el contenido legible que te envío. Responde en JSON estructurado y claro, sin comentarios adicionales. Dame absolutamente todo el texto contenido en la imagen, sin excepcion';
        }else{
            $peticion = 'Actuarás como un OCR, debes responder con el contenido legible que te estoy enviando en formato Markdown, de forma ordenada y concisa, sin comentarios adicionales. Dame absolutamente todo el texto contenido en la imagen, sin excepcion';
        }

        try {
            $mimeType = $archivo->getMimeType();
            $type = explode('/', $mimeType);
            $isImage = $type[0] === 'image';
            $isPdf = $type[0] === 'application' && $type[1] === 'pdf';
            if (!$isImage && !$isPdf) {
                return response()->json(['error' => 'El archivo no es una imagen ni un PDF'], 400);
            }
            // if($isPdf){
            //     $client = new Client([
            //         'base_uri' => 'https://oi.telco.com.ar/api/v1/files/',
            //         'headers' => [
            //             'Authorization' => 'Bearer ' . env('OCR_TOKEN'),
            //         ],
            //     ]);
            //     $response = $client->post('', [
            //         'multipart' => [
            //             [
            //                 'name'     => 'file',
            //                 'contents' => fopen($archivo->getRealPath(), 'rb'),
            //                 'filename' => $archivo->getClientOriginalName(),
            //                 'headers'  => [
            //                     'Content-Type' => $archivo->getMimeType()
            //                 ],
            //             ],
            //         ],
            //     ]);
            //     $dataFile = json_decode($response->getBody()->getContents(), true);
            //     if(isset($dataFile['data']) && empty($dataFile['data']) ){
            //         return response()->json(['error' => 'Contenido vacio o no válido. No se aceptan pdf con imágenes'], 400);
            //     }
            //     $client = new Client([
            //         'base_uri' => 'https://oi.telco.com.ar/api/chat/completions',
            //         'headers' => [
            //             'Authorization' => 'Bearer ' . env('OCR_TOKEN'),
            //         ],
            //     ]);
            //     $body = [
            //         'stream' => false,
            //         'model'  => 'llama3.2-vision:latest',
            //         'keep_alive' => 0,
            //         "files" => [
            //             [
            //                 "type" => "file",
            //                 "file" => $dataFile
            //             ]
            //         ],
            //         'messages' => [
            //             [
            //                 'role'    => 'system',
            //                 'content' => 'Dame absolutamente todo el texto contenido en la imagen, sin excepcion',
            //             ],
            //             [
            //                 'role'    => 'user',
            //                 'content' => [
            //                     [
            //                         'type' => 'text',
            //                         'text' => $peticion,
            //                     ],
            //                     [
            //                         'type'      => 'image_url',
            //                         'image_url' => [
            //                             'url' => 'data:' . $archivo->getMimeType() . ';base64,' . base64_encode(file_get_contents($archivo->getRealPath()))
            //                         ],
            //                     ],
            //                 ],
            //             ],
            //         ],
            //         "model_item" => [
            //             "id" => "llama3.2-vision:latest",
            //             "name" => "llama3.2-vision:latest",
            //             "object" => "model",
            //             "created" => 1741175616,
            //             "owned_by" => "ollama",
            //             "ollama" => [
            //                 "name" => "llama3.2-vision:latest",
            //                 "model" => "llama3.2-vision:latest",
            //                 "modified_at" => "2024-11-07T13:49:14.690776531Z",
            //                 "size" => 7901829417,
            //                 "digest" => "38107a0cd11910a31c300fcfd1e9a107b2928e56ebabd14598702170b004773e",
            //                 "details" => [
            //                     "parent_model" => "",
            //                     "format" => "gguf",
            //                     "family" => "mllama",
            //                     "families" => [
            //                         "mllama",
            //                         "mllama"
            //                     ],
            //                     "parameter_size" => "9.8B",
            //                     "quantization_level" => "Q4_K_M"
            //                 ],
            //                 "urls" => [
            //                     0
            //                 ]
            //             ],
            //             "info" => [
            //                 "id" => "llama3.2-vision:latest",
            //                 "user_id" => "ecb901b5-3a12-4456-b5a6-d1bbe5c8fd1c",
            //                 "base_model_id" => null,
            //                 "name" => "llama3.2-vision:latest",
            //                 "params" => [],
            //                 "meta" => [
            //                     "profile_image_url" => "/static/favicon.png",
            //                     "description" => "",
            //                     "capabilities" => [
            //                         "vision" => true,
            //                         "citations" => true
            //                     ],
            //                     "suggestion_prompts" => null,
            //                     "tags" => []
            //                 ],
            //                 "access_control" => [
            //                     "read" => [
            //                         "group_ids" => [
            //                             "1d56e742-5169-47ec-86cd-9fa1298c6861"
            //                         ]
            //                     ]
            //                 ],
            //                 "is_active" => true,
            //                 "updated_at" => 1736263227,
            //                 "created_at" => 1736263227
            //             ],
            //             "actions" => []
            //         ]
            //     ];
            //     $response = $client->post('', [
            //         'json' => $body,
            //     ]);

            //     return response()->json(['status' => 200, 'data' => $dataFile], 200);
            // }
            if($isImage){
                $client = new Client([
                    'base_uri' => 'https://oi.telco.com.ar/api/chat/completions',
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('OCR_TOKEN'),
                    ],
                ]);
                $body = [
                    'stream' => false,
                    'model'  => 'llama3.2-vision:latest',
                    'keep_alive' => 0,
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'Dame absolutamente todo el texto contenido en la imagen, sin excepcion',
                        ],
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $peticion,
                                ],
                                [
                                    'type'      => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:' . $archivo->getMimeType() . ';base64,' . base64_encode(file_get_contents($archivo->getRealPath()))
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "model_item" => [
                        "id" => "llama3.2-vision:latest",
                        "name" => "llama3.2-vision:latest",
                        "object" => "model",
                        "created" => 1741175616,
                        "owned_by" => "ollama",
                        "ollama" => [
                            "name" => "llama3.2-vision:latest",
                            "model" => "llama3.2-vision:latest",
                            "modified_at" => "2024-11-07T13:49:14.690776531Z",
                            "size" => 7901829417,
                            "digest" => "38107a0cd11910a31c300fcfd1e9a107b2928e56ebabd14598702170b004773e",
                            "details" => [
                                "parent_model" => "",
                                "format" => "gguf",
                                "family" => "mllama",
                                "families" => [
                                    "mllama",
                                    "mllama"
                                ],
                                "parameter_size" => "9.8B",
                                "quantization_level" => "Q4_K_M"
                            ],
                            "urls" => [
                                0
                            ]
                        ],
                        "info" => [
                            "id" => "llama3.2-vision:latest",
                            "user_id" => "ecb901b5-3a12-4456-b5a6-d1bbe5c8fd1c",
                            "base_model_id" => null,
                            "name" => "llama3.2-vision:latest",
                            "params" => [],
                            "meta" => [
                                "profile_image_url" => "/static/favicon.png",
                                "description" => "",
                                "capabilities" => [
                                    "vision" => true,
                                    "citations" => true
                                ],
                                "suggestion_prompts" => null,
                                "tags" => []
                            ],
                            "access_control" => [
                                "read" => [
                                    "group_ids" => [
                                        "1d56e742-5169-47ec-86cd-9fa1298c6861"
                                    ]
                                ]
                            ],
                            "is_active" => true,
                            "updated_at" => 1736263227,
                            "created_at" => 1736263227
                        ],
                        "actions" => []
                    ]
                ];
                $response = $client->post('', [
                    'json' => $body,
                ]);

                $dataResponse = json_decode($response->getBody()->getContents(), true);

                return response()->json(['status' => 200, 'data' => $dataResponse], 200);                
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'data' => $th->getMessage()], 500);
        }
        // $client = new Client();

    }

    public function getToken(){
        return response()->json(['status' => 200, 'data' => env('OCR_TOKEN')], 200);
    }
    
}
