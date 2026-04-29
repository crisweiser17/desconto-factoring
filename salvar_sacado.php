<?php
require_once 'auth_check.php';
require_once 'db_connection.php';
require_once 'functions.php';

$sacadoId = isset($_POST['id']) ? (int)$_POST['id'] : null;
$empresa = trim($_POST['empresa'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$tipoPessoa = isset($_POST['tipo_pessoa']) ? strtoupper(trim($_POST['tipo_pessoa'])) : 'JURIDICA';
if (!in_array($tipoPessoa, ['FISICA', 'JURIDICA'])) {
    $tipoPessoa = 'JURIDICA';
}

// Novos campos de endereço
$cep = trim($_POST['cep'] ?? '');
$logradouro = trim($_POST['logradouro'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$estado = trim($_POST['estado'] ?? '');

// Novos campos adicionados
$porte = trim($_POST['porte'] ?? '');
$representante_nome = trim($_POST['representante_nome'] ?? '');
$representante_cpf = trim($_POST['representante_cpf'] ?? '');
$representante_rg = trim($_POST['representante_rg'] ?? '');
$representante_nacionalidade = trim($_POST['representante_nacionalidade'] ?? 'brasileiro(a)');
$representante_estado_civil = trim($_POST['representante_estado_civil'] ?? '');
$representante_profissao = trim($_POST['representante_profissao'] ?? '');
$representante_endereco = trim($_POST['representante_endereco'] ?? '');

// Cônjuge
$casado = isset($_POST['casado']) ? 1 : 0;
$regime_casamento = trim($_POST['regime_casamento'] ?? '');
$conjuge_nome = trim($_POST['conjuge_nome'] ?? '');
$conjuge_cpf = trim($_POST['conjuge_cpf'] ?? '');
$conjuge_rg = trim($_POST['conjuge_rg'] ?? '');
$conjuge_nacionalidade = trim($_POST['conjuge_nacionalidade'] ?? '');
$conjuge_profissao = trim($_POST['conjuge_profissao'] ?? '');

// Dados bancários
$conta_banco = trim($_POST['conta_banco'] ?? '');
$conta_agencia = trim($_POST['conta_agencia'] ?? '');
$conta_numero = trim($_POST['conta_numero'] ?? '');
$conta_tipo = trim($_POST['conta_tipo'] ?? '');
$conta_pix = trim($_POST['conta_pix'] ?? '');
$conta_titular = trim($_POST['conta_titular'] ?? '');
$conta_documento = trim($_POST['conta_documento'] ?? '');

// Documento principal (CPF/CNPJ)
$documentoPrincipal = preg_replace('/\D/', '', trim($_POST['documento_principal'] ?? ''));

// Sócios
$socios = $_POST['socios'] ?? [];

$message = '';
$messageType = 'danger'; // Padrão para erro

// Validação básica
$nomeDoc = ($tipoPessoa === 'FISICA') ? 'CPF' : 'CNPJ';
if (empty($empresa) || empty($documentoPrincipal)) {
    $nomeEmpresaDoc = ($tipoPessoa === 'FISICA') ? 'Nome' : 'Razão Social';
    $message = "$nomeEmpresaDoc e $nomeDoc são campos obrigatórios.";
    ob_clean();
    header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
    exit;
}

// Validação do Documento Principal
$tamanhoDoc = ($tipoPessoa === 'FISICA') ? 11 : 14;
if (strlen($documentoPrincipal) !== $tamanhoDoc) {
    $message = "$nomeDoc deve ter $tamanhoDoc dígitos.";
    ob_clean();
    header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
    exit;
}

if ($tipoPessoa === 'FISICA' && !validaCPF($documentoPrincipal)) {
    $message = "O CPF informado é inválido.";
    ob_clean();
    header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
    exit;
}

if ($tipoPessoa === 'JURIDICA' && !validaCNPJ($documentoPrincipal)) {
    $message = "O CNPJ informado é inválido.";
    ob_clean();
    header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
    exit;
}

// Validação de Representante CPF
if (!empty($representante_cpf)) {
    $repCpfLimpo = preg_replace('/\D/', '', $representante_cpf);
    if (!validaCPF($repCpfLimpo)) {
        $message = "O CPF do representante é inválido.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    }
}

// Validação de Cônjuge CPF
if ($casado && !empty($conjuge_cpf)) {
    $conjugeCpfLimpo = preg_replace('/\D/', '', $conjuge_cpf);
    if (!validaCPF($conjugeCpfLimpo)) {
        $message = "O CPF do cônjuge é inválido.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    }
}

// Validação da Conta Documento (pode ser CPF ou CNPJ)
if (!empty($conta_documento)) {
    $contaDocLimpo = preg_replace('/\D/', '', $conta_documento);
    if (strlen($contaDocLimpo) === 11 && !validaCPF($contaDocLimpo)) {
        $message = "O CPF do titular da conta é inválido.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    } elseif (strlen($contaDocLimpo) === 14 && !validaCNPJ($contaDocLimpo)) {
        $message = "O CNPJ do titular da conta é inválido.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    } elseif (strlen($contaDocLimpo) !== 11 && strlen($contaDocLimpo) !== 14) {
        $message = "O documento do titular da conta deve ser um CPF (11) ou CNPJ (14) válido.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    }
}

// Validação do e-mail
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Por favor, insira um e-mail válido.";
    ob_clean();
    header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
    exit;
}

// Validação dos sócios
foreach ($socios as $index => $socio) {
    if (empty($socio['nome']) || empty($socio['cpf'])) {
        $message = "Nome e CPF são obrigatórios para todos os sócios.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    }
    
    $cpfLimpo = preg_replace('/\D/', '', $socio['cpf']);
    if (strlen($cpfLimpo) !== 11 || !validaCPF($cpfLimpo)) {
        $message = "CPF do sócio {$socio['nome']} é inválido.";
        ob_clean();
        header("Location: listar_sacados.php?status=error&msg=" . urlencode($message));
        exit;
    }
    
    // Atualizar array com CPF limpo
    $socios[$index]['cpf'] = $cpfLimpo;
}

try {
    $pdo->beginTransaction();
    
    if ($sacadoId) { // Modo de Edição
        $stmt = $pdo->prepare("UPDATE sacados SET
                                empresa = :empresa,
                                email = :email,
                                telefone = :telefone,
                                whatsapp = :whatsapp,
                                tipo_pessoa = :tipo_pessoa,
                                documento_principal = :documento_principal,
                                endereco = :endereco,
                                cep = :cep,
                                logradouro = :logradouro,
                                numero = :numero,
                                complemento = :complemento,
                                bairro = :bairro,
                                cidade = :cidade,
                                estado = :estado,
                                nome = :nome,
                                porte = :porte,
                                representante_nome = :representante_nome,
                                representante_cpf = :representante_cpf,
                                representante_rg = :representante_rg,
                                representante_nacionalidade = :representante_nacionalidade,
                                representante_estado_civil = :representante_estado_civil,
                                representante_profissao = :representante_profissao,
                                representante_endereco = :representante_endereco,
                                casado = :casado,
                                regime_casamento = :regime_casamento,
                                conjuge_nome = :conjuge_nome,
                                conjuge_cpf = :conjuge_cpf,
                                conjuge_rg = :conjuge_rg,
                                conjuge_nacionalidade = :conjuge_nacionalidade,
                                conjuge_profissao = :conjuge_profissao,
                                conta_banco = :conta_banco,
                                conta_agencia = :conta_agencia,
                                conta_numero = :conta_numero,
                                conta_tipo = :conta_tipo,
                                conta_pix = :conta_pix,
                                conta_titular = :conta_titular,
                                conta_documento = :conta_documento
                                WHERE id = :id");
        $stmt->bindParam(':id', $sacadoId, PDO::PARAM_INT);
    } else { // Modo de Adição
        $stmt = $pdo->prepare("INSERT INTO sacados (
            empresa, email, telefone, whatsapp, tipo_pessoa, documento_principal, endereco, 
            cep, logradouro, numero, complemento, bairro, cidade, estado, nome, 
            porte, representante_nome, representante_cpf, representante_rg, 
            representante_nacionalidade, representante_estado_civil, representante_profissao, representante_endereco,
            casado, regime_casamento, conjuge_nome, conjuge_cpf, conjuge_rg, conjuge_nacionalidade, conjuge_profissao,
            conta_banco, conta_agencia, conta_numero, conta_tipo, conta_pix, conta_titular, conta_documento
        ) VALUES (
            :empresa, :email, :telefone, :whatsapp, :tipo_pessoa, :documento_principal, :endereco, 
            :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :nome, 
            :porte, :representante_nome, :representante_cpf, :representante_rg, 
            :representante_nacionalidade, :representante_estado_civil, :representante_profissao, :representante_endereco,
            :casado, :regime_casamento, :conjuge_nome, :conjuge_cpf, :conjuge_rg, :conjuge_nacionalidade, :conjuge_profissao,
            :conta_banco, :conta_agencia, :conta_numero, :conta_tipo, :conta_pix, :conta_titular, :conta_documento
        )");
    }

    // Bind de parâmetros
    $porte_val = !empty($porte) ? $porte : null;
    
    $stmt->bindParam(':empresa', $empresa);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':whatsapp', $whatsapp);
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
    $stmt->bindParam(':porte', $porte_val);
    $stmt->bindParam(':representante_nome', $representante_nome);
    $stmt->bindParam(':representante_cpf', $representante_cpf);
    $stmt->bindParam(':representante_rg', $representante_rg);
    $stmt->bindParam(':representante_nacionalidade', $representante_nacionalidade);
    $stmt->bindParam(':representante_estado_civil', $representante_estado_civil);
    $stmt->bindParam(':representante_profissao', $representante_profissao);
    $stmt->bindParam(':representante_endereco', $representante_endereco);

    // Bind cônjuge
    $stmt->bindParam(':casado', $casado, PDO::PARAM_INT);
    $stmt->bindParam(':regime_casamento', $regime_casamento);
    $stmt->bindParam(':conjuge_nome', $conjuge_nome);
    $stmt->bindParam(':conjuge_cpf', $conjuge_cpf);
    $stmt->bindParam(':conjuge_rg', $conjuge_rg);
    $stmt->bindParam(':conjuge_nacionalidade', $conjuge_nacionalidade);
    $stmt->bindParam(':conjuge_profissao', $conjuge_profissao);
    
    // Bind dados bancários
    $stmt->bindParam(':conta_banco', $conta_banco);
    $stmt->bindParam(':conta_agencia', $conta_agencia);
    $stmt->bindParam(':conta_numero', $conta_numero);
    $stmt->bindParam(':conta_tipo', $conta_tipo);
    $stmt->bindParam(':conta_pix', $conta_pix);
    $stmt->bindParam(':conta_titular', $conta_titular);
    $stmt->bindParam(':conta_documento', $conta_documento);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar dados do sacado: " . implode(" ", $stmt->errorInfo()));
    }

    // Obter ID do sacado (para inserção ou atualização)
    if (!$sacadoId) {
        $sacadoId = $pdo->lastInsertId();
    }

    // Processar sócios
    if ($sacadoId) {
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
            $stmt_delete = $pdo->prepare("DELETE FROM sacados_socios WHERE sacado_id = ? AND id NOT IN ($placeholders)");
            $params = array_merge([$sacadoId], $sociosExistentesIds);
            $stmt_delete->execute($params);
        } else {
            // Se não há sócios existentes, excluir todos
            $stmt_delete = $pdo->prepare("DELETE FROM sacados_socios WHERE sacado_id = ?");
            $stmt_delete->execute([$sacadoId]);
        }
        
        // Inserir ou atualizar sócios
        foreach ($socios as $socio) {
            if (!empty($socio['id'])) {
                // Atualizar sócio existente
                $stmt_socio = $pdo->prepare("UPDATE sacados_socios SET nome = ?, cpf = ? WHERE id = ? AND sacado_id = ?");
                $stmt_socio->execute([
                    trim($socio['nome']),
                    $socio['cpf'],
                    (int)$socio['id'],
                    $sacadoId
                ]);
            } else {
                // Inserir novo sócio
                $stmt_socio = $pdo->prepare("INSERT INTO sacados_socios (sacado_id, nome, cpf) VALUES (?, ?, ?)");
                $stmt_socio->execute([
                    $sacadoId,
                    trim($socio['nome']),
                    $socio['cpf']
                ]);
            }
        }
    }

    $pdo->commit();
    
    $message = "Sacado " . ($sacadoId ? "atualizado" : "adicionado") . " com sucesso!";
    $messageType = "success";

} catch (PDOException $e) {
    $pdo->rollBack();
    
    if ($e->getCode() == '23000') { // Código de erro para violação de UNIQUE
        $nomeDoc = ($tipoPessoa === 'FISICA') ? 'CPF' : 'CNPJ';
        $message = "Erro: O $nomeDoc informado já está cadastrado para outro sacado.";
    } else {
        $message = "Erro no banco de dados ao salvar sacado: " . $e->getMessage();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $message = "Erro ao salvar sacado: " . $e->getMessage();
}

// Redireciona para a lista de sacados com mensagem
ob_clean();
header("Location: listar_sacados.php?status=" . urlencode($messageType) . "&msg=" . urlencode($message));
exit;
?>
