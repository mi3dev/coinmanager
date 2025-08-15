<?php
// v1.1
use App\Helpers\View;
use App\Helpers\Url;

$mode = $mode ?? 'create';
$isEdit = ($mode==='edit');
$action = $isEdit ? 'index.php?route=coincatalogitems/update' : 'index.php?route=coincatalogitems/store';
$imgBase = 'public/uploads/coins/';
?>
<section class="space-y-4 max-w-6xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit nominál' : 'Přidat nominál' ?></h1>
  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-6" autocomplete="off" enctype="multipart/form-data">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>

    <!-- Základní vazby -->
    <div class="grid gap-4 sm:grid-cols-3">
      <label class="block">
        <span class="block text-sm mb-1">Období*</span>
        <select name="periodId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['periods'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['periodId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['periodId'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['periodId']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Typ*</span>
        <select name="typeId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['types'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['typeId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['typeId'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['typeId']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Nominál*</span>
        <select name="denominationId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['denoms'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['denominationId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['denominationId'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['denominationId']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <label class="block">
        <span class="block text-sm mb-1">Kov*</span>
        <select name="metalId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['metals'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['metalId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['metalId'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['metalId']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Hrana*</span>
        <select name="edgeId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['edges'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['edgeId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['edgeId'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['edgeId']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Mincovna*</span>
        <select name="mintId" required class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <option value="">— vyber —</option>
          <?php foreach ($lookups['mints'] as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= (int)($item['mintId']??0)===(int)$o['id']?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['mintId'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['mintId']) ?></div><?php endif; ?>
      </label>
    </div>

    <!-- Parametry -->
    <div class="grid gap-4 sm:grid-cols-3">
      <label class="block">
        <span class="block text-sm mb-1">Průměr (mm)</span>
        <input type="text" name="diameter" maxlength="8" inputmode="decimal" pattern="^\d{1,5}(\.\d{1,2})?$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['diameter'] ?? '') ?>">
        <?php if (!empty($errors['diameter'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['diameter']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Váha (g)</span>
        <input type="text" name="weight" maxlength="10" inputmode="decimal" pattern="^\d{1,6}(\.\d{1,3})?$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['weight'] ?? '') ?>">
        <?php if (!empty($errors['weight'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['weight']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Tloušťka (mm)</span>
        <input type="text" name="thickness" maxlength="7" inputmode="decimal" pattern="^\d{1,4}(\.\d{1,2})?$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['thickness'] ?? '') ?>">
        <?php if (!empty($errors['thickness'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['thickness']) ?></div><?php endif; ?>
      </label>
    </div>

    <!-- Hrana – detail/proof -->
    <div class="grid gap-4 sm:grid-cols-3">
      <label class="block">
        <span class="block text-sm mb-1">Detail hrany</span>
        <textarea name="edgeDetail" rows="2" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['edgeDetail'] ?? '') ?></textarea>
      </label>
      <label class="block">
        <span class="block text-sm mb-1">Hrana (proof)</span>
        <textarea name="edgeProof" rows="2" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['edgeProof'] ?? '') ?></textarea>
      </label>
      <label class="block">
        <span class="block text-sm mb-1">Poznámka k hraně (proof)</span>
        <textarea name="edgeProofNote" rows="2" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['edgeProofNote'] ?? '') ?></textarea>
      </label>
    </div>

    <!-- Titul / roky -->
    <div class="grid gap-4 sm:grid-cols-3">
      <label class="block">
        <span class="block text-sm mb-1">Titul (pamětní)</span>
        <input type="text" name="commemorativeTitle" maxlength="255"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['commemorativeTitle'] ?? '') ?>">
        <?php if (!empty($errors['commemorativeTitle'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['commemorativeTitle']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Design od</span>
        <input type="text" name="designYearFrom" maxlength="5" inputmode="numeric" pattern="^-?\d{1,4}$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['designYearFrom'] ?? '') ?>">
        <?php if (!empty($errors['designYearFrom'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['designYearFrom']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Design do</span>
        <input type="text" name="designYearTo" maxlength="5" inputmode="numeric" pattern="^-?\d{1,4}$"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['designYearTo'] ?? '') ?>">
        <?php if (!empty($errors['designYearTo'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['designYearTo']) ?></div><?php endif; ?>
      </label>
    </div>

    <!-- Obrázky -->
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <span class="block text-sm mb-1">Avers (JPG/PNG/WEBP, max 5 MB)</span>
        <?php if (!empty($item['obverseImage'])): ?>
          <div class="flex items-center gap-3 mb-2">
            <img src="<?= $imgBase . htmlspecialchars($item['obverseImage']) ?>" class="h-24 w-24 object-cover rounded-md border border-slate-700" alt="">
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" name="remove_obverse" value="1"> <span>Smazat existující</span>
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="obverseImage" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm">
        <?php if (!empty($errors['obverseImage'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['obverseImage']) ?></div><?php endif; ?>
      </div>

      <div>
        <span class="block text-sm mb-1">Revers (JPG/PNG/WEBP, max 5 MB)</span>
        <?php if (!empty($item['reverseImage'])): ?>
          <div class="flex items-center gap-3 mb-2">
            <img src="<?= $imgBase . htmlspecialchars($item['reverseImage']) ?>" class="h-24 w-24 object-cover rounded-md border border-slate-700" alt="">
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" name="remove_reverse" value="1"> <span>Smazat existující</span>
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="reverseImage" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm">
        <?php if (!empty($errors['reverseImage'])): ?><div class="text-xs text-red-300 mt-1"><?= htmlspecialchars($errors['reverseImage']) ?></div><?php endif; ?>
      </div>
    </div>

    <!-- Autoři: Líc / Rub -->
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="block text-sm mb-1">Autoři – líc (1‑N)</span>
        <select name="designers_obverse[]" multiple size="8"
                class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?php foreach ($designers as $o): $sel=in_array((int)$o['id'], $selObv ?? [], true); ?>
            <option value="<?= (int)$o['id'] ?>" <?= $sel?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="block">
        <span class="block text-sm mb-1">Autoři – rub (1‑N)</span>
        <select name="designers_reverse[]" multiple size="8"
                class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?php foreach ($designers as $o): $sel=in_array((int)$o['id'], $selRev ?? [], true); ?>
            <option value="<?= (int)$o['id'] ?>" <?= $sel?'selected':'' ?>>
              <?= htmlspecialchars($o['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <label class="block">
      <span class="block text-sm mb-1">Poznámka</span>
      <textarea name="note" rows="3" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2">
      <a href="<?= Url::build('coincatalogitems/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button type="submit" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
