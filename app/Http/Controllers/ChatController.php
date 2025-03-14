<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ChatController extends Controller {

    public function OCR(Request $request) {
        $archivo = $request->file('file');
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
                    'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='. env('GOOGLE_API_KEY'),
                    
                ]);
                $body = 
                [
                    "contents"=> [
                        [
                            "parts"=> [
                                [
                                    "text"=> "Dame el ruc del cliente, fecha de factura, iva ( debes aclarar si es iva 5% o 10%, siempre aclara ambos) y total abonado en formato json.Ademas, añade un campo mas que se llame claridad, donde si la imagen es muy borrosa deberas dar un porcentaje en 0% a 100%, si no puedes leerlo, igualmente debes responder en json con ese campo cargado."
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
                $jsonResp = ['status' => 200, 'data' => ['response_api' => $dataResponse["candidates"][0]["content"]["parts"][0]["text"]]];
                if($bodyInclude) $jsonResp['data']['body_usado'] = $body;                                
                return response()->json($jsonResp, 200);                
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'data' => $th->getMessage()], 500);
        }        

    }
    
}
