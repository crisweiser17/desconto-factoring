# Plano: Permitir CPF 00000000000 e CNPJ 00000000000000 (Medida Provisória)

## Contexto

O usuário precisa cadastrar clientes com CPF `00000000000` ou CNPJ `00000000000000` como valor provisório. Atualmente:

1. As funções `validaCPF()` e `validaCNPJ()` rejeitam documentos com todos os dígitos iguais (linha 104 e 120 de `functions.php`)
2. O banco de dados tem constraints `UNIQUE` em `cpf`, `cnpj` e `documento_principal` na tabela `clientes`
3. O `salvar_cliente.php` valida documentos antes de inserir

## O que precisa mudar

### 1. `functions.php` — Funções de validação

As funções `validaCPF()` e `validaCNPJ()` devem **aceitar** documentos com todos os dígitos zeros.

**Alteração em `validaCPF()` (linha ~104):**
- Remover ou condicionar a verificação `preg_match('/(\d)\1{10}/', $cpf)`
- O CPF `00000000000` deve passar na validação

**Alteração em `validaCNPJ()` (linha ~120):**
- Remover ou condicionar a verificação `preg_match('/(\d)\1{13}/', $cnpj)`
- O CNPJ `00000000000000` deve passar na validação

> **Nota:** Manter a validação dos dígitos verificadores para documentos que NÃO sejam todos zeros. Apenas os documentos `00000000000` e `00000000000000` devem ser aceitos sem verificação de dígitos verificadores.

### 2. `installer2.php` — Schema do banco

Remover as constraints `UNIQUE` de `cpf`, `cnpj` e `documento_principal` na tabela `clientes`, permitindo múltiplos cadastros com o mesmo documento zero.

**Alterações no DDL:**
- Remover: `UNIQUE KEY \`cpf\` (\`cpf\`)`
- Remover: `UNIQUE KEY \`cnpj\` (\`cnpj\`)`
- Remover: `UNIQUE KEY \`documento_principal\` (\`documento_principal\`)`
- Manter apenas os índices comuns (`idx_nome`, `idx_empresa`)

### 3. `salvar_cliente.php` — Tratamento de erro de duplicidade

Quando ocorrer erro `23000` (violação de constraint), verificar se o documento é `00000000000` ou `00000000000000`. Se for, permitir o cadastro mesmo com documento duplicado.

**Abordagem:** Como as constraints UNIQUE serão removidas, o erro de duplicidade não ocorrerá mais para documentos zero. O tratamento de `PDOException 23000` pode ser mantido para outros casos.

## Arquivos a modificar

| Arquivo | Alteração |
|---------|-----------|
| `functions.php` | Permitir CPF/CNPJ com todos os dígitos zeros nas funções de validação |
| `installer2.php` | Remover constraints UNIQUE de cpf, cnpj e documento_principal |

## Implementação detalhada

### `functions.php` — `validaCPF()`

```php
function validaCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);
    if (strlen($cpf) != 11) return false;
    
    // Permitir CPF 00000000000 (medida provisória)
    if ($cpf === '00000000000') return true;
    
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}
```

### `functions.php` — `validaCNPJ()`

```php
function validaCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
    if (strlen($cnpj) != 14) return false;
    
    // Permitir CNPJ 00000000000000 (medida provisória)
    if ($cnpj === '00000000000000') return true;
    
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    // ... resto da validação
}
```

### `installer2.php` — Tabela `clientes`

```sql
CREATE TABLE `clientes` (
  -- ... colunas ...
  PRIMARY KEY (`id`),
  -- REMOVER: UNIQUE KEY `cpf` (`cpf`),
  -- REMOVER: UNIQUE KEY `cnpj` (`cnpj`),
  -- REMOVER: UNIQUE KEY `documento_principal` (`documento_principal`),
  KEY `idx_nome` (`nome`),
  KEY `idx_empresa` (`empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Testes

1. Cadastrar cliente com CNPJ `00000000000000` → deve permitir
2. Cadastrar segundo cliente com CNPJ `00000000000000` → deve permitir (sem erro de duplicidade)
3. Cadastrar cliente com CPF `00000000000` → deve permitir
4. Cadastrar cliente com CNPJ válido normal → deve continuar validando normalmente
5. Cadastrar cliente com CNPJ inválido (ex: `11111111111111`) → deve rejeitar
