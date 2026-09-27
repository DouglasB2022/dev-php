<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMotivosNaoConformidade extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE motivos_nao_conformidade(
                id        INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                codigo    VARCHAR(30) UNIQUE NOT NULL,
                descricao VARCHAR(150) NOT NULL,
                ativo     TINYINT(1) NOT NULL DEFAULT 1
                )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS motivos_nao_conformidade");
    }
}
