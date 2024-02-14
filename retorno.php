<?php

final class Masks {
    const string CPF = '###.###.###-##';
    const string CNPJ = '##.###.###/####-##';
    const string DATE = '##/##/####';

    function __invoke(string $str, string $mask): string {
        for($i=0;$i<strlen($str);$i++) {
            $mask[strpos($mask,'#')] = $str[$i];
        }
        return $mask;
    }
}

try {
    $handle = fopen('MCI470.ret', 'r');
    $handleOutput = fopen('MCI470.csv', 'w+');

    $line = ['Sequencial', 'CNPJ', 'Data', 'Nome Cliente', 'Agencia', 'Digito Ag.', 'Setex', 'Digito Setex', 'Conta', 'Dig. Conta', 'Ocorrencia Cliente', 'Ocorrencia Conta', 'Ocorrencia Lim. Crédito', 'Cod. Cliente'];
    fputcsv($handleOutput, $line, separator: ';');

    for($i=0;($data = fgets($handle, 4096)) !== false;$i++) {
        $line = [];

        if($i==5) throw new Exception('Erro no 5');

        if($i !== 0 && substr($data, 0, 5) !== '99999') {
            $line[] = substr($data, 0, 5);                                                  // 01 - seq
            $line[] = (new Masks)(substr($data, 5, 14), Masks::CNPJ);                 // 02 - cnpj
            $line[] = (new Masks)(substr($data, 19, 8), Masks::DATE);                 // 03 - dt nasc
            $line[] = trim(substr($data, 27, 60));                                          // 04 - nome client
            $line[] = substr($data, 104, 4);                                    // 06 - agencia
            $line[] = trim(substr($data, 108, 1));                                          // 07 - digito -agen
            $line[] = substr($data, 109, 2);                                    // 08 - setex
            $line[] = trim(substr($data, 111, 1));                                          // 09 - dv-setex
            $line[] = substr($data, 112, 11);                                   // 10 - conta
            $line[] = trim(substr($data, 123, 1));                                          // 11 - dv-conta
            $line[] = trim(substr($data, 124, 3));                                          // 12 - occ. client
            $line[] = trim(substr($data, 127, 3));                                          // 13 - occ. conta
            $line[] = trim(substr($data, 130, 3));                                          // 14 - occ. lim. cred
            $line[] = trim(substr($data, 133, 9));                                          // 15 - cod. cliente

            fputs($handleOutput, '"' . implode('";"', $line) . '"' . PHP_EOL);
            echo implode(';', $line,) . PHP_EOL;
        }
    }
}catch(Exception $e) {
    echo "Erro ao gerar Arquivo";
    echo $e->getMessage() . PHP_EOL;
}