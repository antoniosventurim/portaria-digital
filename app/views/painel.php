<?php
session_start();
require_once(__DIR__ . '/../../includes/db.php');
//print_r($_SESSION);
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    header('Location: ../../');
    exit;
}
$idUsuarioLogado = $_SESSION['id'];
$tipoUsuarioLogado = $_SESSION['tipo_usuario'];
// Consulta SQL para obter o nome do usuário com base no ID armazenado na sessão
$queryNomeUsuario = "SELECT usuario FROM usuarios WHERE id = :idUsuario";
$statementNomeUsuario = $pdo->prepare($queryNomeUsuario);
$statementNomeUsuario->bindParam(':idUsuario', $_SESSION['usuario']);
$statementNomeUsuario->execute();
$nomeDoUsuario = $statementNomeUsuario->fetchColumn();


// Consulta SQL para contar o número total de ocorrências na tabela
$queryTotalOcorrencias = "SELECT COUNT(*) AS total_ocorrencias FROM ocorrencias";
$statementTotalOcorrencias = $pdo->query($queryTotalOcorrencias);
$totalOcorrencias = $statementTotalOcorrencias->fetchColumn();

//busca no banco de dados as ocorrências por ID
function buscarObservacoes($pdo, $idOcorrencia)
{
    $query = "SELECT o.*, u.usuario AS nome_usuario FROM observacoes o
              LEFT JOIN usuarios u ON o.id_usuario = u.id
              WHERE o.id_ocorrencia = :idOcorrencia
              ORDER BY o.data_registro DESC";
    $statement = $pdo->prepare($query);
    $statement->bindParam(':idOcorrencia', $idOcorrencia);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Consulta SQL para selecionar todos os usuários
$queryuser = "SELECT id, nome, usuario, tipo_usuario, status_usuario FROM usuarios";
$statement = $pdo->prepare($queryuser);
$statement->execute();

// Recupera os resultados em um array
$usuarios = $statement->fetchAll(PDO::FETCH_ASSOC);

$queryObservacoes = "SELECT obs.id AS observacao_id,
obs.id_ocorrencia AS id_ocorrencia,
obs.observacao,
obs.data_registro,
u.id AS usuario_id,
u.nome AS nome_usuario,
o.titulo AS titulo_ocorrencia,
o.descricao AS descricao_ocorrencia,
o.local AS local_ocorrencia
FROM observacoes AS obs
INNER JOIN usuarios AS u ON obs.id_usuario = u.id
INNER JOIN ocorrencias AS o ON obs.id_ocorrencia = o.id
ORDER BY obs.data_registro DESC
LIMIT 10;";
$statement = $pdo->prepare($queryObservacoes);
$statement->execute();
$totalObservacoes = $statement->fetchAll(PDO::FETCH_ASSOC);

// Query Retorna os Motoristas
$queryMotoristas = "SELECT id, nome, status_motorista, setor FROM motoristas";
$statement = $pdo->prepare($queryMotoristas);
$statement->execute();
$motoristas = $statement->fetchAll(PDO::FETCH_ASSOC);

//Query Retorna Veículos
$queryVeiculos = "SELECT id, nome, tipo_veiculo, placa, status_veiculo FROM veiculos";
$statement = $pdo->prepare($queryVeiculos);
$statement->execute();
$veiculos = $statement->fetchAll(PDO::FETCH_ASSOC);

//Query Retorna retirada de veículos
$queryRetiradaVeiculos = "SELECT usuarios.nome AS nome_usuario, motoristas.nome AS nome_motorista, veiculos.nome AS nome_veiculo, retirada_veiculos.data_retirada, retirada_veiculos.destino, retirada_veiculos.id_data_devolucao, retirada_veiculos.id, devolucoes.data_devolucao FROM retirada_veiculos INNER JOIN usuarios ON retirada_veiculos.id_usuario = usuarios.id INNER JOIN motoristas ON retirada_veiculos.id_motorista = motoristas.id INNER JOIN veiculos ON retirada_veiculos.id_veiculo = veiculos.id LEFT JOIN devolucoes ON retirada_veiculos.id = devolucoes.id_retirada_veiculo ORDER BY retirada_veiculos.id DESC LIMIT 5;";
$statement = $pdo->prepare($queryRetiradaVeiculos);
$statement->execute();
$retiradaVeiculos = $statement->fetchAll(PDO::FETCH_ASSOC);

//Query Retorna locais
$queryLocais = "SELECT id, nome_local, bloco FROM locais";
$statement = $pdo->prepare($queryLocais);
$statement->execute();
$retornalocais = $statement->fetchAll(PDO::FETCH_ASSOC);

$nomesLocais = array_column($retornalocais, 'nome_local');
$locais = $nomesLocais;
$retornaSearchs = '';
$numeroRegistros = 0;

//Sql Pesquisa Ocorrências
if (!empty($_GET['search'])) {
    // Sua consulta atual com base na pesquisa não vazia
    $dataSearch = $_GET['search'];
    $sqlSearch = "SELECT ocorrencias.*, usuarios.nome AS nome_responsavel
    FROM ocorrencias
    INNER JOIN usuarios ON ocorrencias.id_responsavel = usuarios.id
    LEFT JOIN observacoes ON ocorrencias.id = observacoes.id_ocorrencia
    WHERE ocorrencias.id LIKE '%$dataSearch%'
       OR ocorrencias.titulo LIKE '%$dataSearch%'
       OR ocorrencias.descricao LIKE '%$dataSearch%'
       OR DATE_FORMAT(ocorrencias.data_registro, '%d/%m/%Y %H:%i:%s') LIKE '%$dataSearch%'
       OR ocorrencias.local LIKE '%$dataSearch%'
       OR observacoes.observacao LIKE '%$dataSearch%';";
    $statement = $pdo->prepare($sqlSearch);
    $statement->execute();
    $retornaSearchs = $statement->fetchAll(PDO::FETCH_ASSOC);
    $numeroRegistros = $statement->rowCount();
    $msgsqlsearch = 'Registros Encontrados: ';
} else {
    // Consulta para mostrar as 10 últimas ocorrências quando a pesquisa estiver vazia
    $sqlSearch = "SELECT ocorrencias.*, usuarios.nome AS nome_responsavel
    FROM ocorrencias
    INNER JOIN usuarios ON ocorrencias.id_responsavel = usuarios.id
    ORDER BY ocorrencias.data_registro DESC
    LIMIT 10;";
    $statement = $pdo->prepare($sqlSearch);
    $statement->execute();
    $retornaSearchs = $statement->fetchAll(PDO::FETCH_ASSOC);
    $numeroRegistros = $statement->rowCount();
    $msgsqlsearch = 'Estes São os ';
}
$statusmotorista = "";

//Query Retorna Eventos
$queryEventos = "SELECT
e.nome_evento,
e.solicitante,
l.nome_local,
e.data_inicio,
e.data_fim,
e.dia_semana,
e.qtd_participantes,
u.nome as nome_usuario
FROM
eventos e
JOIN
usuarios u
ON
e.usuario_id = u.id
JOIN
locais l
ON
e.local_reservado = l.id
WHERE
(e.data_inicio > CURDATE() OR (e.data_inicio = CURDATE() AND TIME(e.data_inicio) >= TIME(NOW())))
AND e.data_inicio <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY
e.data_inicio;";
$statement = $pdo->prepare($queryEventos);
$statement->execute();
$eventos = $statement->fetchAll(PDO::FETCH_ASSOC);




//var_dump($idUsuarioLogado);
//var_dump($_SESSION['usuario']);
//var_dump($_SESSION['tipo_usuario']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="../../assets/css/pesquisa.css">
    <link rel="shortcut icon" href="../../assets/images/fav.png">
    <title>Painel</title>
</head>

<body>
    <div id="erroMensagem" class="mensagem-erro">Não foi possível Realizar a Retirada de Chave.</div>
    <div id="sucMensagem" class="mensagem-suc">Retirada de Chave Realizada Com Sucesso!.</div>
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <div class="main">
        <main class="d-flex flex-nowrap side-bar">
            <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark menu-left">
                <a href="https://projetopei.dev.br/app/views/painel.php" class="d-flex logo align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                    <span>PORTARIA DIGITAL</span>
                </a>
                <hr>
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="https://projetopei.dev.br/app/views/painel.php" class="nav-link text-white" aria-current="page">
                            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-house-fill" viewBox="0 0 16 16">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z" />
                                <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Z" />
                            </svg>
                            <use xlink:href="#hom"></use>
                            </i>
                            Inicio
                        </a>
                    </li>
                    <li>
                        <div class="li-usuarios">
                            <?php if ($tipoUsuarioLogado === 1) {
                                echo '<a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#collapseusuarios" aria-expanded="false" aria-controls="collapseExample">
                            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                            </svg>
                                Usuários <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-down svg-bottomchaves" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                    <path fill-rule="evenodd" d="M1.646 2.646a.5.5 0 0 1 .708 0L8 8.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                </svg>
                            </a>';
                            } ?>
                        </div>
                        <div class="d-dowm-chaves">
                            <ul>
                                <div class="collapse" id="collapseusuarios">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="painel2" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#adduser">
                                    <i class="bi bi-person-add">
                                        <use xlink:href="#hom"></use>
                                    </i>
                                    Novo Usuário
                                </a>';
                                    } ?>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseusuarios">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="usuarios_cadastrados.php" class="nav-link text-white r-chaves">
                                        <i class="bi bi-people-fill"></i>
                                        Usuários Registrados
                                    </a>';
                                    } ?>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="li-usuarios">
                            <?php if ($tipoUsuarioLogado === 1 || $tipoUsuarioLogado === 0) {
                                echo '<a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#collapseocorrencias" aria-expanded="false" aria-controls="collapseExample">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-journal-bookmark-fill" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M6 1h6v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8V1z" />
                                    <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                                    <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                                </svg>
                                Ocorrências <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-down svg-bottomchaves" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                    <path fill-rule="evenodd" d="M1.646 2.646a.5.5 0 0 1 .708 0L8 8.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                </svg>
                            </a>';
                            } ?>
                        </div>
                        <div class="d-dowm-chaves">
                            <ul>
                                <div class="collapse" id="collapseocorrencias">

                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#addocorrenciaa">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-plus" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5z" />
                                            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                                            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                                        </svg>
                                        Nova Ocorrência
                                    </a>

                            </ul>
                            <ul>
                                <div class="collapse" id="collapseocorrencias">
                                    <a href="painel2" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#ultimasobservacoes">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-check" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M10.854 6.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 8.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                                            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                                        </svg>
                                        <use xlink:href="#hom"></use>
                                        </i>
                                        Ultimas Observações
                                    </a>

                            </ul>

                            <ul>
                                <div class="collapse" id="collapseocorrencias">
                                    <a href="painel2" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#filtrarocorrencias">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                                            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2h-11z" />
                                        </svg>
                                        Filtrar Ocorrências
                                    </a>
                            </ul>
                        </div>
                    </li>

                    <!-- BTN ACESSO DE PESSOAS -->
                    <li>
                        <div class="li-usuarios">
                            <?php if ($tipoUsuarioLogado === 1 || $tipoUsuarioLogado === 0) {
                                echo '<a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#collapseacessodepessoas" aria-expanded="false" aria-controls="collapseExample">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-buildings" viewBox="0 0 16 16">
                                    <path d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z" />
                                    <path d="M2 11h1v1H2v-1Zm2 0h1v1H4v-1Zm-2 2h1v1H2v-1Zm2 0h1v1H4v-1Zm4-4h1v1H8V9Zm2 0h1v1h-1V9Zm-2 2h1v1H8v-1Zm2 0h1v1h-1v-1Zm2-2h1v1h-1V9Zm0 2h1v1h-1v-1ZM8 7h1v1H8V7Zm2 0h1v1h-1V7Zm2 0h1v1h-1V7ZM8 5h1v1H8V5Zm2 0h1v1h-1V5Zm2 0h1v1h-1V5Zm0-2h1v1h-1V3Z" />
                                </svg>
                                Acesso de Pessoas <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-down svg-bottomchaves" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                    <path fill-rule="evenodd" d="M1.646 2.646a.5.5 0 0 1 .708 0L8 8.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                </svg>
                            </a>';
                            } ?>
                        </div>
                        <div class="d-dowm-chaves">
                            <ul>
                                <div class="collapse" id="collapseacessodepessoas">

                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#addacessos">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-plus" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5z" />
                                            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                                            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                                        </svg>
                                        Novo Acesso
                                    </a>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseacessodepessoas">
                                    <a href="" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#filtraacessos">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                                            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2h-11z" />
                                        </svg>
                                        <use xlink:href="#hom"></use>
                                        </i>
                                        Filtrar Acessos
                                    </a>

                            </ul>
                        </div>
                    </li>
                    <li>
                        <?php if ($tipoUsuarioLogado === 1 || $tipoUsuarioLogado === 0) {
                            echo '<a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z" />
                                <path d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z" />
                            </svg>
                            Registros de Veículos <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-down svg-bottomchaves" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                <path fill-rule="evenodd" d="M1.646 2.646a.5.5 0 0 1 .708 0L8 8.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                            </svg>
                        </a>';
                        } ?>
                        <div class="d-dowm-chaves">
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#adicionaveiculo">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Adicionar Veículo
                                    </a>';
                                    } ?>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#adicionamotorista">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Adicionar Motorista
                                    </a>';
                                    } ?>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#motoristascad">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Motoristas Cadastrados
                                    </a>';
                                    } ?>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#veiculoscad">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Veiculos Cadastrados
                                    </a>';
                                    } ?>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#retiradaveiculo">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Retirada de Veiculo
                                    </a>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#devolucaochave">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Devolução
                                    </a>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseExample">
                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#filtraretiradas">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z" />
                                        </svg>
                                        Filtrar Retiradas
                                    </a>
                            </ul>
                        </div>

                    </li>
                    <li>

                    </li>
                    <li>
                        <?php if ($tipoUsuarioLogado === 1) {
                            echo '<a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#collapselocais" aria-expanded="false" aria-controls="collapseExample">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-map" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M15.817.113A.5.5 0 0 1 16 .5v14a.5.5 0 0 1-.402.49l-5 1a.502.502 0 0 1-.196 0L5.5 15.01l-4.902.98A.5.5 0 0 1 0 15.5v-14a.5.5 0 0 1 .402-.49l5-1a.5.5 0 0 1 .196 0L10.5.99l4.902-.98a.5.5 0 0 1 .415.103zM10 1.91l-4-.8v12.98l4 .8V1.91zm1 12.98 4-.8V1.11l-4 .8v12.98zm-6-.8V1.11l-4 .8v12.98l4-.8z"/>
                                </svg>
                                Locais <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-down svg-bottomchaves" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                    <path fill-rule="evenodd" d="M1.646 2.646a.5.5 0 0 1 .708 0L8 8.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                </svg>
                            </a>';
                        } ?>
                        <div class="d-dowm-chaves">
                            <ul>
                                <div class="collapse" id="collapselocais">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#adicionalocal">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        Adicionar Novo Local
                                        </a>';
                                    } ?>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapselocais">
                                    <?php if ($tipoUsuarioLogado === 1) {
                                        echo '<a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#locaisregistrados">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-compass" viewBox="0 0 16 16">
                                        <path d="M8 16.016a7.5 7.5 0 0 0 1.962-14.74A1 1 0 0 0 9 0H7a1 1 0 0 0-.962 1.276A7.5 7.5 0 0 0 8 16.016zm6.5-7.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z"/>
                                     <path d="m6.94 7.44 4.95-2.83-2.83 4.95-4.949 2.83 2.828-4.95z"/>
                                    </svg>
                                        Locais Registrados
                                        </a>';
                                    } ?>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <a href="#" class="nav-link text-white" data-bs-toggle="collapse" data-bs-target="#collapseeventos" aria-expanded="false" aria-controls="collapseExample">
                            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-calendar-event" viewBox="0 0 16 16">
                                <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z" />
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                            </svg>
                            Eventos <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-down svg-bottomchaves" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1.646 6.646a.5.5 0 0 1 .708 0L8 12.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                                <path fill-rule="evenodd" d="M1.646 2.646a.5.5 0 0 1 .708 0L8 8.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" />
                            </svg>
                        </a>
                        <div class="d-dowm-chaves">
                            <ul>
                                <div class="collapse" id="collapseeventos">
                                    <?php if ($tipoUsuarioLogado === 1 || $tipoUsuarioLogado === 2) {
                                        echo  '<a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#adicionaevento">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-plus" viewBox="0 0 16 16">
                                            <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z" />
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                        </svg>
                                        Adicionar Novo Evento
                                    </a>';
                                    } ?>
                                </div>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseeventos">
                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#eventosregistrados">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar2-check" viewBox="0 0 16 16">
                                            <path d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1H2z" />
                                            <path d="M2.5 4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5V4z" />
                                        </svg>
                                        Eventos da Semana
                                    </a>
                                </div>
                            </ul>
                            <ul>
                                <div class="collapse" id="collapseeventos">
                                    <a href="#" class="nav-link text-white r-chaves" data-bs-toggle="modal" data-bs-target="#filtrareventos">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                                            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2h-11z" />
                                        </svg>
                                        Filtrar Eventos
                                    </a>
                                </DIV>
                            </ul>
                        </div>
                    </li>
                </ul>
                <hr>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../../assets/images/fav.png" alt="" width="32" height="32" class="rounded-circle me-2">
                        <strong class="user-logado"><?php echo $_SESSION['usuario']; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                        <li class="d-flex ml-5 align-items-center logout-user"><a class="dropdown-item" href="logout.php"><svg class="svg-logo" xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z" />
                                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z" />
                                </svg>Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="geral">
                <div class="text-center">
                    <h1></h1>
                </div>
                <?php if ($tipoUsuarioLogado != 2) {  ?>
                    <div>
                        <div class="table-info">
                            <div class="box-pesquisa">
                                <div class="titulo-box-pesquisa">
                                    <h1>Buscar Registros</h1>
                                </div>
                                <div class="input-group mb-3 box-search">
                                    <input type="text" class="form-control input-search" id="pesquisar" placeholder="Pesquisar" aria-label="Pesquisar" aria-describedby="button-addon2">
                                    <button class="btn btn-primary" type="button" onclick="searchData()">Pesquisar</button>
                                </div>
                            </div>
                            <div class="tabela-principal">
                                <div>
                                    <table class="table col-xs-7 table-bordered table-striped table-condensed table-fixed text-center">
                                        <thead>
                                            <tr>
                                                <th scope="col">TÍTULO</th>
                                                <th scope="col">LOCAL</th>
                                                <th scope="col">RESPONSÁVEL</th>
                                                <th scope="col">DATA REGISTRO</th>
                                                <th scope="col">RELATÓRIO COMPLETA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($retornaSearchs !== null && is_array($retornaSearchs)) : ?>
                                                <?php foreach ($retornaSearchs as $retornaSearch) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                                    <tr>
                                                        <td><?php echo substr($retornaSearch['titulo'], 0, 20); ?></td>
                                                        <!-- Limitar a 100 caracteres -->
                                                        <td><?php echo substr($retornaSearch['local'], 0, 20); ?></td>
                                                        <td><?php echo $retornaSearch['nome_responsavel']; ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($retornaSearch['data_registro'])); ?></td> <!-- Formata data e hora para dd/mm/aaaa H:i -->
                                                        <td>
                                                            <a class="btn-descricao" href="#" data-bs-toggle="modal" data-bs-target="#descricao_completa_<?php echo $retornaSearch['id']; ?>">
                                                                Visualizar Relatório
                                                                <ion-icon name="paper-plane-outline"></ion-icon></a>
                                                        </td>
                                                    </tr>
                                                    <!-- Modal DESCRIÇÃO COMPLETA -->
                                                    <div class="modal fade modaldescription" id="descricao_completa_<?php echo $retornaSearch['id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">(ID - <?php echo $retornaSearch['id']; ?>) <b>Descrição Completa</b></h1>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body bodydescription">
                                                                    <?php echo $retornaSearch['descricao']; ?>

                                                                    <hr>
                                                                    <div><b><?php echo $retornaSearch['nome_responsavel']; ?></b></div>
                                                                    <div><b><?php echo date('d/m/Y H:i', strtotime($retornaSearch['data_registro'])); ?></b></div>
                                                                </div>
                                                                <div class="observacoes">
                                                                    <p><strong>Observações Adicionais:</strong></p>
                                                                    <!-- Aqui você pode exibir as observações relacionadas a esta ocorrência -->
                                                                    <div>
                                                                        <?php
                                                                        $idOcorrencia = $retornaSearch['id'];
                                                                        $observacoes = buscarObservacoes($pdo, $idOcorrencia); // Função para buscar observações no banco de dados
                                                                        foreach ($observacoes as $observacao) {
                                                                            echo "<div class='textoobservacao'><strong>" . $observacao['nome_usuario'] . "</strong>: " . $observacao['observacao'] . "</div>";
                                                                        }
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <!-- Botão para abrir o Modal de Adicionar Observação -->
                                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adicionarObservacao_<?php echo $retornaSearch['id']; ?>">
                                                                        Adicionar Observação
                                                                    </button>
                                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- MODAL PARA ADICIONAR OBSERVAÇÕES -->
                                                    <div class="modal fade modaldescription" id="adicionarObservacao_<?php echo $retornaSearch['id']; ?>" tabindex="-1" aria-labelledby="adicionarObservacaoLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="adicionarObservacaoLabel"><b>Adicionar Observação</b></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <!-- Formulário para adicionar a observação -->
                                                                    <form action="processaObservacao.php" method="POST">
                                                                        <input type="hidden" name="ocorrencia_id" value="<?php echo $retornaSearch['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="observacao" class="form-label"><b>Observação:</b></label>
                                                                            <textarea class="form-control" id="observacao" name="observacao" rows="4" required></textarea>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="submit" id="cadastra_observacao" name="cadastra_observacao" class="btn btn-primary">Salvar Observação</button>
                                                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach ?>
                                            <?php else : ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="table-footer">
                                <div class="totalfooter">
                                    <h1>TOTAL DE OCORRÊNCIAS: <?php echo $totalOcorrencias ?></h1>
                                </div>
                                <div class="paginacao">
                                    <div class="pagination text-white">
                                        <h4><?php echo $msgsqlsearch . "Últimos " . $numeroRegistros . " Registros" ?></h4>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal ADICIONA NOVA OCORRENCIA -->
                            <div class="modal fade" id="addocorrenciaa" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Adicionar Nova Ocorrência</b></h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <!-- COPO DO MODAL/FORMULARIO ADICIONAR NOVA OCORRENCIA -->
                                        <div class="modal-body">
                                            <form action="processaOcorrencia.php" method="POST">
                                                <div class="col-md-6 mb-3">
                                                    <label for="titulo" class="form-label"><b>Título</b></label>
                                                    <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Informe um pequeno título da ocorrência" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="local" class="form-label"><b>Escolha um local:</b></label>
                                                            <input class="form-control" list="localOptions" id="local" name="local" placeholder="Digite para pesquisar..." required>
                                                            <datalist id="localOptions">
                                                                <?php foreach ($locais as $local) : ?>
                                                                    <option value="<?php echo $local; ?>">
                                                                    <?php endforeach; ?>
                                                            </datalist>
                                                            <span id="localValidationMessage"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="descricao" class="form-label"><b>Relatório Da Ocorrência</b></label>
                                                    <textarea class="form-control" id="descricao" name="descricao" rows="3" maxlength="1000" placeholder="Relate a Ocorrência" required></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="cadastraOcorrencia" id="cadastraOcorrencia" class="btn btn-primary">Cadastrar</button>
                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                } else {
                    echo '<img src="../../assets/images/Events-pana.png" alt="Events-pana" class="imagem-eventos">';
                } ?>
            </div>
        </main>
    </div>
    </div>
    <!-- Modal ADICIONA NOVO USUARIO -->
    <div class="modal fade" id="adduser" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Adicionar Novo Usuário</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL/FORMULARIO ADICIONAR NOVO USUÁRIO -->
                <div class="modal-body">
                    <div class="card-body">
                        <form method="POST" action="processaUsuario.php">
                            <div class="mb-3">
                                <label for="nome" class="form-label"><b>Nome Completo</b></label>
                                <input type="text" class="form-control" id="nome" name="nome" placeholder="Digite Seu nome Completo" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="usuario" class="form-label"><b>Usuário</b></label>
                                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="EX: antonio.venturim" required>
                                        <span id="usuarioValidationMessage"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="senha" class="form-label"><b>Senha</b></label>
                                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Crie uma senha" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_usuario"><b>Tipo de Usuario</b></label>
                                <select class="form-select w-25" name="tipo_usuario" id="tipo_usuario" required>
                                    <option value="" disabled selected>Selecione</option>
                                    <option value="0">Usuario</option>
                                    <option value="1">Administrador</option>
                                    <option value="2">Eventos</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="adduser" id="adduser" class="btn btn-primary">Cadastrar</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </form>
                    </div>
                    <div id="erroCadastroUsuario" class="alert alert-danger" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal TODOS OS USUARIO -->
    <div class="modal fade" id="alluserss" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Usuarios Registrados</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL TODOS USUÁRIO -->
                <div class="modal-body text-center">
                    <table class="table-usuarios table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th scope="col">NOME COMPLETO</th>
                                <th scope="col">USUARIO</th>
                                <th scope="col">PERFIL</th>
                                <th scope="col">STATUS</th>
                                <th scope="col">ACAO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                <tr>
                                    <td><?php echo $usuario['nome']; ?></td>
                                    <td><?php echo $usuario['usuario']; ?></td>
                                    <td><?php echo ($usuario['tipo_usuario'] == 1 ? 'Administrador' : 'Usuário Normal') ?></td>
                                    <td><?php echo ($usuario['status_usuario'] == 1 ? 'Ativo' : 'Desativado') ?></td>
                                    <td>
                                        <?php if ($usuario['usuario'] !== 'admin') : ?>
                                            <form class="d-flex " action="processaHabilitacaoUser.php" method="POST">
                                                <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                <select class="form-select" name="novo_status" id="novo_status">
                                                    <option value="" selected disabled>Selecione</option>
                                                    <?php if ($usuario['status_usuario'] == 1) : ?>
                                                        <!-- Usuário está ativo, exibir opção de desativar -->
                                                        <option value="0">Desativar</option>
                                                    <?php else : ?>
                                                        <!-- Usuário está desativado, exibir opção de ativar -->
                                                        <option value="1">Ativar</option>
                                                    <?php endif; ?>
                                                </select>
                                                <button type="submit" name="alterastatususer" id="alterastatususer" class="btn btn-primary btn-alterastatus">Salvar</button>
                                            </form>
                                        <?php else : ?>
                                            <!-- Exibir uma mensagem ou outra indicação aqui para o usuário 'admin' -->
                                            <p>Administrador Geral</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal FILTRAR OCORRÊNCIAS -->
    <div class="modal fade" id="filtrarocorrencias" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xlx modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Filtrar Ocorrências</b></h1>
                    <div class="form-group exporta-eventos">
                        <button type="submit" id="exportar-dados-ocorrencias">Exportar Dados.csv</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL FILTRAR OCORRÊNCIAS -->
                <div class="modal-body">
                    <form class="text-center" id="filtroFormOcorrencias" action="">
                        <div class="filtro-evento">
                            <div class="form-group">
                                <label for="data-inicio"><b>Data Inicio:</b></label>
                                <input type="date" class="form-control" id="data_inicio_ocorrencia">
                            </div>
                            <div class="form-group data-fim-ocorrencia">
                                <label for="data-fim"><b>Data Fim:</b></label>
                                <input type="date" class="form-control" id="data_fim_ocorrencia">
                            </div>
                            <div class="form-group col-md-3 filtro-titulo-ocorrencia">
                                <label for="exampleInput"><b>Título:</b></label>
                                <input type="text" class="form-control" id="busca_titulo_ocorrencia" placeholder="Filtrar por título...">
                            </div>
                            <div class="form-group col-md-3 filtro-nome-responsavel-ocorrencia">
                                <label for="exampleInput"><b>Responsável:</b></label>
                                <input type="text" class="form-control" id="busca_nome_responsavel_ocorrencia" placeholder="Filtrar por responsável...">
                            </div>
                        </div>
                        <hr>
                        <div class="resultado-filtro-eventos">
                            <div id="result"></div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btn-filtrar">Filtrar</button>
                </div>
                </form>
                <!-- fim data filtro -->
            </div>
        </div>
    </div>

    <!-- Modal Novo Acesso -->
    <div class="modal fade" id="addacessos" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Cadastrar Novo Acesso</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL NOVO ACESSO -->
                <div class="modal-body">
                    <div class="container">
                        <form action="processaNovoAcesso.php" method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="nome"><b>Nome</b></label>
                                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Informe o Nome" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="destino"><b>Destino</b></label>
                                    <input type="text" class="form-control" list="localOptions" id="local" name="local" placeholder="Destino..." required>
                                    <datalist id="localOptions">
                                        <?php foreach ($locais as $local) : ?>
                                            <option value="<?php echo $local; ?>">
                                            <?php endforeach; ?>
                                    </datalist>
                                    <span id="localValidationMessage"></span>
                                </div>
                                <div class="col-md-3">
                                    <label for="documento"><b>Documento</b></label>
                                    <input type="text" class="form-control" id="documento" name="documento" placeholder="Aguardando doc...">
                                </div>
                            </div>
                            <div class="row mt-3">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo"><b>Tipo</b></label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="" selected disabled>Selecione</option>
                                    <option value="0">Aluno</option>
                                    <option value="1">Professor</option>
                                    <option value="2">Visitante</option>
                                </select>
                            </div>
                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary" id="cadastrar_acesso" name="cadastrar_acesso">Cadastrar</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- fim data filtro -->
            </div>
        </div>
    </div>

    <!-- Modal FILTRAR ACESSOS -->
    <div class="modal fade" id="filtraacessos" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xlx modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Filtrar Acessos</b></h1>
                    <div class="form-group exporta-eventos">
                        <button type="submit" id="exportar-dados-acessos">Exportar Dados.csv</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL FILTRAR ACESSOS-->
                <div class="modal-body">
                    <form id="filtroFormAcessos" action="filtra_acessos.php">
                        <div class="filtro-evento">
                            <div class="form-group col-md-12 filtro-nome-pessoa">
                                <label for="exampleInput"><b>Nome da Pessoa</b></label>
                                <input type="text" class="form-control" id="busca_nome_pessoa" name="busca_nome_pessoa" placeholder="Aguardando...">
                            </div>
                        </div>
                        <hr>
                        <div class="resultado-filtro-acessos">
                            <div id="resultadoAcessos"></div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
                </form>
                <!-- fim data filtro -->
            </div>
        </div>
    </div>
    <!-- Modal RETIRADA DE VEICULO -->
    <div class="modal fade" id="retiradaveiculo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-x">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Retirada de Veiculo</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL RETIRADA DE VEICULO-->
                <div class="modal-body">
                    <form method="POST" action="processaRetiradaVeiculo.php">
                        <div class="mb-3">
                            <label for="usuarioResponsavel" class="form-label"><b>Responsavel pelo Veiculo:</b></label>
                            <select class="form-select" id="usuarioResponsavel" name="usuarioResponsavel" required>
                                <option value="" disabled selected>Selecione o Responsavel</option>
                                <?php foreach ($motoristas as $motorista) : ?>
                                    <?php if ($motorista['status_motorista'] != 0) : ?>
                                        <option value="<?php echo $motorista['id']; ?>"><?php echo $motorista['nome']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <span id="motoristaValidationMessage"></span>
                        </div>
                        <div class="mb-3">
                            <label for="tipoVeiculo" class="form-label"><b>Selecione o Veículo:</b></label>
                            <select class="form-select" id="nomeVeiculo" name="nomeVeiculo" required>
                                <option value="" disabled selected>Selecione o tipo de veículo</option>
                                <?php foreach ($veiculos as $veiculo) : ?>
                                    <?php if ($veiculo['status_veiculo'] != 0) : ?>
                                        <option value="<?php echo $veiculo['id']; ?>"><?php echo $veiculo['nome']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="dataRetirada" class="form-label"><b>Data e Hora da Retirada:</b></label>
                            <input type="datetime-local" class="form-control" id="dataRetirada" name="dataRetirada" required>
                        </div>
                        <div class="mb-3">
                            <label for="destino" class="form-label"><b>Informe o Destino do Veículo:</b></label>
                            <textarea class="form-control" id="destino" name="destino" rows="4" placeholder="Informe o destino do veículo" required></textarea>
                        </div>
                        <input type="hidden" name="statusRetirada" value="ativa">
                        <div class="modal-footer">
                            <button type="submit" name="cadretiradaveiculo" id="cadretiradaveiculo" class="btn btn-primary">Cadastrar</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ADICIONA VEICULO -->
    <div class="modal fade" id="adicionaveiculo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-x">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Adicionar Novo Veículo</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL ADICIONA VEICULO-->
                <div class="modal-body">
                    <form method="POST" action="processaVeiculo.php">
                        <div class="mb-3">
                            <label for="tipo_veiculo" class="form-label"><b>Tipo de Veículo</b></label>
                            <select class="form-select custom-width-motorista" id="tipo_veiculo" name="tipo_veiculo" required>
                                <option value="" disabled selected>Selecione</option>
                                <option value="Carro">Carro</option>
                                <option value="Moto">Moto</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="nome" class="form-label"><b>Nome do Veículo</b></label>
                            <input type="text" class="form-control custom-width-motorista" id="nome" name="nome" placeholder="Insira o nome do veículo" required>
                        </div>

                        <div class="mb-3">
                            <label for="placa" class="form-label"><b>Placa</b></label>
                            <input type="text" class="form-control custom-width-motorista" id="placa" name="placa" placeholder="Insira a placa" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="cadveiculo" id="cadveiculo" class="btn btn-primary">Cadastrar</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal VEICULOS CADASTRADOS -->
    <div class="modal fade" id="veiculoscad" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Veiculos Cadastrados</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL VEICULO CADASTRADOS -->
                <div class="modal-body text-center">
                    <table class="table table-bordered table-striped table-condensed table-fixed text-center">
                        <thead>
                            <tr>
                                <th scope="col">VEÍCULO</th>
                                <th scope="col">NOME</th>
                                <th scope="col">PLACA</th>
                                <th scope="col">STATUS</th>
                                <th scope="col">AÇÃO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($veiculos as $veiculo) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                <tr>
                                    <td><?php echo $veiculo['tipo_veiculo']; ?></td>
                                    <td><?php echo $veiculo['nome']; ?></td>
                                    <td><?php echo $veiculo['placa']; ?></td>
                                    <td>
                                        <?php
                                        $status = $veiculo['status_veiculo'];
                                        if ($status == 1) {
                                            echo 'Ativo';
                                        } else {
                                            echo 'Inativo';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $idVeiculo = $veiculo['id'];
                                        $botaoLabel = ($status == 1) ? 'Desativar' : 'Ativar';
                                        $botaoClass = ($status == 1) ? 'btn-danger desativar-veiculo' : 'btn-primary ativar-veiculo';
                                        ?>
                                        <button class="btn <?php echo $botaoClass; ?>" data-id="<?php echo $idVeiculo; ?>">
                                            <?php echo $botaoLabel; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary"><a href="#" class="r-chaves text-white" data-bs-toggle="modal" data-bs-target="#adicionaveiculo">
                                Cadastrar Veiculo
                            </a></button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal MOTORISTAS CADASTRADOS -->
    <div class="modal fade" id="motoristascad" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Motoristas Cadastrados</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL MOTORISTAS CADASTRADOS -->
                <div class="modal-body text-center">
                    <table class="table table-bordered table-striped table-condensed table-fixed text-center">
                        <thead>
                            <tr>
                                <th scope="col">NOME</th>
                                <th scope="col">SETOR</th>
                                <th scope="col">STATUS</th>
                                <th scope="col">AÇÃO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($motoristas as $motorista) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                <tr>
                                    <td><?php echo $motorista['nome']; ?></td>
                                    <td><?php echo $motorista['setor']; ?></td>
                                    <td>
                                        <?php
                                        $status = $motorista['status_motorista'];
                                        if ($status == 1) {
                                            echo 'Ativo';
                                        } else {
                                            echo 'Inativo';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $idMotorista = $motorista['id'];
                                        $botaoLabel = ($status == 1) ? 'Desativar' : 'Ativar';
                                        $botaoClass = ($status == 1) ? 'btn-danger desativar-motorista' : 'btn-primary ativar-motorista';
                                        ?>
                                        <button class="btn <?php echo $botaoClass; ?>" data-id="<?php echo $idMotorista; ?>">
                                            <?php echo $botaoLabel; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary"><a href="#" class="r-chaves text-white" data-bs-toggle="modal" data-bs-target="#adicionamotorista">
                                Cadastrar Motorista
                            </a></button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ADICIONA MOTORISTA -->
    <div class="modal fade" id="adicionamotorista" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-x">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Adicionar Novo Motorista</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL ADICIONA MOTORISTA-->
                <div class="modal-body">
                    <form method="POST" action="processaMotorista.php">
                        <div class="mb-3">
                            <label for="motorista" class="form-label"><b>Nome do Motorista:</b></label>
                            <input type="text" class="form-control custom-width-motorista" id="motorista" name="motorista" placeholder="Insira o nome do motorista" required>
                        </div>
                        <div class="mb-3">
                            <label for="setor" class="form-label"><b>Setor do Motorista:</b></label>
                            <input type="text" class="form-control custom-width-motorista" id="setor" name="setor" placeholder="Insira o setor do motorista" required>
                        </div>

                        <div class="mb-3">
                            <label for="cpf" class="form-label"><b>CPF do Motorista:</b></label>
                            <input type="text" class="form-control custom-width-motorista" id="cpf" name="cpf" placeholder="Insira o CPF do motorista" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="cadmotorista" id="cadmotorista" class="btn btn-primary">Cadastrar</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal DEVOLUCAO -->
    <div class="modal fade" id="devolucaochave" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Devolução de Chaves</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL DEVOLUCAO-->
                <div class="modal-body ">
                    <div class="container">
                        <form action="processaDevolucao.php" method="post">
                            <table class="table table-bordered table-striped table-condensed table-fixed text-center">
                                <thead>
                                    <tr>
                                        <th>NOME DO MOTORISTA</th>
                                        <th>NOME DO VEÍCULO</th>
                                        <th>DESTINO</th>
                                        <th>DATA DE RETIRADA</th>
                                        <th>DATA DE DEVOLUÇÃO</th>
                                        <th>REGISTRAR DEVOLUÇÃO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($retiradaVeiculos as $retiradaVeiculo) : ?>
                                        <tr>
                                            <td><?php echo $retiradaVeiculo['nome_motorista']; ?></td>
                                            <td><?php echo $retiradaVeiculo['nome_veiculo']; ?></td>
                                            <td><?php echo $retiradaVeiculo['destino']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($retiradaVeiculo['data_retirada'])); ?></td>
                                            <td><?php
                                                if ($retiradaVeiculo['id_data_devolucao'] !== null) {
                                                    $dataDevolucao = date('d/m/Y H:i', strtotime($retiradaVeiculo['data_devolucao']));
                                                } else {
                                                    $dataDevolucao = ($retiradaVeiculo['id_data_devolucao'] !== null) ?
                                                        date('d/m/Y H:i', strtotime($retiradaVeiculo['data_devolucao'])) :
                                                        'Sem Data Devolução';
                                                }
                                                echo $dataDevolucao;
                                                ?></td>
                                            <td>
                                                <?php if (empty($retiradaVeiculo['data_devolucao'])) : ?>
                                                    <form action="processaDevolucao.php" method="post">
                                                        <div class="btn-registra-devolucao">
                                                            <input type="hidden" name="idRetiradaVeiculo" value="<?php echo $retiradaVeiculo['id']; ?>">
                                                            <input type="datetime-local" class="form-control" name="dataDevolucao" required>

                                                        </div>
                                                        <input type="hidden" name="statusDevolucao" value="devolvido">
                                                    </form>
                                                <?php else : ?>
                                                    <div class="btn-devolucao-realizada">
                                                        <p><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-check2-square" viewBox="0 0 16 16">
                                                                <path d="M3 14.5A1.5 1.5 0 0 1 1.5 13V3A1.5 1.5 0 0 1 3 1.5h8a.5.5 0 0 1 0 1H3a.5.5 0 0 0-.5.5v10a.5.5 0 0 0 .5.5h10a.5.5 0 0 0 .5-.5V8a.5.5 0 0 1 1 0v5a1.5 1.5 0 0 1-1.5 1.5H3z" />
                                                                <path d="m8.354 10.354 7-7a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z" />
                                                            </svg>Devolução Realizada</p>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <div class="info-footer-tabela">
                                        <p><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z" />
                                            </svg> Atenção, ao registrar data de devolução, uma vez que registrada não será possível alterar.</p>
                                    </div>
                                </tfoot>
                            </table>

                        </form>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" name="devolucao" id="devolucao">Registrar Devolução</button>
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal FILTRAR RETIRADAS -->
    <div class="modal fade" id="filtraretiradas" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xlx modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Filtrar Retiradas</b></h1>
                    <div class="form-group exporta-eventos">
                        <button type="submit" id="exportar-dados-retiradas">Exportar Dados.csv</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL FILTRAR RETIRADAS-->
                <div class="modal-body">
                    <form id="filtroFormRetiradas" action="filtra_retiradas.php">
                        <div class="filtro-evento">
                            <div class="form-group col-md-12 filtro-retiradas">
                                <label for="exampleInput"><b>Nome do Motorista</b></label>
                                <input type="text" class="form-control" id="busca_nome_motorista" name="busca_nome_motorista" placeholder="Aguardando...">
                            </div>
                        </div>
                        <hr>
                        <div class="resultado-filtro-acessos">
                            <div id="resultadoRetiradas"></div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
                </form>
                <!-- fim data filtro -->
            </div>
        </div>
    </div>

    <div class="modal fade" id="ultimasobservacoes" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Ultimas Observações Registradas</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL ULTIMAS OBSERVACOES-->
                <div class="modal-body text-center">
                    <table class="table-usuarios table table-bordered table-hover table-bordered table-striped table-condensed text-center">
                        <thead>
                            <tr>
                                <th scope="col">OBSERVAÇÃO</th>
                                <th scope="col">TÍTULO OCORRÊNCIA</th>
                                <th scope="col">DATA REGISTRO</th>
                                <th scope="col">USUÁRIO REGISTROU</th>
                                <th scope="col">ID OCORRÊNCIA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($totalObservacoes as $observacao) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                <tr>
                                    <td><?php echo substr($observacao['observacao'], 0, 20); ?></td>
                                    <td><?php echo $observacao['titulo_ocorrencia']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($observacao['data_registro'])); ?></td>
                                    <td><?php echo $observacao['nome_usuario']; ?></td>
                                    <td><?php echo $observacao['id_ocorrencia']; ?></td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ADICIONA NOVO LOCAL -->
    <div class="modal fade" id="adicionalocal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-x">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Adicionar Novo Local Para Ocorrências</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL ADICIONA NOVO LOCAL-->
                <div class="modal-body">
                    <form method="POST" action="processaLocal.php">
                        <div class="mb-3">
                            <label for="local" class="form-label"><b>Nome do Local:</b></label>
                            <input type="text" class="form-control custom-width-motorista" list="localOptions" id="local" name="local" placeholder="Informe o novo local" required>
                            <datalist id="localOptions">
                                <?php foreach ($locais as $local) : ?>
                                    <option value="<?php echo $local; ?>">
                                    <?php endforeach; ?>
                            </datalist>
                            <span id="localValidationMessage"></span>
                        </div>
                        <div class="mb-3">
                            <label for="bloco" class="form-label"><b>Bloco</b></label>
                            <input type="text" class="form-control custom-width-motorista" id="bloco" name="bloco" placeholder="EX: Bloco 1" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <button type="submit" id="cadlocal" name="cadlocal" class="btn btn-primary">Cadastrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal LOCAIS REGISTRADOS -->
    <div class="modal fade" id="locaisregistrados" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Locais Registrados</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL LOCAIS REGISTRADOS-->
                <div class="modal-body text-center">
                    <div class="search-locais">
                        <input class="form-control" type="text" id="barraDePesquisa" placeholder="Pesquisar...">
                    </div>
                    <table class="table-usuarios table table-bordered table-hover table-bordered table-striped table-condensed text-center">
                        <thead>
                            <tr>
                                <th scope="col">Local</th>
                                <th scope="col">Bloco</th>
                            </tr>
                        </thead>
                        <tbody id="resultadosPesquisa">
                            <?php foreach ($retornalocais as $retornalocal) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                <tr>
                                    <td><?php echo $retornalocal['nome_local']; ?></td>
                                    <td><?php echo $retornalocal['bloco']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal NOVO EVENTO -->
    <div class="modal fade" id="adicionaevento" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Adicionar Novo Evento</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL NOVO EVENTO-->
                <div class="modal-body text-center">
                    <div class="card-body body-novoevento">
                        <form method="POST" action="processaEventos.php" class="row g-3">
                            <div class="col-md-12">
                                <label for="evento" class="form-label"><b>Nome do Evento</b></label>
                                <input type="text" class="form-control" id="nomeEvento" name="nomeEvento" placeholder="Informe o Nome do Evento">
                            </div>
                            <div class="col-md-3">
                                <label for="solicitante" class="form-label"><b>Solicitante</b></label>
                                <input type="text" class="form-control" id="solicitante" name="solicitante" placeholder="Nome do Solicitante" required>
                            </div>
                            <div class="col-md-3">
                                <label for="diaSemana" class="form-label"><b>Local Reservado</b></label>
                                <select id="localReservado" name="localReservado" class="form-select" required>
                                    <option selected disabled>Selecione</option>
                                    <?php foreach ($retornalocais as $retornalocal) : ?>
                                        <option value="<?php echo $retornalocal['id']; ?>">
                                            <?php echo $retornalocal['nome_local'] . ' (' . $retornalocal['bloco'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="diaSemana" class="form-label"><b>Dia da Semana</b></label>
                                <select id="diaSemana" name="diaSemana" class="form-select" required>
                                    <option selected>Selecione</option>
                                    <option value="Segunda-Feira">Segunda-Feira</option>
                                    <option value="Terca-Feira">Terca-Feira</option>
                                    <option value="Quarta-Feira">Quarta-Feira</option>
                                    <option value="Quinta-Feira">Quinta-Feira</option>
                                    <option value="Sexta-Feira">Sexta-Feira</option>
                                    <option value="Sabado">Sabado</option>
                                    <option value="Domingo">Domingo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="participantes" class="form-label"><b>Quantidade de Participantes</b></label>
                                <input type="number" class="form-control" id="qtdParticipantes" name="qtdParticipantes" placeholder="Informe a quantidade">
                            </div>
                            <div class="col-md-3">
                                <label for="dataHora" class="form-label"><b>Data e Hora Inicio</b></label>
                                <input type="datetime-local" class="form-control" id="dataHoraEventoinicio" name="dataHoraEventoinicio" required>
                            </div>
                            <div class="col-md-3">
                                <label for="dataHora" class="form-label"><b>Data e Hora Fim</b></label>
                                <input type="datetime-local" class="form-control" id="dataHoraEventofim" name="dataHoraEventofim" required>
                            </div>

                            <hr>
                            <div class="modal-footer">
                                <button type="submit" name="cadastraEvento" id="cadastraEvento" class="btn btn-primary">Cadastrar</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- Modal EVENTOS REGISTRADOS -->
    <div class="modal fade" id="eventosregistrados" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xlx modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Eventos nos próximos 7 dias</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO MODAL EVENTOS REGISTRADOS-->
                <div class="modal-body text-center">
                    <table class="table-eventos table table-bordered table-hover table-bordered table-striped table-condensed text-center">
                        <thead class="thead-eventos">
                            <tr>
                                <th scope="col">NOME DO EVENTO</th>
                                <th scope="col">SOLICITANTE</th>
                                <th scope="col">LOCAL RESERVADO</th>
                                <th scope="col">DIA DA SEMANA</th>
                                <th scope="col">DATA INICIO</th>
                                <th scope="col">DATA FIM</th>
                                <th scope="col">PARTICIPANTES</th>
                            </tr>
                        </thead>
                        <tbody id="resultadosPesquisa">
                            <?php foreach ($eventos as $evento) : ?> <!-- Loop para que enquanto exista registro ele mostre na tela -->
                                <tr>
                                    <td><?php echo $evento['nome_evento']; ?></td>
                                    <td><?php echo $evento['solicitante']; ?></td>
                                    <td><?php echo $evento['nome_local']; ?></td>
                                    <td><?php echo $evento['dia_semana']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($evento['data_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($evento['data_fim'])); ?></td>
                                    <td><?php echo $evento['qtd_participantes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal FILTRAR EVENTOS REGISTRADOS -->
    <div class="modal fade" id="filtrareventos" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xlx modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"><b>Filtrar Eventos</b></h1>
                    <div class="form-group exporta-eventos">
                        <button type="submit" id="exportar-dados-eventos">Exportar Dados.csv</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- CORPO DO FILTRAR MODAL EVENTOS REGISTRADOS-->
                <div class="modal-body">
                    <form id="filtroForm" action="filtra_eventos.php">
                        <div class="filtro-evento">
                            <div class="form-group">
                                <label for="data-inicio"><b>Data de Início:</b></label>
                                <input type="date" class="form-control" id="data_inicio">
                            </div>
                            <div class="form-group data-fim-eventos">
                                <label for="data-fim"><b>Data de Término:</b></label>
                                <input type="date" class="form-control" id="data_fim">
                            </div>
                            <div class="form-group col-md-6 filtro-nome-evento">
                                <label for="exampleInput"><b>Filtrar pelo Nome do Evento:</b></label>
                                <input type="text" class="form-control" id="busca_nome_evento" placeholder="Aguardando...">
                            </div>
                        </div>
                        <hr>
                        <div class="resultado-filtro-eventos">
                            <div id="resultado"></div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btn-filtrar">Filtrar</button>
                </div>
                </form>
                <!-- fim data filtro -->
            </div>
        </div>


        <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
        <script>
            new window.VLibras.Widget('https://vlibras.gov.br/app');
        </script>
        <script src="../../assets/scripts/scripts.js"></script>
        <script src="../../assets/scripts/jQuery.min.js"></script>
        <script>
            // Chamar as funções quando a página estiver carregada
            $(document).ready(function() {
                verificaNomeUsuario();
                verificaLocal();
            });
        </script>

        <script>
            var search = document.getElementById('pesquisar');

            search.addEventListener("keydown", function(event) {
                if (event.key === "Enter") {
                    searchData();
                }
            });

            function searchData() {
                window.location = 'painel.php?search=' + search.value;

            }
        </script>

        <script>
            // Obtém a data e hora atual
            const dataHoraAtual = new Date();
            // Formata a data e hora no formato esperado (AAAA-MM-DDTHH:MM)
            const formatoDataHora = `${dataHoraAtual.getFullYear()}-${(dataHoraAtual.getMonth() + 1).toString().padStart(2, '0')}-${dataHoraAtual.getDate().toString().padStart(2, '0')}T${dataHoraAtual.getHours().toString().padStart(2, '0')}:${dataHoraAtual.getMinutes().toString().padStart(2, '0')}`;
            // Define o valor do input como a data e hora formatada
            document.getElementById("dataRetirada").value = formatoDataHora;
        </script>
        <script>
            if (window.location.href.indexOf('?erro=1') !== -1) {
                // A URL contém "?erro=1", mostre a mensagem de erro
                document.getElementById('erroMensagem').style.display = 'block';

                // Adicione um atraso de 5 segundos (5000 milissegundos) para ocultar a mensagem
                setTimeout(function() {
                    document.getElementById('erroMensagem').style.display = 'none';

                    // Remova "?erro=1" da URL usando pushState
                    const newURL = window.location.href.replace('?erro=1', '');
                    window.history.pushState({}, document.title, newURL);
                }, 6000);
            } else if (window.location.href.indexOf('?sucesso=1') !== -1) {
                // A URL contém "?sucesso=1", mostre a mensagem de sucesso
                document.getElementById('sucMensagem').style.display = 'block';

                // Adicione um atraso de 5 segundos (5000 milissegundos) para ocultar a mensagem
                setTimeout(function() {
                    document.getElementById('sucMensagem').style.display = 'none';

                    // Remova "?sucesso=1" da URL usando pushState
                    const newURL = window.location.href.replace('?sucesso=1', '');
                    window.history.pushState({}, document.title, newURL);
                }, 5000);
            }
        </script>
        <script>
            $(document).ready(function() {
                // Quando o usuário digita na barra de pesquisa
                $('#barraDePesquisa').keyup(function() {
                    // Obter o valor digitado na barra de pesquisa
                    var termoDePesquisa = $(this).val().toLowerCase();

                    // Percorrer cada linha da tabela e ocultar/mostrar com base na pesquisa
                    $('.table-usuarios tbody tr').each(function() {
                        var linha = $(this);
                        var nomeLocal = linha.find('td:eq(0)').text().toLowerCase();
                        var bloco = linha.find('td:eq(1)').text().toLowerCase();

                        if (nomeLocal.indexOf(termoDePesquisa) !== -1 || bloco.indexOf(termoDePesquisa) !== -1) {
                            linha.show();
                        } else {
                            linha.hide();
                        }
                    });
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                $('.ativar-motorista, .desativar-motorista').click(function() {
                    var idMotorista = $(this).data('id');
                    var novoStatus = $(this).hasClass('ativar-motorista') ? 1 : 0; // Verifique a classe do botão

                    // Armazene a referência ao botão atual para uso posterior
                    var botao = $(this);

                    // Envie uma solicitação AJAX para o servidor para alterar o status do motorista
                    $.ajax({
                        url: 'altera_status_motorista.php', // Substitua pelo URL correto do seu script de servidor
                        method: 'POST',
                        data: {
                            id_motorista: idMotorista,
                            novo_status: novoStatus
                        },
                        success: function(response) {
                            // Atualize a tabela ou faça qualquer outra coisa necessária após a alteração de status
                            if (novoStatus === 1) {
                                alert('Motorista ativado com sucesso.');
                            } else {
                                alert('Motorista desativado com sucesso.');
                            }

                            // Recarregue a página
                            location.reload();
                        },
                        error: function() {
                            alert('Ocorreu um erro ao alterar o status do motorista.');
                        }
                    });
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                $('.ativar-veiculo, .desativar-veiculo').click(function() {
                    var idVeiculo = $(this).data('id');
                    var novoStatus = $(this).hasClass('ativar-veiculo') ? 1 : 0; // Verifique a classe do botão

                    // Armazene a referência ao botão atual para uso posterior
                    var botao = $(this);

                    // Envie uma solicitação AJAX para o servidor para alterar o status do motorista
                    $.ajax({
                        url: 'altera_status_veiculo.php', // Substitua pelo URL correto do seu script de servidor
                        method: 'POST',
                        data: {
                            id_veiculo: idVeiculo,
                            novo_status: novoStatus
                        },
                        success: function(response) {
                            // Atualize a tabela ou faça qualquer outra coisa necessária após a alteração de status
                            if (novoStatus === 1) {
                                alert('Veículo ativado com sucesso.');
                            } else {
                                alert('Veículo desativado com sucesso.');
                            }

                            // Recarregue a página
                            location.reload();
                        },
                        error: function() {
                            alert('Ocorreu um erro ao alterar o status do veículo.');
                        }
                    });
                });
            });
        </script>

        <!-- SCRIPT FILTRO DE OCORRÊNCIAS -->
        <script>
            // Submeter o formulário dentro da modal
            $('#filtroFormOcorrencias').submit(function(event) {
                event.preventDefault(); // Impede o envio do formulário padrão

                // Obtém as datas de início e término do formulário
                var dataInicio = $('#data_inicio_ocorrencia').val();
                var dataFim = $('#data_fim_ocorrencia').val();
                var tituloOcorrencia = $('#busca_titulo_ocorrencia').val();
                var nomeResponsavel = $('#busca_nome_responsavel_ocorrencia').val();

                // Envia uma requisição AJAX para o servidor para filtrar ocorrencias
                $.ajax({
                    url: 'filtra_ocorrencias.php',
                    method: 'POST',
                    data: {
                        data_inicio: dataInicio,
                        data_fim: dataFim,
                        titulo_ocorrencia: tituloOcorrencia,
                        nome_responsavel: nomeResponsavel

                    },
                    success: function(response) {
                        // Atualiza a div de resultado com os eventos filtrados
                        $('#result').html(response);
                    },
                    error: function() {
                        alert('Ocorreu um erro ao buscar Ocorrências.');
                    }
                });
            });
        </script>

        <!-- SCRIPT FILTRO DE ACESSOS -->
        <script>
            // Submeter o formulário dentro da modal
            $('#filtroFormAcessos').submit(function(event) {
                event.preventDefault(); // Impede o envio do formulário padrão

                var nomePessoa = $('#busca_nome_pessoa').val();

                // Envia uma requisição AJAX para o servidor para filtrar ocorrencias
                $.ajax({
                    url: 'filtra_acessos.php',
                    method: 'POST',
                    data: {
                        busca_nome_pessoa: nomePessoa

                    },
                    success: function(response) {
                        // Atualiza a div de resultado com os eventos filtrados
                        $('#resultadoAcessos').html(response);
                    },
                    error: function() {
                        alert('Ocorreu um erro ao buscar Acessos.');
                    }
                });
            });
        </script>

        <!-- SCRIPT FILTRO RETIRADAS DE VEÍCULOS -->
        <script>
            // Submeter o formulário dentro da modal
            $('#filtroFormRetiradas').submit(function(event) {
                event.preventDefault(); // Impede o envio do formulário padrão

                var nomeMotorista = $('#busca_nome_motorista').val();

                // Envia uma requisição AJAX para o servidor para filtrar motorista
                $.ajax({
                    url: 'filtra_retiradas.php',
                    method: 'POST',
                    data: {
                        busca_nome_motorista: nomeMotorista

                    },
                    success: function(response) {
                        // Atualiza a div de resultado com os eventos filtrados
                        $('#resultadoRetiradas').html(response);
                    },
                    error: function() {
                        alert('Ocorreu um erro ao buscar Acessos.');
                    }
                });
            });
        </script>

        <!-- SCRIPT FILTRO DE EVENTOS -->
        <script>
            // Submeter o formulário dentro da modal
            $('#filtroForm').submit(function(event) {
                event.preventDefault(); // Impede o envio do formulário padrão

                // Obtém as datas de início e término do formulário
                var dataInicio = $('#data_inicio').val();
                var dataFim = $('#data_fim').val();
                var nomeEvento = $('#busca_nome_evento').val();

                // Envia uma requisição AJAX para o servidor para filtrar eventos
                $.ajax({
                    url: 'filtra_eventos.php', // Substitua pelo URL correto do seu script de servidor
                    method: 'POST',
                    data: {
                        data_inicio: dataInicio,
                        data_fim: dataFim,
                        busca_nome_evento: nomeEvento
                    },
                    success: function(response) {
                        // Atualiza a div de resultado com os eventos filtrados
                        $('#resultado').html(response);
                    },
                    error: function() {
                        alert('Ocorreu um erro ao buscar eventos.');
                    }
                });
            });
        </script>

        <!-- EXPORTAR DADOS CVS TABELA FILTRADA OCORRÊNCIAS -->
        <script>
            document.getElementById('exportar-dados-ocorrencias').addEventListener('click', function() {
                var table = document.querySelector('.tabelafiltradaOcorrencias');

                if (!table) {
                    alert('Nenhuma tabela encontrada para exportar.');
                    return;
                }

                var csvData = [];

                // Obtenha as linhas da tabela
                var rows = table.querySelectorAll('tr');

                // Obtenha os nomes das colunas (linha de cabeçalho)
                var headerRow = rows[0];
                var headers = headerRow.querySelectorAll('th');
                var headerData = Array.from(headers).map(function(th) {
                    return th.innerText;
                });
                var utf16 = csvData.map(function(line) {
                    return line + '\n';
                });

                var blob = new Blob([new TextEncoder().encode(utf16)], {
                    type: 'text/csv;charset=UTF-16LE;'
                });

                // Adicione os nomes das colunas ao array CSV
                csvData.push(headerData.join(','));

                // Percorra as linhas de dados
                for (var i = 1; i < rows.length; i++) {
                    var rowData = [];
                    var cells = rows[i].querySelectorAll('td');
                    cells.forEach(function(cell) {
                        rowData.push(cell.innerText);
                    });
                    csvData.push(rowData.join(','));
                }
                csvData.unshift('\uFEFF' + csvData[0]);
                // Crie um blob de dados CSV
                var csvContent = 'data:text/csv;charset=utf-8,' + csvData.join('\n');

                // Crie um elemento 'a' para o link de download
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement('a');
                link.href = encodedUri;
                link.target = '_blank';
                link.download = 'dados.csv';

                // Clique automaticamente no link para iniciar o download
                link.click();
            });
        </script>

        <!-- EXPORTAR DADOS CVS TABELA FILTRADA ACESSOS PESSOAS -->
        <script>
            document.getElementById('exportar-dados-acessos').addEventListener('click', function() {
                var table = document.querySelector('.tabelafiltradaAcessos');

                if (!table) {
                    alert('Nenhuma tabela encontrada para exportar.');
                    return;
                }

                var csvData = [];

                // Obtenha as linhas da tabela
                var rows = table.querySelectorAll('tr');

                // Obtenha os nomes das colunas (linha de cabeçalho)
                var headerRow = rows[0];
                var headers = headerRow.querySelectorAll('th');
                var headerData = Array.from(headers).map(function(th) {
                    return th.innerText;
                });
                var utf16 = csvData.map(function(line) {
                    return line + '\n';
                });

                var blob = new Blob([new TextEncoder().encode(utf16)], {
                    type: 'text/csv;charset=UTF-16LE;'
                });

                // Adicione os nomes das colunas ao array CSV
                csvData.push(headerData.join(','));

                // Percorra as linhas de dados
                for (var i = 1; i < rows.length; i++) {
                    var rowData = [];
                    var cells = rows[i].querySelectorAll('td');
                    cells.forEach(function(cell) {
                        rowData.push(cell.innerText);
                    });
                    csvData.push(rowData.join(','));
                }
                csvData.unshift('\uFEFF' + csvData[0]);
                // Crie um blob de dados CSV
                var csvContent = 'data:text/csv;charset=utf-8,' + csvData.join('\n');

                // Crie um elemento 'a' para o link de download
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement('a');
                link.href = encodedUri;
                link.target = '_blank';
                link.download = 'dados.csv';

                // Clique automaticamente no link para iniciar o download
                link.click();
            });
        </script>


        <!-- EXPORTAR DADOS CVS TABELA FILTRADA EVENTOS -->
        <script>
            document.getElementById('exportar-dados-eventos').addEventListener('click', function() {
                var table = document.querySelector('.tabelafiltradaEventos');

                if (!table) {
                    alert('Nenhuma tabela encontrada para exportar.');
                    return;
                }

                var csvData = [];

                // Obtenha as linhas da tabela
                var rows = table.querySelectorAll('tr');

                // Obtenha os nomes das colunas (linha de cabeçalho)
                var headerRow = rows[0];
                var headers = headerRow.querySelectorAll('th');
                var headerData = Array.from(headers).map(function(th) {
                    return th.innerText;
                });
                var utf16 = csvData.map(function(line) {
                    return line + '\n';
                });

                var blob = new Blob([new TextEncoder().encode(utf16)], {
                    type: 'text/csv;charset=UTF-16LE;'
                });

                // Adicione os nomes das colunas ao array CSV
                csvData.push(headerData.join(','));

                // Percorra as linhas de dados
                for (var i = 1; i < rows.length; i++) {
                    var rowData = [];
                    var cells = rows[i].querySelectorAll('td');
                    cells.forEach(function(cell) {
                        rowData.push(cell.innerText);
                    });
                    csvData.push(rowData.join(','));
                }
                csvData.unshift('\uFEFF' + csvData[0]);
                // Crie um blob de dados CSV
                var csvContent = 'data:text/csv;charset=utf-8,' + csvData.join('\n');

                // Crie um elemento 'a' para o link de download
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement('a');
                link.href = encodedUri;
                link.target = '_blank';
                link.download = 'dados.csv';

                // Clique automaticamente no link para iniciar o download
                link.click();
            });
        </script>
</body>

</html>