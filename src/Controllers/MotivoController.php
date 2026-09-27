<?php

namespace App\Controllers;

use App\Database;


class MotivoController
{
    public static function index(array $params)
    {
        
        $db = Database::connection();
        $sql = '
            SELECT id, codigo, descricao FROM motivos_nao_conformidade WHERE ativo = 1
        ';
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        

        json(array_map(fn($r) => [
            'id' => (int) $r['id'],
            'codigo' =>  $r['codigo'],
            'descricao' => $r['descricao']
        ], $rows));
        
    }
}
