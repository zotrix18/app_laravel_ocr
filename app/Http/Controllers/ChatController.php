<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChatController extends Controller {

    public function OCR(Request $request) {
        $archivo = $request->file('file');
        if(!$archivo){
            return response()->json(['error' => 'Debe adjuntar una imagen'], 400);
        }
        $jsonResponse = (bool) $request->jsonResponse;
        $peticion = '';
        /*La respuesta y la exactitud de la misma depende del prompt que se le de*/
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
           
            if($isImage){
                //Nuevo chatCompletion
                $client = new Client([
                    'base_uri' => 'https://oi.telco.com.ar/api/chat/completions',
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('OCR_TOKEN'),
                    ],
                ]);
                $body = [
                    'stream' => false, //No retorna un stream, es decir, solo hace una respuesta
                    'model'  => 'llama3.2-vision:latest', //Por el momento, a menos que salga un modelo de vision mejor, debera usarse este
                    'keep_alive' => 0,
                    'messages' => [ //La estructura debe respetarse, aunque puede darse mas contexto antes para una mejor respuesta
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
                                    'image_url' => [ //La imagen debe enviarse en base64 y con el mime type correspondiente
                                        'url' => 'data:' . $archivo->getMimeType() . ';base64,' . base64_encode(file_get_contents($archivo->getRealPath()))
                                    ],
                                ],
                            ],
                        ],
                    ],            
                ];
                $response = $client->post('', [ //Envio en POST y como body la estructura del prompt
                    'json' => $body,
                ]);

                $dataResponse = json_decode($response->getBody()->getContents(), true);

                return response()->json(['status' => 200, 'data' => $dataResponse], 200);                
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'data' => $th->getMessage()], 500);
        }        

    }
    
}
