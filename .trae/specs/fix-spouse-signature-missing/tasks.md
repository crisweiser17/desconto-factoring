# Tasks

- [x] Task 1: Alterar campo Estado Civil para select em form_cliente.php
  - [x] SubTask 1.1: Substituir o `<input type="text">` do `representante_estado_civil` por `<select>` com as 5 opções padronizadas.
  - [x] SubTask 1.2: Garantir que o valor salvo seja pré-selecionado corretamente no modo de edição.

- [x] Task 2: Corrigir a definição de `casado` no array do devedor em api_contratos.php
  - [x] SubTask 2.1: Adicionar a propriedade `casado` ao array retornado por `montarParteContrato()`, derivada do `estado_civil` do devedor (deve ser `true` quando "Casado(a)" ou "União Estável").
  - [x] SubTask 2.2: Verificar que a linha `$parteContrato['conjuge_assina'] = $parteContrato['casado'] && $conjuge_assina;` funcione corretamente após a correção.

- [x] Task 3: Adicionar controle de habilitação do campo "Cônjuge vai Assinar?" no modal
  - [x] SubTask 3.1: Em `detalhes_operacao.php`, adicionar lógica JavaScript para ler o estado civil do cliente (devedor) ao abrir o modal de geração de contrato.
  - [x] SubTask 3.2: Se o estado civil NÃO for "Casado(a)" ou "União Estável", desabilitar o select, adicionar classe `bg-light` e forçar valor "Não".
  - [x] SubTask 3.3: Se for casado/união estável, habilitar o select normalmente.

- [x] Task 4: Verificar os templates de mútuo
  - [x] SubTask 4.1: Confirmar que todos os 6 templates (`02a` a `02f`) possuem o bloco `{{#devedor.conjuge_assina}}` com o layout correto.
  - [x] SubTask 4.2: Corrigir qualquer template que esteja com o bloco ausente ou mal formatado.

- [x] Task 5: Testar a geração do contrato
  - [x] SubTask 5.1: Gerar um contrato de mútuo com cliente casado e "Cônjuge vai Assinar? = Sim" e confirmar que o bloco aparece no PDF.
  - [x] SubTask 5.2: Gerar um contrato com cliente solteiro e confirmar que o campo "Cônjuge vai Assinar?" está desabilitado e o bloco NÃO aparece.
  - [x] SubTask 5.3: Verificar que não há erros de sintaxe PHP após as alterações.
