<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

$cedenteId = isset($_POST['id']) ? (int)$_POST['id'] : null;
$empresa = trim($_POST['empresa'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$tipoPessoa = trim($_POST['tipo_pessoa'] ?? 'JURIDICA');

// Novos campos de endereço
$cep = trim($_POST['cep'] ?? '');
$logradouro = trim($_POST['logradouro'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$estado = trim($_POST['estado'] ?? '');

// Documento principal (CPF ou CNPJ)
$documentoPrincipal = preg_replace('/\D/', '', trim($_POST['documento_principal'] ?? ''));

// Sócios
$socios = $_POST['socios'] ?? [];

$message = '';
$messageType = 'danger'; // Padrão para erro

// Validação básica
if (empty($empresa) || empty($documentoPrincipal)) {
    $message = "Razão Social e documento são campos obrigatórios.";
    ob_clean();
    header("Location: listar_cedentes.php?status=error&msg=" . urlencode($message));
    exit;
}

// Validação de email
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Por favor, insira um email válido.";
    ob_clean();
    header("Location: listar_cedentes.php?status=error&msg=" . urlencode($message));
    exit;
}

// Validação do documento
$expectedLength = ($tipoPessoa === 'FISICA') ? 11 : 14;
$documentoTipo = ($tipoPessoa === 'FISICA') ? 'CPF' : 'CNPJ';

if (strlen($documentoPrincipal) !== $expectedLength) {
    $message = "{$documentoTipo} deve ter {$expectedLength} dígitos.";
    ob_clean();
    header("Location: listar_cedentes.php?status=error&msg=" . urlencode($message));
    exit;
}

// Validação dos sócios
foreach ($socios as $index => $socio) {
    if (empty($socio['nome']) || empty($socio['cpf'])) {
        $message = "Nome e CPF são obrigatórios para todos os sócios.";
        ob_clean();
        header("Location: listar_cedentes.php?status=error&msg=" . urlencode($message));
        exit;
    }
    
    $cpfLimpo = preg_replace('/\D/', '', $socio['cpf']);
    if (strlen($cpfLimpo) !== 11) {
        $message = "CPF do sócio deve ter 11 dígitos.";
        ob_clean();
        header("Location: listar_cedentes.php?status=error&msg=" . urlencode($message));
        exit;
    }
    
    // Atualizar array com CPF limpo
    $socios[$index]['cpf'] = $cpfLimpo;
}

try {
    $pdo->beginTransaction();
    
    if ($cedenteId) { // Modo de Edição
        $stmt = $pdo->prepare("UPDATE cedentes SET
                                empresa = :empresa,
                                email = :email,
                                telefone = :telefone,
                                tipo_pessoa = :tipo_pessoa,
                                documento_principal = :documento_principal,
                                endereco = :endereco,
                                cep = :cep,
                                logradouro = :logradouro,
                                numero = :numero,
                                complemento = :complemento,
                                bairro = :bairro,
                                cidade = :cidade,
                                estado = :estado
                                WHERE id = :id");
        $stmt->bindParam(':id', $cedenteId, PDO::PARAM_INT);
    } else { // Modo de Adição
        $stmt = $pdo->prepare("INSERT INTO cedentes (empresa, email, telefone, tipo_pessoa, documento_principal, endereco, cep, logradouro, numero, complemento, bairro, cidade, estado, nome)
                                VALUES (:empresa, :email, :telefone, :tipo_pessoa, :documento_principal, :endereco, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :nome)");
    }

    $stmt->bindParam(':empresa', $empresa);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':tipo_pessoa', $tipoPessoa);
    $stmt->bindParam(':documento_principal', $documentoPrincipal);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':cep', $cep);
    $stmt->bindParam(':logradouro', $logradouro);
    $stmt->bindParam(':numero', $numero);
    $stmt->bindParam(':complemento', $complemento);
    $stmt->bindParam(':bairro', $bairro);
    $stmt->bindParam(':cidade', $cidade);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':nome', $empresa); // nome = empresa

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar dados do cedente: " . implode(" ", $stmt->errorInfo()));
    }

    // Obter ID do cedente (para inserção ou atualização)
    if (!$cedenteId) {
        $cedenteId = $pdo->lastInsertId();
    }

    // Processar sócios
    if ($cedenteId) {
        // Primeiro, marcar todos os sócios existentes para exclusão
        $sociosExistentesIds = [];
        
        foreach ($socios as $socio) {
            if (!empty($socio['id'])) {
                $sociosExistentesIds[] = (int)$socio['id'];
            }
        }
        
        // Excluir sócios que não estão mais na lista
        if (!empty($sociosExistentesIds)) {
            $placeholders = str_repeat('?,', count($sociosExistentesIds) - 1) . '?';
            $stmt_delete = $pdo->prepare("DELETE FROM cedentes_socios WHERE cedente_id = ? AND id NOT IN ($placeholders)");
            $params = array_merge([$cedenteId], $sociosExistentesIds);
            $stmt_delete->execute($params);
        } else {
            // Se não há sócios existentes, excluir todos
            $stmt_delete = $pdo->prepare("DELETE FROM cedentes_socios WHERE cedente_id = ?");
            $stmt_delete->execute([$cedenteId]);
        }
        
        // Inserir ou atualizar sócios
        foreach ($socios as $socio) {
            if (!empty($socio['id'])) {
                // Atualizar sócio existente
                $stmt_socio = $pdo->prepare("UPDATE cedentes_socios SET nome = ?, cpf = ? WHERE id = ? AND cedente_id = ?");
                $stmt_socio->execute([
                    trim($socio['nome']),
                    $socio['cpf'],
                    (int)$socio['id'],
                    $cedenteId
                ]);
            } else {
                // Inserir novo sócio
                $stmt_socio = $pdo->prepare("INSERT INTO cedentes_socios (cedente_id, nome, cpf) VALUES (?, ?, ?)");
                $stmt_socio->execute([
                    $cedenteId,
                    trim($socio['nome']),
                    $socio['cpf']
                ]);
            }
        }
    }

    $pdo->commit();
    
    $message = "Cedente " . ($cedenteId ? "atualizado" : "adicionado") . " com sucesso!";
    $messageType = "success";

} catch (PDOException $e) {
    $pdo->rollBack();
    
    if ($e->getCode() == '23000') { // Código de erro para violação de UNIQUE
        $message = "Erro: O documento informado já está cadastrado para outro cedente.";
    } else {
        $message = "Erro no banco de dados ao salvar cedente: " . $e->getMessage();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $message = "Erro ao salvar cedente: " . $e->getMessage();
}

// Redireciona para a lista de cedentes com mensagem
ob_clean();
header("Location: listar_cedentes.php?status=" . urlencode($messageType) . "&msg=" . urlencode($message));
exit;
?>