# Gerador de MCIF460
Script para gerar arquivo no formarto MCIF460 para abertura em massa de contas no Banco do Brasil.

Para executar o programa execute:
```sh
php index.php [nome-do-arquivo]
```
Ao final será gerado um arquivo chamado `mcif460.txt` pronto para ser enviado ao banco.
Para testar o layout, acesse:
https://gmtedi.bb.com.br/validaleiaute/#/

> [!NOTE]
> - Necessita do PHP 8.
> - Necessário instalar mb_string.
