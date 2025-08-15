<?php
// v1.2
use App\Helpers\View;
use App\Helpers\Url;

$mode = $mode ?? 'create';
$isEdit = ($mode==='edit');
$action = $isEdit ? 'index.php?route=catalogentries/update' : 'index.php?route=catalogentries/store';

function opts($list,$sel,$ph='— vyber —'){
  $out = '<option value="">'.$ph.'</option>';
  foreach ($list as $o) {
    $s = ((string)$o['id']===(string)$sel)?'selected':'';
    $out .= '<option value="'.(int)$o['id'].'" '.$s.'>'.htmlspecialchars($o['label']).'</option>';
  }
  return $out;
}
?>
<section class="space-y-4 max-w-5xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit položku katalogu' : 'Přidat položku katalogu' ?></h1>
  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-4" autocomplete="off">
    <?= View::csrfField() ?>
    <input type="hidden" name="catalogId" value="<?= (int)$catalogId ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-3">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Nominál (CoinCatalogItem)*</span>
        <select name="catalogItemId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?= opts($optItems, $item['catalogItemId'] ?? '') ?>
        </select>
        <?php if (!empty($errors['catalogItemId'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['catalogItemId']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Rok*</span>
        <input type="text" name="year" inputmode="numeric" required
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars((string)($item['year'] ?? '')) ?>">
        <?php if (!empty($errors['year'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['year']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Vzácnost*</span>
        <select name="rarityId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?= opts($optRarity, $item['rarityId'] ?? '') ?>
        </select>
        <?php if (!empty($errors['rarityId'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['rarityId']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="mb-1 block text-sm text-slate-300">Mintage standard (tis.)</span>
          <input type="text" name="mintageStandard" inputmode="decimal"
                 class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                 value="<?= htmlspecialchars((string)($item['mintageStandard'] ?? '')) ?>">
          <?php if (!empty($errors['mintageStandard'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['mintageStandard']) ?></div><?php endif; ?>
        </label>
        <label class="block">
          <span class="mb-1 block text-sm text-slate-300">Mintage proof (tis.)</span>
          <input type="text" name="mintageProof" inputmode="decimal"
                 class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                 value="<?= htmlspecialchars((string)($item['mintageProof'] ?? '')) ?>">
          <?php if (!empty($errors['mintageProof'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['mintageProof']) ?></div><?php endif; ?>
        </label>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="mb-1 block text-sm text-slate-300">Staženo z oběhu – standard (tis.)</span>
          <input type="text" name="mintageWithdrawnStandard" inputmode="decimal"
                 class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                 value="<?= htmlspecialchars((string)($item['mintageWithdrawnStandard'] ?? '')) ?>">
          <?php if (!empty($errors['mintageWithdrawnStandard'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['mintageWithdrawnStandard']) ?></div><?php endif; ?>
        </label>
        <label class="block">
          <span class="mb-1 block text-sm text-slate-300">Staženo z oběhu – proof (tis.)</span>
          <input type="text" name="mintageWithdrawnProof" inputmode="decimal"
                 class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                 value="<?= htmlspecialchars((string)($item['mintageWithdrawnProof'] ?? '')) ?>">
          <?php if (!empty($errors['mintageWithdrawnProof'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['mintageWithdrawnProof']) ?></div><?php endif; ?>
        </label>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <?php
        $types = ['fixed'=>'Fixní','market'=>'Tržní'];
        $priceFields = [
          ['price2_2','price2_2_type','Cena 2/2'],
          ['price1_1','price1_1_type','Cena 1/1'],
          ['price0_0','price0_0_type','Cena 0/0'],
        ];
      ?>
      <?php foreach ($priceFields as [$pf,$pt,$label]): ?>
        <div>
          <label class="block">
            <span class="mb-1 block text-sm text-slate-300"><?= $label ?></span>
            <input type="text" name="<?= $pf ?>" inputmode="decimal"
                   class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
                   value="<?= htmlspecialchars((string)($item[$pf] ?? '')) ?>">
            <?php if (!empty($errors[$pf])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors[$pf]) ?></div><?php endif; ?>
          </label>
          <label class="mt-2 block">
            <span class="mb-1 block text-xs text-slate-400">Typ ceny</span>
            <select name="<?= $pt ?>" class="w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1.5 text-xs">
              <?php foreach ($types as $v=>$t): $sel=((string)($item[$pt] ?? 'fixed')===$v)?'selected':''; ?>
                <option value="<?= $v ?>" <?= $sel ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors[$pt])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors[$pt]) ?></div><?php endif; ?>
          </label>
        </div>
      <?php endforeach; ?>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Cena Proof</span>
        <input type="text" name="priceProof" inputmode="decimal"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars((string)($item['priceProof'] ?? '')) ?>">
        <?php if (!empty($errors['priceProof'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['priceProof']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="counterfeitWarning" value="1" <?= !empty($item['counterfeitWarning'])?'checked':'' ?>>
        <span class="text-sm">Upozornění na falsa</span>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Typ varianty</span>
        <select name="variantType" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?php
            $vTypes = ['none'=>'Žádná','has_variants'=>'Má varianty','is_variant'=>'Varianta'];
            foreach ($vTypes as $v=>$t):
              $sel = ((string)($item['variantType'] ?? 'none')===$v)?'selected':'';
          ?>
            <option value="<?= $v ?>" <?= $sel ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['variantType'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['variantType']) ?></div><?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Popis varianty</span>
      <textarea name="variantDescription" rows="3" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['variantDescription'] ?? '') ?></textarea>
    </label>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Poznámka</span>
      <textarea name="note" rows="3" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="<?= Url::build('catalogs/detail', ['id'=>$catalogId]) ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button type="submit" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
