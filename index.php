<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Gerar MCIF 460</title>
</head>
<body>
<form method="post" action="#" enctype="multipart/form-data">
    <input type="file" name="csv_data">
    <button>Gerar</button>
</form>
</body>
</html><?php

if(!$_FILES['csv_data'] && !isset($argv[1]) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    exit;
} else {
    $filename = $_FILES['csv_data']['tmp_name'] ?? $argv[1];
}

function cleanString(string $text): string
{
    $utf8 = array(
        '/[áàâãªä]/u'   =>   'a',
        '/[ÁÀÂÃÄ]/u'    =>   'A',
        '/[ÍÌÎÏ]/u'     =>   'I',
        '/[íìîï]/u'     =>   'i',
        '/[éèêë]/u'     =>   'e',
        '/[ÉÈÊË]/u'     =>   'E',
        '/[óòôõºö]/u'   =>   'o',
        '/[ÓÒÔÕÖ]/u'    =>   'O',
        '/[úùûü]/u'     =>   'u',
        '/[ÚÙÛÜ]/u'     =>   'U',
        '/ç/'           =>   'c',
        '/Ç/'           =>   'C',
        '/ñ/'           =>   'n',
        '/Ñ/'           =>   'N',
        '/–/'           =>   '-', // UTF-8 hyphen to "normal" hyphen
        '/[’‘‹›‚]/u'    =>   ' ', // Literally a single quote
        '/[“”«»„]/u'    =>   ' ', // Double quote
        '/ /'           =>   ' ', // nonbreaking space (equiv. to 0x160)
        '/\(|\)/'       =>   ''
    );
    return preg_replace(array_keys($utf8), array_values($utf8), $text);
}

if(!$filename) {
    die('Arquivo não informado' . PHP_EOL);
}

if(!file_exists($filename)) {
    die('Arquivo não existe' . PHP_EOL);
}

$seqRemessa = '00012';

$outputMCIF460 = [
    '0000000'                                  // 01
    . (new DateTime())->format('dmY')   // 02 - ddmmaaaa
    . 'MCIF460 '                               // 03 - nome do arquivo
    . '103401304'                              // 04 - MCI da empresa (passado pelo banco)
    . '94477'                                  // 05 - numero do processo
    . $seqRemessa                              // 06 - sequencial de remessa
    . '04'                                     // 07 - versão do layout
    . '0008'                                   // 08 - agência da Secult
    . '6'                                      // 09 - dv-agência da Secult
    . '00000028890'                            // 10 - conta da Secult
    . 'X'                                      // 11 - dv-conta da Secult
    . '1'                                      // 12 - indicador de envio do kit [1|2|3]
];
$outputMCIF460[0] = str_pad($outputMCIF460[0], 150);

try {
    $handle = fopen($filename,'r');//'pessoas.csv', 'r');

    for($i = -1; ($data = fgetcsv($handle, separator: ",")) !== false; $i++) {
        if($i > 0) {

            $isPf = str_contains(mb_strtolower(cleanString($data[0])), 'pessoa fisica');

            if($isPf > 0) {
                $tipoPessoa = '1';
                $tipoCGC = '1';
                $posAgencia = 7;
                $posAgenciaDv = 8;
                $posNomeCurto = 2;
                $posCreateDt = 6;
            } else {
                $tipoPessoa = '2';
                $tipoCGC = '3';
                $posAgencia = 10;
                $posAgenciaDv = 11;
                $posNomeCurto = 3;
                $posCreateDt = 4;
            }

//            $cnpj = preg_replace('/(\/|-|.)/', '', $data[1]);
            $cnpj = preg_replace('/[^0-9]/', '', $data[1]);
            $createDt = preg_replace('/([\/|-])/', '', $data[$posCreateDt]);
            $nome = str_pad(substr(cleanString($data[2]), 0, 60), 60);
            $nomeCurto = str_pad(substr(cleanString($data[$posNomeCurto]), 0, 25), 25);
            $agencia = str_pad($data[$posAgencia], 4, '0', STR_PAD_LEFT);
            $agenciaDv = strtoupper($data[$posAgenciaDv]);

            $outputMCIF460[$i] =
                str_pad($i, 5, '0', STR_PAD_LEFT)              // 01 - sequencial do cliente
                . '01'                                                                 // 02 - tipo do detalhe [01]
                . $tipoPessoa                                                          // 03 - tipo de pessoa [1|2|3|4|5...N|O|P]
                . $tipoCGC                                                             // 04 - tipo de CPF/CNPJ
                . str_pad($cnpj, 14, '0', STR_PAD_LEFT)        // 05 - CPF/CNPJ
                . $createDt                                                            // 06 - data de nascimento/abertura
                . $nome                                                                // 07 - razao social
                . $nomeCurto                                                           // 08 - nome fantasia
                . ' '                                                                  // 09 - espaço em branco
                . str_pad('', 8)                                          // 10 - Uso cliente
                . '000000000'                                                          // 11 - Núm. Prog. Gestão Ágil
                . $agencia                                                             // 12 - agencia
                . $agenciaDv                                                           // 13 - dv-agencia
                . '019'                                                                // 14 e 15 - grupo-setex
                . '000'                                                                // 16 - Natureza jurídica (Contante '000')
                . '02'                                                                 // 17 - Código Repasse [01|02]
                . '   '                                                                // Código do Programa
            ;
        }
    }
} catch (Exception) {
    echo 'Erro';
}

$outputMCIF460[] = '9999999'                                                            // 01 (Contante '9999999')
    . str_pad($i - 1, 5, '0', STR_PAD_LEFT)               // 02 Total de clientes
    . str_pad($i + 1, 9, '0', STR_PAD_LEFT)               // 03 Quantidade de registros (Header e Trailer inclusos)
    . str_pad('', 129);                                                     // 04 Espaço em branco

$filenameOutput = $seqRemessa . '_' . ($argv[1] ?? $_FILES['csv_data']['name']) . '.txt';
file_put_contents($filenameOutput, implode(PHP_EOL, $outputMCIF460));

if($_SERVER['REQUEST_METHOD'] !== 'POST')
foreach ($outputMCIF460 as $line) {
    echo $line . PHP_EOL;
}

?><a href="<?= $filenameOutput ?>">Baixar MCIF460</a>
