<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ConceptToolController extends Controller
{
    public function index()
    {
        return view('conceptTool');
    }

    public function process(Request $request)
    {
        $request->validate([
            'prj' => 'required|string|max:50',
            'sid' => 'required|string|max:50',
            'code' => 'required|string',
            'codici' => 'nullable|string',
            'tipo' => 'required|in:0,1'
        ]);

        $prj = strtoupper($request->prj);
        $sid = strtoupper($request->sid);
        $tipo = $request->tipo;
        $codici = array_filter(array_map('trim', explode(',', $request->codici ?? '')));
        $righe = explode("\n", $request->code);

        $pathResources = base_path("var/imr/fields/$prj/$sid/resources/");
        if (!File::exists($pathResources)) {
            File::makeDirectory($pathResources, 0775, true);
        }

        $htmlOutput = "";
        $riga = 0;
        $contaImg = 0;

        foreach ($righe as $result) {
            $riga++;

            // Creazione immagini spacer.gif
            if (strpos($result, "spacer.gif") !== false) {
                preg_match('/width="(.*?)"/s', $result, $mW);
                preg_match('/height="(.*?)"/s', $result, $mH);
                $width = isset($mW[1]) ? (int)$mW[1] : -1;
                $height = isset($mH[1]) ? (int)$mH[1] : -1;

                if ($width > 0 && $height > 0) {
                    $image = imagecreate($width, $height);
                    imagecolorallocate($image, 255, 255, 255);
                    $nomeImmagine = time() . "_{$riga}.gif";
                    $savePath = $pathResources . strtolower($nomeImmagine);
                    imagepng($image, $savePath);
                    imagedestroy($image);

                    $result = str_replace("spacer.gif", $nomeImmagine, $result);
                }
            }

            // Rimozione width/height -> img-responsive
            $reg = '#width="[0-9]+" height="[0-9]+"#i';
            $result = preg_replace($reg, 'class="img-responsive"', $result);

            // Correzione virgolette
            $result = str_replace('"', "'", $result);

            // Correzione percorso immagini
            $result = str_replace(
                'images/',
                "https://www.primisoft.com/fields/$prj/$sid/resources/",
                $result
            );

            // Gestione codici
            foreach ($codici as $value) {
                $value = str_pad($value, 2, '0', STR_PAD_LEFT);
                $stringa1 = "_$value.png";
                $stringa2 = "_$value.jpg";

                if ($tipo == "0") { // Evaluator
                    if (strpos($result, $stringa1) !== false || strpos($result, $stringa2) !== false) {
                        $result = str_replace('<img', "#as<img", $result);
                        $result = str_replace("alt=''>", "alt=''>#ae", $result);
                    }
                }

                if ($tipo == "1") { // Zoom
                    if (strpos($result, $stringa1) !== false || strpos($result, $stringa2) !== false) {
                        $contaImg++;
                        $result = str_replace(
                            '<img',
                            "<a class='fancybox' rel='group' href='https://www.primisoft.com/fields/$prj/$sid/resources/b$contaImg.jpg'><img",
                            $result
                        );
                        $result = str_replace("alt=''>", "alt=''></a>", $result);
                    }
                }
            }

            $htmlOutput .= htmlspecialchars($result) . "\n";
        }

        return view('conceptTool', [
            'result' => $htmlOutput,
            'prj' => $prj,
            'sid' => $sid,
            'tipo' => $tipo,
            'codici' => implode(',', $codici),
            'code' => $request->code
        ]);
    }
}
