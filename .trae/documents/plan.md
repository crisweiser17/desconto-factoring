# Plano: Correção do Endereço no Contrato

## Resumo
O problema relatado ("o endereço continua errado no contrato") está sendo causado pelo **cache do navegador**. Como o nome do arquivo PDF gerado não possui os minutos/segundos, o navegador do usuário está exibindo o PDF antigo (que estava na memória) em vez do novo arquivo gerado com os dados atualizados.

## Análise do Estado Atual (Current State Analysis)
1. **O Banco de Dados foi atualizado:** Como você salvou o Sacado após a correção do Erro 500, o Logradouro, Número, Bairro e os dados do Representante foram salvos corretamente no banco de dados.
2. **A Geração do Contrato:** O arquivo `api_contratos.php` puxou os dados novos e gerou um PDF novo no servidor.
3. **O Nome do Arquivo PDF:** Atualmente, a nomenclatura do arquivo gerado é `[ID_OPERACAO]_[NATUREZA]_[DATA].pdf` (exemplo: `999_EMPRESTIMO_20250429.pdf`). Como o nome **não muda ao longo do dia**, a URL do PDF continua exatamente a mesma.
4. **O Cache:** Quando você clica para abrir o contrato, o seu navegador (Chrome/Edge/Safari) vê que a URL é a mesma que você abriu 10 minutos atrás e carrega o PDF que está salvo na memória temporária dele, em vez de baixar o novo PDF do servidor. É por isso que você continua vendo "advogada" e os espaços em branco!

## Mudanças Propostas (Proposed Changes)
Vamos alterar o código responsável por nomear o arquivo PDF para que ele sempre seja único a cada clique em "Gerar Contrato".

- **Arquivo a ser modificado:** `api_contratos.php`
- **O que será feito:** Modificar a linha de geração do `$filename` para incluir a hora, minuto e segundo. 
  - *De:* `$filename = $operacao_id . '_' . $natureza . '_' . date('Ymd') . '.pdf';`
  - *Para:* `$filename = $operacao_id . '_' . $natureza . '_' . date('Ymd_His') . '.pdf';`
- **Por que faremos isso:** Isso garantirá que toda vez que você clicar em "Gerar Contrato", um arquivo com um nome inédito (ex: `999_EMPRESTIMO_20250429_143022.pdf`) seja criado. Como a URL será nova, o navegador será forçado a baixar o PDF atualizado.

## Passos de Verificação (Verification Steps)
Após aplicarmos o plano e você subir para o servidor online:
1. Acesse os Detalhes da Operação.
2. Clique em "Gerar Contrato".
3. Você verá que um novo documento aparecerá na lista com o horário no final do nome.
4. Ao clicar nele, o PDF abrirá atualizado com o endereço correto ("Rua José Pinto de Almeida...") e o nome da representante.