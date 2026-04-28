# Plano de Implementação: Exclusão de Contratos e Assinaturas

## Resumo
Adicionar a funcionalidade de excluir arquivos da seção "Contratos e Assinaturas" na página de detalhes da operação. A exclusão removerá o arquivo físico do servidor e o registro do banco de dados, exigindo uma dupla confirmação do usuário através de um modal. O status do contrato na operação será atualizado de acordo com os arquivos restantes.

## Análise do Estado Atual
- A seção "Contratos e Assinaturas" já foi movida para ficar abaixo de "Recebíveis da Operação" e acima de "Documentos Anexados" no arquivo `detalhes_operacao.php`.
- O botão de exclusão (`btn-excluir-contrato`) já está sendo gerado dinamicamente no JavaScript da função `carregarContratos()`.
- O modal de confirmação de exclusão **ainda não foi adicionado** no HTML.
- A lógica JavaScript para abrir o modal e realizar a requisição de exclusão **ainda não foi implementada**.
- O backend `api_contratos.php` **ainda não possui** a ação `delete` nem a função `deleteContrato`.

## Mudanças Propostas

### 1. Atualização do Frontend (`detalhes_operacao.php`)
- **O que**: Adicionar o modal de confirmação de exclusão e a lógica JavaScript correspondente.
- **Por que**: Para exigir dupla confirmação do usuário e enviar a requisição ao backend.
- **Como**:
  - Adicionar o HTML do modal `#modalExcluirContrato` logo após o `#modalGerarContrato` (por volta da linha 1151). O modal terá um texto de confirmação exibindo o nome do arquivo, um botão de cancelar e um botão "Sim, Apagar Arquivo".
  - No bloco de scripts (dentro de `document.addEventListener('DOMContentLoaded', ...)` relacionado a "Lógica de Contratos e Assinaturas"), adicionar event delegation no `listaContratos` para capturar o clique em `.btn-excluir-contrato`.
  - Ao clicar, preencher o `id` e `nome` do documento no modal e exibir o modal.
  - Adicionar um listener no botão de confirmar exclusão dentro do modal que fará um `POST` para `api_contratos.php?action=delete`, passando `operacao_id` e `documento_id`. Em caso de sucesso, esconder o modal e chamar `carregarContratos()` para atualizar a lista.

### 2. Atualização do Backend (`api_contratos.php`)
- **O que**: Implementar a rota de exclusão no backend.
- **Por que**: Para deletar o arquivo fisicamente, apagar o registro e atualizar o status da operação.
- **Como**:
  - No `switch ($action)`, adicionar: `case 'delete': deleteContrato($pdo, $operacao_id); break;`
  - Criar a função `deleteContrato($pdo, $operacao_id)` que:
    1. Obtém `$documento_id = $_POST['documento_id'] ?? null;` e valida.
    2. Busca o arquivo no banco: `SELECT caminho_arquivo FROM operacao_documentos WHERE id = ? AND operacao_id = ?`
    3. Inicia transação: `$pdo->beginTransaction();`
    4. Exclui o registro: `DELETE FROM operacao_documentos WHERE id = ?`
    5. Apaga o arquivo físico: `if (file_exists($caminho)) { unlink($caminho); }`
    6. Atualiza o status da operação (`status_contrato`):
       - Verifica se sobrou algum assinado (`is_assinado = 1`). Se sim, `status = 'assinado'`.
       - Se não, verifica se sobrou algum não assinado. Se sim, `status = 'aguardando_assinatura'`.
       - Se não sobrou nenhum documento, `status = 'pendente'`.
    7. Executa `UPDATE operacoes SET status_contrato = ? WHERE id = ?`
    8. `commit()` e retorna `json_encode(['success' => true])`.

## Verificação
- Recarregar a página e garantir que a seção Contratos e Assinaturas está na ordem correta.
- Clicar no botão de lixeira, ver o modal abrindo.
- Confirmar exclusão, verificar sumiço da linha.
- Checar que o arquivo sumiu da pasta `uploads/`.
- Ver atualização do status_contrato.
