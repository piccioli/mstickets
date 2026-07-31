<?php

declare(strict_types=1);

namespace App\Import\Stages;

use RuntimeException;

/**
 * Errore esplicito di pianificazione/esecuzione del runner (stage sconosciuto,
 * dipendenza non registrata, dipendenza non inclusa nella sessione, ciclo tra
 * stage): il PRD richiede che questi casi falliscano in modo esplicito invece
 * di essere risolti con un ordine arbitrario.
 */
final class ImportRunnerException extends RuntimeException {}
