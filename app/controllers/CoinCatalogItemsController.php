<?php
// v1.1
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\Config;
use App\Helpers\Pagination;
use App\Helpers\Sort;
use App\Helpers\Validator;

class CoinCatalogItemsController extends Controller
{
    private Database $db;
    private string $uploadDir;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireRole('admin');

        // Cesta pro uložení mincí (lze přesunout do configu)
        $this->uploadDir = $config['uploads']['coins'] ?? __DIR__ . '/../../public/uploads/coins';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0775, true);
        }
    }

    /** Seznam s náhledy a filtrem */
    public function list(): void
    {
        $q = trim($_GET['q'] ?? '');
        [$sort,$dir] = Sort::validate(
            ['id','unit,value,commemorativeTitle','designYearFrom','designYearTo','updated_at','created_at'],
            'id','DESC'
        );

        $w=[]; $p=[];
        if ($q!=='') {
            $w[] = '(cci.id = ? OR cci.commemorativeTitle LIKE ? OR cci.note LIKE ?)';
            array_push($p, (ctype_digit($q)?(int)$q:-1), "%{$q}%", "%{$q}%");
        }
        $where = $w ? 'WHERE '.implode(' AND ', $w) : '';

        // count
        $total = (int)$this->db->fetch("
          SELECT COUNT(*) c
          FROM CoinCatalogItem cci
          {$where}
        ", $p)['c'];

        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT
              cci.id, cci.commemorativeTitle, cci.obverseImage, cci.reverseImage,
              cci.designYearFrom, cci.designYearTo, cci.updated_at, cci.created_at,
              p.display   AS periodName,
              t.display   AS typeName,
              d.display   AS denomName
            FROM CoinCatalogItem cci
            JOIN CoinPeriod       p ON p.id = cci.periodId
            JOIN CoinType         t ON t.id = cci.typeId
            JOIN CoinDenomination d ON d.id = cci.denominationId
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        // Počty autorů pro rychlý přehled
        $ids = array_column($rows, 'id');
        $counts = [];
        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            $crows = $this->db->fetchAll("
		SELECT catalogItemId, side, GROUP_CONCAT(CONCAT(cd.lastName,' ', cd.firstName )) c
                FROM CoinCatalogDesigner ccd
                INNER JOIN CoinDesigner cd ON cd.id = ccd.designerId
                WHERE catalogItemId IN ({$in})
                GROUP BY catalogItemId, side
            ");
            foreach ($crows as $cr) {
                $counts[$cr['catalogItemId']][$cr['side']] = $cr['c'];
            }
        }

        $this->render('coincatalogitems/list', [
            'pageTitle' => 'Katalog – nominály (CoinCatalogItem)',
            'rows'      => $rows,
            'counts'    => $counts,
            'q'         => $q,
            'sort'      => $sort,
            'dir'       => $dir,
            'p'         => $pg,
        ]);
    }

    /** Formulář – create */
    public function create(): void
    {
        $this->render('coincatalogitems/form', [
            'pageTitle' => 'Přidat položku katalogu (nominál)',
            'mode'      => 'create',
            'item'      => $this->blankItem(),
            'errors'    => [],
            'lookups'   => $this->lookups(),
            'designers' => $this->designerOptions(),
            'selObv'    => [],
            'selRev'    => [],
        ]);
    }

    /** Uložení – create */
    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.');
            header('Location: index.php?route=coincatalogitems/create'); exit;
        }

        [$d, $errors] = $this->sanitizeAndValidate($_POST);
        // Uploady (volitelné)
        [$obvFile, $err1] = $this->handleUpload('obverseImage');
        [$revFile, $err2] = $this->handleUpload('reverseImage');
        $errors = array_merge($errors, $err1, $err2);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('coincatalogitems/form', [
                'pageTitle'=>'Přidat položku', 'mode'=>'create',
                'item'=>array_merge($d,['obverseImage'=>null,'reverseImage'=>null]),
                'errors'=>$errors,
                'lookups'=>$this->lookups(),
                'designers'=>$this->designerOptions(),
                'selObv'=>array_map('intval', $_POST['designers_obverse'] ?? []),
                'selRev'=>array_map('intval', $_POST['designers_reverse'] ?? []),
            ]);
            return;
        }

        $this->db->query("
            INSERT INTO CoinCatalogItem
              (periodId, typeId, denominationId, metalId, edgeId, mintId,
               diameter, weight, thickness,
               edgeDetail, edgeProof, edgeProofNote,
               commemorativeTitle, obverseImage, reverseImage, note,
               designYearFrom, designYearTo, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?, ?,?,?, ?,?,?, ?,?, NOW(), NOW())
        ", [
            (int)$d['periodId'], (int)$d['typeId'], (int)$d['denominationId'],
            (int)$d['metalId'],  (int)$d['edgeId'], (int)$d['mintId'],
            $d['diameter'], $d['weight'], $d['thickness'],
            $d['edgeDetail'] ?: null, $d['edgeProof'] ?: null, $d['edgeProofNote'] ?: null,
            $d['commemorativeTitle'] ?: null,
            $obvFile, $revFile,
            $d['note'] ?: null,
            $d['designYearFrom'], $d['designYearTo'],
        ]);

        $itemId = (int)$this->db->lastInsertId();

        // Uložení autorů (1‑N)
        $this->saveDesigners($itemId, $_POST['designers_obverse'] ?? [], 'obverse');
        $this->saveDesigners($itemId, $_POST['designers_reverse'] ?? [], 'reverse');

        Session::flash('success','Položka vytvořena.');
        header('Location: index.php?route=coincatalogitems/list'); exit;
    }

    /** Formulář – edit */
    public function edit(): void
    {
        $id  = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM CoinCatalogItem WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coincatalogitems/list'); exit; }

        [$selObv, $selRev] = $this->loadDesignerSelections($id);

        $this->render('coincatalogitems/form', [
            'pageTitle' => 'Upravit položku katalogu',
            'mode'      => 'edit',
            'item'      => $row,
            'errors'    => [],
            'lookups'   => $this->lookups(),
            'designers' => $this->designerOptions(),
            'selObv'    => $selObv,
            'selRev'    => $selRev,
        ]);
    }

    /** Uložení – edit */
    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coincatalogitems/list'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $orig = $this->db->fetch("SELECT * FROM CoinCatalogItem WHERE id=?", [$id]);
        if (!$orig) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coincatalogitems/list'); exit; }

        [$d, $errors] = $this->sanitizeAndValidate($_POST);

        // Upload/replace/smazání
        $removeObv = !empty($_POST['remove_obverse']);
        $removeRev = !empty($_POST['remove_reverse']);

        [$obvNew, $err1] = $this->handleUpload('obverseImage', $orig['obverseImage']);
        [$revNew, $err2] = $this->handleUpload('reverseImage', $orig['reverseImage']);
        $errors = array_merge($errors, $err1, $err2);

        $obvFile = $removeObv ? null : ($obvNew ?: $orig['obverseImage']);
        $revFile = $removeRev ? null : ($revNew ?: $orig['reverseImage']);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            [$selObv, $selRev] = [$this->ids($_POST['designers_obverse'] ?? []), $this->ids($_POST['designers_reverse'] ?? [])];
            $this->render('coincatalogitems/form', [
                'pageTitle'=>'Upravit položku','mode'=>'edit',
                'item'=>array_merge($orig, $d, ['obverseImage'=>$obvFile,'reverseImage'=>$revFile]),
                'errors'=>$errors,
                'lookups'=>$this->lookups(),
                'designers'=>$this->designerOptions(),
                'selObv'=>$selObv, 'selRev'=>$selRev,
            ]);
            return;
        }

        $this->db->query("
            UPDATE CoinCatalogItem
            SET periodId=?, typeId=?, denominationId=?, metalId=?, edgeId=?, mintId=?,
                diameter=?, weight=?, thickness=?,
                edgeDetail=?, edgeProof=?, edgeProofNote=?,
                commemorativeTitle=?, obverseImage=?, reverseImage=?, note=?,
                designYearFrom=?, designYearTo=?, updated_at=NOW()
            WHERE id=?
        ", [
            (int)$d['periodId'], (int)$d['typeId'], (int)$d['denominationId'],
            (int)$d['metalId'],  (int)$d['edgeId'], (int)$d['mintId'],
            $d['diameter'], $d['weight'], $d['thickness'],
            $d['edgeDetail'] ?: null, $d['edgeProof'] ?: null, $d['edgeProofNote'] ?: null,
            $d['commemorativeTitle'] ?: null,
            $obvFile, $revFile,
            $d['note'] ?: null,
            $d['designYearFrom'], $d['designYearTo'],
            $id
        ]);

        // reset a uložení autorů
        $this->db->query("DELETE FROM CoinCatalogDesigner WHERE catalogItemId=?", [$id]);
        $this->saveDesigners($id, $_POST['designers_obverse'] ?? [], 'obverse');
        $this->saveDesigners($id, $_POST['designers_reverse'] ?? [], 'reverse');

        Session::flash('success','Položka upravena.');
        header('Location: index.php?route=coincatalogitems/list'); exit;
    }

    // ===== helpers =====

    private function blankItem(): array
    {
        return [
            'periodId'=>'','typeId'=>'','denominationId'=>'','metalId'=>'','edgeId'=>'','mintId'=>'',
            'diameter'=>'','weight'=>'','thickness'=>'',
            'edgeDetail'=>'','edgeProof'=>'','edgeProofNote'=>'',
            'commemorativeTitle'=>'','obverseImage'=>null,'reverseImage'=>null,'note'=>'',
            'designYearFrom'=>'','designYearTo'=>'',
        ];
    }

    private function lookups(): array
    {
        return [
            'periods'      => $this->db->fetchAll("SELECT id, COALESCE(display,name) AS name FROM CoinPeriod WHERE active=1 ORDER BY name"),
            'types'        => $this->db->fetchAll("SELECT id, COALESCE(display,name) AS name FROM CoinType WHERE 1 ORDER BY name"),
            'denoms'       => $this->db->fetchAll("SELECT id, COALESCE(display,name) AS name FROM CoinDenomination ORDER BY value, name"),
            'metals'       => $this->db->fetchAll("SELECT id, COALESCE(display,name) AS name FROM CoinMetal ORDER BY name"),
            'edges'        => $this->db->fetchAll("SELECT id, COALESCE(display,name) AS name FROM CoinEdge ORDER BY name"),
            'mints'        => $this->db->fetchAll("SELECT id, COALESCE(display,name) AS name FROM CoinMint ORDER BY name"),
        ];
    }

    private function designerOptions(): array
    {
        return $this->db->fetchAll("
            SELECT id, CONCAT(lastName, ' ', firstName, COALESCE(CONCAT(' (', nationality, ')'), '')) AS name
            FROM CoinDesigner
            WHERE active IS NULL OR active=1
            ORDER BY lastName, firstName
        ");
    }

    private function loadDesignerSelections(int $itemId): array
    {
        $rows = $this->db->fetchAll("
            SELECT designerId, side FROM CoinCatalogDesigner WHERE catalogItemId=?
        ", [$itemId]);
        $obv=[]; $rev=[];
        foreach ($rows as $r) {
            if ($r['side']==='obverse') $obv[]=(int)$r['designerId'];
            if ($r['side']==='reverse') $rev[]=(int)$r['designerId'];
        }
        return [$obv,$rev];
    }

    private function saveDesigners(int $itemId, array $ids, string $side): void
    {
        $ids = $this->ids($ids);
        if (!$ids) return;
        $vals = []; $params=[];
        foreach ($ids as $did) {
            $vals[] = "(?, ?, ?)";
            array_push($params, $itemId, $did, $side);
        }
        $this->db->query("
            INSERT INTO CoinCatalogDesigner (catalogItemId, designerId, side)
            VALUES ".implode(',', $vals), $params
        );
    }

    private function ids(array $arr): array
    {
        $out=[];
        foreach ($arr as $v) { if (ctype_digit((string)$v)) $out[]=(int)$v; }
        return array_values(array_unique($out));
    }

    /**
     * Upload obrázku: vrací [filename|null, errors[]]
     * $existing – pokud je zadán, nový upload nahradí starý a starý se smaže.
     */
    private function handleUpload(string $field, ?string $existing=null): array
    {
        $errors = [];
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, $errors]; // nic nenahráno
        }

        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[$field] = 'Chyba při nahrávání souboru.';
            return [null, $errors];
        }

        // Limity
        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if ($f['size'] > $maxBytes) {
            $errors[$field] = 'Soubor je příliš velký (max 5 MB).';
            return [null, $errors];
        }

        // MIME / přípona
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            $errors[$field] = 'Povolené formáty: JPG, PNG, WEBP.';
            return [null, $errors];
        }

        $ext = $allowed[$mime];
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = rtrim($this->uploadDir, '/').'/'.$name;

        if (!@move_uploaded_file($f['tmp_name'], $target)) {
            $errors[$field] = 'Nepodařilo se uložit soubor.';
            return [null, $errors];
        }

        // smažeme starý pokud existuje
        if ($existing) {
            @unlink(rtrim($this->uploadDir, '/').'/'.$existing);
        }

        return [$name, $errors];
    }

    /** Validace vstupu */
    private function sanitizeAndValidate(array $in): array
    {
        $d = [
            'periodId' => trim($in['periodId'] ?? ''),
            'typeId'   => trim($in['typeId'] ?? ''),
            'denominationId'=> trim($in['denominationId'] ?? ''),
            'metalId'  => trim($in['metalId'] ?? ''),
            'edgeId'   => trim($in['edgeId'] ?? ''),
            'mintId'   => trim($in['mintId'] ?? ''),

            'diameter' => trim($in['diameter'] ?? ''),
            'weight'   => trim($in['weight'] ?? ''),
            'thickness'=> trim($in['thickness'] ?? ''),

            'edgeDetail' => trim($in['edgeDetail'] ?? ''),
            'edgeProof'  => trim($in['edgeProof'] ?? ''),
            'edgeProofNote' => trim($in['edgeProofNote'] ?? ''),

            'commemorativeTitle' => trim($in['commemorativeTitle'] ?? ''),
            'note' => trim($in['note'] ?? ''),

            'designYearFrom' => trim($in['designYearFrom'] ?? ''),
            'designYearTo'   => trim($in['designYearTo'] ?? ''),
        ];

        $e = [];
        // povinné FK
        foreach (['periodId','typeId','denominationId','metalId','edgeId','mintId'] as $fk) {
            if ($d[$fk]==='' || !ctype_digit($d[$fk])) $e[$fk] = 'Vyber hodnotu.';
        }

        // čísla s desetinnou tečkou dle DB
        if ($d['diameter'] !== ''  && !preg_match('/^\d{1,5}(\.\d{1,2})?$/',  $d['diameter']))  $e['diameter']='Max 5 číslic + 2 des. místa.';
        if ($d['weight']   !== ''  && !preg_match('/^\d{1,6}(\.\d{1,3})?$/',  $d['weight']))    $e['weight']='Max 6 číslic + 3 des. místa.';
        if ($d['thickness']!== ''  && !preg_match('/^\d{1,4}(\.\d{1,2})?$/',  $d['thickness'])) $e['thickness']='Max 4 číslice + 2 des. místa.';

        // délky
        if ($x = Validator::str($d['commemorativeTitle'], 255)) $e['commemorativeTitle']=$x;

        // roky
        $year = function($v){ return $v===''? null : (preg_match('/^-?\d{1,4}$/',$v) ? (int)$v : false); };
        $yf = $year($d['designYearFrom']); $yt = $year($d['designYearTo']);
        if ($yf === false) $e['designYearFrom']='Zadej rok (−9999…9999).';
        if ($yt === false) $e['designYearTo']  ='Zadej rok (−9999…9999).';
        if ($yf !== null && $yt !== null && $yt < $yf) $e['designYearTo'] = 'Koncový rok nesmí být menší.';

        $d['designYearFrom'] = $yf;
        $d['designYearTo']   = $yt;

        // převod číselných textů na DB-friendly formát
        foreach (['diameter','weight','thickness'] as $n) {
            if ($d[$n] === '') $d[$n] = null;
        }

        return [$d, $e];
    }
}
