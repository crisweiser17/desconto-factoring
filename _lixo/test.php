<?php
require_once 'db_connection.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $empresa = "TESTE";
    $email = "a@a.com";
    $telefone = "111";
    $whatsapp = "111";
    $tipoPessoa = "JURIDICA";
    $documentoPrincipal = "55344469000139";
    $endereco = "";
    $cep = "";
    $logradouro = "";
    $numero = "";
    $complemento = "";
    $bairro = "";
    $cidade = "";
    $estado = "";
    $porte_val = null;
    $representante_nome = "";
    $representante_cpf = "";
    $representante_rg = "";
    $representante_nacionalidade = "";
    $representante_estado_civil = "";
    $representante_profissao = "";
    $representante_endereco = "";
    $casado = 0;
    $regime_casamento = "";
    $conjuge_nome = "";
    $conjuge_cpf = "";
    $conjuge_rg = "";
    $conjuge_nacionalidade = "";
    $conjuge_profissao = "";
    $conta_banco = "";
    $conta_agencia = "";
    $conta_numero = "";
    $conta_tipo = "";
    $conta_pix = "";
    $conta_titular = "";
    $conta_documento = "";

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
    $stmt->bindParam(':nome', $empresa);
    $stmt->bindParam(':porte', $porte_val);
    $stmt->bindParam(':representante_nome', $representante_nome);
    $stmt->bindParam(':representante_cpf', $representante_cpf);
    $stmt->bindParam(':representante_rg', $representante_rg);
    $stmt->bindParam(':representante_nacionalidade', $representante_nacionalidade);
    $stmt->bindParam(':representante_estado_civil', $representante_estado_civil);
    $stmt->bindParam(':representante_profissao', $representante_profissao);
    $stmt->bindParam(':representante_endereco', $representante_endereco);

    $stmt->bindParam(':casado', $casado, PDO::PARAM_INT);
    $stmt->bindParam(':regime_casamento', $regime_casamento);
    $stmt->bindParam(':conjuge_nome', $conjuge_nome);
    $stmt->bindParam(':conjuge_cpf', $conjuge_cpf);
    $stmt->bindParam(':conjuge_rg', $conjuge_rg);
    $stmt->bindParam(':conjuge_nacionalidade', $conjuge_nacionalidade);
    $stmt->bindParam(':conjuge_profissao', $conjuge_profissao);
    
    $stmt->bindParam(':conta_banco', $conta_banco);
    $stmt->bindParam(':conta_agencia', $conta_agencia);
    $stmt->bindParam(':conta_numero', $conta_numero);
    $stmt->bindParam(':conta_tipo', $conta_tipo);
    $stmt->bindParam(':conta_pix', $conta_pix);
    $stmt->bindParam(':conta_titular', $conta_titular);
    $stmt->bindParam(':conta_documento', $conta_documento);

    $stmt->execute();
    echo "OK";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
