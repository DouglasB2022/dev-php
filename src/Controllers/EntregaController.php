<?php

namespace App\Controllers;

use App\Database;
use PDO;

class EntregaController
{
    private const TRANSITIONS = [
        'CRIADA'       => ['COLETADA'],
        'COLETADA'     => ['EM_TRANSITO'],
        'EM_TRANSITO'  => ['SAIU_ENTREGA'],
        'SAIU_ENTREGA' => ['ENTREGUE', 'DEVOLVIDA'],
        'ENTREGUE'     => [],
        'DEVOLVIDA'    => [],
    ];

    public static function index(array $params): void
    {
        $db    = Database::connection();
        $where = ['1=1'];
        $binds = [];

        if (!empty($_GET['status'])) {
            $where[] = 'e.status = ?';
            $binds[] = $_GET['status'];
        }

        if (!empty($_GET['id_transportadora'])) {
            $where[] = 'e.id_transportadora = ?';
            $binds[] = $_GET['id_transportadora'];
        }

        if (!empty($_GET['data_inicio'])) {
            $where[] = 'e.data_prazo >= ?';
            $binds[] = $_GET['data_inicio'];
        }

        if (!empty($_GET['data_fim'])) {
            $where[] = 'e.data_prazo <= ?';
            $binds[] = $_GET['data_fim'];
        }

        if (!empty($_GET['cpf_cnpj'])) {
            $where[] = 'd.cpf_cnpj = ?';
            $binds[] = $_GET['cpf_cnpj'];
        }

        $sql = '
            SELECT e.id, e.codigo, e.status, e.data_prazo, e.peso_kg, e.volumes,
                   t.nome_fantasia AS transportadora_nome,
                   d.nome AS destinatario_nome, d.cidade, d.uf
            FROM entregas e
            JOIN transportadoras t ON t.id = e.id_transportadora
            JOIN destinatarios   d ON d.id = e.id_destinatario
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.created_at DESC
        ';

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $rows = $stmt->fetchAll();

        json(array_map(fn($r) => [
            'id'         => (int) $r['id'],
            'codigo'     => $r['codigo'],
            'status'     => $r['status'],
            'data_prazo' => $r['data_prazo'],
            'peso_kg'    => (float) $r['peso_kg'],
            'volumes'    => (int) $r['volumes'],
            'transportadora' => $r['transportadora_nome'],
            'destinatario'   => [
                'nome'   => $r['destinatario_nome'],
                'cidade' => $r['cidade'],
                'uf'     => $r['uf'],
            ],
        ], $rows));
    }

    public static function store(array $params): void
    {
        $data = body();
        $db   = Database::connection();

        foreach (['id_transportadora', 'id_remetente', 'id_destinatario', 'data_prazo', 'peso_kg', 'volumes'] as $campo) {
            if (empty($data[$campo])) {
                json(['erro' => "Campo obrigatório: {$campo}"], 422);
            }
        }

        $stmt = $db->prepare('SELECT id, deleted_at FROM transportadoras WHERE id = ?');
        $stmt->execute([$data['id_transportadora']]);
        $transportadora = $stmt->fetch();

        if (!$transportadora) {
            json(['erro' => 'Transportadora não encontrada'], 404);
        }

        if ($transportadora['deleted_at'] !== null) {
            json(['erro' => 'Transportadora inativa'], 403);
        }

        $stmt = $db->prepare('SELECT id FROM remetentes WHERE id = ?');
        $stmt->execute([$data['id_remetente']]);
        if (!$stmt->fetch()) {
            json(['erro' => 'Remetente não encontrado'], 404);
        }

        $stmt = $db->prepare('SELECT id FROM destinatarios WHERE id = ?');
        $stmt->execute([$data['id_destinatario']]);
        if (!$stmt->fetch()) {
            json(['erro' => 'Destinatário não encontrado'], 404);
        }

        $codigo = self::gerarCodigo($db);

        $stmt = $db->prepare('
            INSERT INTO entregas (codigo, id_transportadora, id_remetente, id_destinatario, status, data_prazo, peso_kg, volumes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $codigo,
            (int) $data['id_transportadora'],
            (int) $data['id_remetente'],
            (int) $data['id_destinatario'],
            'CRIADA',
            $data['data_prazo'],
            (float) $data['peso_kg'],
            (int) $data['volumes'],
        ]);

        $id = $db->lastInsertId();

        $stmt = $db->prepare('INSERT INTO ocorrencias (id_entrega, status, descricao, cidade, uf) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$id, 'CRIADA', 'Entrega cadastrada no sistema', '', '']);

        json(self::findById($db, (int) $id), 201);
    }

    public static function show(array $params): void
    {
        $db  = Database::connection();
        $ref = $params['id'];

        // Aceita ID numérico ou código BRD-
        if (str_starts_with(strtoupper($ref), 'BRD-')) {
            $stmt = $db->prepare('SELECT id FROM entregas WHERE codigo = ?');
            $stmt->execute([strtoupper($ref)]);
            $row = $stmt->fetch();
            if (!$row) {
                json(['erro' => 'Entrega não encontrada'], 404);
            }
            $id = (int) $row['id'];
        } else {
            $id = (int) $ref;
        }

        $entrega = self::findById($db, $id);
        if (!$entrega) {
            json(['erro' => 'Entrega não encontrada'], 404);
        }

        json($entrega);
    }

    public static function updateStatus(array $params): void
    {
        $data = body();
        $db   = Database::connection();

        $stmt = $db->prepare('SELECT * FROM entregas WHERE id = ?');
        $stmt->execute([$params['id']]);
        $entrega = $stmt->fetch();

        if (!$entrega) {
            json(['erro' => 'Entrega não encontrada'], 404);
        }

        $novoStatus = strtoupper(trim($data['status'] ?? ''));

        $proximosPermitidos = self::TRANSITIONS[$entrega['status']] ?? [];

        if (!in_array($novoStatus, $proximosPermitidos, true)) {
            $proximos = implode(' ou ', $proximosPermitidos);
            json([
                'erro'     => 'Transição inválida',
                'mensagem' => $proximos
                    ? "Status '{$entrega['status']}' só pode avançar para: '{$proximos}'."
                    : "Status '{$entrega['status']}' é final. Não é possível alterá-lo.",
            ], 422);
        }

        $descricao = trim($data['descricao'] ?? '');
        $cidade    = trim($data['cidade'] ?? '');
        $uf        = strtoupper(trim($data['uf'] ?? ''));

        $stmt = $db->prepare('UPDATE entregas SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$novoStatus, $params['id']]);

        $stmt = $db->prepare('INSERT INTO ocorrencias (id_entrega, status, descricao, cidade, uf) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$params['id'], $novoStatus, $descricao, $cidade, $uf]);
        $ocorrenciaId = $db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM ocorrencias WHERE id = ?');
        $stmt->execute([$ocorrenciaId]);
        $ocorrencia = $stmt->fetch();

        json([
            'id'     => (int) $params['id'],
            'codigo' => $entrega['codigo'],
            'status' => $novoStatus,
            'ocorrencia_registrada' => [
                'id'        => (int) $ocorrencia['id'],
                'status'    => $ocorrencia['status'],
                'descricao' => $ocorrencia['descricao'],
                'cidade'    => $ocorrencia['cidade'],
                'uf'        => $ocorrencia['uf'],
                'created_at' => $ocorrencia['created_at'],
            ],
        ]);
    }

    private static function gerarCodigo(PDO $db): string
    {
        $ano  = date('Y');
        $stmt = $db->prepare("SELECT COUNT(*) FROM entregas WHERE codigo LIKE ?");
        $stmt->execute(["BRD-{$ano}-%"]);
        $count = (int) $stmt->fetchColumn();
        return sprintf('BRD-%s-%05d', $ano, $count + 1);
    }

    private static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('
            SELECT e.*,
                   t.nome_fantasia AS t_nome,
                   r.nome AS r_nome, r.cidade AS r_cidade, r.uf AS r_uf,
                   d.nome AS d_nome, d.cidade AS d_cidade, d.uf AS d_uf
            FROM entregas e
            JOIN transportadoras t ON t.id = e.id_transportadora
            JOIN remetentes      r ON r.id = e.id_remetente
            JOIN destinatarios   d ON d.id = e.id_destinatario
            WHERE e.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $stmt = $db->prepare('SELECT * FROM ocorrencias WHERE id_entrega = ? ORDER BY created_at ASC');
        $stmt->execute([$id]);
        $ocorrencias = $stmt->fetchAll();

        return [
            'id'         => (int) $row['id'],
            'codigo'     => $row['codigo'],
            'status'     => $row['status'],
            'data_prazo' => $row['data_prazo'],
            'peso_kg'    => (float) $row['peso_kg'],
            'volumes'    => (int) $row['volumes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'transportadora' => [
                'id'            => (int) $row['id_transportadora'],
                'nome_fantasia' => $row['t_nome'],
            ],
            'remetente' => [
                'id'     => (int) $row['id_remetente'],
                'nome'   => $row['r_nome'],
                'cidade' => $row['r_cidade'],
                'uf'     => $row['r_uf'],
            ],
            'destinatario' => [
                'id'     => (int) $row['id_destinatario'],
                'nome'   => $row['d_nome'],
                'cidade' => $row['d_cidade'],
                'uf'     => $row['d_uf'],
            ],
            'rastreamento' => array_map(fn($o) => [
                'id'        => (int) $o['id'],
                'status'    => $o['status'],
                'descricao' => $o['descricao'],
                'cidade'    => $o['cidade'],
                'uf'        => $o['uf'],
                'data'      => $o['created_at'],
            ], $ocorrencias),
        ];
    }
}
