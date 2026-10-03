<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

final readonly class ManualBilancioAnomaly
{
    public const NOME_NON_CONFORME = 'nome_non_conforme';

    public const SEZIONE_NON_NEL_DATAPACK = 'sezione_non_nel_datapack';

    public const RICEVUTO_SENZA_FILE = 'ricevuto_senza_file';

    public const FILE_SENZA_RICEVUTO = 'file_senza_ricevuto';

    public const SEZIONE_NON_IN_EXCEL = 'sezione_non_in_excel';

    public const CODICE_EXCEL_NON_NEL_DATAPACK = 'codice_excel_non_nel_datapack';

    public const HASH_DUPLICATO_STESSA_SEZIONE = 'hash_duplicato_stessa_sezione';

    public function __construct(
        public string $code,
        public ?string $codiceCai,
        public string $message,
    ) {}
}
