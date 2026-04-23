# Fix File Upload Spec

## Why
Ao registrar uma operação com envio de arquivos (garantias, documentos), a aplicação retorna um erro de JSON na tela do cliente. Isso ocorre devido a um aviso de deprecação (Deprecated) no PHP 8.5 gerado pela função `finfo_close()`, o qual é incluído na resposta que deveria ser apenas um JSON, quebrando o parsing do frontend.

## What Changes
- **upload_arquivos.php**: Remover ou substituir a chamada à função `finfo_close()`. No PHP >= 8.0, o objeto `finfo` é gerenciado automaticamente e seu fechamento manual com `finfo_close` foi deprecado no PHP 8.5. 
- Substituir a abordagem procedural (`finfo_open`, `finfo_file`, `finfo_close`) pela abordagem orientada a objetos (`new finfo()`) ou simplesmente omitir o `finfo_close` para evitar o *Warning*.

## Impact
- Affected specs: Upload de arquivos e anexos de garantias na tela principal de simulação.
- Affected code: `upload_arquivos.php`

## ADDED Requirements
N/A

## MODIFIED Requirements
### Requirement: Upload de Arquivos
A rotina de upload deve continuar verificando corretamente o tipo MIME do arquivo (usando `FILEINFO_MIME_TYPE`) para garantir a segurança, mas não deve emitir nenhum log de deprecação (Deprecated Warning) que possa corromper o retorno JSON consumido pelo frontend.

## REMOVED Requirements
N/A