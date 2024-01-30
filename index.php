<?php

function cleanString($text) {
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

$outputMCIF460 = [
    '0000000'                                  // 01
    . (new \DateTime())->format('dmY')  // 02 - ddmmaaaa
    . 'MCIF460 '                               // 03 - nome do arquivo
    . '103401304'                              // 04 - MCI da empresa (passado pelo banco)
    . '00001'                                  // 05 - numero do processo
    . '00001'                                  // 06 - sequencial de remessa
    . '03'                                     // 07 - versão do layout
    . '0008'                                   // 08 - agência da Secult
    . '6'                                      // 09 - dv-agência da Secult
    . '00000029662'                            // 10 - conta da Secult
    . '7'                                      // 11 - dv-conta da Secult
    . '1'                                      // 12 - indicador de envio do kit [1|2|3]
];

$outputMCIF460[0] = str_pad($outputMCIF460[0], 150);

//echo $outputMCIF460[0] . PHP_EOL;

try {
    $handle = fopen('pessoas.csv', 'r');

    for($i = -1; ($data = fgetcsv($handle, separator: ';')) !== false; $i++) {
        if($i > 0) {
            $cnpj = preg_replace('/(\/|\-|\.)/', '', $data[1]);
            $createDt = preg_replace('/(\/|\-)/', '', $data[4]);
            $nome = str_pad(substr(cleanString($data[2]), 0, 60), 60);
            $nomeCurto = str_pad(substr(cleanString($data[3]), 0, 25), 25);
            $agencia = $data[10];
            $agenciaDv = $data[11];

            $outputMCIF460[$i] =
                str_pad($i, 5, '0', STR_PAD_LEFT)       // 01 - sequencial do cliente (ter certeza do que pode ser)
                . '01'                                                          // 02 - tipo do detalhe [01]
                . '3'                                                           // 03 - tipo de pessoa [1|2|3|4|5]
                . '3'                                                           // 04 - tipo de CPF/CNPJ
                . $cnpj                                                         // 05 - CPF/CNPJ
                . $createDt                                                     // 06 - data de nascimento/abertura
                . $nome                                                         // 07 - razao social
                . $nomeCurto                                                    // 08 - nome fantasia
                . ' '                                                           // 09 - espaço em branco
                . str_pad('', 17)                                  // 10 -
                . $agencia                                                      // 11 - agencia
                . $agenciaDv                                                    // 12 - dv-agencia
                . '019'                                                         // 13 e 14 - grupo-setex
                . str_pad('', 8)                                    // 15
            ;
        }
    }
} catch (Exception) {
    echo 'Erro';
}

array_push(
    $outputMCIF460,
    '9999999'        // 01
    . str_pad($i-1,5, '0', STR_PAD_LEFT)           // 02
    . str_pad($i+1, 9, '0', STR_PAD_LEFT)
    . str_pad('', 129)
);

file_put_contents('mcif460.txt', implode(PHP_EOL, $outputMCIF460));

foreach ($outputMCIF460 as $line) {
    echo $line . PHP_EOL;
}