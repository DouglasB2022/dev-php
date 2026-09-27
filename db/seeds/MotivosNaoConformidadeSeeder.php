<?php

declare(strict_types=1);
use Phinx\Seed\AbstractSeed;

class MotivosNaoConformidadeSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->table("motivos_nao_conformidade")->insert([
            ['codigo' => 'AVARIA_PRODUTO','descricao' => 'Produto com avaria ou dano',],
            ['codigo' => 'NAO_ENTREGUE','descricao' => 'Destinatário ausente',],
            ['codigo' => 'ENDERECO_INCORRETO','descricao' => 'Endereço incorreto ou não localizado',],
            ['codigo' => 'RECUSADO','descricao' => 'Recusado pelo destinatário'],
            ['codigo' => 'EXTRAVIO','descricao' => 'Produto extraviado'],
            ['codigo' => 'OUTROS','descricao' => 'Outros motivos'],
        ])->saveData();
    }
}
