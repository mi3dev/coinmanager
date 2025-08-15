<?php
// v1.0
use App\Helpers\View;
use App\Helpers\Url;

$mode   = $mode ?? 'create';
$isEdit = ($mode==='edit');
$action = $isEdit ? 'index.php?route=collection/update' : 'index.php?route=collection/store';
$imgBase = 'public/uploads/collection/';
?>
<section class="space-y-4 max-w-5xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit položku sbírky' : 'Přidat položku do sbírky' ?></h1>
  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-6" enctype="multipart/form-data" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm">Položka katalogu*</span>
        <select name="catalogItemId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['items'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['catalogItemId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['catalogItemId'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['catalogItemId']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm">Rok*</span>
        <input type="text" name="year" required maxlength="4" inputmode="numeric" pattern="^\d{1,4}$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['year'] ?? '') ?>">
        <?php if (!empty($errors['year'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['year']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-4 items-end">
      <label class="block sm:col-span-1">
        <span class="mb-1 block text-sm">Kvalita</span>
        <select name="grade" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— nevybráno —</option>
          <?php foreach ($grades as $g): ?>
            <option value="<?= htmlspecialchars($g) ?>" <?= ($item['grade']??'')===$g?'selected':'' ?>><?= htmlspecialchars($g) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['grade'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['grade']) ?></div><?php endif; ?>
      </label>

      <label class="block sm:col-span-1">
        <span class="mb-1 block text-sm">Ve sbírce</span>
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="inCollection" value="1" <?= !empty($item['inCollection'])?'checked':'' ?>>
          <span class="text-sm text-slate-300">Ano</span>
        </label>
      </label>

      <label class="block sm:col-span-2">
        <span class="mb-1 block text-sm">Plato</span>
        <select name="trayId" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— žádné —</option>
          <?php foreach ($lookups['trays'] as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= (int)($item['trayId']??0)===(int)$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['trayId'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['trayId']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
      <label class="block">
        <span class="mb-1 block text-sm">Nákupní cena</span>
        <input type="text" name="purchasePrice" maxlength="13" inputmode="decimal" pattern="^\d{1,10}(\.\d{1,2})?$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['purchasePrice'] ?? '') ?>">
        <?php if (!empty($errors['purchasePrice'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['purchasePrice']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm">Rok nákupu</span>
        <input type="text" name="purchaseYear" maxlength="4" inputmode="numeric" pattern="^\d{1,4}$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['purchaseYear'] ?? '') ?>">
        <?php if (!empty($errors['purchaseYear'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['purchaseYear']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm">Odhadní cena</span>
        <input type="text" name="estimatedPrice" maxlength="13" inputmode="decimal" pattern="^\d{1,10}(\.\d{1,2})?$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['estimatedPrice'] ?? '') ?>">
        <?php if (!empty($errors['estimatedPrice'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['estimatedPrice']) ?></div><?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm">Poznámka k nákupu</span>
      <textarea name="purchaseNote" rows="2" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['purchaseNote'] ?? '') ?></textarea>
    </label>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="block text-sm mb-1">Líc (JPG/PNG/WEBP, max 5 MB)</span>
        <?php if (!empty($item['obverseImage'])): ?>
          <div class="flex items-center gap-3 mb-2">
            <img src="<?= $imgBase.htmlspecialchars($item['obverseImage']) ?>" class="h-24 w-24 object-cover rounded-md border border-slate-700" alt="">
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" name="remove_obverse" value="1"> <span>Smazat existující</span>
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="obverseImage" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm">
        <?php if (!empty($errors['obverseImage'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['obverseImage']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Rub (JPG/PNG/WEBP, max 5 MB)</span>
        <?php if (!empty($item['reverseImage'])): ?>
          <div class="flex items-center gap-3 mb-2">
            <img src="<?= $imgBase.htmlspecialchars($item['reverseImage']) ?>" class="h-24 w-24 object-cover rounded-md border border-slate-700" alt="">
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" name="remove_reverse" value="1"> <span>Smazat existující</span>
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="reverseImage" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm">
        <?php if (!empty($errors['reverseImage'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['reverseImage']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm">Typ varianty</span>
        <select name="variantType" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?php foreach ($variantTypes as $val=>$lbl): ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= ($item['variantType'] ?? 'none')===$val?'selected':'' ?>><?= htmlspecialchars($lbl) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['variantType'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['variantType']) ?></div><?php endif; ?>
      </label>
      <label class="block">
        <span class="mb-1 block text-sm">Popis varianty</span>
        <textarea name="variantDescription" rows="2" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['variantDescription'] ?? '') ?></textarea>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm">Poznámka</span>
      <textarea name="note" rows="3" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2">
      <a href="<?= Url::build('collection/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button type="submit" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
        <?= $isEdit ? 'Uložit změny' : 'Přidat' ?>
      </button>
    </div>
  </form>
</section>
