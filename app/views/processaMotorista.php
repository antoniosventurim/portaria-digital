<?php
session_start();
require_once(__DIR__ . '/../../includes/db.php');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    // Redirecionar para a página de login se não estiver logado
    header('Location: login');
    exit;
}

if (isset($_POST['cadmotorista'])) {
    $motorista = $_POST['motorista'];
    $setor = $_POST['setor'];
    $cpf = $_POST['cpf'];

    // Obtém o ID do usuário logado da variável de sessão
    $idUsuarioLogado = $_SESSION['id']; // Certifique-se de que $_SESSION['usuario'] contém o ID do usuário

    // Processa o formulário e insere a ocorrência no banco de dados
    $queryInserirOcorrencia = "INSERT INTO motoristas (nome, setor, cpf, id_usuario, data_registro) VALUES (:motorista, :setor, :cpf, :id_usuario, NOW())";
    $statement = $pdo->prepare($queryInserirOcorrencia);
    $statement->bindParam(':motorista', $motorista);
    $statement->bindParam(':setor', $setor);
    $statement->bindParam(':cpf', $cpf);
    $statement->bindParam(':id_usuario', $idUsuarioLogado); // Use o ID do usuário logado
    $statement->execute();

    // Redirecionar de volta para a página do painel após a inserção
    header('Location: painel');
    exit;
}

// Se o formulário não foi enviado ou ocorreu algum erro, você pode adicionar tratamento de erro aqui
// ...
?>
