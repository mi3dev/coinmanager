<?php
// v1.0
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\Config;
use App\Helpers\Pagination;
use App\Helpers\Sort;
use App\Helpers\Validator;

class CollectionController extends Controller
{
    private Database $db;
    private int $uid;
    private string $uploadDir;

    /** pevný číselník kvalit (max length v DB: grade VARCHAR(16)) */
    private array $grades = ['PROOF','RL','0/0','-0/0-','1/1','-1/1-','2/2'];

    /** enum variant */
    private array $variantTypes = ['none'=>'Žádná','has_variants'=>'Má varianty','is_variant'=>'Je varianta'];

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireAnyRole(['admin','collector']);
        $this->uid = (int)(Auth::id() ?? 0);

        $this->uploadDir = $config['uploads']['collection'] ?? __DIR__ . '/../../public/uploads/collection';
        if (!is_dir($this->uploadDir)) @mkdir($this->uploadDir, 0775, true);
    }

    public function list(): void
    {
        $q     = trim($_GET['q'] ?? '');
        $tray  = (int)($_GET['tray'] ?? 0);
        $year  = trim($_GET['year'] ?? '');
        $only  = ($_GET['only_in'] ?? '') === '1';

        [$sort,$dir] = Sort::validate(
            ['id','year','created_at','updated_at'],
            'updated_at','DESC'
        );

        $w = ['c.userId=?']; $p = [$this->uid];
        if ($q!=='') {
            $w[]='(cci.commemorativeTitle LIKE ? OR c.note LIKE ?)';
            $like="%{$q}%"; array_push($p,$like,$like);
        }
        if ($tray>0) { $w[]='c.trayId=?'; $p[]=$tray; }
        if ($year!=='') {
            if (ctype_digit($year)) { $w[]='c.year=?'; $p[]=(int)$year; }
        }
        if ($only) { $w[]='c.inCollection=1'; }
        $where = 'WHERE '.implode(' AND ', $w);

        $total = (int)$this->db->fetch("
            SELECT COUNT(*) c
            FROM Collection c
            JOIN CoinCatalogItem cci ON cci.id = c.catalogItemId
            {$where}
        ", $p)['c'];

        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT
              c.id, c.catalogItemId, c.year, c.inCollection, c.grade, c.estimatedPrice, c.trayId,
              c.variantType, c.variantDescription,
              c.obverseImage, c.reverseImage,
              c.updated_at, c.created_at,
              cci.commemorativeTitle,
              d.display AS denomName,
              p.display AS periodName
            FROM Collection c
            JOIN CoinCatalogItem cci ON cci.id = c.catalogItemId
            JOIN CoinDenomination d ON d.id = cci.denominationId
            JOIN CoinPeriod p ON p.id = cci.periodId
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        $trays = $this->db->fetchAll("
            SELECT id, name FROM CollectionTray WHERE userId=? ORDER BY position, name
        ", [$this->uid]);

        $this->render('collection/list', [
            'pageTitle' => 'Moje sbírka',
            'rows'      => $rows,
            'trays'     => $trays,
            'q'         => $q,
            'tray'      => $tray,
            'year'      => $year,
            'only'      => $only,
            'sort'      => $sort,
            'dir'       => $dir,
            'p'         => $pg,
        ]);
    }

    public function create(): void
    {
        $this->render('collection/form', [
            'pageTitle' => 'Přidat minci do sbírky',
            'mode'      => 'create',
            'item'      => $this->blank(),
            'errors'    => [],
            'lookups'   => $this->lookups(),
            'grades'    => $this->grades,
            'variantTypes' => $this->variantTypes,
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collection/list'); exit;
        }

        [$d,$errors] = $this->sanitize($_POST);

        [$obv,$e1] = $this->handleUpload('obverseImage');
        [$rev,$e2] = $this->handleUpload('reverseImage');
        $errors = array_merge($errors, $e1, $e2);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('collection/form', [
                'pageTitle'=>'Přidat minci','mode'=>'create','item'=>array_merge($d,['obverseImage'=>null,'reverseImage'=>null]),
                'errors'=>$errors,'lookups'=>$this->lookups(),'grades'=>$this->grades,'variantTypes'=>$this->variantTypes
            ]); return;
        }

        $this->db->query("
            INSERT INTO Collection
              (userId, catalogItemId, year, inCollection, grade,
               purchasePrice, purchaseNote, purchaseYear, estimatedPrice, trayId, note,
               variantType, variantDescription, obverseImage, reverseImage,
               created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())
        ", [
            $this->uid,
            (int)$d['catalogItemId'], (int)$d['year'], (int)$d['inCollection'],
            ($d['grade']!=='' ? $d['grade'] : null),
            $d['purchasePrice'], ($d['purchaseNote']!==''?$d['purchaseNote']:null),
            $d['purchaseYear'], $d['estimatedPrice'],
            $d['trayId'] ?: null,
            ($d['note']!==''?$d['note']:null),
            $d['variantType'], ($d['variantDescription']!==''?$d['variantDescription']:null),
            $obv, $rev
        ]);

        Session::flash('success','Položka přidána do sbírky.');
        header('Location: index.php?route=collection/list'); exit;
    }

    public function edit(): void
    {
        $id  = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM Collection WHERE id=? AND userId=?", [$id,$this->uid]);
        if (!$row) { Session::flash('error','Položka nenalezena.'); header('Location: index.php?route=collection/list'); exit; }

        $this->render('collection/form', [
            'pageTitle' => 'Upravit položku sbírky',
            'mode'      => 'edit',
            'item'      => $row,
            'errors'    => [],
            'lookups'   => $this->lookups(),
            'grades'    => $this->grades,
            'variantTypes' => $this->variantTypes,
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collection/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $orig = $this->db->fetch("SELECT * FROM Collection WHERE id=? AND userId=?", [$id,$this->uid]);
        if (!$orig) { Session::flash('error','Položka nenalezena.'); header('Location: index.php?route=collection/list'); exit; }

        [$d,$errors] = $this->sanitize($_POST);

        $removeObv = !empty($_POST['remove_obverse']);
        $removeRev = !empty($_POST['remove_reverse']);
        [$obvNew,$e1] = $this->handleUpload('obverseImage', $orig['obverseImage']);
        [$revNew,$e2] = $this->handleUpload('reverseImage', $orig['reverseImage']);
        $errors = array_merge($errors,$e1,$e2);

        $obv = $removeObv ? null : ($obvNew ?: $orig['obverseImage']);
        $rev = $removeRev ? null : ($revNew ?: $orig['reverseImage']);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('collection/form', [
                'pageTitle'=>'Upravit položku','mode'=>'edit',
                'item'=>array_merge($orig,$d,['obverseImage'=>$obv,'reverseImage'=>$rev]),
                'errors'=>$errors,'lookups'=>$this->lookups(),'grades'=>$this->grades,'variantTypes'=>$this->variantTypes
            ]); return;
        }

        $this->db->query("
            UPDATE Collection
            SET catalogItemId=?, year=?, inCollection=?, grade=?,
                purchasePrice=?, purchaseNote=?, purchaseYear=?, estimatedPrice=?,
                trayId=?, note=?, variantType=?, variantDescription=?,
                obverseImage=?, reverseImage=?, updated_at=NOW()
            WHERE id=? AND userId=?
        ", [
            (int)$d['catalogItemId'], (int)$d['year'], (int)$d['inCollection'],
            ($d['grade']!=='' ? $d['grade'] : null),
            $d['purchasePrice'], ($d['purchaseNote']!==''?$d['purchaseNote']:null),
            $d['purchaseYear'], $d['estimatedPrice'],
            $d['trayId'] ?: null,
            ($d['note']!==''?$d['note']:null),
            $d['variantType'], ($d['variantDescription']!==''?$d['variantDescription']:null),
            $obv, $rev, $id, $this->uid
        ]);

        Session::flash('success','Položka upravena.');
        header('Location: index.php?route=collection/list'); exit;
    }

    public function delete(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collection/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $this->db->query("DELETE FROM Collection WHERE id=? AND userId=?", [$id,$this->uid]);
        Session::flash('success','Položka smazána.');
        header('Location: index.php?route=collection/list'); exit;
    }

    // ===== helpers =====

    private function blank(): array
    {
        return [
            'catalogItemId'=>'','year'=>'','inCollection'=>1,'grade'=>'',
            'purchasePrice'=>'','purchaseNote'=>'','purchaseYear'=>'','estimatedPrice'=>'',
            'trayId'=>'','note'=>'',
            'variantType'=>'none','variantDescription'=>'',
            'obverseImage'=>null,'reverseImage'=>null,
        ];
    }

    private function lookups(): array
    {
        return [
            'items' => $this->db->fetchAll("
                SELECT cci.id, CONCAT(COALESCE(cci.commemorativeTitle,'(bez názvu)'),' — ', COALESCE(d.display,''),' · ', COALESCE(p.display,'')) label
                FROM CoinCatalogItem cci
                JOIN CoinDenomination d ON d.id = cci.denominationId
                JOIN CoinPeriod p ON p.id = cci.periodId
                ORDER BY p.display, d.value, cci.id
            "),
            'trays' => $this->db->fetchAll("SELECT id, name FROM CollectionTray WHERE userId=? ORDER BY position, name", [$this->uid]),
        ];
    }

    private function sanitize(array $in): array
    {
        $d = [
            'catalogItemId' => trim($in['catalogItemId'] ?? ''),
            'year'          => trim($in['year'] ?? ''),
            'inCollection'  => !empty($in['inCollection']) ? 1 : 0,
            'grade'         => trim($in['grade'] ?? ''),
            'purchasePrice' => trim($in['purchasePrice'] ?? ''),
            'purchaseNote'  => trim($in['purchaseNote'] ?? ''),
            'purchaseYear'  => trim($in['purchaseYear'] ?? ''),
            'estimatedPrice'=> trim($in['estimatedPrice'] ?? ''),
            'trayId'        => trim($in['trayId'] ?? ''),
            'note'          => trim($in['note'] ?? ''),
            'variantType'   => trim($in['variantType'] ?? 'none'),
            'variantDescription' => trim($in['variantDescription'] ?? ''),
        ];
        $e=[];

        if ($d['catalogItemId']==='' || !ctype_digit($d['catalogItemId'])) $e['catalogItemId']='Vyber položku katalogu.';
        if ($d['year']==='' || !ctype_digit($d['year'])) $e['year']='Zadej rok (celé číslo).';

        if ($d['grade'] !== '' && !in_array($d['grade'], $this->grades, true)) $e['grade']='Vyber z nabídky.';
        if ($d['purchasePrice'] !== '' && !preg_match('/^\d{1,10}(\.\d{1,2})?$/',$d['purchasePrice'])) $e['purchasePrice']='Formát čísla s 2 des. místy.';
        if ($d['estimatedPrice']!== '' && !preg_match('/^\d{1,10}(\.\d{1,2})?$/',$d['estimatedPrice'])) $e['estimatedPrice']='Formát čísla s 2 des. místy.';
        if ($d['purchaseYear']  !== '' && !preg_match('/^\d{1,4}$/',$d['purchaseYear'])) $e['purchaseYear']='Rok (max 4 číslice).';

        if ($d['trayId'] !== '' && !ctype_digit($d['trayId'])) $e['trayId']='Neplatné plato.';

        if (!isset($this->variantTypes[$d['variantType']])) $e['variantType']='Neplatný typ varianty.';

        // nullify numbers for DB
        foreach (['purchasePrice','estimatedPrice'] as $n) { if ($d[$n]==='') $d[$n]=null; }
        $d['purchaseYear'] = ($d['purchaseYear']==='') ? null : (int)$d['purchaseYear'];
        $d['trayId']       = ($d['trayId']==='')       ? null : (int)$d['trayId'];

        return [$d,$e];
    }

    /** Obrázky líc/rub — validace + uložení */
    private function handleUpload(string $field, ?string $existing=null): array
    {
        $errors=[];
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null,$errors];
        }
        $f=$_FILES[$field];
        if ($f['error']!==UPLOAD_ERR_OK) { $errors[$field]='Chyba při nahrávání souboru.'; return [null,$errors]; }
        if ($f['size']>5*1024*1024) { $errors[$field]='Max 5 MB.'; return [null,$errors]; }

        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $mime=finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']);
        if (!isset($allowed[$mime])) { $errors[$field]='Povolené: JPG, PNG, WEBP.'; return [null,$errors]; }

        $name=date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$allowed[$mime];
        $target=rtrim($this->uploadDir,'/').'/'.$name;
        if (!@move_uploaded_file($f['tmp_name'],$target)) { $errors[$field]='Nelze uložit soubor.'; return [null,$errors]; }
        if ($existing) @unlink(rtrim($this->uploadDir,'/').'/'.$existing);
        return [$name,$errors];
    }
}
