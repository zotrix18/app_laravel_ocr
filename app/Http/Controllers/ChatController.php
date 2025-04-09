<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChatController extends Controller {

    public function OCR(Request $request) {
        $archivo = $request->file('file');
        $prompt = $request->prompt ?? "Dame el ruc del cliente, fecha de factura, iva ( debes aclarar si es iva 5% o 10%, siempre aclara ambos) y total abonado en formato json.Ademas, añade un campo mas que se llame claridad, donde si la imagen es muy borrosa deberas dar un porcentaje en 0% a 100%, si no puedes leerlo, igualmente debes responder en json con ese campo cargado en null. Si algun campo es nulo, claridad sera 0%";
        $model = $request->model ?? 'gemini-1.5-flash';
        $bodyInclude = (bool) $request->body;
        if(!$archivo){
            return response()->json(['error' => 'Debe adjuntar una imagen'], 400);
        }
 
        $peticion = 'Actuarás como un OCR, debes responder con el contenido legible que te estoy enviando en formato Markdown, de forma ordenada y concisa, sin comentarios adicionales. Necesito saber que RUC tiene el cliente, cuanto gasto y cuanto le cobraron de iva';
     

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
                    'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/models/'. $model .':generateContent?key='. env('GOOGLE_API_KEY'),
                    
                ]);
                $body = 
                [
                    "contents"=> [
                        [
                            "parts"=> [
                                [
                                    "text"=> $prompt
                                ],
                                [
                                    "inline_data"=> [
                                        "mime_type"=> $archivo->getMimeType(),
                                        "data"=> base64_encode(file_get_contents($archivo->getRealPath()))
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                $response = $client->post('', [
                    'json' => $body,
                ]);

                $dataResponse = json_decode($response->getBody()->getContents(), true);                               
                $jsonResp = [$dataResponse];
                if($bodyInclude) $jsonResp['data']['body_usado'] = $body;                                
                return response()->json($jsonResp, 200);                
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'data' => $th->getMessage()], 500);
        }        

    }
    
    public function consultaDoCo (Request $request){
        $archivo = $request->file('file');
        $prompt = $request->prompt;
        $continuacion = $request->continuacion;
        $model = env('GOOGLE_MODEL_DOCO');
        $user = $request->user;

        try {
            $mimeType = $archivo->getMimeType();
            if(!$archivo){
                return response()->json(['error' => 'Debe adjuntar un pdf'], 400);
            }
            $type = explode('/', $mimeType);        
            $isPdf = $type[0] === 'application' && $type[1] === 'pdf';
            if (!$isPdf) {
                return response()->json(['error' => 'El archivo no es un PDF'], 400);
            }
            $client = new Client([
                'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/models/'. $model .':generateContent?key='. env('GOOGLE_API_KEY_DOCO'),
            ]);
            
            $body = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "inline_data" => [
                                    "mime_type" => "application/pdf",
                                    "data" => base64_encode(file_get_contents($archivo->getRealPath()))
                                ]
                            ],
                            [
                                "text" => $prompt . " \n \n Dirigete al user como " . $user . " y no olvides que el usuario es un cliente, por lo tanto debes ser amable y cordial."
                            ]
                        ]
                    ]
                ]
            ];

            $response = $client->post('', [
                'json' => $body,
            ]);

            $dataResponse = json_decode($response->getBody()->getContents(), true);                               
            $jsonResp = [
                "history" => [
                    $body['contents'],
                    $dataResponse["candidates"][0]["content"]
                ]
                ];

            // if($bodyInclude) $jsonResp['data']['body_usado'] = $body;                                
            return response()->json($jsonResp, 200);                
        } catch (\Throwable $th) {
            return response()->json(['status' => $th->getCode(), 'data' => $th->getMessage()], $th->getCode());
        }
    }
}
