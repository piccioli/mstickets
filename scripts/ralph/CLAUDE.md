# Ralph — note per fasi automatizzate

## Collaudo di fine fase

Quando l'ultima user story di una fase (US-2xx, US-3xx, ecc.) è `passes: true`, prima di considerare la
fase conclusa:
1. Estendi/crea `docs/collaudo/fase-<N>.php` con un topic per ogni gruppo di requisiti della fase appena
   conclusa, mappando ogni test numerato a un test automatico reale scritto in questa fase.
2. Esegui `php artisan collaudo:verify-manifest <N>` — deve passare prima di committare.
3. Esegui `php artisan collaudo:generate <N>` e verifica visivamente il PDF prodotto.
4. Il deploy su UAT via merge a `develop` è un passo separato, gestito dal committente/dev umano — non
   automatizzato dentro il loop di Ralph.
